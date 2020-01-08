<?php

class Truonglv_YetiShareBridge_XenForo_ControllerPublic_Register extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerPublic_Register
{
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
        $writer->YetiShare_setUserPassword('');

        return $writer;
    }
}
