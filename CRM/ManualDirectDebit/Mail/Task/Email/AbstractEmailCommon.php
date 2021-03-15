<?php


abstract class CRM_ManualDirectDebit_Mail_Task_Email_AbstractEmailCommon extends CRM_Contact_Form_Task_EmailCommon {

  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(&$form) {
    $this->bounceIfSimpleMailLimitExceeded(count($form->_contactIds));

    // check and ensure that
    $formValues = $form->controller->exportValues($form->getName());

    $this->submit($form, $formValues);
  }

  /**
   * Validates ids
   *
   * @param $ids
   *
   * @return array
   */
  protected static function validateIds($ids) {
    if (is_array($ids)) {
      $validatedIds = [];
      foreach ($ids as $id) {
        $validatedIds[] = (int) $id;
      }

      return $validatedIds;
    }

    return [];
  }

  abstract protected function submit(&$form, $formValues);

}
