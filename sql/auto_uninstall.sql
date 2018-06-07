-- /*******************************************************
-- * Delete direct debit tables
-- *******************************************************/

--SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `dd_contribution_recurr_mandate_ref`;

DELETE FROM civicrm_setting WHERE `name` LIKE 'manualdirectdebit_%';
