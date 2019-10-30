<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Account extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Account
{
    public function actionUpgradePurchaseCredit()
    {
        $response = parent::actionUpgradePurchaseCredit();

        if ($response instanceof XenForo_ControllerResponse_Redirect
            && $this->isConfirmedPost()
        ) {
            // upgrade success
            $ourRedirect = Truonglv_YetiShareBridge_Helper_YetiShare::getSSOUrl(
                XenForo_Visitor::getUserId(),
                'login',
                $this->_buildLink('canonical:account/upgrades'),
                $this->_request->getClientIp()
            );
            if ($ourRedirect !== null) {
                return $this->responseRedirect(
                    XenForo_ControllerResponse_Redirect::SUCCESS,
                    $ourRedirect
                );
            }
        }

        return $response;
    }
}
