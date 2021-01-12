<?php

/**
 * Gets target contribution data
 */
class CRM_ManualDirectDebit_ScheduleJob_TargetContribution {

  /**
   * Gets target contribution data
   *
   * @return array
   */
  public static function retrieve() {
    $reminderOffsetDays = CRM_ManualDirectDebit_ScheduleJob_ReminderOffsetDays::retrieve();
    $pendingStatusId = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('contribution_status', 'Pending');
    $cancelledStatusId = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('contribution_status', 'Cancelled');
    $directDebitPaymentInstrumentId = CRM_ManualDirectDebit_Common_OptionValue::getValueForOptionValue('payment_instrument', 'direct_debit');

    $query = "
      SELECT 
        contribution.id AS contribution_id,
        contact.id AS contact_id,
        (
          SELECT email.email
          FROM civicrm_email AS email
          WHERE email.contact_id = contact.id
            AND (contact.do_not_email IS NULL OR contact.do_not_email = 0)
            AND is_primary = 1
          LIMIT 1
        ) AS email
      FROM civicrm_contribution AS contribution
      LEFT JOIN civicrm_contact AS contact
        ON contribution.contact_id = contact.id 
      LEFT JOIN civicrm_value_direct_debit_collectionreminder_sendflag AS sendflag_customgroup 
        ON sendflag_customgroup.entity_id = contribution.id 
      WHERE 
        contribution.receipt_date IS NULL
        AND contribution.payment_instrument_id = %2
        AND (contribution.contribution_status_id = %3 OR contribution.contribution_status_id = %4)
        AND (DATE(contribution.receive_date) - INTERVAL %1 DAY) <= CURDATE() 
        AND sendflag_customgroup.is_notification_sent = 0 
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$reminderOffsetDays, 'Integer'],
      2 => [$directDebitPaymentInstrumentId, 'String'],
      3 => [$pendingStatusId, 'String'],
      4 => [$cancelledStatusId, 'String'],
    ]);

    $contributionDataList = [];
    while ($dao->fetch()) {
      $contributionDataList[] = [
        "contributionId" => $dao->contribution_id,
        "email" => $dao->email,
        "contactId" => $dao->contact_id,
      ];
    }

    return $contributionDataList;
  }

}
