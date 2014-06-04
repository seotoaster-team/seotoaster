<?php

/**
 * User
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_User extends Zend_Form {

	protected $_email    = '';

	protected $_fullName = '';

	protected $_password = '';

	protected $_roleId   = '';

	protected $_id       = '';

	public function init() {

        $email = new Zend_Form_Element_Text(array(
            'id'         => 'e-mail',
            'name'       => 'email',
            'label'      => 'E-mail',
            'value'      => $this->_email,
            'validators' => array(
                new Zend_Validate_EmailAddress(),
                new Zend_Validate_Db_NoRecordExists(array(
                    'table' => 'user',
                    'field' => 'email'
                ))
            ),
            'required'   => true,
            'filters'    => array('StringTrim')
        ));

        $this->addElement($email);

		$this->addElement(new Zend_Form_Element_Text(array(
			'name'       => 'fullName',
			'id'         => 'full-name',
			'label'      => 'Full name',
			'required'   => true,
			'validators' => array(
				new Zend_Validate_Alnum(array('allowWhiteSpace' => true)),
			),
			'value'      => $this->_fullName
		)));

		$this->addElement(new Zend_Form_Element_Password(array(
			'name'       => 'password',
			'id'         => 'password',
			'label'      => 'Password',
			'required'   => true,
			'validators' => array(
				new Zend_Validate_StringLength(array(
					'encoding' => 'UTF-8',
					'min'      => 4
				)),
			),
			'value'      => $this->_password
		)));

		$acl = Zend_Registry::get('acl');
		$roles = array_filter($acl->getRoles(), function($role){
			return (($role !== Tools_Security_Acl::ROLE_SUPERADMIN) && $role !== Tools_Security_Acl::ROLE_GUEST);
		});

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'         => 'roleId',
			'id'           => 'role-id',
			'label'        => 'Role',
			'value'        => $this->_roleId,
			'multiOptions' => array_combine($roles, array_map('ucfirst', $roles)),
			'required'     => true
		)));

        $this->addElement(new Zend_Form_Element_Text(array(
            'name'  => 'gplusProfile',
            'id'    => 'gplus-profile',
            'label' => 'Google+ profile'
        )));

        $this->addElement(new Zend_Form_Element_Select(array(
            'name'         => 'roleId',
            'id'           => 'role-id',
            'label'        => 'Role',
            'value'        => $this->_roleId,
            'multiOptions' => array_combine($roles, array_map('ucfirst', $roles)),
            'required'     => true
        )));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'user-id',
			'name'  => 'id',
			'value' => $this->_id
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'   => 'saveUser',
			'id'     => 'save-user',
			'value'  => 'Save user',
			'class'  => 'btn',
			'ignore' => true,
			'label'  => 'Save user',
			'escape' => false
		)));

		$this->setElementDecorators(array('ViewHelper', 'Label'));
		$this->getElement('saveUser')->removeDecorator('Label');
	}

	public function getEmail() {
		return $this->_email;
	}

	public function setEmail($email) {
		$this->_email = $email;
		$this->getElement('email')->setValue($email);
		return $this;
	}

	public function getFullName() {
		return $this->_fullName;
	}

	public function setFullName($fullName) {
		$this->_fullName = $fullName;
		$this->getElement('fullName')->setValue($fullName);
		return $this;
	}

	public function getPassword() {
		return $this->_password;
	}

	public function setPassword($password) {
		$this->_password = $password;
		$this->getElement('password')->setValue($password);
		return $this;
	}

	public function getRoleId() {
		return $this->_roleId;
	}

	public function setRoleId($roleId) {
		$this->_roleId = $roleId;
		$this->getElement('roleId')->setValue($roleId);
		return $this;
	}

	public function getId() {
		return $this->_id;
	}

	public function setId($id) {
		$this->_id = $id;
		$this->getElement('id')->setValue($id);
        $this->getElement('email')->removeValidator('Zend_Validate_Db_NoRecordExists');
		return $this;
	}
}

