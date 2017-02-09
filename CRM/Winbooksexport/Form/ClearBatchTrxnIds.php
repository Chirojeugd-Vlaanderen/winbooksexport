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
require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Winbooksexport_Form_ClearBatchTrxnIds extends CRM_Core_Form {

  private $_batch;
  private $_aansluitingsfactuur_nr;
  private $_cursusfactuur_nr;
  
  /**
   * Build the form
   */
  public function buildQuickForm() {
    $this->_aansluitingsfactuur_nr = CRM_Core_BAO_Setting::getItem('chirocontribution', 'chirocontribution_max_aansluitingsfactuur_nr') + 1;
    $this->_cursusfactuur_nr = CRM_Core_BAO_Setting::getItem('chirocontribution', 'chirocontribution_max_cursusfactuur_nr') + 1;
    $this->assign('aansluitingsfactuur_nr', $this->_aansluitingsfactuur_nr);
    $this->assign('cursusfactuur_nr', $this->_cursusfactuur_nr);

    $result = civicrm_api3('BatchedContribution', 'get', array(
      'trxn_id' => array('IS NOT NULL' => 1),
      'api.Batch.getsingle' => array(
        'id' => '$value.batch_id',
        'return' => 'title,id',
      ),
      'return' => array("batch_id"),
      'options' => array('sort' => "batch_id desc", 'limit' => 1),
    ));
    $this->_batch = CRM_Utils_Array::first($result['values'])['api.Batch.getsingle'];
    $this->assign('batch', $this->_batch);

    $this->add(
      'text',
      'aansluitingsfactuur_nr',
      ts('New next transaction ID for membership contributions')
    );

    $this->add(
      'text',
      'cursusfactuur_nr',
      ts('New next transaction ID for event fees')
    );

    // add form elements
    $this->add(
      'select', // field type
      'confirmation', // field name
      ts('Are you sure you want to do this?'), // field label
      array(
        0 => ts("No"),
        1 => ts("Yes"),
      ),
      TRUE // is required
    );
    $this->addButtons(array(
      array(
        'type' => 'done',
        'name' => ts('Clear'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Set efault values for the form.
   * 
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $aansluitings_prefix = substr($this->_aansluitingsfactuur_nr, 0, -4);
    $cursus_prefix = substr($this->_cursusfactuur_nr, 0, -4);
    $params = array(
      'batch_id' => array('!=' => $this->_batch['id']),
      'trxn_id' => array('LIKE' => $aansluitings_prefix . '%'),
      'return' => 'trxn_id',
      'options' => array(
        'sort' => 'trxn_id DESC',
        'limit' => 1)
    );
    $result1 = civicrm_api3('BatchedContribution', 'get', $params);
    $defaults['aansluitingsfactuur_nr'] = CRM_Utils_Array::first($result1['values'])['trxn_id'] + 1;
    $params['trxn_id'] = array('LIKE' => $cursus_prefix . '%');
    $result2 = civicrm_api3('BatchedContribution', 'get', $params);
    $defaults['cursusfactuur_nr'] = CRM_Utils_Array::first($result2['values'])['trxn_id'] + 1;
    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();
    if (empty($values['confirmation'])) {
      CRM_Core_Session::setStatus(ts("No confirmation"), ts("Aborted"), "error");
    }
    civicrm_api3('BatchedContribution', 'get', array(
      'batch_id' => $this->_batch['id'],
      'return' => 'id',
      'options' => array('limit' => 0),
      'api.Contribution.create' => array(
        'id' => '$value.id',
        // if I do 'trxn_id' => NULL, it does not seem to work.
        'trxn_id' => '',
      ),
    ));

    CRM_Core_BAO_Setting::setItem($values['aansluitingsfactuur_nr'] - 1, 'chirocontribution', 'chirocontribution_max_aansluitingsfactuur_nr');
    CRM_Core_BAO_Setting::setItem($values['cursusfactuur_nr'] - 1, 'chirocontribution', 'chirocontribution_max_cursusfactuur_nr');

    CRM_Core_Session::setStatus(ts('Batch %1', array(1 => $this->_batch['id'])),
      ts('Transaction IDs cleared'),
      'success');

    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
