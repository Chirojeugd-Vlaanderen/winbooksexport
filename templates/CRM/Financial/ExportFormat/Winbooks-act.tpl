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
*}{foreach from=$act key=act_id item=act_factuur}{foreach from=$act_factuur key=act_id item=act_item}{if $act_id == 0}"1","{$act_item.dbkcode}","2","{$act_item.docnumber}","001","","40000000","{$act_item.accountrp}","","","{$act_item.date}","{$act_item.date}","{$act_item.duedate}","{$act_item.comment}","{$act_item.ogm}","","{$act_item.amounteur}","{$act_item.amounteur}",""{"\r\n"}{/if}"3","{$act_item.dbkcode}","2","{$act_item.docnumber}","{"%03d"|sprintf:$act_id+2}","","{$act_item.accountgl}","{$act_item.accountrp}","","","{$act_item.date}","{$act_item.date}","{$act_item.duedate}","{$act_item.comment}","{$act_item.ogm}","","-{$act_item.lineamounteur}","0",""{"\r\n"}{/foreach}"4","{$act_item.dbkcode}","2","{$act_item.docnumber}","VAT","FIXED","","{$act_item.accountrp}","","","{$act_item.date}","{$act_item.date}","{$act_item.duedate}","{$act_item.comment}","","","0","{$act_item.amounteur}","2443000"{"\r\n"}{/foreach}