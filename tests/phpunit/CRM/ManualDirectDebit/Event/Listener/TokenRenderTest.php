<?php

require_once __DIR__ . '/../../../../BaseHeadlessTest.php';

/**
 * Tests for TokenRender functionality and memory optimization.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Event_Listener_TokenRenderTest extends BaseHeadlessTest {

  public function setUp() {
    parent::setUp();
    
    // Reset static caches before each test
    $reflection = new \ReflectionClass('CRM_ManualDirectDebit_Event_Listener_TokenRender');
    
    $props = [
      'activityTypeNameCache', 'activityTypeNameCacheOrder',
      'dataCollectorCache', 'dataCollectorCacheOrder', 'processedTemplates'
    ];
    
    foreach ($props as $prop) {
      if ($reflection->hasProperty($prop)) {
        $property = $reflection->getProperty($prop);
        $property->setAccessible(TRUE);
        $property->setValue(NULL, []);
      }
    }
    
    // Reset processed templates counter
    if ($reflection->hasProperty('processedTemplates')) {
      $property = $reflection->getProperty('processedTemplates');
      $property->setAccessible(TRUE);
      $property->setValue(NULL, 0);
    }
    
    // Mock required functions and classes
    $this->mockRequiredClasses();
  }

  /**
   * Test basic functionality - token replacement should work.
   */
  public function testReplaceDirectDebitTokensBasic() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    
    // Mock MessageTemplate to return FALSE (should exit early)
    global $isDirectDebitTemplate;
    $isDirectDebitTemplate = FALSE;
    
    // Should exit early and not throw exceptions
    $tokenRender->replaceDirectDebitTokens();
    
    // If we get here, the test passes (no exceptions thrown)
    $this->assertTrue(TRUE);
  }

  /**
   * Test LRU cache functionality for activity type names.
   */
  public function testActivityTypeNameLRUCache() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    // Test LRU cache methods
    $getActivityTypeMethod = $reflection->getMethod('getActivityTypeNameById');
    $getActivityTypeMethod->setAccessible(TRUE);
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);
    $updateLRUOrderMethod = $reflection->getMethod('updateLRUOrder');
    $updateLRUOrderMethod->setAccessible(TRUE);
    
    // First call should hit API
    global $apiCallCount;
    $apiCallCount = 0;
    
    $result1 = $getActivityTypeMethod->invoke($tokenRender, 1);
    $this->assertEquals('test_activity_type', $result1);
    $this->assertEquals(1, $apiCallCount);
    
    // Second call should use cache
    $result2 = $getActivityTypeMethod->invoke($tokenRender, 1);
    $this->assertEquals('test_activity_type', $result2);
    $this->assertEquals(1, $apiCallCount); // Should not increment
  }
  
  /**
   * Test data collector LRU cache functionality.
   */
  public function testDataCollectorLRUCache() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    $getDataCollectorMethod = $reflection->getMethod('getTokenDataCollector');
    $getDataCollectorMethod->setAccessible(TRUE);
    
    // First call should create new data collector
    $collector1 = $getDataCollectorMethod->invoke($tokenRender);
    $this->assertNotNull($collector1);
    
    // Second call should use cached data collector
    $collector2 = $getDataCollectorMethod->invoke($tokenRender);
    $this->assertNotNull($collector2);
    
    // Verify cache contains the data collector
    $cacheProperty = $reflection->getProperty('dataCollectorCache');
    $cacheProperty->setAccessible(TRUE);
    $cache = $cacheProperty->getValue();
    
    $this->assertNotEmpty($cache);
    $this->assertArrayHasKey('1_123', $cache); // activityTypeId_sourceRecordId
  }
  
  /**
   * Test LRU cache eviction when at capacity.
   */
  public function testLRUCacheEviction() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    // Set small cache size for testing
    $maxCacheSizeProperty = $reflection->getProperty('maxCacheSize');
    $maxCacheSizeProperty->setAccessible(TRUE);
    $maxCacheSizeProperty->setValue(NULL, 2);
    
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);
    
    $cache = [];
    $order = [];
    
    // Fill cache to capacity
    $addToLRUCacheMethod->invoke($tokenRender, $cache, $order, 'key1', 'value1');
    $addToLRUCacheMethod->invoke($tokenRender, $cache, $order, 'key2', 'value2');
    
    $this->assertCount(2, $cache);
    $this->assertTrue(isset($cache['key1']));
    
    // Add one more - should evict LRU
    $addToLRUCacheMethod->invoke($tokenRender, $cache, $order, 'key3', 'value3');
    
    $this->assertCount(2, $cache);
    $this->assertFalse(isset($cache['key1'])); // First item evicted
    $this->assertTrue(isset($cache['key3'])); // New item present
  }
  
  /**
   * Test LRU order updates.
   */
  public function testLRUOrderUpdates() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    $updateLRUOrderMethod = $reflection->getMethod('updateLRUOrder');
    $updateLRUOrderMethod->setAccessible(TRUE);
    
    $order = [1, 2, 3, 4];
    
    // Move item 2 to end
    $updateLRUOrderMethod->invoke($tokenRender, $order, 2);
    
    // Item 2 should now be at the end
    $this->assertEquals(2, end($order));
    $this->assertEquals([0 => 1, 1 => 3, 2 => 4, 3 => 2], $order);
  }
  
  /**
   * Test memory management during bulk processing.
   */
  public function testMemoryManagementDuringBulkProcessing() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    $performMemoryManagementMethod = $reflection->getMethod('performMemoryManagement');
    $performMemoryManagementMethod->setAccessible(TRUE);
    
    // Set counter to trigger memory management
    $processedTemplatesProperty = $reflection->getProperty('processedTemplates');
    $processedTemplatesProperty->setAccessible(TRUE);
    $processedTemplatesProperty->setValue(NULL, 50);
    
    // Test that memory management runs without errors
    $this->assertNull($performMemoryManagementMethod->invoke($tokenRender));
  }
  
  /**
   * Test cache size limits are respected.
   */
  public function testCacheSizeLimits() {
    $mockEvent = $this->createMockEvent();
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    $reflection = new \ReflectionClass($tokenRender);
    
    $maxCacheSizeProperty = $reflection->getProperty('maxCacheSize');
    $maxCacheSizeProperty->setAccessible(TRUE);
    $maxSize = $maxCacheSizeProperty->getValue();
    
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);
    
    $cache = [];
    $order = [];
    
    // Add more items than max size
    for ($i = 1; $i <= $maxSize + 10; $i++) {
      $addToLRUCacheMethod->invoke($tokenRender, $cache, $order, "key$i", "value$i");
    }
    
    // Cache should never exceed max size
    $this->assertLessThanOrEqual($maxSize, count($cache));
    $this->assertLessThanOrEqual($maxSize, count($order));
  }
  
  /**
   * Test error handling in token processing.
   */
  public function testErrorHandlingInTokenProcessing() {
    // Create event that will trigger an error
    $mockEvent = $this->createMockEvent();
    $mockEvent->context['actionSearchResult']->activity_type_id = 999; // Non-existent
    
    $tokenRender = new CRM_ManualDirectDebit_Event_Listener_TokenRender($mockEvent);
    
    // Mock MessageTemplate to return TRUE for isDirectDebitTemplate
    global $isDirectDebitTemplate;
    $isDirectDebitTemplate = TRUE;
    
    // Should handle gracefully
    try {
      $tokenRender->replaceDirectDebitTokens();
      $this->assertTrue(TRUE); // Test passes if no exception
    } catch (Exception $e) {
      // Should not reach here in normal operation
      $this->fail('Should handle errors gracefully: ' . $e->getMessage());
    }
  }

  /**
   * Create mock event for testing.
   */
  private function createMockEvent() {
    $mockEvent = new \stdClass();
    $mockEvent->context = [
      'actionSchedule' => (object)['msg_template_id' => 1],
      'actionSearchResult' => (object)[
        'activity_type_id' => 1,
        'source_record_id' => 123
      ]
    ];
    $mockEvent->string = 'Test template string';
    
    return $mockEvent;
  }
  
  /**
   * Mock required classes and functions for testing.
   */
  private function mockRequiredClasses() {
    // Mock civicrm_api3 function
    if (!function_exists('civicrm_api3')) {
      eval('
        function civicrm_api3($entity, $action, $params = []) {
          global $apiCallCount;
          
          if (!isset($apiCallCount)) {
            $apiCallCount = 0;
          }
          $apiCallCount++;
          
          if ($entity === "OptionValue" && $action === "get") {
            return [
              "count" => 1,
              "values" => [
                ["name" => "test_activity_type"]
              ]
            ];
          }
          
          return ["count" => 0, "values" => []];
        }
      ');
    }
    
    // Mock Civi class
    if (!class_exists('\Civi')) {
      eval('
        class Civi {
          public static function log() {
            return new class {
              public function error($message) {
                // Mock logger
              }
            };
          }
        }
      ');
    }
    
    // Mock MessageTemplate class
    if (!class_exists('CRM_ManualDirectDebit_Common_MessageTemplate')) {
      eval('
        class CRM_ManualDirectDebit_Common_MessageTemplate {
          public static function isDirectDebitTemplate($templateId) {
            global $isDirectDebitTemplate;
            return isset($isDirectDebitTemplate) ? $isDirectDebitTemplate : FALSE;
          }
        }
      ');
    }
    
    // Mock data collector classes
    if (!class_exists('CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution')) {
      eval('
        class CRM_ManualDirectDebit_Mail_DataCollector_RecurringContribution {
          public function __construct($id) {}
          public function retrieve() { return []; }
        }
        
        class CRM_ManualDirectDebit_Mail_DataCollector_Contribution {
          public function __construct($id) {}
          public function retrieve() { return []; }
        }
        
        class CRM_ManualDirectDebit_Mail_DataCollector_Mandate {
          public function __construct($id) {}
          public function retrieve() { return []; }
        }
      ');
    }
  }

}