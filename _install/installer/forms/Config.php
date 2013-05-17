<?php

/**
 * Config
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Installer_Form_Config extends Zend_Form {

    public function init(){
        $translator = $this->getTranslator();

        $this->setName(strtolower(__CLASS__))
            ->setAction('')
            ->setAttrib('class', 'ui-helper-clearfix')
            ->setMethod(Zend_Form::METHOD_POST)
            ->setDecorators(array(
            'FormElements',
            'Form'
        ))
            ->setElementDecorators(array(
            'ViewHelper',
            'Label',
            new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('mt10px') ))
        ));

        $this->addElement('text', 'corepath', array(
            'value'		    => $this->_corepath,
            'label'		    => 'Path to core',
            'placeholder'	=> $translator->translate('input toaster core location'),
	        'validators'    => array(
		        array('Callback', true, array(
		            'callback' => array($this, 'validateCorePath'),
			        'messages' => array(
				        Zend_Validate_Callback::INVALID_VALUE => 'Toaster core is not found in given location',
			        )
		        ))
	        )
        ));

        $this->addElement('text', 'sitename', array(
            'value'		=> $this->_sitename,
            'label'		=> 'Site name',
            'placeholder' => $translator->translate('you can leave this field empty'),
            'title'		=> ($translator ? $translator->translate('Give a name for your site') : 'Give a name for your site'),
            'validators'=> array(
                array('Hostname', true, array(
                    'allow' => Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_IP | Zend_Validate_Hostname::ALLOW_LOCAL,
                    'idn'   => false,
                    'tld'   => false
                ))
            )
        ));

        $this->addElement('text', 'host', array(
            'value'		=> 'localhost',
            'label'		=> 'Host',
	        'required'  => true,
            'placeholder'		=> $translator->translate('Database server address'),
            'validators'=> array(
                new Zend_Validate_Hostname(array(
                    'allow' => Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_IP | Zend_Validate_Hostname::ALLOW_LOCAL,
                    'idn'   => false,
                    'tld'   => false
                ))
            )
        ));

        $this->addElement('text', 'username', array(
            'label'		=> 'User',
            'required'  => true,
            'placeholder' => $translator->translate('User allowed to connect to database server')
        ));

        $this->addElement('password', 'password', array(
            'label'		=> 'Password',
            'placeholder'     => $translator->translate('Password for database'),
            'renderPassword' => true
        ));

        $this->addElement('text', 'dbname', array(
            'label'		=> 'Database name',
            'required'  => true,
            'placeholder'     => $translator->translate('Name of the database to use')
        ));

        $this->addDisplayGroup(array('host', 'username', 'password', 'dbname'), 'dbinfo');
        $this->addDisplayGroup(array('corepath', 'sitename'), 'coreinfo');
        $this->setDisplayGroupDecorators(array(
            'FormElements',
            'Fieldset'
        ));

        $this->addElement('hidden', 'check', array(
            'value'	=> 'config',
            'ignore'=> true
        ));

        $this->addElement('submit', 'submit', array(
            'label'		=> 'Go ahead!',
	        'ignore'    => true,
            'decorators'=> array(
                'ViewHelper'
            )
        ));

        $this->setElementFilters(array(new Zend_Filter_StringTrim(), new Zend_Filter_StripTags()));
    }

	public function isValid($data){
		$valid = parent::isValid($data);

		foreach ($this->getElements() as $element) {
			if ($element->hasErrors()){
				$currentClass = $element->getAttrib('class');
				if (!empty($currentClass)){
					$element->setAttrib('class', $currentClass.' error');
				} else {
					$element->setAttrib('class', 'error');
				}
			}
		}

		return $valid;
	}

	public function validateCorePath($corepath){
		$corepath = realpath($corepath);

        if ( !$corepath || !is_dir($corepath)
            || !is_dir($corepath.'/application')
            || !is_dir($corepath.'/library') ) {
	        return false;
        }
		$element = $this->getElement('corepath');

		$requirements = Zend_Registry::get('requirements');
		if (is_dir(realpath($configsDir = $corepath.DIRECTORY_SEPARATOR.$requirements['corePermissions']['configdir']))) {
			if (!is_writable($configsDir)) {
				$element->addError('Config directory must be writable: '.$configsDir);
			}
		} else {
			$element->addError('Config directory doesn\'t exists: '.$corepath.DIRECTORY_SEPARATOR.$requirements['corePermissions']['configdir']);
		}

		$element->setValue($corepath);
        return true;
	}

}