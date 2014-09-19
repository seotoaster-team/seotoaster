<?php

/**
 * Form mapper
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Mappers_FormMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_Form';

    protected $_model = 'Application_Model_Models_Form';

    public function save($form)
    {
        if (!$form instanceof Application_Model_Models_Form) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Form instance');
        }
        $data = array(
            'name' => $form->getName(),
            'code' => $form->getCode(),
            'contact_email' => $form->getContactEmail(),
            'message_success' => $form->getMessageSuccess(),
            'message_error' => $form->getMessageError(),
            'reply_from' => $form->getReplyFrom(),
            'reply_from_name' => $form->getReplyFromName(),
            'reply_subject' => $form->getReplySubject(),
            'reply_mail_template' => $form->getReplyMailTemplate(),
            'reply_text' => $form->getReplyText(),
            'captcha' => $form->getCaptcha(),
            'mobile' => $form->getMobile(),
            'enable_sms' => $form->getEnableSms()
        );

        if (!($id = $form->getId())) {
            unset($data['id']);
            return $this->getDbTable()->insert($data);
        } else {
            return $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function findByName($name)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto("name = ?", $name);
        return $this->_findWhere($where);
    }

    public function delete(Application_Model_Models_Form $form)
    {
        $where = array('name = ?' => $form->getName());
        $result = $this->getDbTable()->delete($where);
        $form->notifyObservers();
        return $result;
    }

}