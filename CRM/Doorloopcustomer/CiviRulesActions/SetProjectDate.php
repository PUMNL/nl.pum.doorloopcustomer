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
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData)
  {
    $trigger = $triggerData->getTrigger();
    if (method_exists($trigger, 'getObjectName')) {
      $objectName = $trigger->getObjectName();
    } else {
      $objectName = '';
    }
    $entityData = $triggerData->getEntityData($objectName);
    //get case and project data if available and always entityData for object
    $projectData = $triggerData->getEntityData('Project');
    $caseData = $triggerData->getEntityData('Case');
    $entityData = $triggerData->getEntityData($objectName);
    CRM_Core_Error::debug('projectData', $projectData);
    CRM_Core_Error::debug('caseData', $caseData);
    CRM_Core_Error::debug('entityData', $entityData);
    /*
     * project id should be in entity project. If not there, use case_id in caseData or entityData
     */
    if (!empty($projectData)) {
      $projectId = $projectData['id'];
    } else {
      $projectId = $this->findProjectId($caseData, $entityData);
    }
    // no reason to do anything if no project id
    if ($projectId) {
      $actionParams = $this->getActionParameters();
      $clauses = array();
      $params = array();
      $params[1] = array($projectId, 'Integer');
      $nowDate = new DateTime();
      $index = 1;
      foreach ($actionParams['project_date'] as $projectDate) {
        $index++;
        $clauses[] = $projectDate . ' = %' . $index;
        $params[$index] = array($nowDate->format('Y-m-d'), 'String');
      }
      if (!empty($clauses)) {
        $sql = 'UPDATE civicrm_project SET ' . implode(', ', $clauses) . ' WHERE id = %1';
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }
  }

  /**
   * Method to find project id based on available caseData and if not there attempt entityData
   *
   * @param array $caseData
   * @param array $entityData
   * @return int|bool
   */
  private function findProjectId($caseData, $entityData) {
    // if caseData, use id to find project else check if there is a case_id in entityData
    if (!empty($caseData)) {
      $caseId = $caseData['id'];
    } else {
      if (isset($entityData['case_id'])) {
        $caseId = $entityData['case_id'];
      }
    }
    if ($caseId) {
      $sql = 'SELECT project_id FROM civicrm_case_project WHERE case_id = %1 LIMIT 1';
      return CRM_Core_DAO::singleValueQuery($sql, array(1 => array($caseId, 'Integer')));
    }
    return FALSE;
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
        $labelParts = array();
        $parts = explode('_', $projectDate);
        foreach ($parts as $part) {
          $labelParts[] = ucfirst($part);
        }
        $columnNames[] = implode(' ', $labelParts);
      }
    }
    if (!empty($columnNames)) {
      $return = 'Project Date(s) : ' .implode('; ', $columnNames).' set to date action is executed';
    }
    return $return;
  }
}