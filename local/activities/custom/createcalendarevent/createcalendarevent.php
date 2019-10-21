<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCreateCalendarEvent extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'Title' => '',
            'EventName' => '',
            'EventID' => null,
            'EventDesc' => '',
            'DateStart' => '',
            'DateEnd' => '',
            'ReminderTime' => 60,
            'CreateIcsFile' => false,
            'Members' => null
        );
    }

    public function Execute()
    {
        if (!CModule::IncludeModule("calendar"))
            return CBPActivityExecutionStatus::Closed;

        $rootActivity = $this->GetRootActivity();
        $documentId = $rootActivity->GetDocumentId();

        $fromTs = CCalendar::Timestamp($this->DateStart);
        $toTs = $this->DateEnd == '' ? $fromTs : CCalendar::Timestamp($this->DateEnd);

        $arFields = array(
            'CAL_TYPE' => 'company_calendar	',
            'OWNER_ID' => 0,
            'ID' => $this->EventID,
            'NAME' => trim($this->EventName) == '' ? GetMessage('EC_DEFAULT_EVENT_NAME') : $this->EventName,
            'DESCRIPTION' => $this->EventDesc,
            'IS_MEETING' => false,
            'RRULE' => false,
            'REMIND' => array(
                'type' => 'min',
                'count' => $this->ReminderTime
            )
        );

        $arFields['DATE_FROM'] = CCalendar::Date($fromTs);
        $arFields['DATE_TO'] = CCalendar::Date($toTs);

        $eventID = CCalendar::SaveEvent(
            array(
                'userId' => CBPHelper::ExtractUsers($this->Members, $documentId, true),
                'arFields' => $arFields,
                'autoDetectSection' => true,
                'autoCreateSection' => true
            )
        );

        $rootActivity->SetVariable('EventID', $eventID);

        if ($this->CreateIcsFile) {
            $icsFile = $this->getIcsFile($arFields['DATE_FROM'], $arFields['DATE_TO'], $arFields['NAME'], $arFields['DESCRIPTION']);

            $rootActivity->SetVariable('icsFile', $icsFile);
        }

        return CBPActivityExecutionStatus::Closed;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        global $USER;
        CModule::IncludeModule("calendar");
        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            'EventName' => 'event_name',
            'EventID' => 'event_id',
            'EventDesc' => 'event_desc',
            'Members' => 'event_members',
            'DateStart' => 'date_start',
            'DateEnd' => 'date_end',
            'ReminderTime' => 'reminder_time',
            'CreateIcsFile' => 'create_ics_file'
        );

        if (!is_array($arWorkflowParameters))
            $arWorkflowParameters = array();
        if (!is_array($arWorkflowVariables))
            $arWorkflowVariables = array();

        if (!is_array($arCurrentValues))
        {
            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity['Properties']))
            {
                foreach ($arMap as $k => $v)
                {
                    if (array_key_exists($k, $arCurrentActivity['Properties']))
                    {
                        if ($k == 'Members')
                            $arCurrentValues[$arMap[$k]] = CBPHelper::UsersArrayToString($arCurrentActivity['Properties'][$k], $arWorkflowTemplate, $documentType);
                        else
                            $arCurrentValues[$arMap[$k]] = $arCurrentActivity['Properties'][$k];
                    }
                    else
                    {
                        $arCurrentValues[$arMap[$k]] = '';
                    }
                }
            }
            else
            {
                foreach ($arMap as $k => $v)
                    $arCurrentValues[$arMap[$k]] = '';
            }
        }

        if (!$arCurrentValues['calendar_timezone'])
        {
            $userId = $USER->GetId();
            $tzName = CCalendar::GetUserTimezoneName($userId);
            $arCurrentValues['calendar_timezone'] = $tzName;
        }

        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            array(
                'arCurrentValues' => $arCurrentValues,
                'formName' => $formName,
            )
        );
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $arErrors = array();

        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            'event_name' => 'EventName',
            'event_id' => 'EventID',
            'event_members' => 'Members',
            'event_desc' => 'EventDesc',
            'date_start' => 'DateStart',
            'date_end' => 'DateEnd',
            'reminder_time' => 'ReminderTime',
            'create_ics_file' => 'CreateIcsFile'
        );

        $arProperties = array();
        foreach ($arMap as $key => $value)
        {
            if ($key == 'event_members')
                continue;
            $arProperties[$value] = $arCurrentValues[$key];
        }

        $arProperties['Members'] = CBPHelper::UsersStringToArray($arCurrentValues['event_members'], $documentType, $arErrors);
        if (count($arErrors) > 0)
            return false;

        $arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($arErrors) > 0)
            return false;

        if ($arCurrentValues['event_id'] > 0 && $arCurrentValues['event_name'] == '') {
            $arProperties['EventName'] = self::getEventName($arCurrentValues['event_id']);
        }

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity['Properties'] = $arProperties;

        return true;
    }

    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();

        if (!array_key_exists('Members', $arTestProperties) || count($arTestProperties['Members']) <= 0)
            $arErrors[] = array('code' => 'NotExist', 'parameter' => 'Members', 'message' => GetMessage("BPCCE_EMPTY_CALENDARUSER"));
        if (!array_key_exists('DateStart', $arTestProperties) || $arTestProperties['DateStart'] == '')
            $arErrors[] = array('code' => 'NotExist', 'parameter' => 'DateStart', 'message' => GetMessage("BPCCE_EMPTY_CALENDARFROM"));
        if (!array_key_exists('DateEnd', $arTestProperties) || $arTestProperties['DateEnd'] == '')
            $arErrors[] = array('code' => 'NotExist', 'parameter' => 'DateEnd', 'message' => GetMessage("BPCCE_EMPTY_CALENDARTO"));

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    /**
     * @param $eventId
     * @return bool|mixed
     */
    private static function getEventName($eventId)
    {
        CModule::IncludeModule("calendar");
        $name = CCalendarEvent::GetById($eventId);
        return $name;
    }

    /**
     * @param $start
     * @param $end
     * @param $name
     * @param $description
     * @return string
     */
    private function getIcsFile($start, $end, $name, $description)
    {
        $data = "BEGIN:VCALENDAR\nVERSION:2.0\nMETHOD:PUBLISH\nBEGIN:VEVENT\nDTSTART:".date("Ymd\THis\Z",strtotime($start))."\nDTEND:".date("Ymd\THis\Z",strtotime($end))."\nLOCATION:\nTRANSP: OPAQUE\nSEQUENCE:0\nUID:\nDTSTAMP:".date("Ymd\THis\Z")."\nSUMMARY:".$name."\nDESCRIPTION:".$description."\nPRIORITY:1\nCLASS:PUBLIC\nBEGIN:VALARM\nTRIGGER:-PT10080M\nACTION:DISPLAY\nDESCRIPTION:Reminder\nEnd:VALARM\nEnd:VEVENT\nEnd:VCALENDAR\n";
        $filename = CUtil::translit($name, 'ru');
        file_put_contents('/upload/ics/'.$filename.'.ics',$data);
        return '/upload/ics/'.$filename.'.ics';
    }
}