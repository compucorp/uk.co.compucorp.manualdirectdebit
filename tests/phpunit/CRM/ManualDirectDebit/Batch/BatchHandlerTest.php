<?php

use CRM_ManualDirectDebit_Batch_BatchHandler as BatchHandler;
use CRM_ManualDirectDebit_Batch_Transaction as BatchTransaction;
use CRM_ManualDirectDebit_Test_Fabricator_Batch as BatchFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Mandate as MandateFabricator;
use CRM_ManualDirectDebit_Test_Fabricator_Setting as SettingFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Runs tests on the data BatchHandler exports.
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Batch_BatchHandlerTest extends BaseHeadlessTest {

  /**
   * Leading zeros make any numeric coercion visible: a consumer that treats
   * this as a number gives back 123456.
   */
  const TEST_ACCOUNT_NUMBER = '00123456';

  const TEST_SORT_CODE = '04-00-04';

  public function setUp(): void {
    SettingFabricator::fabricate();
  }

  /**
   * Exported values must match what is stored against the mandate, byte for
   * byte.
   */
  public function testExportedValuesMatchTheStoredMandate() {
    $ddCode = $this->getFirstDirectDebitCode();
    $batchID = $this->fabricateBatchWithMandate($ddCode['value']);

    $row = $this->getExportedRow($batchID);

    $this->assertSame(self::TEST_ACCOUNT_NUMBER, $row['account_number']);
    $this->assertSame(self::TEST_SORT_CODE, $row['sort_code']);
    $this->assertSame($ddCode['label'], $row['transaction_type']);
  }

  /**
   * No exported column may carry leading or trailing whitespace, whatever it
   * was added for. The file is consumed by PTX, not only read in Excel.
   */
  public function testNoExportedColumnIsPadded() {
    $ddCode = $this->getFirstDirectDebitCode();
    $batchID = $this->fabricateBatchWithMandate($ddCode['value']);

    foreach ($this->getExportedRow($batchID) as $column => $value) {
      if ($value === NULL) {
        continue;
      }

      $this->assertSame(
        trim((string) $value),
        (string) $value,
        "Exported column '{$column}' is padded with whitespace. Exported values must be byte-exact; "
        . 'presentation concerns belong outside the export query.'
      );
    }
  }

  /**
   * Creates an instructions batch holding a single fabricated mandate.
   *
   * @param string $ddCode
   *   Direct debit code the mandate is given.
   *
   * @return int
   *   ID of the batch.
   */
  private function fabricateBatchWithMandate($ddCode) {
    $batch = BatchFabricator::fabricate([
      'type_id' => $this->getInstructionsBatchTypeId(),
    ]);

    $mandate = MandateFabricator::fabricate([
      'ac_number' => self::TEST_ACCOUNT_NUMBER,
      'sort_code' => self::TEST_SORT_CODE,
      'dd_code' => $ddCode,
    ]);

    CRM_Batch_BAO_EntityBatch::create([
      'entity_id' => $mandate['id'],
      'entity_table' => BatchTransaction::DD_MANDATE_TABLE,
      'batch_id' => $batch['id'],
    ]);

    return $batch['id'];
  }

  /**
   * Returns the single exported row for the given batch.
   *
   * @param int $batchID
   *
   * @return array
   */
  private function getExportedRow($batchID) {
    $handler = new BatchHandler($batchID);
    $method = new ReflectionMethod($handler, 'getDataForSaving');
    $method->setAccessible(TRUE);

    $rows = $method->invoke($handler);
    $this->assertCount(1, $rows, 'Expected the fabricated mandate to be the only item in the batch.');

    return reset($rows);
  }

  /**
   * Returns an installed direct debit code.
   *
   * @return array
   */
  private function getFirstDirectDebitCode() {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => 'direct_debit_codes',
      'options' => ['limit' => 1, 'sort' => 'weight ASC'],
    ]);

    $this->assertNotEmpty($result['values'], 'No direct debit codes are installed.');

    return $result['values'][0];
  }

  /**
   * Obtains the value for the 'New Instructions' batch type option value.
   *
   * @return string
   */
  private function getInstructionsBatchTypeId() {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'batch_type',
      'name' => BatchHandler::BATCH_TYPE_INSTRUCTIONS,
    ]);
  }

}
