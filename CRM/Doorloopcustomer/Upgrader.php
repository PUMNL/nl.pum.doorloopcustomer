<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Doorloopcustomer_Upgrader extends CRM_Doorloopcustomer_Upgrader_Base {
  /**
   * Create new fields if not exists on install
   */
  public function install() {
    // add project date indicator fields to civicrm_project
    if (CRM_Core_DAO::checkTableExists('civicrm_project')) {
      $columnNames = array(
        'date_customer_created',
        'date_request_submitted',
        'date_assess_rep',
        'date_assess_prof',
        'date_first_main',
        'date_expert_added',
        'date_cv_sent',
        'date_cust_approves_expert',
        'date_start_logistics',
        'date_next_step'
        );
      foreach ($columnNames as $columnName) {
        if (!CRM_Core_DAO::checkFieldExists('civicrm_project', $columnName)) {
          CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_project ADD COLUMN '.$columnName.' DATE NULL');
        }
      }
    } else {
      throw new Exception('Table civicrm_project not found in database in '.__METHOD__
        .', this table is required to use this extension. Contact your system administrator');
    }
  }
}
