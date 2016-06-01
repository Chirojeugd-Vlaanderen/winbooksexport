{*
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
 
 Alles op 1 lijn, om overbodige whitespace in export te verrmijden (#4251)
*}{foreach from=$csf key=csf_id item=csf_item}"{$csf_item.referentie}","1","{$csf_item.naam1}","{$csf_item.naam2}","","","{$csf_item.adres1}","","3","","","14","{$csf_item.tel}","","{$csf_item.bankrekening}","{$csf_item.postcode}","{$csf_item.woonplaats}","","N","{$csf_item.categorie}","40000000","","","","","","","","","","","","","FALSE","","FALSE","","FALSE","","","","FALSE","","","{$csf_item.email}","{$csf_item.email}"{"\r\n"}{/foreach}