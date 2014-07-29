<?php

class LoginControllerTest extends ControllerTestCase
{

    protected $_userMapper;
    protected $_userModel;

    public function setUp()
    {
        parent::setUp();
        $this->_userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $this->_userModel = new Application_Model_Models_User();
    }

    public function testLoginIndexAction()
    {
        $this->dispatch('/go');
        $this->assertController('login');
        $this->assertAction('index');
        $this->assertXpath("//a[@id='forgot-password']", 'retrieve button not exists');
        $this->resetResponse()->resetRequest();
    }

    public function testPasswordRetrieveAction()
    {
        $this->dispatch('/login/retrieve/');
        $this->assertController('login');
        $this->assertAction('passwordretrieve');
        $this->assertXpath("//input[@id='retrieve']", 'retrieve button not exists');
        $this->resetResponse()->resetRequest();
        $email = 'test@seosamba.com';
        $this->_userModel->setEmail($email)
            ->setRoleId('admin')
            ->setPassword('testtest')
            ->setFullName('Test User')
            ->setLastLogin(date(DATE_ATOM))
            ->setIpaddress('127.0.0.1')
            ->setGplusProfile('');
        $userId = $this->_userMapper->save($this->_userModel);
        $this->assertGreaterThan(0, $userId);
        $this->request->setMethod('POST')->setPost(
            array(
                'email' => $email
            )
        );
        $this->dispatch('/login/retrieve/');
        $this->assertController('login');
        $this->assertAction('passwordretrieve');
        $this->assertRedirectTo('/login/retrieve', 'email ' . $email . ' does\'t exist');
        //remove user after creation
        $this->_userModel->setId($userId);
        $this->assertGreaterThan(0, $this->_userMapper->delete($this->_userModel), "Can't delete user with 0 id");
    }

} 