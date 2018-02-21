-- /*******************************************************
-- * Create direct debit tables
-- *******************************************************/

CREATE TABLE IF NOT EXISTS `dd_contribution_recurr_mandate_ref` (
  `id` INT unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `recurr_id` INT(11) NULL COMMENT 'FK to Recurrent ID ....',
  `mandate_id` INT(11) NULL COMMENT 'FK to Mandate ID ....',
   PRIMARY KEY (`id`)
--   CONSTRAINT FK_....._recurr_id FOREIGN KEY (`recurr_id`) REFERENCES `.....`(`id`) ON DELETE SET NULL,
--   CONSTRAINT FK_....._mandate_id FOREIGN KEY (`mandate_id`) REFERENCES `....`(`id`) ON DELETE SET NULL,
);
-- TODO: Add table names for FK
