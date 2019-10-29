<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Account extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Account
{
    public function actionYetiShareAssociate()
    {
        $this->_assertPostOnly();

        $input = $this->_input->filter(array(
            'api_key1' => XenForo_Input::STRING,
            'api_key2' => XenForo_Input::STRING
        ));

        if (empty($input['api_key1']) || empty($input['api_key2'])) {
            return $this->responseError(new XenForo_Phrase('yetishare_bridge_error_invalid_api_key'));
        }

        $accessToken = Truonglv_YetiShareBridge_Helper_YetiShare::fetchAccessToken($input['api_key1'], $input['api_key2']);
        if ($accessToken === null) {
            return $this->responseError(new XenForo_Phrase('yetishare_bridge_error_invalid_api_key'));
        }

        $accountInfo = Truonglv_YetiShareBridge_Helper_YetiShare::fetchAccountInfo(
            $accessToken['account_id'],
            $accessToken['access_token']
        );
        if ($accountInfo === null) {
            return $this->responseError(new XenForo_Phrase('yetishare_bridge_error_invalid_api_key'));
        }
        if ($accountInfo['status'] === 'disabled') {
            return $this->responseError(new XenForo_Phrase('yetishare_bridge_error_account_disabled'));
        }

        /** @var XenForo_Model_UserExternal $userExternalModel */
        $userExternalModel = $this->getModelFromCache('XenForo_Model_UserExternal');

        $existing = $userExternalModel->getExternalAuthAssociation(
            Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER,
            $accountInfo['account_id']
        );
        if ($existing && $existing['user_id'] != XenForo_Visitor::getUserId()) {
            return $this->responseError(new XenForo_Phrase('yetishare_bridge_error_account_integrated_with_other'));
        }

        $userExternalModel->updateExternalAuthAssociation(
            Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER,
            $accountInfo['id'],
            XenForo_Visitor::getUserId(),
            $accountInfo
        );

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            $this->_buildLink('account/external-accounts')
        );
    }

    public function actionExternalAccounts()
    {
        $response = parent::actionExternalAccounts();
        if ($response instanceof XenForo_ControllerResponse_View) {
            $params =& $response->subView->params;

            $external = $params['external'];
            if (isset($external[Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER])) {
                $YetiShare = $external[Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER];
                $YetiShare['extraData'] = XenForo_Helper_Php::safeUnserialize($YetiShare['extra_data']);

                $params['YetiShare'] = $YetiShare;
            }
        }

        return $response;
    }
}
