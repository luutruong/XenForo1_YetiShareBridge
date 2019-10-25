<?php

class Truonglv_YetiShareBridge_Helper_YetiShare
{
    /**
     * @var array
     */
    protected static $_packages = null;

    public static function upgradeUser(array $user)
    {
        self::_ensureAccessTokenLoaded();
    }

    public static function downgradeUser(array $user)
    {
        self::_ensureAccessTokenLoaded();
    }

    public static function createUser(array $user, $password)
    {
        self::_ensureAccessTokenLoaded();

        $payload = array(
            'username' => $user['username'],
            'password' => $password,
            'email' => $user['email'],
            'package_id' => 0,
            'status' => $user['user_state'] === 'valid' ? 'active' : 'pending',
            'title' => $user['gender'] === 'male' ? 'Mr' : 'Ms',
            'firstname' => $user['username'],
            'lastname' => $user['username']
        );

        $response = self::_request('POST', 'account/create', $payload);
    }

    public static function getPackageListing()
    {
        if (self::$_packages === null) {
            self::_ensureAccessTokenLoaded();

            $packages = (array) self::_request('GET', 'package/listing');
            self::$_packages = $packages;
        }

        return self::$_packages;
    }

    protected static function _ensureAccessTokenLoaded()
    {
        $accessToken = Truonglv_YetiShareBridge_Option::get('accessToken');
        if (empty($accessToken)) {
            $token = self::_request('POST', 'authorize', [
                'key1' => Truonglv_YetiShareBridge_Option::get('apiKey1'),
                'key2' => Truonglv_YetiShareBridge_Option::get('apiKey2')
            ]);

            if (!$token || (isset($token['status']) && $token['status'] === 'error')) {
                throw new XenForo_Exception('Fetch access token with error: '
                    . $token['response']);
            }

            $optionDw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
            $optionDw->setExistingData('YetishareBridge_accessToken');
            $optionDw->set('option_value', $token);
            $optionDw->save();

            $options = XenForo_Application::getOptions();
            $options->set('YetishareBridge_accessToken', $token);
        }
    }

    /**
     * @param string $method
     * @param string $endPoint
     * @param array $payload
     * @return mixed|null
     * @throws XenForo_Exception
     */
    protected static function _request($method, $endPoint, array $payload = [])
    {
        $url = rtrim(Truonglv_YetiShareBridge_Option::get('apiUrl')) . '/' . $endPoint;
        $method = strtoupper($method);

        if ($endPoint !== 'authorize' && !isset($payload['access_token'])) {
            $accessToken = Truonglv_YetiShareBridge_Option::get('accessToken');
            $payload['access_token'] = $accessToken['data']['access_token'];
        }

        $client = XenForo_Helper_Http::getClient($url);
        if ($method === 'GET') {
            $client->setParameterGet($payload);
        } elseif ($method === 'POST') {
            $client->setParameterPost($payload);
        } else {
            throw new \XenForo_Exception('Unknown request http method: ' . $method);
        }

        $start = microtime(true);

        try {
            $response = $client->request($method);

            $body = $response->getBody();

            $json = json_decode($body, true);
            if (!is_array($json)) {
                $json = array();
            }
            $json['_request_timing'] = number_format((microtime(true) - $start), 4);

            return $json;
        } catch (Zend_Http_Exception $e) {
            self::log($e);
        }

        return null;
    }

    public static function log($message)
    {
        if ($message instanceof \Exception) {
            XenForo_Error::logException($message, false, '[tl] YetiShare Bridge: ');
        } else {
            self::log(new \Exception($message));
        }
    }
}
