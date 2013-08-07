<?php
namespace OAuth2Yii\Component;

use \Yii;
use \CComponent;

class HttpClient extends CComponent
{
    /**
     * @param string $url to GET
     * @param array $headers for the GET request as list of strings
     * @param string|null optional username for HTTP Auth
     * @param string|null optional password for HTTP Auth
     * @return string the response body as string
     */
    public function get($url, $headers = array(), $username = null, $password = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if($username && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
        }

        if($headers!==array()) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        return curl_exec($ch);
    }

    /**
     * @param string $url to POST to
     * @param array $data the optional data to POST
     * @param array $headers for the POST request as list of strings
     * @param string|null optional username for HTTP Auth
     * @param string|null optional password for HTTP Auth
     * @return string|bool the response body as string or `false` on error
     */
    public function post($url, $data = array(), $headers = array(), $username = null, $password = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        if($username && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
        }

        if($headers!==array()) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        return curl_exec($ch);
    }
}
