<?php

/**
 * Class for Project processing
 *
 * @author Erik Hommel (CiviCooP)
 * @date 28 Sep 2016
 * @license AGPL-3.0
 */
class CRM_Doorloopcustomer_Project {
  /**
   * Method to perform processing on civicrm_hook_post
   *
   * @param $op
   * @param $objectName
   * @param $objectId
   * @param $objectRef
   */
  public static function post($op, $objectName, $objectId, $objectRef) {
    $objectName = strtolower($objectName);
    // if new project, pick up the contact create date from customer or country and set in civicrm_project
    // and then do the same with the request submitted date using today
    if ($objectName == 'pumproject' && $op == 'create') {
      if (isset($objectRef->country_id)) {
        $contactId = $objectRef->country_id;
      } else {
        $contactId = $objectRef->customer_id;
      }
      $sql1 = 'SELECT created_date FROM civicrm_contact WHERE id = %1';
      $createdDate = CRM_Core_DAO::singleValueQuery($sql1, array(1 => array($contactId, 'Integer')));
      $nowDate = new DateTime();
      if ($createdDate) {

        $sql2 = 'UPDATE civicrm_project SET date_customer_created = %1, date_request_submitted = %2 WHERE id = %3';
        $params = array(
          1 => array(date('Y-m-d', strtotime($createdDate)), 'String'),
          2 => array($nowDate->format('Y-m-d'), 'String'),
          3 => array($objectId, 'Integer'));
        CRM_Core_DAO::executeQuery($sql2, $params);
      }
    }
  }
}