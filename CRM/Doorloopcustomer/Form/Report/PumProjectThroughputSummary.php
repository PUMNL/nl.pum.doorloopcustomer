<?php

/**
 * Class CRM_Doorloopcustomer_Form_Report_PumProjectThroughput for PUM report Project Doorlooptijden
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 29 Sep 2016
 * @license AGPL-3.0

 */
class CRM_Doorloopcustomer_Form_Report_PumProjectThroughputSummary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_add2groupSupported = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupExtends = array();
  protected $_projectOfficerSelectList = array();
  private $_throughputColumnNames = array();
  private $_fieldNames = array();
  private $_poRows = array();
  private $_totalRow = array();

  /**
   * Constructor method
   */
  function __construct() {
    $this->setReportUserContext();
    $this->setThroughputColumns();
    $this->setProjectOfficerSelectList();
    $this->setPoRows();
    $this->setTotalRow();

    parent::__construct();
  }

  /**
   * Method to set the column names for the throughput columns
   *
   * @access private
   */
  private function setThroughputColumns() {
    // todo check against column names in modifyColumnHeaders
    $norms = CRM_Doorloopcustomer_Utils::getThroughutNormValues();
    $names = array(
      'from_request_to_approve_rep' => array(
        'from_date' => 'date_request_submitted',
        'to_date' => 'date_assess_rep'),
      'from_approve_rep_to_approve_prof' => array(
        'from_date' => 'date_assess_rep',
        'to_date' => 'date_assess_prof'),
      'from_request_to_approve_prof' => array(
        'from_date' => 'date_request_submitted',
        'to_date' => 'date_assess_prof'),
      'from_approve_prof_to_first_main' => array(
        'from_date' => 'date_assess_prof',
        'to_date' => 'date_first_main'),
      'from_first_main_to_expert_reacted' => array(
        'from_date' => 'date_first_main',
        'to_date' => 'date_expert_reacted'),
      'from_expert_reacted_to_cv_sent' => array(
        'from_date' => 'date_expert_reacted',
        'to_date' => 'date_cv_sent'),
      'from_cv_sent_to_customer_approves' => array(
        'from_date' => 'date_cv_sent',
        'to_date' => 'date_cust_approves_expert'),
      'from_customer_approves_to_start_logistics' => array(
        'from_date' => 'date_cust_approves_expert',
        'to_date' => 'date_start_logistics'),
    );
    foreach ($names as $key => $values) {
      $this->_fieldNames[] = $key;
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
    $this->_from = "FROM pum_project_throughput_view";
  }

  /**
   * Overridden parent method to build where clause
   */
  function where() {
    // only show projects where intake was between now and 2 mnths ago
    $selectionDate = date('Y').'-01-01 00:00:00';
    $endDate = date('Y-m-d').' 00:00:00';
    $this->_where = "WHERE date_request_submitted IS NOT NULL AND date_request_submitted > '{$selectionDate}' AND 
      (end_date IS NULL OR end_date > '{$endDate}')";
  }

  /**
   * Overridden parent method to set the column headers
   * add the form... to .... columns and sequence all headers
   */
  function modifyColumnHeaders() {
    $this->_columnHeaders['project_officer'] = array('title' => ts('Project Officer'),'type' => CRM_Utils_Type::T_STRING,);
    $this->_columnHeaders['no_projects'] = array('title' => ts('No. of Projects'),'type' => CRM_Utils_Type::T_STRING,);
    foreach ($this->_fieldNames as $name) {
      $title = $this->generateTitleFromColumnName($name);
      $this->_columnHeaders[$name] = array('title' => strtoupper(ts($title).':'),'type' => CRM_Utils_Type::T_STRING,);
      $this->_columnHeaders[$name.'_norm'] = array('title' => ts('Norm'),'type' => CRM_Utils_Type::T_STRING,);
      $this->_columnHeaders[$name.'_no_in'] = array('title' => ts('No. In'),'type' => CRM_Utils_Type::T_STRING,);
      $this->_columnHeaders[$name.'_pct_in'] = array('title' => ts('% In'),'type' => CRM_Utils_Type::T_STRING,);
      $this->_columnHeaders[$name.'_no_out'] = array('title' => ts('No. Out'),'type' => CRM_Utils_Type::T_STRING,);
      $this->_columnHeaders[$name.'_pct_out'] = array('title' => ts('%. Out'),'type' => CRM_Utils_Type::T_STRING,);
    }
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
   * Method to get the project officers list for the user filter
   *
   * @access private
   */
  private function setProjectOfficerSelectList() {
    $projectOfficerGroupId = civicrm_api3('Group', 'Getvalue', array('name' => 'Project_Officers_54', 'return' => 'id'));

    $result = array();
    $groupContactParams = array('group_id' => $projectOfficerGroupId, 'options' => array('limit' => 99999));
    try {
      $members = civicrm_api3('GroupContact', 'Get', $groupContactParams);
      foreach ($members['values'] as $member) {
        $result[$member['contact_id']] = CRM_Threepeas_Utils::getContactName($member['contact_id']);
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($result);
    $this->_projectOfficerSelectList = $result;
  }

  /**
   * method to set the Project Officer Rows
   */
  private function setPoRows()
  {
    foreach ($this->_projectOfficerSelectList as $projectOfficerId => $projectOfficer) {
      $poRow = array();
      $poRow['project_officer'] = $projectOfficer;
      $poRow['no_projects'] = 0;
      foreach ($this->_fieldNames as $element) {
        $poRow[$element . '_no_in'] = 0;

        $poRow[$element . '_no_out'] = 0;
        $poRow[$element . '_pct_in'] = 0;
        $poRow[$element . '_pct_out'] = 0;
      }
      $this->_poRows[$projectOfficerId] = $poRow;
    }
  }


  /**
   * method to set the total row
   */
  private function setTotalRow() {
    $this->_totalRow =array(
      'project_officer' => 'Totals',
      'no_projects' => 0
    );
    foreach ($this->_fieldNames as $element) {
      $this->_totalRow[$element . '_no_in'] = 0;
      $this->_totalRow[$element . '_no_out'] = 0;
      $this->_totalRow[$element . '_pct_in'] = 0;
      $this->_totalRow[$element . '_pct_out'] = 0;
    }
  }

  /**
   * Overridden parent method orderBy (issue 2995 order by status on weight)
   */
  function orderBy() {
    $this->_orderBy  = "ORDER BY project_officer_id";
  }

  /**
   * Set report url as user context
   *
   */
  private function setReportUserContext() {
    $session = CRM_Core_Session::singleton();
    $instanceId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_report_instance WHERE report_id = %1',
      array(1 => array('nl.pum.doorloopcustomer/pumprojectthroughputsummary', 'String')));
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
    $this->_select = "SELECT * ";
  }

  /**
   * Overridden parent method to build rows taking throughput columns into account
   * @param $sql
   * @param $rows
   */
  function buildRows($sql, &$rows) {
    $this->modifyColumnHeaders();
    $dao = CRM_Core_DAO::executeQuery($sql);
    // count no of project within norm or outside norm based on dao
    while ($dao->fetch()) {
      $currentNo = $this->_poRows[$dao->project_officer_id]['no_projects'];
      $currentNo++;
      $this->_poRows[$dao->project_officer_id]['no_projects'] = $currentNo;
      $this->enhancePoRowWithThroughput($dao);
    }
    // add norm row
    $rows[] = $this->fillNormRow();
    // now calculate percentages for project officer rows and add norms
    $this->completePoRows();
    foreach ($this->_poRows as $poRow) {
      $rows[] = $poRow;
    }
    $this->completeTotalRow();
    $rows[] = $this->_totalRow;
  }

  /**
   * Method to set the norm row
   *
   * @return array
   */
  private function fillNormRow() {
    $row = array(
      'project_officer' => '<strong>NORMS</strong>',
      'no_projects' => ''
    );
    foreach ($this->_fieldNames as $element) {
      $row[$element.'_no_in'] = $this->_throughputColumnNames[$element]['norm'];
      $row[$element.'_no_out'] = '';
      $row[$element.'_pct_in'] = '';
      $row[$element.'_pct_out'] = '';
    }
    return $row;
  }

  /**
   * Method to complete the project officer rows with percentage and add up in total row
   */
  private function completePoRows() {
    foreach ($this->_poRows as $projectOfficerId => $poRow) {
      // add po number of projects to total row number of projects
      $this->_totalRow['no_projects'] = $this->_totalRow['no_projects'] + $poRow['no_projects'];
      // for each from_to element
      foreach ($this->_fieldNames as $element) {
        // add numbers po to total numbers
        $this->_totalRow[$element.'_no_in'] = $this->_totalRow[$element.'_no_in'] + $poRow[$element.'_no_in'];
        $this->_totalRow[$element.'_no_out'] = $this->_totalRow[$element.'_no_in'] + $poRow[$element.'_no_out'];
        // calculate percentages
        if (!empty($poRow['no_projects'])) {
          $pctIn = round(($poRow[$element . '_no_in'] / $poRow['no_projects']) * 100);
          $this->_poRows[$projectOfficerId][$element . '_pct_in'] = $pctIn;
          $pctOut = round(($poRow[$element . '_no_out'] / $poRow['no_projects']) * 100);
          $this->_poRows[$projectOfficerId][$element . '_pct_out'] = $pctOut;
        }
      }
    }
  }

  /**
   * Method to complete the total row with percentages
   */
  private function completeTotalRow() {
    foreach ($this->_fieldNames as $element) {
      // calculate percentages
      $pctIn = round(($this->_totalRow[$element.'_no_in']/$this->_totalRow['no_projects']) * 100);
      $this->_totalRow[$element.'_pct_in'] = $pctIn;
      $pctOut = round(($this->_totalRow[$element.'_no_out']/$this->_totalRow['no_projects']) * 100);
      $this->_totalRow[$element.'_pct_out'] = $pctOut;
    }
  }

  /**
   * Method to add throughput data to each dao element
   *
   * @param object $dao
   */
  private function enhancePoRowWithThroughput($dao) {
    if (isset($this->_poRows[$dao->project_officer_id])) {
      foreach ($this->_fieldNames as $name) {
        $fromDateColumn = $this->_throughputColumnNames[$name]['from_date'];
        $toDateColumn = $this->_throughputColumnNames[$name]['to_date'];
        $fromDate = $dao->$fromDateColumn;
        $toDate = $dao->$toDateColumn;
        if (empty($fromDate) || empty($toDate)) {
          $this->_poRows[$dao->project_officer_id][$name.'_no_in']++;
      } else {
          $throughput = $this->calculateThroughput($fromDate, $toDate);
          $norm = $this->_throughputColumnNames[$name]['norm'];
          if ($throughput > $norm) {
            $this->_poRows[$dao->project_officer_id][$name.'_no_out']++;
          } else {
            $this->_poRows[$dao->project_officer_id][$name.'_no_in']++;
          }
        }
      }
    }
  }
  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      if (empty($row['no_projects'])) {
        $rows[$rowNum]['no_projects'] = '-';
      }
      foreach ($this->_fieldNames as $name) {
        if (empty($row[$name.'_no_in'])) {
          $rows[$rowNum][$name.'_no_in'] = '-';
        }
        if (empty($row[$name.'_no_out'])) {
          $rows[$rowNum][$name.'_no_out'] = '-';
        }
        if (empty($row[$name.'_pct_in'])) {
          $rows[$rowNum][$name.'_pct_in'] = '-';
        }
        if (empty($row[$name.'_pct_out'])) {
          $rows[$rowNum][$name.'_pct_out'] = '-';
        }
      }
    }
  }
}
