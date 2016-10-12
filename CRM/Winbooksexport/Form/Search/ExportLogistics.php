<?php
/*
  be.chiro.civi.winbooksexport - export contribution batches to Winbooks.
  Copyright (C) 2016  Chirojeugd-Vlaanderen vzw

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Custom search that exports contacts to logistics.
 */
class CRM_Winbooksexport_Form_Search_ExportLogistics extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Financieel verantwoordelijken voor Logistics'));

    $contactTypes = array('' => ts('- any contact type -')) + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type',
      ts('is...'),
      $contactTypes,
      FALSE,
      array('class' => 'crm-select2')
    );

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('contact_type'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    // Let's not translate the column names, because they might be relevant
    // for this export.
    $columns = array(
      'groepid' => 'external_identifier',
      'naam' => 'organization_name',
      'contact' => 'supplemental_address_1',
      'adres' => 'street_address',
      'postcode' => 'postal_code',
      'plaats' => 'city',
      'land' => 'iso_code',
      'tel' => 'phone',
      'fax' => 'fax',
      'taal' => 'taal',
      'rekeningnr' => 'rekeningnummer_10',
      'email' => 'email',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id AS contact_id,
      contact_a.external_identifier,
      contact_a.organization_name,
      a.supplemental_address_1,
      a.street_address,
      a.postal_code,
      a.city,
      ctr.iso_code,
      finver.phone,
      '' AS fax,
      'N' AS taal,
      ei.rekeningnummer_10,
      finver.email
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM civicrm_contact contact_a
      -- we only export if there is a postal address
      JOIN civicrm_address a ON contact_a.id = a.contact_id AND a.is_billing = 1
      LEFT OUTER JOIN civicrm_country ctr ON a.county_id = ctr.id
      LEFT OUTER JOIN civicrm_value_extra_informatie_2 ei ON contact_a.id = ei.entity_id
      -- the e-mail adddress and phone number that we are searching this way, do
      -- not necessarily correspond to the address. This is because we organize
      -- the payment information for ploegen in a buggy way.
      LEFT OUTER JOIN (
        SELECT r.contact_id_b, e.email, p.phone
        FROM civicrm_relationship r
        JOIN ccciv.civicrm_value_extra_lid_informatie_4 li ON r.id = li.entity_id
        LEFT OUTER JOIN civicrm_phone p ON r.contact_id_a = p.contact_id AND p.is_primary
        LEFT OUTER JOIN civicrm_email e ON r.contact_id_a = e.contact_id AND e.is_primary
        WHERE r.relationship_type_id = " . CHIRO_RELATIONSHIP_LID_VAN . " 
        AND r.is_active = 1
        AND li.functie_15 LIKE '%" . CRM_Utils_Array::implodePadded('FI') . "%'
        GROUP BY (r.contact_id_b)
      ) finver ON contact_a.id = finver.contact_id_b
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();
    $clauses = array();
    $clauses[] = "(contact_a.is_deceased = 0)";
    $contactType = $this->_formValues['contact_type'];
    if (!empty($contactType)) {
      $parts = explode('__', $contactType);
      $clauses[] = "(contact_a.contact_type = %0)";
      $params[0] = [$parts[0], 'String'];
      if (isset($parts[1])) {
        $clauses[] = "(contact_a.contact_sub_type like %1)";
        $params[1] = ['%' . CRM_Utils_Array::implodePadded($parts[1]) . '%', 'String'];
      }
    }
    $clauses[] = "(contact_a.contact_sub_type like '%Ploeg%')";
    $where = implode(' AND ', $clauses);
    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    $row['external_identifier'] = CRM_Financial_BAO_ExportFormat_Winbooks::format($row['external_identifier'], 'referentie');
    $row['supplemental_address_1'] = CRM_Financial_BAO_ExportFormat_Winbooks::format($row['supplemental_address_1'], 'display_name');
  }
}
