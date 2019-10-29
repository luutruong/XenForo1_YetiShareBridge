<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Logout extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Logout
{
    public function actionIndex()
    {
        $beforeUserId = XenForo_Visitor::getUserId();

        $response = parent::actionIndex();

        $afterUserId = XenForo_Visitor::getUserId();
        if ($afterUserId === 0
            && $beforeUserId > 0
            && $response instanceof XenForo_ControllerResponse_Redirect
        ) {
            // it's perform log out
            $redirect = $this->getDynamicRedirect(false, false);
            $ourUrl = Truonglv_YetiShareBridge_Helper_YetiShare::getSSOUrl(
                $beforeUserId,
                'logout',
                $redirect,
                $this->_request->getClientIp()
            );
            if ($ourUrl !== null) {
                $response = $this->responseRedirect(
                    XenForo_ControllerResponse_Redirect::SUCCESS,
                    $ourUrl
                );
            }
        }

        return $response;
    }
}
