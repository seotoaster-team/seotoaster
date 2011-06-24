<?php
/**
 * MimeType
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Validators_MimeType extends Zend_Validate_File_MimeType {

	public function isValid($value, $file = null) {
		 if ($file === null) {
            $file = array(
                'type' => null,
                'name' => $value
            );
        }

        // Is file readable ?
        require_once 'Zend/Loader.php';
        if (!Zend_Loader::isReadable($value)) {
            return $this->_throw($file, self::NOT_READABLE);
        }

        $mimefile = $this->_magicfile;
		if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!empty($mimefile) && empty($this->_finfo)) {
                $this->_finfo = @finfo_open($const, $mimefile);
            }

            if (empty($this->_finfo)) {
                $this->_finfo = @finfo_open($const);
            }

            $this->_type = null;
            if (!empty($this->_finfo)) {
                $this->_type = finfo_file($this->_finfo, $value);
            }
        }

        if (empty($this->_type) &&
            (function_exists('mime_content_type') && ini_get('mime_magic.magicfile'))) {
                $this->_type = mime_content_type($value);
        }

        if (empty($this->_type) && $this->_headerCheck) {
            $this->_type = $file['type'];
        }

        if (empty($this->_type)) {
            return $this->_throw($file, self::NOT_DETECTED);
        }

        $mimetype = $this->getMimeType(true);
        if (in_array($this->_type, $mimetype)) {
            return true;
        }

        $types = explode('/', $this->_type);
        $types = array_merge($types, explode('-', $this->_type));
        $types = array_merge($types, explode(';', $this->_type));
        foreach($mimetype as $mime) {
            if (in_array($mime, $types)) {
                return true;
            }
        }

        return $this->_throw($file, self::FALSE_TYPE);
	}

}