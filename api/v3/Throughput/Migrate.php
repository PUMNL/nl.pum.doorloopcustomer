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
  $dao = CRM_Core_DAO::executeQuery('SELECT id AS project_id, customer_id FROM civicrm_project WHERE customer_id IS NOT NULL 
    AND date_customer_created IS NULL LIMIT 1000');
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
  return civicrm_api3_create_success($returnValues, $params, 'Throughput', 'Migrate');
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
  $sql = 'SELECT created_date FROM civicrm_contact WHERE id = %1';
  $dateCreated = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($customerId, 'Integer')));
  if ($dateCreated) {
    return $dateCreated;
  } else {
    return FALSE;
  }
}

/**
 * Function to get latest active projectintake case id from project
 *
 * @param $projectId
 * @return bool|string
 */
function _get_project_intake_case_id_for_project($projectId) {
  $config = CRM_Projectintake_Config::singleton();
  $intakeCaseTypeId = $config->getCaseTypeId();
  $sql = 'SELECT cp.case_id FROM civicrm_case_project cp JOIN civicrm_case cc ON cp.case_id = cc.id 
    AND cc.is_deleted = %1 WHERE cp.project_id = %2 AND cc.case_type_id LIKE %3 ORDER BY cc.id DESC LIMIT 1';
  $sqlParams = array(
    1 => array(0, 'Integer'),
    2 => array($projectId, 'Integer'),
    3 => array('%'.$intakeCaseTypeId.'%', 'String')
  );
  $caseId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
  if ($caseId) {
    return $caseId;
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
  $config = CRM_Projectintake_Config::singleton();
  $intakeCaseTypeId = $config->getCaseTypeId();
  $sql = 'SELECT cc.start_date FROM civicrm_project prj JOIN civicrm_case_project cp ON prj.id = cp.project_id
    JOIN civicrm_case cc ON cp.case_id = cc.id WHERE prj.id = %1 AND prj.is_active = %2 AND cc.case_type_id LIKE %3 
    ORDER BY prj.id DESC LIMIT 1';
  $sqlParams = array(
    1 => array($projectId, 'Integer'),
    2 => array(1, 'Integer'),
    3 => array('%'.$intakeCaseTypeId.'%', 'String')
  );
  $dateRequestSubmitted = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
  if ($dateRequestSubmitted) {
    return $dateRequestSubmitted;
  } else {
    return FALSE;
  }
  /**
   * Function to get date assess rep (retrieve latest Change Custom Data activity with the relevant subject)
   *
   * @param $projectId
   * @return string|bool
   */
  function get_date_assess_rep($projectId) {
    $caseId = _get_project_intake_case_id_for_project($projectId);
    if ($caseId) {
      $changeCustomDataActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'activity_type',
        'name' => 'Change Custom Data',
        'return' => 'value'
      ));
      $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
        JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = %1
        WHERE cc.id = %2 AND a.activity_type_id = %3 AND a.subject = %4 ORDER BY a.activity_date_time DESC LIMIT 1';
      $sqlParams = array(
        1 => array(1, 'Integer'),
        2 => array($caseId, 'Integer'),
        3 => array($changeCustomDataActivityTypeId, 'Integer'),
        4 => array('Intake : change data', 'String')
      );
      $dateAssessRep = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
      if ($dateAssessRep) {
        return $dateAssessRep;
      } else {
        return FALSE;
      }
    }
  }
  function get_date_assess_prof($projectId) {
    $config = CRM_Projectintake_Config::singleton();
    $intakeCaseTypeId = $config->getCaseTypeId();
    $caseStatusChangedActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Case Status Changed',
      'option_group_id' => 'activity_type',
      'return' => 'value'
    ));
    $acceptedCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Accepted',
      'option_group_id' => 'case_status',
      'return' => 'value'
    ));
    $declinedCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Declined',
      'option_group_id' => 'case_status',
      'return' => 'value'
    ));
    $rejectedCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Rejected',
      'option_group_id' => 'case_status',
      'return' => 'value'
    ));
    $errorCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Error',
      'option_group_id' => 'case_status',
      'return' => 'value'
    ));
    $cancelledCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Cancelled',
      'option_group_id' => 'case_status',
      'return' => 'value'
    ));

    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_project cp ON cc.id = cp.case_id 
      AND cp.project_id = %1
      JOIN civicrm_case_activity ca ON cc.id = ca.case_id
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = %2 AND a.activity_type_id = %3
      WHERE cc.case_type_id LIKE %4 AND cc.status_id IN (%5,%6, %7, %8, %9) ORDER BY activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array($projectId, 'Integer'),
      2 => array(1, 'Integer'),
      3 => array($caseStatusChangedActivityTypeId, 'Integer'),
      4 => array($intakeCaseTypeId, 'Integer'),
      5 => array($acceptedCaseStatusId, 'Integer'),
      6 => array($cancelledCaseStatusId, 'Integer'),
      7 => array($declinedCaseStatusId, 'Integer'),
      8 => array($errorCaseStatusId, 'Integer'),
      9 => array($rejectedCaseStatusId, 'Integer')
    );
    $dateAssessProf = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateAssessProf) {
      return $dateAssessProf;
    } else {
      return FALSE;
    }
  }
}

