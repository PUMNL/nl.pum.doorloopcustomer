<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Throughput.Resetmigrate API
 * API for resetting the migrated data.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_throughput_resetmigrate($params) {
  $returnValues = array();
  $sql = "UPDATE `civicrm_project`SET 
    date_customer_created = NULL,
    date_request_submitted = NULL,
    date_assess_rep = NULL,
    date_assess_prof = NULL,
    date_first_main = NULL,
    date_expert_added = NULL,
    date_expert_reacted = NULL,
    date_cv_sent = NULL,
    date_cust_approves_expert = NULL,
    date_start_logistics = NULL";
  CRM_Core_DAO::executeQuery($sql);
  return civicrm_api3_create_success($returnValues, $params, 'Throughput', 'Migrate');
}