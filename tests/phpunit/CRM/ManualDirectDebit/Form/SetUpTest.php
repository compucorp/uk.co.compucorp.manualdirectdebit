<?php

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on SettingsManager.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Form_SetUpTest extends BaseHeadlessTest {

  private $setupForm;

  public function setUp() {
    $formController = new CRM_Core_Controller();
    $this->setupForm = new CRM_ManualDirectDebit_Form_SetUp();
    $this->setupForm->controller = $formController;
   // $this->setupForm->buildForm();
  }

  public function testPostProcess() {

    $this->setupForm->setVar('_submitValues', [
      'contribution_id' => 1,
    ]);

    //print_r($this->setupForm->exportValues());

    //$this->setupForm->postProcess();
  }

}
