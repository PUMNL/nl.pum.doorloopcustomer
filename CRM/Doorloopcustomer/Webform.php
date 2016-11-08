<?php

/**
 * Class for Webform processing
 *
 * @author Erik Hommel (CiviCooP)
 * @date 29 Sep 2016
 * @license AGPL-3.0
 */
class CRM_Doorloopcustomer_Webform {
  /**
   * Method to process webform (called from extension org.civicoop.civiruleswebform)
   *
   * @param $webformData
   * @throws Exception if no nid in $webformData
   */
  public static function processWebform(&$webformData) {
    // Check whether the element nid and uid are set.
    // $webformData['uid'] could be 0 when an anonymous user has filled in the webform.
    if (!isset($webformData['nid']) || !isset($webformData['uid'])) {
      throw new Exception('Could not find one of the required elements nid, uid in param webformData in '.__METHOD__
        .', contact your system administrator!');
    }
    switch ($webformData['nid']) {
      case 732:
        self::processAsssessRequestByRep($webformData);
        break;
      default:
        if (!empty($webformData['uid'])) {
          $webformData['contact_id'] = civicrm_api3('UFMatch', 'getvalue', array(
            'uf_id' => $webformData['uid'],
            'return' => 'contact_id'
          ));
        }
        break;
    }
  }

  /**
   * Method to add relevant data to the webformData array for Assess Project Request by Rep webform
   *
   * @param $webformData
   */
  public static function processAsssessRequestByRep(&$webformData) {
    $request = CRM_Utils_Request::exportValues();
    if ($request['caseid']) {
      $caseId = (int) $request['caseid'];
      $webformData['case_id'] = $caseId;
      $query = 'SELECT project_id FROM civicrm_case_project WHERE case_id = %1 LIMIT 1';
      $webformData['project_id'] = CRM_Core_DAO::singleValueQuery($query, array(1 => array($caseId, 'Integer')));
    }
    foreach ($webformData['data'] as $componentId => $component) {
      if ($component['form_key'] == 'civicrm_1_contact_1_contact_existing') {
        $webformData['contact_id'] = (int) $component['value'][0];
        $webformData['representative_id'] = (int) $component['value'][0];
      }
      if ($component['form_key'] == 'civicrm_1_contact_2_contact_existing') {
        $webformData['customer_id'] = (int) $component['value'][0];
      }
    }
  }
}