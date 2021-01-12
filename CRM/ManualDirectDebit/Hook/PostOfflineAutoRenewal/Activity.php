<?php

/**
 * Process 'postOfflineAutoRenewal' hook and create activity
 */
class CRM_ManualDirectDebit_Hook_PostOfflineAutoRenewal_Activity extends CRM_ManualDirectDebit_Hook_Common_PostActivityBase {

  /**
   * Creates activity
   */
  protected function createActivity() {
    CRM_ManualDirectDebit_Common_Activity::create(
      "Offline Direct Debit Auto-renewal",
      "offline_direct_debit_auto_renewal",
      $this->contributionRecurDao->id,
      CRM_ManualDirectDebit_Common_User::getAdminContactId(),
      $this->contributionRecurDao->contact_id
    );
  }

}
