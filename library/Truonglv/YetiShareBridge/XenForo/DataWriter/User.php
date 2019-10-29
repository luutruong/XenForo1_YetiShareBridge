<?php

class Truonglv_YetiShareBridge_XenForo_DataWriter_User extends XFCP_Truonglv_YetiShareBridge_XenForo_DataWriter_User
{
    /**
     * @var null|string
     */
    private $_YetiShare_userPassword = null;

    public function setPassword(
        $password,
        $passwordConfirm = false,
        XenForo_Authentication_Abstract $auth = null,
        $requirePassword = false
    ) {
        $success = parent::setPassword($password, $passwordConfirm, $auth, $requirePassword);
        if ($success && $password !== '') {
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

        if ($this->isUpdate()) {
            $changes = array();
            if ($this->isChanged('email')) {
                $changes['email'] = $this->get('email');
            }
            if ($this->isChanged('user_state')) {
                $changes['user_state'] = $this->get('user_state');
            }
            if ($this->_YetiShare_userPassword !== null) {
                $changes['password'] = $this->_YetiShare_userPassword;
            }

            if (count($changes) > 0) {
                Truonglv_YetiShareBridge_Helper_YetiShare::updateUser($userData, $changes);
            }
        }

        $this->_YetiShare_userPassword = null;
    }
}
