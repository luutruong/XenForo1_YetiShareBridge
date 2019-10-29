<?php

class Truonglv_YetiShareBridge_XenForo_ControllerAdmin_Log extends XFCP_Truonglv_YetiShareBridge_XenForo_ControllerAdmin_Log
{
    public function actionYetiShareBridgeLogs()
    {
        /** @var Truonglv_YetiShareBridge_Model_Log $logModel */
        $logModel = $this->getModelFromCache('Truonglv_YetiShareBridge_Model_Log');

        $id = $this->_input->filterSingle('id', XenForo_Input::UINT);
        if ($id > 0) {
            $log = $logModel->getLogById($id, array(
                'join' => Truonglv_YetiShareBridge_Model_Log::FETCH_USER
            ));

            if (empty($log)) {
                return $this->responseError(new XenForo_Phrase('requested_page_not_found'));
            }

            return $this->responseView(
                'Truonglv_YetiShareBridge_ViewAdmin_Tools_LogView',
                'yetishare_bridge_log_view',
                array(
                    'entry' => $logModel->prepareLog($log)
                )
            );
        }

        $page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
        $perPage = 20;

        $logs = $logModel->getAllLog(array(), array(
            'page' => $page,
            'perPage' => $perPage,
            'order' => 'log_date',
            'direction' => 'desc',
            'join' => Truonglv_YetiShareBridge_Model_Log::FETCH_USER
        ));

        foreach ($logs as &$log) {
            if (empty($log['username'])) {
                $log['username'] = new XenForo_Phrase('guest');
            }
        }

        $total = $logModel->countAllLog();

        $viewParams = array(
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'entries' => $logs
        );

        return $this->responseView(
            'Truonglv_YetiShareBridge_ViewAdmin_Tools_Log',
            'yetishare_bridge_log_list',
            $viewParams
        );
    }
}
