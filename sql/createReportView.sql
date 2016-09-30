CREATE OR REPLACE VIEW pum_project_throughput_view AS
  SELECT proj.id AS project_id, proj.title AS project_name, proj.start_date, proj.end_date, proj.projectmanager_id,
    proj.date_customer_created, proj.date_request_submitted, proj.date_assess_rep, proj.date_assess_prof,
    proj.anamon_id AS anamon_id, proj.sector_coordinator_id AS sector_coordinator_id, proj.country_coordinator_id
    AS country_coordinator_id, proj.project_officer_id AS project_officer_id, proj.programme_id, proj.country_id,
    proj.customer_id,
    prjmngr.display_name as projectmanager_name, cntry.display_name AS country_name,
    cst.display_name AS customer_name, prog.title AS programme_name, prog.manager_id AS programme_manager_id,
    prgmngr.display_name AS programme_manager_name
  FROM civicrm_project proj
    LEFT JOIN civicrm_programme prog ON proj.programme_id = prog.id
    LEFT JOIN civicrm_contact prjmngr ON proj.projectmanager_id = prjmngr.id
    LEFT JOIN civicrm_contact cntry ON proj.country_id = cntry.id
    LEFT JOIN civicrm_contact cst ON proj.customer_id = cst.id
    LEFT JOIN civicrm_contact prgmngr ON prog.manager_id = prgmngr.id
  WHERE proj.is_active = 1 AND proj.customer_id IS NOT NULL