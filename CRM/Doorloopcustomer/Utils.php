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
      $norms = civicrm_api3('Doorloopnormen', 'get', array());
      foreach ($norms['values'] as $norm) {
        $result[$norm['name']] =  $norm['norm'];
      }
    } catch (CiviCRM_API3_Exception $ex) { }
    return $result;
  }
}