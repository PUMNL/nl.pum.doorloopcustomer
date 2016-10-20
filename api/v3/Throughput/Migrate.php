<?php
/**
 * Throughput.Migrate API
 * API for the migration of existing throughput data. Should only run once
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 20 Oct 2016
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_throughput_migrate($params) {
  $returnValues = array();
  $dao = CRM_Core_DAO::executeQuery('SELECT id AS project_id, customer_id FROM civicrm_project WHERE customer_id IS NOT NULL');
  while ($dao->fetch()) {
    $sqlParams = array();
    $sqlClauses = array();
    $sqlIndex = 0;
    $dateColumns = array(
      'date_customer_created' => 'customer_id',
      'date_request_submitted' => 'project_id',
      'date_assess_rep' => 'project_id',
      'date_assess_prof' => 'project_id',
      'date_first_main' => 'project_id',
      'date_expert_added' => 'project_id',
      'date_expert_reacted' => 'project_id',
      'date_cv_sent' => 'project_id',
      'date_cust_approves_expert' => 'project_id',
      'date_start_logistics' => 'project_id');
    foreach ($dateColumns as $dateColumn => $dateColumnParam) {
      _build_sql_clause($dateColumn, $dao->$dateColumnParam, $sqlIndex, $sqlClauses, $sqlParams);
    }
    if (!empty($sqlClauses)) {
      $sqlIndex++;
      $sql = 'UPDATE civicrm_project SET '.implode(', ', $sqlClauses).' WHERE entity_id = %'.$sqlIndex;
      $sqlParams[$sqlIndex] = array($dao->project_id, 'Integer');
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    }
  }
  return civicrm_api3_create_success($returnValues, $params, 'roughput', 'Migrate');
}

/**
 * Function to find the date and set the sql clause and sql params
 *
 * @param $columnName
 * @param $columnType
 * @param $functionParam
 * @param $sqlIndex
 * @param $sqlClauses
 * @param $sqlParams
 */
function _build_sql_clause($columnName, $functionParam, &$sqlIndex, &$sqlClauses, &$sqlParams) {
  $functionName = '_get_'.$columnName;
  $value = $functionName($functionParam);
  if ($value) {
    $sqlIndex++;
    $sqlClauses[] = $columnName.' = %'.$sqlIndex;
    $sqplParams[$sqlIndex] = array($value, 'String');
  }
}

/**
 * Function to get date customer created
 *
 * @param $customerId
 * @return bool|string
 */
function _get_date_customer_created($customerId) {
  $sql = 'SELECT created_date FROM civicrm_contact WHERE id = %1 AND date_customer_created IS NULL LIMIT 1000';
  $dateCreated = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($customerId, 'Integer')));
  if ($dateCreated) {
    return $dateCreated;
  } else {
    return FALSE;
  }
}

/**
 * Function to get date request sumbitted (start date of projectintake case on project)
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_request_submitted($projectId) {
  $sql = 'SELECT cc.start_date FROM civicrm_project prj JOIN civicrm_case_project cp ON prj.id = cp.project_id
    JOIN civicrm_case cc ON cp.case_id = cc.id WHERE prj.id = %1 AND cc.case_type_id LIKE %2';
}

