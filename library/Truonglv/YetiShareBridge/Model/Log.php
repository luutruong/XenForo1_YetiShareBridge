<?php

class Truonglv_YetiShareBridge_Model_Log extends XenForo_Model
{
    const FETCH_USER = 2;

    public function log(
        $method,
        $endPoint,
        array $requestData,
        array $responseData,
        $responseCode,
        $logDate = 0,
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        $db = $this->_getDb();
        $db->insert('xf_truonglv_yetisharebridge_log', array(
            'user_id' => $viewingUser['user_id'],
            'method' => strtoupper($method),
            'end_point' => $endPoint,
            'request_data' => json_encode($requestData),
            'response_data' => json_encode($responseData),
            'response_code' => $responseCode,
            'log_date' => $logDate ?: XenForo_Application::$time
        ));

        return $db->lastInsertId('xf_truonglv_yetisharebridge_log');
    }

    public function prepareLog(array $log)
    {
        $requestData = json_decode($log['request_data'], true);
        if (isset($requestData['password'])) {
            $requestData['password'] = '******';
        }
        if (isset($requestData['access_token'])) {
            $requestData['access_token'] = '******';
        }
        $log['requestData'] = $requestData;

        $responseData = json_decode($log['response_data'], true);
        if (isset($responseData['data'], $responseData['data']['access_token'])) {
            $responseData['data']['access_token'] = '******';
        }
        $log['responseData'] = $responseData;

        return $log;
    }

    /* Start auto-generated lines of code. Change made will be overwriten... */

    public function getList(array $conditions = array(), array $fetchOptions = array())
    {
        $allLog = $this->getAllLog($conditions, $fetchOptions);
        $list = array();

        foreach ($allLog as $id => $log) {
            $list[$id] = $log['method'];
        }

        return $list;
    }

    public function getLogById($id, array $fetchOptions = array())
    {
        $allLog = $this->getAllLog(array('log_id' => $id), $fetchOptions);

        return reset($allLog);
    }

    public function getLogIdsInRange($start, $limit)
    {
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
            SELECT log_id
            FROM xf_truonglv_yetisharebridge_log
            WHERE log_id > ?
            ORDER BY log_id
        ', $limit), $start);
    }

    public function getAllLog(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareLogConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareLogOrderOptions($fetchOptions);
        $joinOptions = $this->prepareLogFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $allLog = $this->fetchAllKeyed($this->limitQueryResults("
            SELECT log.*
                $joinOptions[selectFields]
            FROM `xf_truonglv_yetisharebridge_log` AS log
                $joinOptions[joinTables]
            WHERE $whereConditions
                $orderClause
            ", $limitOptions['limit'], $limitOptions['offset']), 'log_id');

        $this->_getAllLogCustomized($allLog, $fetchOptions);

        return $allLog;
    }

    public function countAllLog(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareLogConditions($conditions, $fetchOptions);

        $orderClause = $this->prepareLogOrderOptions($fetchOptions);
        $joinOptions = $this->prepareLogFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne("
            SELECT COUNT(*)
            FROM `xf_truonglv_yetisharebridge_log` AS log
                $joinOptions[joinTables]
            WHERE $whereConditions
        ");
    }

    public function prepareLogConditions(array $conditions = array(), array $fetchOptions = array())
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if (isset($conditions['log_id'])) {
            if (is_array($conditions['log_id'])) {
                if (!empty($conditions['log_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.log_id IN (" . $db->quote($conditions['log_id']) . ")";
                }
            } else {
                $sqlConditions[] = "log.log_id = " . $db->quote($conditions['log_id']);
            }
        }

        if (isset($conditions['user_id'])) {
            if (is_array($conditions['user_id'])) {
                if (!empty($conditions['user_id'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.user_id IN (" . $db->quote($conditions['user_id']) . ")";
                }
            } else {
                $sqlConditions[] = "log.user_id = " . $db->quote($conditions['user_id']);
            }
        }

        if (isset($conditions['method'])) {
            if (is_array($conditions['method'])) {
                if (!empty($conditions['method'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.method IN (" . $db->quote($conditions['method']) . ")";
                }
            } else {
                $sqlConditions[] = "log.method = " . $db->quote($conditions['method']);
            }
        }

        if (isset($conditions['end_point'])) {
            if (is_array($conditions['end_point'])) {
                if (!empty($conditions['end_point'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.end_point IN (" . $db->quote($conditions['end_point']) . ")";
                }
            } else {
                $sqlConditions[] = "log.end_point = " . $db->quote($conditions['end_point']);
            }
        }

        if (isset($conditions['response_code'])) {
            if (is_array($conditions['response_code'])) {
                if (!empty($conditions['response_code'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.response_code IN (" . $db->quote($conditions['response_code']) . ")";
                }
            } else {
                $sqlConditions[] = "log.response_code = " . $db->quote($conditions['response_code']);
            }
        }

        if (isset($conditions['log_date'])) {
            if (is_array($conditions['log_date'])) {
                if (!empty($conditions['log_date'])) {
                    // only use IN condition if the array is not empty (nasty!)
                    $sqlConditions[] = "log.log_date IN (" . $db->quote($conditions['log_date']) . ")";
                }
            } else {
                $sqlConditions[] = "log.log_date = " . $db->quote($conditions['log_date']);
            }
        }

        $this->_prepareLogConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

        return $this->getConditionsForClause($sqlConditions);
    }

    public function prepareLogFetchOptions(array $fetchOptions = array())
    {
        $selectFields = '';
        $joinTables = '';

        $this->_prepareLogFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    public function prepareLogOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
    {
        $choices = array();

        $this->_prepareLogOrderOptionsCustomized($choices, $fetchOptions);

        return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    protected function _getAllLogCustomized(array &$data, array $fetchOptions)
    {
        // customized code goes here
    }

    protected function _prepareLogConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
    {
        // customized code goes here
    }

    protected function _prepareLogFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
    {
        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_USER) {
                $selectFields .= ',user.*';
                $joinTables .= 'LEFT JOIN xf_user AS user ON (user.user_id = log.user_id)';
            }
        }
    }

    protected function _prepareLogOrderOptionsCustomized(array &$choices, array &$fetchOptions)
    {
        $choices += array(
            'log_date' => 'log.log_date'
        );
    }
}
