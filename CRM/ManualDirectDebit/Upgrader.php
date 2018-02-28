<?php
use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  public function uninstall() {
    $this->executeSqlFile('sql/uninstall.sql');
  }

}
