<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
/*
 * @see http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+Specifications+-++Batches#CiviAccountsSpecifications-Batches-%C2%A0Overviewofimplementation
 */
class CRM_Financial_BAO_ExportFormat_Winbooks extends CRM_Financial_BAO_ExportFormat {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * @param $exportParams
   */
  function export($exportParams) {
    parent::export($exportParams);

    self::assign('csf', $this->_exportParams['csf']);
    self::assign('act', $this->_exportParams['act']);
    self::assign('ant', $this->_exportParams['ant']);
    $this->output();
  }

  /**
   * @param null $fileName
   */
  public function output($fileName = NULL) {
    $tplFiles = $this->getHookedTemplateFileNames();
    foreach ($tplFiles as $tplFile) {
      $out[] = self::getTemplate()->fetch($tplFile);
    }
    $fileNames = $this->putFile($out);
    foreach ($fileNames as $fileName) {
      self::createActivityExport($this->_batchIds, $fileName);
    }
  }

  /**
   * @param $out array three strings to save as act, ant and csf.
   *
   * @return array file names of act, ant and csf file.
   */
  function putFile($out) {
    $config = CRM_Core_Config::singleton();
    // Winbooks heeft drie bestanden nodig.
    // Met specifieke naamgeving.
    //act.txt (bevat transacties; in ons geval allemaal verkoopfacturen)
    //ant.txt (bevat de info over de analytische plannen m.b.t. de facturen)
    //csf.txt (bevat de gegevens van te importeren klanten)

    $fileName[] = $config->uploadDir . 'act.txt';
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName[0]));
    $buffer = fopen($fileName[0], 'w');
    fwrite($buffer, $out[0]);
    fclose($buffer);

    $fileName[] = $config->uploadDir . 'ant.txt';
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName[1]));
    $buffer = fopen($fileName[1], 'w');
    fwrite($buffer, $out[1]);
    fclose($buffer);

    $fileName[] = $config->uploadDir . 'csf.txt';
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName[2]));
    $buffer = fopen($fileName[2], 'w');
    fwrite($buffer, $out[2]);
    fclose($buffer);
    return $fileName;
  }

  /**
   * Abuse generateExportQuery to generate the 3 queries for Winbooks export.
   *
   * @param type $batchId
   */
  function generateExportQuery($batchId) {
    $this->prepareWinbooksExport($batchId);
    $result = array();
    $result['csf'] = $this->generateCSFQuery($batchId);
    $result['act'] = $this->generateACTQuery($batchId);
    $result['ant'] = $this->generateANTQuery($batchId);
    return $result;
  }

  /**
   * @param $batchId
   *
   * @return Object
   */
  function generateCSFQuery($batchId) {

    // Query voor CSF bestand
    // Deel 1 voor groepsfacturen
    // Deel 2 is voor personen
    $results = array();
    $params = array('name'=>'relationship_lid_van_customfields');
    CRM_Core_BAO_CustomGroup::retrieve( $params, $results);
    $relationship_lid_van_customfields_tabel = $results['table_name'];

    $params = array('name'=>'contact_ploeg_customfields');
    CRM_Core_BAO_CustomGroup::retrieve( $params, $results);
    $contact_ploeg_customfields_tabel = $results['table_name'];

    $params = array('name'=>'contact_ploeg_customfields_rekeningnummer');
    CRM_Core_BAO_CustomField::retrieve( $params, $results);
    $contact_ploeg_customfields_rekeningnummer_kolom = $results['column_name'];

    $params = array('name'=>'relationship_lid_van_customfields_functie');
    CRM_Core_BAO_CustomField::retrieve( $params, $results);
    $relationship_lid_van_customfields_functie_kolom = $results['column_name'];

    $sql = "SELECT DISTINCT
      eb.id as batch_id,
      UPPER(contact_from.external_identifier) as referentie,
      CONCAT_WS(' ', contact_from.last_name, contact_from.first_name) as naam1,
      '' as naam2,
      contact_from_adres.street_address as adres1,
      contact_from_tel.phone as tel,
      '' as bankrekening,
      contact_from_adres.city as woonplaats,
      contact_from_adres.postal_code as postcode,
      contact_from_email.email as email,
      '' as categorie
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cont ON cont.id = eft.entity_id
      LEFT JOIN civicrm_contact contact_from ON contact_from.id = cont.contact_id
      LEFT JOIN civicrm_address contact_from_adres ON contact_from_adres.contact_id = contact_from.id and contact_from_adres.is_billing = 1
      LEFT JOIN civicrm_phone contact_from_tel ON contact_from_tel.contact_id = contact_from.id and contact_from_tel.is_primary = 1
      LEFT JOIN civicrm_email contact_from_email ON contact_from_email.contact_id = contact_from.id and contact_from_email.is_primary = 1
      WHERE eb.batch_id = ( %1 ) and contact_from.contact_type = 'Individual'
      UNION
      SELECT DISTINCT
      eb.id as batch_id,
      UPPER(contact_from.external_identifier) as referentie,
      contact_from.organization_name as naam1,
      "
      // HACK ALERT! : onderstaande constructie is een lelijke hack
      // om het AD-nummer uit de extra adreslijn weg te halen. We gebruiken
      // hier de kennis dat dat er staat, en wel tussen haakjes na een spatie.
      // Dit gaat ooit helemaal verkeerde resultaten opleveren als die 2de
      // adreslijn verandert, of als er een persoon is met een 'haakje open' in
      // zijn naam. Maar voorlopig kan dit ermee door als fix voor #4411.
      // Van zodra we in winbooks geen groepen meer hebben zonder financieel
      // verantwoordelijke, kunnen we dit beter aanpassen zodanig dat er
      // enkel records worden gegenereerd voor groepen die daadwerkelijk
      // een financieel verantwoordelijke hebben.
      ."LEFT(supplemental_address_1, LOCATE('(', supplemental_address_1) - 2) as naam2,
      cba.street_address as adres1,
      contact_fin_verantw_tel.phone as tel,
      extra_groep_informatie.$contact_ploeg_customfields_rekeningnummer_kolom as bankrekening,
      cba.city as woonplaats,
      cba.postal_code as postcode,
      contact_fin_verantw_email.email as email,
      'C' as categorie
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cont ON cont.id = eft.entity_id
      LEFT JOIN civicrm_contact contact_from ON contact_from.id = cont.contact_id
      # Zoektocht naar financieel verantwoordelijk
      LEFT JOIN civicrm_relationship contact_relation ON contact_from.id = contact_relation.contact_id_b and contact_relation.start_date <= NOW()  and contact_relation.end_date >= NOW()
      LEFT JOIN civicrm_relationship_type contact_relation_type ON contact_relation.relationship_type_id = contact_relation_type.id and contact_relation_type.name_b_a = 'ploeg_van'
      LEFT JOIN $relationship_lid_van_customfields_tabel extra_lid_informatie ON contact_relation.id = extra_lid_informatie.entity_id
      LEFT JOIN civicrm_contact contact_fin_verantw ON contact_fin_verantw.id = contact_relation.contact_id_a and extra_lid_informatie.$relationship_lid_van_customfields_functie_kolom like '%FI%'
      LEFT JOIN civicrm_address cba ON cba.contact_id = contact_from.id and cba.is_billing = 1
      LEFT JOIN civicrm_phone contact_fin_verantw_tel ON contact_fin_verantw_tel.contact_id = contact_fin_verantw.id and contact_fin_verantw_tel.is_primary = 1
      LEFT JOIN $contact_ploeg_customfields_tabel extra_groep_informatie on contact_from.id = extra_groep_informatie.entity_id
      LEFT JOIN civicrm_email contact_fin_verantw_email ON contact_fin_verantw_email.contact_id = contact_fin_verantw.id and contact_fin_verantw_email.is_primary = 1
      WHERE eb.batch_id = ( %1 ) and contact_from.contact_type = 'Organization'";

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    return $dao;
  }

  /**
   * @param $batchId
   *
   * @return Object
   */
  function generateACTQuery($batchId) {

    // Query voor ACT bestand

    // Lap. Hier wordt entity_batch aan contribution via financial_trxn en
    // entity_financial_trxn. In Batch.php (exportFinancialBatch) werd vroeger een
    // entity_batch rechtstreeks aan een contribution gekoppeld. Dat veroorzaakte
    // issue #5347.
    // Ik gok dat dit juist is, en pas de code in Batch.php aan.

    // Vermoedelijk worden transacties overgezet naar het boekhoudpakket,
    // en is het mogelijk dat er meerdere transacties zijn voor 1
    // contributie.

    $sql = "SELECT eb.id as batch_id,
      cont.source as dbkcode,
      cont.trxn_id as docnumber,
      cfa.accounting_code as accountgl,
      UPPER(contact.external_identifier) as accountrp,
      cont.receive_date as date,
      ft.total_amount as amounteur,
      li.line_total as lineamounteur,
      event.title as comment
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cont ON cont.id = eft.entity_id
      LEFT JOIN civicrm_line_item li ON cont.id = li.contribution_id
      LEFT JOIN civicrm_financial_type ftype on ftype.id = li.financial_type_id
      -- cluelessly joining financial account and financial type, see #4265
      LEFT JOIN civicrm_financial_account cfa on ftype.name = cfa.name
      LEFT JOIN civicrm_participant_payment partpay on cont.id = partpay.contribution_id
      LEFT JOIN civicrm_participant part on partpay.participant_id = part.id
      LEFT JOIN civicrm_event event on part.event_id = event.id
      LEFT JOIN civicrm_contact contact on contact.id = cont.contact_id
      WHERE cfa.account_type_code = 'INC' and eb.batch_id = ( %1 )
      -- only contributions with a transaction ID (#4464)
      AND cont.trxn_id IS NOT NULL
      ORDER BY docnumber, accountgl";

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    return $dao;
  }

  /**
   * @param $batchId
   *
   * @return Object
   */
  function generateANTQuery($batchId) {

    // Query voor ANT bestand

    $results = array();
    $params = array('name'=>'event_informatie');
    CRM_Core_BAO_CustomGroup::retrieve( $params, $results);
    $event_informatie_tabel = $results['table_name'];

    $params = array('name'=>'event_informatie_analytische_code');
    CRM_Core_BAO_CustomField::retrieve( $params, $results);
    $event_informatie_analytische_code_kolom = $results['column_name'];

    $sql = "SELECT eb.id as batch_id,
      cont.source as dbkcode,
      cont.trxn_id as docnumber,
      cfa.accounting_code as accountgl,
      cont.receive_date as date,
      li.line_total as amountgl,
      extra_event_informatie.$event_informatie_analytische_code_kolom as zonana1,
      event.title as comment
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cont ON cont.id = eft.entity_id
      LEFT JOIN civicrm_line_item li ON cont.id = li.contribution_id
      LEFT JOIN civicrm_financial_type ftype on ftype.id = li.financial_type_id
      -- cluelessly joining financial account and financial type, see #4265
      LEFT JOIN civicrm_financial_account cfa on ftype.name = cfa.name
      LEFT JOIN civicrm_participant_payment partpay on cont.id = partpay.contribution_id
      LEFT JOIN civicrm_participant part on partpay.participant_id = part.id
      LEFT JOIN civicrm_event event on part.event_id = event.id
      LEFT JOIN $event_informatie_tabel extra_event_informatie on event.id = extra_event_informatie.entity_id
      WHERE cfa.account_type_code = 'INC' and eb.batch_id = ( %1 )
      -- only contributions with a transaction ID (#4464)
      AND cont.trxn_id IS NOT NULL
      ORDER BY docnumber, accountgl";

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    return $dao;
  }

  /**
   * Create export based on DAO's for act, ant and csf files.
   *
   * This is a hack. Normally, makeExport expects an array of
   * DAO's, but for Winbooks, we have an array of arrays of 3 dao's,
   * one for each export file.
   *
   * But it works, because makeExport uses the output of
   * generateExportQuery, which we overloaded as well to generate the
   * 3 DAOs.
   *
   * @param $export array Een array die een batch-ID afbeeldt
   *        op een nieuwe array, met keys 'csf', 'act' en 'ant', en
   *        als value een DAO. De bedoeling is dat het overlopen van
   *        die DAO de velden oplevert die hieronder gebruikt worden.
   */
  function makeExport($export) {
    foreach ($export as $batchId => $dao_array) {
      // Execute winbooks querys, and fetch and format values, to assign to tpl
      $dao_csf = $dao_array['csf'];
      $this->_batchIds = $batchId;
      while ($dao_csf->fetch()) {
        $csf[$dao_csf->referentie] = array(
          'referentie' => $this->format($dao_csf->referentie, 'referentie'),
          'naam1' => $dao_csf->naam1,
          'naam2' => $dao_csf->naam2,
          'adres1' => $dao_csf->adres1,
          'tel' => $dao_csf->tel,
          'bankrekening' => $dao_csf->bankrekening,
          'woonplaats' => $dao_csf->woonplaats,
          'postcode' => $this->format($dao_csf->postcode, 'postcode'),
          'email' => $dao_csf->email,
          'categorie' => $dao_csf->categorie,
        );
      }
      $dao_act = $dao_array['act'];
      while ($dao_act->fetch()) {
        $act[$dao_act->docnumber][] = array(
          'dbkcode' => $this->format($dao_act->dbkcode, 'dbkcode'),
          'docnumber' => $dao_act->docnumber,
          'accountgl' => $dao_act->accountgl,
          'accountrp' => $this->format($dao_act->accountrp, 'accountrp'),
          'date' => $this->format($dao_act->date, 'date'),
          'duedate' => $this->format($dao_act->date, 'duedate'),
          'comment' => trim($this->format($dao_act->dbkcode, 'comment') . ' ' . $dao_act->comment),
          'amounteur' => $this->format($dao_act->amounteur,'amount'),
          'lineamounteur' => $this->format($dao_act->lineamounteur,'amount'),
          // Gestructureerde mededeling
          'ogm' => $this->ogm($dao_act->dbkcode, $dao_act->docnumber),
        );
      }
      $dao_ant = $dao_array['ant'];
      while ($dao_ant->fetch()) {
        $ant[$dao_ant->docnumber][] = array(
          'dbkcode' => $this->format($dao_ant->dbkcode, 'dbkcode'),
          'docnumber' => $dao_ant->docnumber,
          'accountgl' => $dao_ant->accountgl,
          'date' => $this->format($dao_ant->date, 'date'),
          'amountgl' => $this->format($dao_ant->amountgl,'amount'),
          'zonana1' => $dao_ant->zonana1,
          'comment' => trim($this->format($dao_ant->dbkcode, 'comment') . ' ' . $dao_ant->comment),
        );
      }
      $exportParams = array(
        'csf' => $csf,
        'act' => $act,
        'ant' => $ant,
      );
      self::export($exportParams);
    }
    self::uploadToFtpServer();
  }

  function uploadToFtpServer() {
    $ftp_user = variable_get('chiroftp_user', NULL);
    $ftp_pass = variable_get('chiroftp_pass', NULL);
    $ftp_url = variable_get('chiroftp_url', NULL);
    $ftp_folder = variable_get('chiroftp_folder', NULL) . '/factuurexport-' . date("Y-m-d-G-i");
    foreach ($this->_downloadFile as $file) {
      $ch = curl_init();
      $fp = fopen($file, 'r');
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_FTP_SSL, true);
      curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, true);
      curl_setopt($ch, CURLOPT_URL, 'ftp://' . $ftp_user . ':' . $ftp_pass . '@' . $ftp_url . '/' . $ftp_folder . '/' . CRM_Utils_File::cleanFileName(basename($file)));
      curl_setopt($ch, CURLOPT_UPLOAD, 1);
      curl_setopt($ch, CURLOPT_INFILE, $fp);
      curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
      curl_exec($ch);
      curl_close($ch);
    }
  }

  /**
   * @return string
   */
  function getMimeType() {
    return 'application/octet-stream';
  }

  /**
   * @return string
   */
  function getFileExtension() {
    return 'winbooks';
  }

  /**
   * @return array
   */
  function getHookedTemplateFileNames() {
    return array('CRM/Financial/ExportFormat/Winbooks-act.tpl', 'CRM/Financial/ExportFormat/Winbooks-ant.tpl', 'CRM/Financial/ExportFormat/Winbooks-csf.tpl');
  }

  /*
   * $s the input string
   * $type can be string, date, or notepad
   */

  /**
   * Formatteert veld $s om gebruikt te worden als outputveld $type
   * voor wblink.
   *
   * @param $s
   * @param string $type
   *
   * @return bool|mixed|string
   */
  static function format($s, $type = 'string') {
    switch ($type) {
      case 'referentie':
      case 'accountrp':
        // ADnr
        if (is_numeric($s)) {
          $sout = 'AD' . $s;
        }
        // stamnummer remove spaces and slash
        else {
          $sout = str_replace(array(" ", "/"), "", $s);
        }
        break;
      case 'postcode':
        //Blijkbaar doen we niet aan buitenlandse facturen
        $sout = 'BE-' . $s;
        break;
      case 'dbkcode':
        if ($s == 'AANSLUIT' || $s == 'U_VERZEK') {
          $sout = (string) "005";
        }
        else if ($s == 'DP') {
          $sout = (string) "006";
        }
        else {
          $sout = (string) "007";
        }
        break;
      case 'date':
        $sout = date('Ymd', strtotime($s));
        break;
      case 'duedate':
        $sout = date('Ymd', strtotime('+3 week', strtotime($s)));
        break;
      case 'comment':
        if ($s == 'AANSLUIT' || $s == 'U_VERZEK') {
          $sout = (string) "Aansluitingsfactuur";
        }
        else if ($s == 'DP') {
          $sout = (string) "Dubbelpuntfactuur";
        }
        else {
          // Hier gaat het vermoedelijk om een evenement. We vermelden niets,
          // want de naam van het evenement wordt nog aan deze output
          // geplakt.
          // Separation of concerns m'n gat :-P
          // (nee, TODO: dit moet eigenlijk beter.)
          $sout = (string) "";
        }
        break;
      case 'amount':
        // Punt als decimaal scheidingsteken, zie ook #4251
        $sout = number_format($s, 2, '.', '');
        break;
      case 'string':
        break;
    }

    return $sout;
  }

  /**
   * @param $bron_boekhouding
   * @param $factuurnummer
   *
   * @return string
   */
  static function ogm($bron_boekhouding, $factuurnummer) {
    if (!isset($factuurnummer) || trim($factuurnummer)==='') {
      throw new Exception('Leeg factuurnummer, dat zou niet mogen.');
    }
    $laatste_cijfer_jaar = ($factuurnummer / 100000) % 10;
    if ($bron_boekhouding == 'AANSLUIT' || $bron_boekhouding == 'U_VERZEK') {
      $ogm_code = 43;
    }
    else if ($bron_boekhouding == 'DP') {
      $ogm_code = 45;
    }
    else {
      $ogm_code = 47;
    }
    $controlegetal = (int)(($laatste_cijfer_jaar . $ogm_code . $factuurnummer) % 97);
    if($controlegetal == 0){
      $controlegetal = 97;
    }
    if($controlegetal < 10){
      $controlegetal = '0' . $controlegetal;
    }
    $factuurnummerdeel1 = substr($factuurnummer, 0, -3);
    $factuurnummerdeel2 = substr($factuurnummer, -3);
    return '+++' . $laatste_cijfer_jaar . $ogm_code . '/'
        . $factuurnummerdeel1 . '/' .
         $factuurnummerdeel2 . $controlegetal . '+++';
  }

  public function prepareWinbooksExport($batchId) {
    $max_aansluitingsfactuur_nr = CRM_Core_BAO_Setting::getItem('chirocontribution', 'chirocontribution_max_aansluitingsfactuur_nr');
    $max_cursusfactuur_nr = CRM_Core_BAO_Setting::getItem('chirocontribution', 'chirocontribution_max_cursusfactuur_nr');

    // Factuurnummer en OGM aanpassen van contribution. We proberen
    // eerst de contributies te vinden.
    // Pas ook receive_date aan (#4479)

    // Eigenlijk is het niet helemaal juist wat we hier doen. We
    // gebruiken de functionaliteit om transacties te exporteren, om
    // contributies te exporteren. In ons geval wordt er bij iedere
    // contributie automatisch een transactie aangemaakt, dat is ons
    // geluk.

    $sql = "SELECT cont.id, cont.financial_type_id
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution cont ON cont.id = eft.entity_id
      WHERE eb.batch_id = ( %1 ) AND cont.total_amount > 0
      ORDER BY cont.id";
    // Voor de zekerheid sorteren we hierboven ook op contributie-ID.
    // Op die manier probeer ik de aansluitingscontributies, die
    // gemaakt zijn op volgorde van stamnummer, op dezelfde manier
    // doorgeboekt te krijgen naar Winbooks. (#4407)

    // We geven enkel contributies met een total_amount > 0 een
    // factuurnummer. In Winbooks.php worden alleen contributies met
    // factuurnummer geëxporteerd. Op die manier vermijden we een
    // export van nulfacturen (#4464)

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $contribution_types = array();

    // Hacky.
    while ($dao->fetch()) {
      $contribution_types[$dao->id] = $dao->financial_type_id;
    }

    //Update facturen met factuurnr en ogm
    foreach ($contribution_types as $contribution_id => $financial_type_id) {
      if ($financial_type_id == CHIRO_FINANCIAL_TYPE_KOSTEN_EVENT ||
          // issue #4546
          $financial_type_id == CHIRO_FINANCIAL_TYPE_70100000) {
        $max_cursusfactuur_nr++;
        $ogm = CRM_Financial_BAO_ExportFormat_Winbooks::ogm('CURSUS', $max_cursusfactuur_nr);
        civicrm_api3('Contribution', 'create', array(
          'id' => $contribution_id,
          // Pruts met factuurdatum #4479
          'receive_date' =>  date("Y-m-d"),
          // Ik ben er zelf niet zeker van of trxn_id wel goed is voor
          // factuurnummer. Maar ik blijf eraf, in de hoop dat het zo
          // werkt.
          'trxn_id' => $max_cursusfactuur_nr,
          CHIRO_FIELD_CONTRIBUTION_OGM => $ogm,
        ));
      }
      if ($financial_type_id == CHIRO_FINANCIAL_TYPE_LIDGELD) {
        $max_aansluitingsfactuur_nr++;
        $ogm = CRM_Financial_BAO_ExportFormat_Winbooks::ogm('AANSLUIT', $max_aansluitingsfactuur_nr);
        // Voor aansluitingsfacturen stellen we ook het source veld in
        // Dat wordt in de winbooks export gebruikt voor het dagboek in
        // te vullen.
        civicrm_api3('Contribution', 'create', array(
          'id' => $contribution_id,
          // Pruts met factuurdatum #4479
          'receive_date' =>  date("Y-m-d"),
          'trxn_id' => $max_aansluitingsfactuur_nr,
          // 'source' => 'AANSLUIT' is al gezet bij maken aansluitingsfacturen.
          // We veranderen dat hier niet meer, voor wanneer we ooit de
          // vrienden van de Chiro gaan factureren, of misschien
          // opnieuw betalende Dubbelpuntabonnementen invoeren.
          CHIRO_FIELD_CONTRIBUTION_OGM => $ogm,
        ));
      }
    }
    CRM_Core_BAO_Setting::setItem($max_aansluitingsfactuur_nr, 'chirocontribution', 'chirocontribution_max_aansluitingsfactuur_nr');
    CRM_Core_BAO_Setting::setItem($max_cursusfactuur_nr, 'chirocontribution', 'chirocontribution_max_cursusfactuur_nr');
  }
}