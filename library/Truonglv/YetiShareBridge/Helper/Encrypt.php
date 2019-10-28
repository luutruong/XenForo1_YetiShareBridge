<?php

class Truonglv_YetiShareBridge_Helper_Encrypt
{
    const METHOD = 'AES-256-CBC';

    public static function encrypt(array $payload, $key)
    {
        $key = md5($key, true);

        $ivLength = openssl_cipher_iv_length(self::METHOD);
        $iv = XenForo_Application::generateRandomString($ivLength);

        $value = openssl_encrypt(
            json_encode($payload),
            self::METHOD,
            $key,
            0,
            $iv
        );

        $mac = self::_hash($iv, $value, $key);
        $json = json_encode(array(
            'iv' => $iv,
            'mac' => $mac,
            'value' => $value
        ));

        return base64_encode($json);
    }

    public static function decrypt($encrypted, $key)
    {
        $key = md5($key, true);
        $payload = json_decode(base64_decode($encrypted), true);
        if (!isset($payload['iv']) || !isset($payload['value']) || !isset($payload['mac'])) {
            return false;
        }

        $hashed = self::_hash($payload['iv'], $payload['value'], $key);
        if (!hash_equals($hashed, $payload['mac'])) {
            return false;
        }

        return openssl_decrypt($payload['value'], self::METHOD, $key, 0, $payload['iv']);
    }

    protected static function _hash($iv, $value, $key)
    {
        return hash_hmac('sha256', $iv . $value, $key);
    }
}
