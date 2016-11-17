<?php
/**
 * Class for CiviRules Action Set PUM Project Date Form
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 28 Sep 2016
 * @license AGPL-3.0
 */

class CRM_Doorloopcustomer_CiviRulesActions_Form_SetProjectDate extends CRM_CivirulesActions_Form_Form {

  /**
   * Overridden parent method to build the form
   *
   * @access public
   */
  public function buildQuickForm() {
    $this->add('hidden', 'rule_action_id');
    $dateColumnList = $this->getDateColumnList();
    $this->add('select', 'project_date', ts('PUM Project Indicator Date(s)'), $dateColumnList, true,
      array('id' => 'project_date', 'multiple' => 'multiple','class' => 'crm-select2'));
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE,),
      array('type' => 'cancel', 'name' => ts('Cancel'))));
  }

  /**
   * Overridden parent method to set default values
   *
   * @return array $defaultValues
   * @access public
   */
  public function setDefaultValues() {
    $defaultValues = parent::setDefaultValues();
    $defaultValues['rule_action_id'] = $this->ruleActionId;
    if (!empty($this->ruleAction->action_params)) {
      $data = unserialize($this->ruleAction->action_params);
    }
    if (!empty($data['project_date'])) {
      $defaultValues['project_date'] = $data['project_date'];
    }
    return $defaultValues;
  }

  /**
   * Overridden parent method to process form data after submitting
   *
   * @access public
   */
  public function postProcess() {
    if (!empty($this->_submitValues['project_date'])) {
      $data['project_date'] = $this->_submitValues['project_date'];
    } else {
      $data['project_date'] = null;
    }
    $this->ruleAction->action_params = serialize($data);
    $this->ruleAction->save();
    parent::postProcess();
  }

  /**
   * Method to get list of PUM Project indicator date fields (recognized by date as first 4 chars of column name)
   * @return array
   */
  private function getDateColumnList() {
    $dao = CRM_Core_DAO::executeQuery('SHOW COLUMNS FROM civicrm_project');
    $result = array();
    while ($dao->fetch()) {
      if (substr($dao->Field, 0, 4) == 'date') {
        $labelParts = array();
        $parts = explode('_', $dao->Field);
        foreach ($parts as $part) {
          $labelParts[] = ucfirst($part);
        }
        $result[$dao->Field] = implode(' ', $labelParts);
      }
    }
    asort($result);
    return $result;
  }
}