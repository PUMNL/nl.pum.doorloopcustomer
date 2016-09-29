<h3>{$ruleActionHeader}</h3>
<div class="crm-block crm-form-block crm-civirule-rule_action-block-set_project_date">
  <div class="crm-section set_project_date-section">
    <div class="label">
      <label for="project_date-select">{ts}PUm Project Indicator Date(s){/ts}</label>
    </div>
    <div class="content crm-select-container" id="project_date_block">
      {$form.project_date.html}
    </div>
    <div class="clear"></div>
  </div>
</div>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>