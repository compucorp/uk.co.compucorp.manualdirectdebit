<?php

use CRM_ManualDirectDebit_Test_Fabricator_Batch as BatchFabricator;

require_once __DIR__ . '/../../../BaseHeadlessTest.php';

/**
 * Test for CRM_ManualDirectDebit_Page_BatchList
 *
 * @group headless
 */
class CRM_ManualDirectDebit_Page_BatchListTest extends BaseHeadlessTest {

  /**
   * Test getBatchCount with date range.
   *
   * This covers the BETWEEN array case that was crashing.
   */
  public function testGetBatchCountWithDateRange() {
    $page = new CRM_ManualDirectDebit_Page_BatchList();

    // Create a few batches
    BatchFabricator::fabricate(['created_date' => '2023-01-01 10:00:00', 'status_id' => 1, 'name' => 'Direct_Debit_Batch_1']);
    BatchFabricator::fabricate(['created_date' => '2023-01-05 10:00:00', 'status_id' => 1, 'name' => 'Direct_Debit_Batch_2']);
    BatchFabricator::fabricate(['created_date' => '2023-01-10 10:00:00', 'status_id' => 1, 'name' => 'Direct_Debit_Batch_3']);

    $params = [
      'created_date' => ['BETWEEN' => ['2023-01-02 00:00:00', '2023-01-08 23:59:59']],
    ];

    // Use reflection to call private method getBatchCount
    $method = new ReflectionMethod('CRM_ManualDirectDebit_Page_BatchList', 'getBatchCount');
    $method->setAccessible(TRUE);

    $count = $method->invoke($page, $params);
    $this->assertEquals(1, $count);
  }

  /**
   * Test getBatchCount with no date filter.
   *
   * Ensure existing behaviour still works.
   */
  public function testGetBatchCountNoDate() {
    $page = new CRM_ManualDirectDebit_Page_BatchList();
    BatchFabricator::fabricate(['status_id' => 1, 'name' => 'Direct_Debit_Batch_4']);
    BatchFabricator::fabricate(['status_id' => 1, 'name' => 'Direct_Debit_Batch_5']);

    $method = new ReflectionMethod('CRM_ManualDirectDebit_Page_BatchList', 'getBatchCount');
    $method->setAccessible(TRUE);

    $count = $method->invoke($page, []);
    $this->assertEquals(2, $count);
  }

  /**
   * Test that getBatchCount uses apiParams from whereClause.
   *
   * Verifies that $apiParams from whereClause() is actually used in the count query
   * by checking if 'Data Entry' batches are excluded (which whereClause does by default).
   */
  public function testGetBatchCountExcludesDataEntry() {
    $page = new CRM_ManualDirectDebit_Page_BatchList();
    BatchFabricator::fabricate(['status_id' => 1, 'name' => 'Direct_Debit_Batch_6']);
    BatchFabricator::fabricate(['status_id' => 3, 'name' => 'Direct_Debit_Batch_7']);

    $method = new ReflectionMethod('CRM_ManualDirectDebit_Page_BatchList', 'getBatchCount');
    $method->setAccessible(TRUE);

    $count = $method->invoke($page, []);
    // It should be 1 because Data Entry is excluded by whereClause()
    // If it was still using $batchSearchParameters (which is empty), it would return 2.
    $this->assertEquals(1, $count);
  }

}
