<?php

use CRM_ManualDirectDebit_Common_MessageTemplate as MessageTemplate;

class CRM_ManualDirectDebit_Event_Listener_TokenRender {

  private $event;

  private $templateId = 0;

  private static $activityTypeNameCache = [];
  private static $activityTypeNameCacheOrder = [];
  private static $dataCollectorCache = [];
  private static $dataCollectorCacheOrder = [];
  private static $processedTemplates = 0;
  private static $maxCacheSize = 100;

  public function __construct($event) {
    $this->event = $event;

    if (!empty($event->context['actionSchedule']->msg_template_id)) {
      $this->templateId = $event->context['actionSchedule']->msg_template_id;
    }
  }

  public function replaceDirectDebitTokens() {
    if (!MessageTemplate::isDirectDebitTemplate($this->templateId)) {
      return;
    }

    try {
      $tokenDataCollector = $this->getTokenDataCollector();
      if (empty($tokenDataCollector)) {
        return;
      }

      $this->replaceTemplateTokens($tokenDataCollector);

      // Increment counter and manage memory
      self::$processedTemplates++;
      $this->performMemoryManagement();
    }
    catch (Exception $e) {
      \Civi::log()->error('TokenRender processing failed: ' . $e->getMessage());
      throw $e;
    }
  }

  private function getTokenDataCollector() {
    $activityTypeId = $this->event->context['actionSearchResult']->activity_type_id;
    $sourceRecordId = $this->event->context['actionSearchResult']->source_record_id;

    // Create cache key combining activity type and source record
    $cacheKey = $activityTypeId . '_' . $sourceRecordId;

    // Check LRU cache first
    if (isset(self::$dataCollectorCache[$cacheKey])) {
      $this->updateLRUOrder(self::$dataCollectorCacheOrder, $cacheKey);
      return self::$dataCollectorCache[$cacheKey];
    }

    $dataCollector = NULL;
    $activityTypeName = $this->getActivityTypeNameById($activityTypeId);

    switch ($activityTypeName) {
      case 'new_direct_debit_recurring_payment':
      case 'update_direct_debit_recurring_payment':
      case 'offline_direct_debit_auto_renewal':
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution($sourceRecordId);
        break;

      case 'direct_debit_payment_reminder':
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Contribution($sourceRecordId);
        break;

      case 'direct_debit_mandate_update':
        $dataCollector = new CRM_ManualDirectDebit_Mail_DataCollector_Mandate($sourceRecordId);
        break;
    }

    // Cache the data collector if one was created
    if ($dataCollector) {
      $this->addToLRUCache(self::$dataCollectorCache, self::$dataCollectorCacheOrder, $cacheKey, $dataCollector);
    }

    return $dataCollector;
  }

  private function getActivityTypeNameById($id) {
    // Check LRU cache first
    if (isset(self::$activityTypeNameCache[$id])) {
      // Move to end of LRU order (most recently used)
      $this->updateLRUOrder(self::$activityTypeNameCacheOrder, $id);
      return self::$activityTypeNameCache[$id];
    }

    $optionValue = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => 'activity_type',
      'value' => $id,
    ]);

    if (empty($optionValue['count'])) {
      self::$activityTypeNameCache[$id] = NULL;
      return NULL;
    }

    $name = $optionValue['values'][0]['name'];
    // Add to LRU cache
    $this->addToLRUCache(self::$activityTypeNameCache, self::$activityTypeNameCacheOrder, $id, $name);
    return $name;
  }

  /**
   * Manages memory using adaptive garbage collection strategy.
   */
  private function performMemoryManagement() {
    // Use adaptive garbage collection for token rendering operations
    CRM_ManualDirectDebit_Common_GCManager::maybeCollectGarbage('token_rendering');
  }

  /**
   * Updates LRU order by moving item to end (most recently used).
   */
  private function updateLRUOrder(&$orderArray, $key) {
    $index = array_search($key, $orderArray);
    if ($index !== FALSE) {
      unset($orderArray[$index]);
      $orderArray = array_values($orderArray); // Re-index array
    }
    $orderArray[] = $key;
  }

  /**
   * Adds item to LRU cache, evicting least recently used if at capacity.
   */
  private function addToLRUCache(&$cache, &$orderArray, $key, $value) {
    // If already exists, update value and move to end
    if (isset($cache[$key])) {
      $cache[$key] = $value;
      $this->updateLRUOrder($orderArray, $key);
      return;
    }

    // If at capacity, remove least recently used item
    if (count($cache) >= self::$maxCacheSize) {
      $lruKey = array_shift($orderArray);
      unset($cache[$lruKey]);
    }

    // Add new item
    $cache[$key] = $value;
    $orderArray[] = $key;
  }

  private function replaceTemplateTokens($tokenDataCollector) {
    $templateParams = $tokenDataCollector->retrieve();
    $smarty = CRM_Core_Smarty::singleton();
    foreach ($templateParams as $name => $value) {
      $smarty->assign($name, $value);
    }

    $renderedTemplateText = $smarty->fetch("string:{$this->event->string}");
    $this->event->string = $renderedTemplateText;
  }

}
