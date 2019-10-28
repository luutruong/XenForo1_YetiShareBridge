<?php

class Truonglv_YetiShareBridge_XenForo_DataWriter_User extends XFCP_Truonglv_YetiShareBridge_XenForo_DataWriter_User
{
    protected $_YetiShare_userPassword = null;

    public function setPassword(
        $password,
        $passwordConfirm = false,
        XenForo_Authentication_Abstract $auth = null,
        $requirePassword = false
    ) {
        $success = parent::setPassword($password, $passwordConfirm, $auth, $requirePassword);
        if ($success) {
            $this->_YetiShare_userPassword = $password;
        }

        return $success;
    }

    protected function _postSave()
    {
        parent::_postSave();

        $userData = $this->getMergedData();
        if ($this->isInsert() && $this->_YetiShare_userPassword !== null) {
            // create a new user
            Truonglv_YetiShareBridge_Helper_YetiShare::createUser($userData, $this->_YetiShare_userPassword);
        }
    }
}
