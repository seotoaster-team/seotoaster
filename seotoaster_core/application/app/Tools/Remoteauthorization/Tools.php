<?php

/**
 * Remote authorization tools
 */
class Tools_Remoteauthorization_Tools
{


    /**
     * Get remote authorization token
     * and save additional authorization params
     *
     * @param array $params params
     * @return array
     * @throws Exception
     * @throws Exceptions_SeotoasterException
     */
    public static function postRemoteAuthorizationToken($params)
    {
        $translator = Zend_Registry::get('Zend_Translate');
        if (!empty($params) && !empty($params['email']) && !empty($params['additionalParams'])) {
            $email = $params['email'];
            $userMapper = Application_Model_Mappers_UserMapper::getInstance();
            $userModel = $userMapper->findByEmail($email);
            if (!$userModel instanceof Application_Model_Models_User) {
                return array('done' => false, 'message' => $translator->translate('user doesn\'t exist'));
            }

            $allowRemoteAuthorization = $userModel->getAllowRemoteAuthorization();
//            if (empty($allowRemoteAuthorization)) {
//                return array('done' => false, 'message' => $translator->translate('remote authorization not allowed'));
//            }
            $hash = sha1($params['email'] . uniqid(microtime()));
            $userModel->setRemoteAuthorizationToken($hash);
            $userModel->setPassword('');
            $userModel->setRemoteAuthorizationInfo(json_encode($params['additionalParams']));
            $userMapper->save($userModel);

            return array(
                'done' => true,
                'message' => $translator->translate('authorization token has been generated'),
                'code' => $hash
            );
        }

        return array('done' => false, 'message' => $translator->translate('wrong email address'));

    }
}

