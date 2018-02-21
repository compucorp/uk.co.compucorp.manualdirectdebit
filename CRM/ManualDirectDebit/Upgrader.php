<?php
use CRM_ManualDirectDebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_ManualDirectDebit_Upgrader extends CRM_ManualDirectDebit_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  public function enable() {

  }

  public function disable() {

  }

  public function uninstall() {
    $this->executeSqlFile('sql/uninstall.sql');
  }

}
