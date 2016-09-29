<?php
/**
 * Class for CiviRules Set Project Date for PUM Project
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 28 Sep 2016
 * @license AGPL-3.0
 */
class CRM_Doorloopcustomer_CiviRulesActions_SetProjectDate extends CRM_Civirules_Action {
  /**
   * Method processAction to execute the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   * @access public
   *
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $trigger = $triggerData->getTrigger();
    if (method_exists($trigger, 'getObjectName')) {
      $objectName = $trigger->getObjectName();
    } else {
      $objectName = '';
    }
    $entityData = $triggerData->getEntityData($objectName);
    if (!$entityData['project_id']) {
      $this->findProjectId($entityData);
    }
    $actionParams = $this->getActionParameters();
    $clauses = array();
    $params = array();
    $params[1] = array($entityData['project_id'], 'Integer');
    $nowDate = new DateTime();
    $index = 1;
    foreach ($actionParams['project_date'] as $projectDate) {
      $index++;
      $clauses[] = $projectDate.' = %'.$index;
      $params[$index] = array($nowDate->format('Y-m-d'), 'String');
    }
    if (!empty($clauses)) {
      $sql = 'UPDATE civicrm_project SET '.implode(', ', $clauses).' WHERE id = %1';
      CRM_Core_DAO::executeQuery($sql, $params);
    }
  }

  /**
   * Method to find project id based on available entityData
   *
   * @param $entityData
   */
  private function findProjectId(&$entityData) {
    // find by case_id if there is one
    if ($entityData['case_id']) {
      $sql = 'SELECT project_id FROM civicrm_case_project WHERE case_id = %1 LIMIT 1';
      $entityData['project_id'] = CRM_Core_DAO::singleValueQuery($sql, array(
        1 => array($entityData['case_id'], 'Integer')));
    }
  }

  /**
   * Returns a redirect url to extra data input from the user after adding a action
   *
   * Return false if you do not need extra data input
   *
   * @param int $ruleActionId
   * @return bool|string
   * @access public
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return CRM_Utils_System::url('civicrm/doorloopcustomer/civirules/action/setprojectdate', 'rule_action_id='.$ruleActionId);
  }

  /**
   * Returns a user friendly text explaining the condition params
   * e.g. 'Older than 65'
   *
   * @return string
   * @access public
   */
  public function userFriendlyConditionParams() {
    $return = "";
    $columnNames = array();
    $params = $this->getActionParameters();
    if (isset($params['project_date']) && !empty($params['project_date'])) {
      foreach ($params['project_date'] as $projectDate) {
        $columnNames[] = $projectDate;
      }
    }
    if (!empty($columnNames)) {
      $return = 'Project Date(s) : ' .implode('; ', $columnNames).' set to date action is executed';
    }
    return $return;
  }
}