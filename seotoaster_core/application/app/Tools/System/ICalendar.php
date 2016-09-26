<?php

class Tools_System_ICalendar {

    /**
     * Get the start time set for the even
     * @return string
     */
    public static function formatDate($date) {
        $date = new DateTime($date);
        return $date->format("Ymd\THis\Z");
    }

    public static function formatValue($str) {
        return addcslashes($str, ",\\;");
    }

    public static function generateString($parameters) {
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
        return "BEGIN:VCALENDAR\r\n"
            . "VERSION:2.0\r\n"
            . "PRODID:-//" . $parameters['author ']. "//NONSGML//EN\r\n"
            . "X-WR-CALNAME:" . $parameters['title ']. "\r\n"
            . "CALSCALE:GREGORIAN\r\n"
            . "BEGIN:VEVENT\r\n"
            . "UID:{$parameters['uid']}\r\n"
            . "DTSTART:{" . self::formatDate($parameters['start']) . "}\r\n"
            . "DTEND:{" . self::formatDate($parameters['end']) . "}\r\n"
            . "DTSTAMP:{" . self::formatDate($parameters['start']) . "}\r\n"
            . "CREATED:{" . self::formatDate(date('Y/m/d H:i:s')) . "}\r\n"
            . "DESCRIPTION:{" . self::formatValue($parameters['description']) . "}\r\n"
            . "LAST-MODIFIED:{" . self::formatDate($parameters['start']) . "}\r\n"
            . "LOCATION:{$parameters['location']}\r\n"
            . "SUMMARY:{" . self::formatValue($parameters['summary']) . "}\r\n"
            . "SEQUENCE:0\r\n"
            . "STATUS:CONFIRMED\r\n"
            . "TRANSP:OPAQUE\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR";
    }

    /**
     *
     * Call this function to download the invite.
     */
    public static function generateDownloadInvite()
    {
        $generated = self::generateString();
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); //date in the past
        header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); //tell it we just updated
        header('Cache-Control: no-store, no-cache, must-revalidate' ); //force revaidation
        header('Cache-Control: post-check=0, pre-check=0', false );
        header('Pragma: no-cache' );
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="calendar.ics"');
        header("Content-Description: File Transfer");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . strlen($generated));
        print $generated;
        exit;
    }

    /**
     *
     * Call this function to email the invite.
     */
    public static function generateEmailInviteAttachment($parameters)
    {
        $type = !empty($parameters['mimeType']) ? $parameters['mimeType'] : 'text/calendar';
        $fileName = !empty($parameters['mimeType']) ? $parameters['fileName'] : 'calendar.ics';
        $generated = self::generateString($parameters);
        $at              = new Zend_Mime_Part($generated);
        $at->type        = $type;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding    = Zend_Mime::ENCODING_8BIT;
        $at->filename    = $fileName;
        return $at;
    }

}