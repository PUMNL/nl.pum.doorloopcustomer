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
        'date_expert_reacted',
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
      $this->executeSqlFile('/sql/createReportView.sql');
    } else {
      throw new Exception('Table civicrm_project not found in database in '.__METHOD__
        .', this table is required to use this extension. Contact your system administrator');
    }
  }

  /**
   * Upgrade 1001 - add view
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001 (add report view to database');
    $this->executeSqlFile('/sql/createReportView.sql');
    return TRUE;
  }

  /**
   * Upgrade 1002 - add latest update of view
   */
  public function upgrade_1002() {
    $this->ctx->log->info('Applying update 1002 (add column date_expert_reacted and update report view to database');
    if (CRM_Core_Dao::checkTableExists('civicrm_project')) {
      if (!CRM_Core_DAO::checkFieldExists('civicrm_project', 'date_expert_reacted')) {
        CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_project ADD COLUMN date_expert_reacted DATE NULL');
      }
    }
    $this->executeSqlFile('/sql/createReportView.sql');
    return TRUE;
  }
}
