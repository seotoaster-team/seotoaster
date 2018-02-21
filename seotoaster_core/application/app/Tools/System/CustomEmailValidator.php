<?php

/**
 * Custom class for zend email forms validation
 *
 * Class Tools_System_CustomEmailValidator
 */
class Tools_System_CustomEmailValidator extends Zend_Validate_EmailAddress
{

    protected $_messageTemplates = array(
        self::INVALID => "Invalid type given. String expected",
        self::INVALID_FORMAT => "'%value%' is not a valid email address",
        self::INVALID_HOSTNAME => "'%hostname%' is not a valid hostname for email address '%value%'",
        self::INVALID_MX_RECORD => "'%hostname%' does not appear to have a valid MX record for the email address '%value%'",
        self::INVALID_SEGMENT => "'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network",
        self::DOT_ATOM => "'%localPart%' can not be matched against dot-atom format",
        self::QUOTED_STRING => "'%localPart%' can not be matched against quoted-string format",
        self::INVALID_LOCAL_PART => "'%localPart%' is not a valid local part for email address '%value%'",
        self::LENGTH_EXCEEDED => "'%value%' exceeds the allowed length",
    );

    /**
     * Verify email address
     *
     * @param string $value email address
     * @return bool
     */
    public function isValid($value)
    {
        $isValid = Tools_System_Tools::isEmailValid($value);

        $this->_setValue($value);

        if ($isValid === false) {
            $this->_error(self::INVALID_FORMAT);

            return false;
        }

        return true;

    }

}

