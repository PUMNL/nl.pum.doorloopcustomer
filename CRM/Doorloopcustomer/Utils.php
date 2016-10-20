<?php

/**
 * Class for PUM Doorloopcustomer Utils functions
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 19 Oct 2016
 * @license AGPL-3.0
 */
class CRM_Doorloopcustomer_Utils {

  /**
   * Method to retrieve the throughput norm values
   *
   * @return array $result holding norm name and norm value
   * @access public
   * @static
   */
  public static function getThroughutNormValues() {
    $result = array();
    try {
      $optionValues = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'pum_throughput_norm',
        'is_active' => 1
      ));
      foreach ($optionValues['values'] as $optionValue) {
        $normNameParts = explode(' ', $optionValue['name']);
        $normNameLower = array();
        foreach ($normNameParts as $normNamePart) {
          $normNameLower[] = strtolower($normNamePart);
        }
        $normName = implode('_', $normNameLower);
        $result[$normName] =  $optionValue['label'];
      }
    } catch (CiviCRM_API3_Exception $ex) { }
    return $result;
  }
}