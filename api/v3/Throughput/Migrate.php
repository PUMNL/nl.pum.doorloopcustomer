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
  $noDataFound = 0;
  $dao = CRM_Core_DAO::executeQuery('SELECT id AS project_id, customer_id FROM civicrm_project WHERE 
    customer_id IN (SELECT id FROM civicrm_contact WHERE is_deleted=0)
    AND date_customer_created IS NULL AND is_active = 1 LIMIT 1000');
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
      $sql = 'UPDATE civicrm_project SET '.implode(', ', $sqlClauses).' WHERE id = %'.$sqlIndex;
      $sqlParams[$sqlIndex] = array($dao->project_id, 'Integer');
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
    } else {
      $noDataFound ++;
    }
  }

  $remainCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_project WHERE 
    customer_id IN (SELECT id FROM civicrm_contact WHERE is_deleted=0) 
    AND date_customer_created IS NULL AND is_active = 1 ');
  $remainCount = $remainCount - $noDataFound;

  $returnValues[] = array(
    'Remaining projects to migrate' => $remainCount,
  );

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
    $sqlParams[$sqlIndex] = array($value, 'String');
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
  }
  return FALSE;
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
  $sql = "SELECT cp.case_id FROM civicrm_case_project cp JOIN civicrm_case cc ON cp.case_id = cc.id 
    AND cc.is_deleted = %1 WHERE cp.project_id = %2 AND cc.case_type_id LIKE %3 ORDER BY cc.id DESC LIMIT 1";
  $sqlParams = array(
    1 => array(0, 'Integer'),
    2 => array($projectId, 'Integer'),
    3 => array('%'.$intakeCaseTypeId.'%', 'String')
  );
  $caseId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
  if ($caseId) {
    return $caseId;
  }
  return FALSE;
}

/**
 * Method to get the case id of the first main activity on a project
 *
 * @param int $projectId
 * @return int|bool
 * @access public
 * @static
 */
function _get_first_mainactivity_case_id_for_project($projectId) {
  static $caseIds = array();
  if (isset($caseIds[$projectId])) {
    return $caseIds[$projectId];
  }
  $config = CRM_Threepeas_CaseRelationConfig::singleton();
  $validCaseTypes = $config->getExpertCaseTypes();
  $projectCases = CRM_Threepeas_BAO_PumProject::getCasesByProjectId($projectId);
  foreach ($projectCases as $projectCaseId => $projectCase) {
    $caseTypeId = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, "", $projectCase['case_type']);
    if (in_array($caseTypeId, $validCaseTypes)) {
      $caseIds[$projectId] = $projectCaseId;
      return $projectCaseId;
    }
  }
  return FALSE;
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
    3 => array('%' . $intakeCaseTypeId . '%', 'String')
  );
  $dateRequestSubmitted = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

  if ($dateRequestSubmitted) {
    return $dateRequestSubmitted;
  }
  return FALSE;
}

/**
 * Function to get date assess rep (retrieve latest Change Custom Data activity with the relevant subject)
 *
 * @param $projectId
 * @return string|bool
 */
function _get_date_assess_rep($projectId) {
  $caseId = _get_project_intake_case_id_for_project($projectId);
  if ($caseId) {
    $changeCustomDataActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Change Custom Data',
      'return' => 'value'
    ));
    $assessmentRepActivitytypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Assessment Project Request by Rep',
      'return' => 'value'
    ));
    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = %1
      WHERE cc.id = %2 AND ((a.activity_type_id = %3 AND a.subject = %4) OR a.activity_type_id = %5) ORDER BY a.activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array(1, 'Integer'),
      2 => array($caseId, 'Integer'),
      3 => array($changeCustomDataActivityTypeId, 'Integer'),
      4 => array('Intake : change data', 'String'),
      5 => array($assessmentRepActivitytypeId, 'Integer')
    );
    $dateAssessRep = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateAssessRep) {
      return $dateAssessRep;
    }
  }
  return FALSE;
}

/**
 * Function to get date assess prof (retrieve latest Case status Change to Accepted, Declined,Rejected, Error or Cancelled)
 *
 * @param $projectId
 * @return string|bool
 */
function _get_date_assess_prof($projectId) {
  $config = CRM_Projectintake_Config::singleton();
  $intakeCaseTypeId = $config->getCaseTypeId();
  $caseStatusChangedActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
    'name' => 'Change Case Status',
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
  $completedCaseStatusId = civicrm_api3('OptionValue', 'getvalue', array(
    'name' => 'Completed',
    'option_group_id' => 'case_status',
    'return' => 'value'
  ));

  $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_project cp ON cc.id = cp.case_id 
    AND cp.project_id = %1
    JOIN civicrm_case_activity ca ON cc.id = ca.case_id
    JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = %2 AND a.activity_type_id = %3
    WHERE cc.case_type_id LIKE %4 AND cc.status_id IN (%5,%6, %7, %8, %9, %10) ORDER BY activity_date_time DESC LIMIT 1';
  $sqlParams = array(
    1 => array($projectId, 'Integer'),
    2 => array(1, 'Integer'),
    3 => array($caseStatusChangedActivityTypeId, 'Integer'),
    4 => array('%'.CRM_Core_DAO::VALUE_SEPARATOR.$intakeCaseTypeId.CRM_Core_DAO::VALUE_SEPARATOR.'%', 'String'),
    5 => array($acceptedCaseStatusId, 'Integer'),
    6 => array($cancelledCaseStatusId, 'Integer'),
    7 => array($declinedCaseStatusId, 'Integer'),
    8 => array($errorCaseStatusId, 'Integer'),
    9 => array($rejectedCaseStatusId, 'Integer'),
    10 => array($completedCaseStatusId, 'Integer'),
  );
  $dateAssessProf = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
  if ($dateAssessProf) {
    return $dateAssessProf;
  }
  return FALSE;
}

/**
 * Function to get dstart date of the first main activity case on project.
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_first_main($projectId) {
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $sql = 'SELECT cc.start_date FROM civicrm_case cc WHERE cc.id = %1';
    $sqlParams = array(
      1 => array($caseId, 'Integer')
    );
    $dateFirstMain = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateFirstMain) {
      return $dateFirstMain;
    }
  }
  return FALSE;
}

/**
 * Function to get the date the last time an expert is added to the first main activity.
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_expert_added($projectId) {
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $assignCaseRoleActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Assign Case Role',
      'return' => 'value'
    ));
    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = 1
      WHERE cc.id = %1 AND a.activity_type_id = %2 AND a.subject LIKE "Expert%" ORDER BY a.activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array($caseId, 'Integer'),
      2 => array($assignCaseRoleActivityTypeId, 'Integer'),
    );
    $dateExpertAdded = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateExpertAdded) {
      return $dateExpertAdded;
    }
  }
  return false;
}

/**
 * Function to retrieve the date of the last time an expert has reacted on the first main activity.
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_expert_reacted($projectId) {
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $acceptMainActivityProposalActTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Accept Main Activity Proposal',
      'return' => 'value'
    ));
    $rejectMainActivityProposalActTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Reject Main Activity Proposal',
      'return' => 'value'
    ));
    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = 1
      WHERE cc.id = %1 AND a.activity_type_id IN (%2, %3) ORDER BY a.activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array($caseId, 'Integer'),
      2 => array($acceptMainActivityProposalActTypeId, 'Integer'),
      3 => array($rejectMainActivityProposalActTypeId, 'Integer'),
    );

    $dateExpertReacted = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateExpertReacted) {
      return $dateExpertReacted;
    }
  }
  return FALSE;
}

/**
 * Function to retrieve the date of the last time a customer approved an expert on the first main activity.
 * Ideally this should have been the date the activity has been scheduled however we only know the date of when the activity
 * got completed. So the date is a few days late.
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_cv_sent($projectId) {
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $approveExpertByCustomerActTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Approve Expert by Customer',
      'return' => 'value'
    ));
    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = 1
      WHERE cc.id = %1 AND a.activity_type_id IN (%2) ORDER BY a.activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array($caseId, 'Integer'),
      2 => array($approveExpertByCustomerActTypeId, 'Integer'),
    );
    $dateCvSent = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateCvSent) {
      return $dateCvSent;
    }
  }
  return FALSE;
}

/**
 * Function to get the date of the last time a customer approved an expert in the first main activity.
 *
 * @param $projectId
 * @return bool|string
 */
function _get_date_cust_approves_expert($projectId) {
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $approveExpertByCustomerActTypeId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_type',
      'name' => 'Approve Expert by Customer',
      'return' => 'value'
    ));
    $completedStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'option_group_id' => 'activity_status',
      'name' => 'completed',
      'return' => 'value'
    ));
    $sql = 'SELECT a.activity_date_time FROM civicrm_case cc JOIN civicrm_case_activity ca ON cc.id = ca.case_id 
      JOIN civicrm_activity a ON ca.activity_id = a.id AND a.is_current_revision = 1
      WHERE cc.id = %1 AND a.activity_type_id IN (%2) AND a.status_id = %3 ORDER BY a.activity_date_time DESC LIMIT 1';
    $sqlParams = array(
      1 => array($caseId, 'Integer'),
      2 => array($approveExpertByCustomerActTypeId, 'Integer'),
      3 => array($completedStatusId, 'Integer'),
    );
    $dateCustomerApproved = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateCustomerApproved) {
      return $dateCustomerApproved;
    }
  }
  return FALSE;
}

function _get_date_start_logistics($projectId) {
  $config = CRM_Travelcase_Config::singleton();
  $travelCaseTypeId = $config->getCaseType('value');
  $caseId = _get_first_mainactivity_case_id_for_project($projectId);
  if ($caseId) {
    $sql = "SELECT travel.start_date 
            FROM civicrm_case travel 
            INNER JOIN civicrm_value_travel_parent travel_parent ON travel_parent.entity_id = travel.id
            WHERE travel_parent.case_id = %1 AND travel.case_type_id LIKE %2
            ORDER BY travel.start_date DESC LIMIT 1";
    $sqlParams = array(
      1 => array($caseId, 'Integer'),
      2 => array('%'.$travelCaseTypeId.'%', 'String')
    );
    $dateStartLogistics = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($dateStartLogistics) {
      return $dateStartLogistics;
    }
  }
  return FALSE;
}