-- /*******************************************************
-- * Create direct debit tables
-- *******************************************************/

CREATE TABLE IF NOT EXISTS `dd_contribution_recurr_mandate_ref` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `recurr_id` int(10) unsigned NULL COMMENT 'FK to civicrm_contribution_recur ID',
  `mandate_id` int(10) unsigned NULL COMMENT 'FK to civicrm_value_dd_mandate ID',
   PRIMARY KEY (`id`),
   CONSTRAINT FK_dd_contribution_recurr_mandate_ref_recurr_id FOREIGN KEY (`recurr_id`) REFERENCES `civicrm_contribution_recur`(`id`) ON DELETE SET NULL
);
