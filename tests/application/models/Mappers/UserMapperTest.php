<?php


class UserMapperTest extends ControllerTestCase
{

    protected $_userMapper;
    protected $_userModel;

    public function setUp()
    {
        parent::setUp();
        $this->_userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $this->_userModel = new Application_Model_Models_User();
    }

    public function testNewUserSaveAndDelete()
    {
        $this->_userModel->setEmail('jon@doe.com')
            ->setRoleId('admin')
            ->setPassword('testtest')
            ->setFullName('Jon Doe')
            ->setLastLogin(date(DATE_ATOM))
            ->setIpaddress('127.0.0.1')
            ->setGplusProfile('');
        $userId = $this->_userMapper->save($this->_userModel);
        $this->assertGreaterThan(0, $userId);

        //remove user after creation
        $this->_userModel->setId($userId);
        $this->assertGreaterThan(0, $this->_userMapper->delete($this->_userModel), "Can't delete user with 0 id");
    }

} 