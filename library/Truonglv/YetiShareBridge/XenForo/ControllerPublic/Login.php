<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Login extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Login
{
    public function completeLogin($userId, $remember, $redirect, array $postData = array())
    {
        $ssoLoginUrl = Truonglv_YetiShareBridge_Helper_YetiShare::getSSOLoginUrl(
            $userId,
            $redirect,
            $this->_request->getClientIp()
        );
        if ($ssoLoginUrl !== null) {
            $redirect = $ssoLoginUrl;
        }

        return parent::completeLogin($userId, $remember, $redirect, $postData);
    }
}
