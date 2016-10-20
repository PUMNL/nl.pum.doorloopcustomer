<?php

/**
 * Class CRM_Doorloopcustomer_Form_Report_PumProjectThroughput for PUM report Project Doorlooptijden
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 29 Sep 2016
 * @license AGPL-3.0

 */
class CRM_Doorloopcustomer_Form_Report_PumProjectThroughput extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_add2groupSupported = FALSE;
  protected $_customGroupExtends = array();
  protected $_userSelectList = array();
  protected $_countrySelectList = array();
  protected $_customerSelectList = array();
  protected $_userId = NULL;
  private $_throughputColumnNames = array();

  /**
   * Constructor method
   */
  function __construct() {
    $this->setReportUserContext();
    $this->setUserSelectList();
    $this->setCountrySelectList();
    $this->setCustomerSelectList();
    $this->setThroughputColumnNames();

    $this->_columns = array(
      'project' => array(
        'alias' => 'project',
        'fields' => array(
          'project_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'project_name' => array(
            'title' => ts('Project Name'),
            'required' => TRUE
          ),
          'customer_name' => array(
            'title' => ts('Customer'),
            'default' => TRUE
          ),
          'date_customer_created' => array(
            'title' => ts('Date Customer Created'),
          ),
          'date_request_submitted' => array(
            'title' => ts('Date Request Received'),
          ),
          'date_assess_rep' => array(
            'title' => ts('Date Assessment Rep'),
          ),
          'date_assess_prof' => array(
            'title' => ts('Date Assessment Prof'),
          ),
          'date_first_main' => array(
            'title' => ts('Date First Main Act'),
          ),
          'date_expert_reacted' => array(
            'title' => ts('Date Expert Reacted'),
          ),
          'date_cv_sent' => array(
            'title' => ts('Date CV Sent'),
          ),
          'date_cust_approves_expert' => array(
            'title' => ts('Date Cust Appr. Expert'),
          ),
          'date_start_logistics' => array(
            'title' => ts('Date Logistics Started'),
          ),
          'start_date' => array(
            'title' => ts('Project Start Date'),
          ),
          'end_date' => array(
            'title' => ts('Project End Date')
          ),
          'country_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'customer_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'country_name' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'anamon_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'country_coordinator_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'project_officer_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'sector_coordinator_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),
          'projectmanager_id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'programme_manager_id' => array(
            'no_display' => TRUE,
            'required' => TRUE
          ),),
        'filters' => array(
          'user_id' => array(
            'title' => ts('Projects for user'),
            'default' => 1,
            'pseudofield' => 1,
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->_userSelectList,
          ),
          'project_name' => array(
            'title' => ts('Project'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ),
          'country_id' => array(
            'title' => ts('Country'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_countrySelectList,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'customer_id' => array(
            'title' => ts('Customer'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_customerSelectList,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  /**
   * Method to set the column names for the throughput columns
   *
   * @access private
   */
  private function setThroughputColumnNames() {
    // todo check against column names in modifyColumnHeaders
    $norms = CRM_Doorloopcustomer_Utils::getThroughutNormValues();
    $names = array(
      'from_request_to_approve_prof' => array(
        'from_date' => 'project_date_request_submitted',
        'to_date' => 'project_date_assess_prof'),
      'from_customer_created_to_request' => array(
        'from_date' => 'project_date_customer_created',
        'to_date' => 'project_date_request_submitted'),
      'from_request_to_approve_rep' => array(
        'from_date' => 'project_date_request_submitted',
        'to_date' => 'project_date_assess_rep'),
      'from_approve_rep_to_approve_prof' => array(
        'from_date' => 'project_date_assess_rep',
        'to_date' => 'project_date_assess_prof'),
      'from_approve_prof_to_first_main' => array(
        'from_date' => 'project_date_assess_prof',
        'to_date' => 'project_date_first_main'),
      'from_first_main_to_expert_reacted' => array(
        'from_date' => 'project_date_first_main',
        'to_date' => 'project_date_expert_reacted'),
      'from_expert_reacted_to_cv_sent' => array(
        'from_date' => 'project_date_expert_reacted',
        'to_date' => 'project_date_cv_sent'),
      'from_cv_sent_to_customer_approves' => array(
        'from_date' => 'project_date_cv_sent',
        'to_date' => 'project_date_cust_approves_expert'),
      'from_customer_approves_to_start_logistics' => array(
        'from_date' => 'project_date_cust_approves_expert',
        'to_date' => 'project_date_start_logistics'),
    );
    foreach ($names as $key => $values) {
      $this->_throughputColumnNames[$key] = array(
        'from_date' => $values['from_date'],
        'to_date' => $values['to_date'],
        'norm' => $norms[$key]);
    }
  }

  /**
   * Overridden parent method to build from part of query
   */

  function from() {
    $this->_from = "FROM pum_project_throughput_view {$this->_aliases['project']}";
  }

  /**
   * Overridden parent method to build where clause
   */
  function where() {
    $clauses = array();
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value("operatorType", $field) & CRM_Report_Form::OP_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to,
              CRM_Utils_Array::value('type', $field)
            );
          } else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($fieldName == 'user_id') {
              $this->setUserClause();
              $value = $this->_userId;
              if (!empty($value)) {
                $pum = $this->_aliases['project'];
                $clause = "({$pum}.anamon_id = {$value} OR {$pum}.programme_manager_id = {$value}
                OR {$pum}.country_coordinator_id = {$value} OR {$pum}.project_officer_id = {$value}
                OR {$pum}.projectmanager_id = {$value} OR {$pum}.sector_coordinator_id = {$value})";
              }
              $op = NULL;
            }

            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    } else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  /**
   * Overridden parent method to set the column headers
   * add the form... to .... columns and sequence all headers
   */
  function modifyColumnHeaders() {
    $sortedHeaders = array();
    foreach ($this->_columnHeaders as $headerKey => $header) {
      if ($headerKey == 'project_project_id' || $headerKey == 'project_project_name' || $headerKey == 'project_customer_name') {
        $sortedHeaders[] = array('key' => $headerKey, 'values' => $header);
      }
    }
    $newColumnHeaders = array(
      'from_customer_created_to_request',
      'from_request_to_approve_rep',
      'from_approve_rep_to_approve_prof',
      'from_request_to_approve_prof',
      'from_approve_prof_to_first_main',
      'from_first_main_to_expert_reacted',
      'from_expert_reacted_to_cv_sent',
      'from_cv_sent_to_customer_approves',
      'from_customer_approves_to_start_logistics');
    foreach ($newColumnHeaders as $newColumnHeader) {
      $title = $this->generateTitleFromColumnName($newColumnHeader);
      $newHeader = array(
        'key' => $newColumnHeader,
        'values' => array('title' => ts($title), 'type' => CRM_Utils_Type::T_STRING,));
      $sortedHeaders[] = $newHeader;
    }
    foreach ($this->_columnHeaders as $headerKey => $header) {
      if ($headerKey != 'project_project_id' && $headerKey != 'project_project_name' && $headerKey != 'project_customer_name') {
        $sortedHeaders[] = array('key' => $headerKey, 'values' => $header);
      }
    }
    $this->_columnHeaders = array();
    foreach ($sortedHeaders as $sortedKey => $sortedHeader) {
      $this->_columnHeaders[$sortedHeader['key']] = $sortedHeader['values'];
    }
    $this->_columnHeaders['drill_down'] = array('title' => '','type' => CRM_Utils_Type::T_STRING,);
  }

  /**
   * Method to generate header title for from to columns from column name
   *
   * @param $columnName
   * @return string
   */
  private function generateTitleFromColumnName($columnName) {
    $parts = explode('_', $columnName);
    $result = array();
    foreach ($parts as $part) {
      $result[] = ucfirst($part);
    }
    return implode(' ', $result);
  }

  /**
   * Overridden parent method to process criteria into report with data
   */
  function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * Overridden parent method to alter the display of each row
   * @param array $rows
   */
  function alterDisplay(&$rows) {

    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('project_project_id', $row)) {
        $drillUrl = CRM_Utils_System::url('civicrm/pumdrill', 'pumEntity=project&pid='.$row['project_project_id'], $this->_absoluteUrl);
        $rows[$rowNum]['drill_down'] = 'Drill Down';
        $rows[$rowNum]['drill_down_link'] = $drillUrl;
        $rows[$rowNum]['drill_down_hover'] = ts("Drill Down Project");
      }

      if (array_key_exists('project_start_date', $row) && (!empty($row['project_start_date']))) {
        $rows[$rowNum]['project_start_date'] = date('j F Y', strtotime($row['project_start_date']));
      }

      if (array_key_exists('project_end_date', $row) && (!empty($row['project_end_date']))) {
        $rows[$rowNum]['project_end_date'] = date('j F Y', strtotime($row['project_end_date']));
      }

      if (array_key_exists('project_date_customer_created', $row) && (!empty($row['project_date_customer_created']))) {
        $rows[$rowNum]['project_date_customer_created'] = date('j F Y', strtotime($row['project_date_customer_created']));
      }

      if (array_key_exists('project_date_request_submitted', $row) && (!empty($row['project_date_request_submitted']))) {
        $rows[$rowNum]['project_date_request_submitted'] = date('j F Y', strtotime($row['project_date_request_submitted']));

      }

      if (array_key_exists('project_date_assess_rep', $row) && (!empty($row['project_date_assess_rep']))) {
        $rows[$rowNum]['project_date_assess_rep'] = date('j F Y', strtotime($row['project_date_assess_rep']));
      }

      if (array_key_exists('project_date_assess_prof', $row) && (!empty($row['project_date_assess_prof']))) {
        $rows[$rowNum]['project_date_assess_prof'] = date('j F Y', strtotime($row['project_date_assess_prof']));
      }
      if (array_key_exists('project_date_first_main', $row) && (!empty($row['project_date_first_main']))) {
        $rows[$rowNum]['project_date_first_main'] = date('j F Y', strtotime($row['project_date_first_main']));
      }
      if (array_key_exists('project_date_expert_reacted', $row) && (!empty($row['project_date_expert_reacted']))) {
        $rows[$rowNum]['project_date_expert_reacted'] = date('j F Y', strtotime($row['project_date_expert_reacted']));
      }
      if (array_key_exists('project_date_cv_sent', $row) && (!empty($row['project_date_cv_sent']))) {
        $rows[$rowNum]['project_date_cv_sent'] = date('j F Y', strtotime($row['project_date_cv_sent']));
      }
      if (array_key_exists('project_date_cust_approves_expert', $row) && (!empty($row['project_date_cust_approves_expert']))) {
        $rows[$rowNum]['project_date_cust_approves_expert'] = date('j F Y', strtotime($row['project_date_cust_approves_expert']));
      }
      if (array_key_exists('proect_date_start_logistics', $row) && (!empty($row['proect_date_start_logistics']))) {
        $rows[$rowNum]['proect_date_start_logistics'] = date('j F Y', strtotime($row['proect_date_start_logistics']));
      }

      if (array_key_exists('project_project_name', $row)) {
        $projectUrl = CRM_Utils_System::url('civicrm/pumproject', 'action=view&pid='.$row['project_project_id'], $this->_absoluteUrl);
        $rows[$rowNum]['project_project_name_link'] = $projectUrl;
        $rows[$rowNum]['project_project_name_hover'] = ts('View Project');
      }

      if (CRM_Utils_Array::value('project_country_id', $rows[$rowNum]) && !empty($row['project_country_id'])) {
        $countryUrl = CRM_Utils_System::url("civicrm/contact/view" , "action=view&reset=1&cid=". $row['project_country_id'], $this->_absoluteUrl);
        $rows[$rowNum]['project_customer_name'] = $row['project_country_name'];
        $rows[$rowNum]['project_customer_name_link'] = $countryUrl;
        $rows[$rowNum]['project_customer_name_hover'] = ts("View Country");
      }

      if (CRM_Utils_Array::value('project_customer_id', $rows[$rowNum]) && !empty($row['project_customer_id'])) {
        $customerUrl = CRM_Utils_System::url("civicrm/contact/view" , "action=view&reset=1&cid=". $row['project_customer_id'], $this->_absoluteUrl);
        $rows[$rowNum]['project_customer_name_link'] = $customerUrl;
        $rows[$rowNum]['project_customer_name_hover'] = ts("View Customer");
      }
    }
  }

  /**
   * Method to add the required throughput columns to the report row
   *
   * @param $row
   */
  private function addThroughputColumns($dao, &$row) {

    foreach ($this->_throughputColumnNames as $columnName => $columnValues) {
      $fromDateColumn = $columnValues['from_date'];
      $toDateColumn = $columnValues['to_date'];
      $fromDate = $dao->$fromDateColumn;
      $toDate = $dao->$toDateColumn;
      if (!empty($fromDate) && !empty($toDate)) {
        $throughput = $this->calculateThroughput($fromDate, $toDate);
        if ($throughput == 0) {
          $throughputString = '0 days';
        } else {
          $throughputString = $throughput . ' days';
        }
        if ($throughput > $columnValues['norm']) {
          $row[$columnName] = '<span style="color: red;">' . $throughputString . '</span>';
        } else {
          $row[$columnName] = '<span style="color: green;">' . $throughputString . '</span>';
        }
      } else {
        $row[$columnName] = '<span style="color: red;"> - </span>';
      }
    }
  }

  /**
   * Method to calculate the number of working days between from date and to date
   *
   * @param $fromDate
   * @param $toDate
   * @return int
   */
  private function calculateThroughput($fromDate, $toDate) {
    $fromDate = new DateTime($fromDate);
    $toDate = new DateTime($toDate);
    $interval = $fromDate->diff($toDate);
    return $interval->format('%a');
  }

  /**
   * Overridden parent method to set the found rows on distinct project_id
   */
  function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    if ($this->_limit && ($this->_limit != '')) {
      $sql              = "SELECT COUNT(DISTINCT({$this->_aliases['project']}.project_id)) ".$this->_from." ".$this->_where;
      $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
      $params           = array(
        'total' => $this->_rowsFound,
        'rowCount' => $rowCount,
        'status' => ts('Records') . ' %%StatusMessage%%',
        'buttonBottom' => 'PagerBottomButton',
        'buttonTop' => 'PagerTopButton',
        'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
      );
      $pager = new CRM_Utils_Pager($params);
      $this->assign_by_ref('pager', $pager);
    }
  }

  /**
   * Method to add the user clause for where
   */
  private function setUserClause() {
    if (!isset($this->_params['user_id_value']) || empty($this->_params['user_id_value'])) {
      $session = CRM_Core_Session::singleton();
      $this->_userId = $session->get('userID');
    } else {
      $this->_userId = $this->_params['user_id_value'];
    }
  }

  /**
   * Method to get the users list for the user filter
   *
   * @access private
   */
  private function setUserSelectList() {
    if (method_exists('CRM_Groupsforreports_GroupReport', 'getGroupMembersForReport')) {
      $allContacts = CRM_Groupsforreports_GroupReport::getGroupMembersForReport(__CLASS__);
      $sortedContacts = array();
      foreach ($allContacts as $contact) {
        $sortedContacts[$contact] = CRM_Threepeas_Utils::getContactName($contact);
      }
      asort($sortedContacts);
      $this->_userSelectList = array(0 => 'current user') + $sortedContacts;
    }
  }

  /**
   * Method to get the country list for the user filter
   */
  private function setCountrySelectList() {
    $config = CRM_Threepeas_Config::singleton();
    $countryParams = array(
      'contact_sub_type' => $config->countryContactType,
      'contact_is_deleted' => 0,
      'options' => array('limit' => 0),
      'return' => 'display_name'
    );
    try {
      $countryContacts = civicrm_api3('Contact', 'Get', $countryParams);
      foreach ($countryContacts['values'] as $contactId => $contactValues) {
        $this->_countrySelectList[$contactId] = $contactValues['display_name'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
  }

  /**
   * Method to get the customer list for the user filter
   */
  private function setCustomerSelectList() {
    $config = CRM_Threepeas_Config::singleton();
    $customerParams = array(
      'contact_sub_type' => $config->customerContactType,
      'contact_is_deleted' => 0,
      'options' => array('limit' => 0),
      'return' => 'display_name'
    );
    try {
      $customerContacts = civicrm_api3('Contact', 'Get', $customerParams);
      foreach ($customerContacts['values'] as $contactId => $contactValues) {
        $this->_customerSelectList[$contactId] = $contactValues['display_name'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
  }

  /**
   * Overridden parent method orderBy (issue 2995 order by status on weight)
   */
  function orderBy() {
    $this->_orderBy  = "";
    $this->_orderByArray[] = $this->_aliases['project'].".project_id DESC";
    if(!empty($this->_orderByArray) && !$this->_rollup == 'WITH ROLLUP'){
      $this->_orderBy = "ORDER BY " . implode(', ', $this->_orderByArray);
    }
    $this->assign('sections', $this->_sections);
  }

  /**
   * Set report url as user context
   *
   */
  private function setReportUserContext() {
   $session = CRM_Core_Session::singleton();
    $instanceId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_report_instance WHERE report_id = %1',
      array(1 => array('pumprojectthroughputcustomer', 'String')));
    if (!empty($instanceId)) {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/report/instance/'.$instanceId, 'reset=1', true));
    } else {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/dashboard/', 'reset=1', true));
    }
  }
  /**
   * Overridden parent method select to make sure that the required date fields are selected in the row
   * even if they are not selected to be displayed
   */
  public function select() {
    parent::select();
    $dateColumns = array('date_customer_created', 'date_request_submitted', 'date_assess_rep', 'date_assess_prof',
      'date_first_main', 'date_expert_added', 'date_expert_reacted', 'date_cv_sent', 'date_cust_approves_expert',
      'date_start_logistics');
    foreach ($dateColumns as $dateColumn) {
      $clause = $this->_aliases['project'].'.'.$dateColumn.' as project_'.$dateColumn;
      if (!in_array($clause, $this->_selectClauses)) {
        $this->_selectClauses[] = $clause;
      }
    }
    $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
  }

  /**
   * Overridden parent method to build rows taking throughput columns into account
   * @param $sql
   * @param $rows
   */
  function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      // section headers not selected for display need to be added to row
      foreach ($unselectedSectionColumns as $key => $values) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      // add throughput columns to row
      $this->addThroughputColumns($dao, $row);
      $rows[] = $row;
    }
  }
}
