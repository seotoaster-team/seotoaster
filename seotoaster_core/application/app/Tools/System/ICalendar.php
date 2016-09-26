<?php

class Tools_System_ICalendar
{

    /**
     * Get the start time set for the even
     * @return string
     */
    public static function formatDate($date)
    {
        $date = new DateTime($date);
        return $date->format("Ymd\THis\Z");
    }

    public static function formatValue($str)
    {
        return addcslashes($str, ",\\;");
    }

    public static function generateString($parameters)
    {
        $parameters += array(
            'summary' => 'Untitled Event',
            'description' => '',
            'location' => '',
            'title' => 'Calendar',
            'author' => 'Calender Generator'
        );
        $parameters = filter_var_array($parameters);
        if (empty($parameters['uid'])) {
            $parameters['uid'] = uniqid(rand(0, getmypid()));
        }
        return "BEGIN:VCALENDAR" . PHP_EOL
        . "VERSION:2.0" . PHP_EOL
        . "PRODID:-//" . $parameters['author'] . "//NONSGML//EN" . PHP_EOL
        . "X-WR-CALNAME:" . $parameters['title'] . PHP_EOL
        . "CALSCALE:GREGORIAN" . PHP_EOL
        . "BEGIN:VEVENT" . PHP_EOL
        . "UID:" . $parameters['uid'] . PHP_EOL
        . "DTSTART:" . self::formatDate($parameters['start']) . PHP_EOL
        . "DTEND:" . self::formatDate($parameters['end']) . PHP_EOL
        . "DTSTAMP:" . self::formatDate($parameters['start']) . PHP_EOL
        . "CREATED:" . self::formatDate(date('Y/m/d H:i:s')) . PHP_EOL
        . "DESCRIPTION:" . self::formatValue($parameters['description']) . PHP_EOL
        . "LAST-MODIFIED:" . self::formatDate($parameters['start']) . PHP_EOL
        . "LOCATION:" . $parameters['location'] . PHP_EOL
        . "SUMMARY:" . self::formatValue($parameters['summary']) . PHP_EOL
        . "SEQUENCE:0" . PHP_EOL
        . "STATUS:CONFIRMED" . PHP_EOL
        . "TRANSP:OPAQUE" . PHP_EOL
        . "END:VEVENT" . PHP_EOL
        . "END:VCALENDAR";
    }

    /**
     *
     * Call this function to download the invite.
     */
    public static function generateDownloadInvite($params)
    {
        $generated = self::generateString($params);
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename=calendar.ics',
            'Content-type', 'application/force-download',
            'Content-Length', strlen($generated)
        );
        $response->setBody($generated);
        $response->sendResponse();
        exit;
    }

    /**
     *
     * Call this function to email the invite.
     */
    public static function generateEmailInviteAttachment($parameters)
    {
        $type = !empty($parameters['mimeType']) ? filter_var($parameters['mimeType'],
            FILTER_SANITIZE_STRING) : 'text/calendar';
        $fileName = !empty($parameters['mimeType']) ? filter_var($parameters['fileName'],
            FILTER_SANITIZE_STRING) : 'calendar.ics';
        $generated = self::generateString($parameters);
        $at = new Zend_Mime_Part($generated);
        $at->type = $type;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding = Zend_Mime::ENCODING_8BIT;
        $at->filename = $fileName;
        return $at;
    }

}