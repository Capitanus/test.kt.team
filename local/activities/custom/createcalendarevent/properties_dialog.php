<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right" width="40%"><span><?= GetMessage("BPCCE_EVENT_ID") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("string", 'event_id', $arCurrentValues['event_id'], Array('size'=> 50))?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span><?= GetMessage("BPCCE_EVENT_NAME") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("string", 'event_name', $arCurrentValues['event_name'], Array('size'=> 50))?>
    </td>
</tr>
<tr>
    <td align="right" width="40%" valign="top"> <?= GetMessage("BPCCE_EVENT_DESC") ?>:</td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("text", 'event_desc', $arCurrentValues['event_desc'], ['rows'=> 7, 'cols' => 40])?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPCCE_DATE_START") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("datetime", 'date_start', $arCurrentValues['date_start'])?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span><?= GetMessage("BPCCE_DATE_END") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("datetime", 'date_end', $arCurrentValues['date_end'])?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span><?= GetMessage("BPCCE_REMINDER_TIME") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("string", 'reminder_time', $arCurrentValues['reminder_time'], Array('size'=> 50))?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span><?= GetMessage("BPCCE_CREATE_ICS_FILE") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("bool", 'create_ics_file', $arCurrentValues['create_ics_file'])?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPCCE_EVENT_MEMBERS") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField('user', 'event_members', $arCurrentValues['event_members'], Array('rows' => 1))?>
    </td>
</tr>