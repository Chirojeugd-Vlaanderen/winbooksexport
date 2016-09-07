{* 
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
*}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<p>
{ts}
This will clear the transaction IDs assigned to the contributions in
<a href='{crmURL p='civicrm/batchtransaction' q="bid=`$batch.id`"}'>{$batch.title}</a>
(ID {$batch.id}).
{/ts}
</p>

{* manual layout because I want to show the current value of the
   next trxn IDs to be assigned. *}
   
<h3>{ts}Next transaction ID for membership contributions{/ts}</h3>
<div class="crm-section">
    <div class="label">{ts}current{/ts}</div>
    <div class="content">{$aansluitingsfactuur_nr}</div>
</div>
<div class="crm-section">
    <div class="label">{ts}after clearing{/ts}</div>
    <div class="content">{$form.aansluitingsfactuur_nr.html}</div>
</div>

<h3>{ts}Next transaction ID for event fees{/ts}</h3>
<div class="crm-section">
    <div class="label">{ts}current{/ts}</div>
    <div class="content">{$cursusfactuur_nr}</div>
</div>
<div class="crm-section">
    <div class="label">{ts}after clearing{/ts}</div>
    <div class="content">{$form.cursusfactuur_nr.html}</div>
</div>

<h3>{ts}Are you sure you want to do this?{/ts}</h3>
<div class="crm-section">
    <div class="label">{ts}please confirm{/ts}</div>
    <div class="content">{$form.confirmation.html}</div>
</div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT)

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}
*}

{* FIELD EXAMPLE: OPTION 2 (MANUAL LAYOUT)

  <div>
    <span>{$form.favorite_color.label}</span>
    <span>{$form.favorite_color.html}</span>
  </div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
