<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Register extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Register
{
    /**
     * @var int|null
     */
    protected $_associatedUserId = null;

    public function actionFacebook()
    {
        $response = parent::actionFacebook();

        /** @var Truonglv_YetiShareBridge_XenForo_Model_UserExternal $userExternalModel */
        $userExternalModel = $this->_getUserExternalModel();
        $this->_YetiShare_handleResponse($response, $userExternalModel->getYetiShareAssociateUserId());

        return $response;
    }

    public function actionFacebookRegister()
    {
        $response = parent::actionFacebookRegister();

        $this->_YetiShare_handleResponse($response, $this->_associatedUserId);

        return $response;
    }

    public function actionGoogle()
    {
        $response = parent::actionGoogle();

        /** @var Truonglv_YetiShareBridge_XenForo_Model_UserExternal $userExternalModel */
        $userExternalModel = $this->_getUserExternalModel();
        $this->_YetiShare_handleResponse($response, $userExternalModel->getYetiShareAssociateUserId());

        return $response;
    }

    public function actionGoogleRegister()
    {
        $response = parent::actionGoogleRegister();

        $this->_YetiShare_handleResponse($response, $this->_associatedUserId);

        return $response;
    }

    /**
     * @param XenForo_ControllerResponse_Abstract $response
     * @param int|null $userId
     * @throws XenForo_Exception
     */
    protected function _YetiShare_handleResponse(
        XenForo_ControllerResponse_Abstract &$response,
        $userId
    ) {
        if ($response instanceof XenForo_ControllerResponse_Redirect
            && $userId > 0
        ) {
            $ssoUrl = Truonglv_YetiShareBridge_Helper_YetiShare::getSSOUrl(
                $userId,
                'login',
                $response->redirectTarget,
                $this->_request->getClientIp()
            );
            if ($ssoUrl !== null) {
                $response = $this->responseRedirect(
                    XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
                    $ssoUrl
                );
            }
        }
    }

    /**
     * @param array $user
     * @param array $extraParams
     * @return XenForo_ControllerResponse_Redirect|XenForo_ControllerResponse_View
     * @throws XenForo_Exception
     */
    protected function _completeRegistration(array $user, array $extraParams = array())
    {
        $response = parent::_completeRegistration($user, $extraParams);

        if ($response instanceof XenForo_ControllerResponse_View) {
            $params =& $response->params;

            $redirect = isset($params['redirect']) ? $params['redirect'] : '';
            $ourRedirect = Truonglv_YetiShareBridge_Helper_YetiShare::getSSOUrl(
                $user['user_id'],
                'login',
                $redirect,
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

    /**
     * @param array $data
     * @return XenForo_DataWriter
     * @throws XenForo_ControllerResponse_Exception
     */
    protected function _setupExternalUser(array $data)
    {
        /** @var Truonglv_YetiShareBridge_XenForo_DataWriter_User $writer */
        $writer = parent::_setupExternalUser($data);
        $writer->YetiShare_setUserPassword(
            XenForo_Application::generateRandomString(10)
        );

        return $writer;
    }

    /**
     * @return false|int
     * @throws XenForo_ControllerResponse_Exception
     */
    protected function _associateExternalAccount()
    {
        $userId = parent::_associateExternalAccount();
        if ($userId > 0) {
            $this->_associatedUserId = $userId;
        }

        return $userId;
    }
}
