<?php


class IndexControllerTest extends ControllerTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testIndexAction()
    {
        $this->dispatch('/');
        $this->assertController('index');
        $this->assertAction('index');
    }

} 