-- /*******************************************************
-- * Create direct debit tables
-- *******************************************************/

CREATE TABLE IF NOT EXISTS `dd_contribution_recurr_mandate_ref` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `recurr_id` int(10) unsigned NULL COMMENT 'FK to Recurrent ID civicrm_contribution_recur',
  `mandate_id` int(10) unsigned NULL COMMENT 'FK to Mandate ID civicrm_value_dd_mandate',
   PRIMARY KEY (`id`),
   CONSTRAINT FK_dd_contribution_recurr_mandate_ref_recurr_id FOREIGN KEY (`recurr_id`) REFERENCES `civicrm_contribution_recur`(`id`) ON DELETE SET NULL
);
