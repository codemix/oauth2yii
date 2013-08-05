<?php
namespace OAuth2Yii\Component;

use \Yii;
use \CComponent;

/**
 * AccessToken
 */
class AccessToken extends CComponent
{
    const TYPE_CLIENT   = 'client';
    const TYPE_USER     = 'user';

    /**
     * @var string type of token. One of TYPE_(CLIENT|USER).
     */
    public $type;

    /**
     * @var string name of provider of this token
     */
    public $provider;

    /**
     * @var string the access token
     */
    public $token;

    /**
     * @var int unix timestamp when this token expires
     */
    public $expires;

    /**
     * @var string the refresh token. Can be empty.
     */
    public $refreshToken;

    /**
     * @var string the space separated list of scopes that this token is valid for.
     */
    public $scope;

    /**
     * @return bool whether this token is expired
     */
    public function getIsExpired()
    {
        return time() > $this->expires;
    }

    /**
     * @param string $scope single scope or a space separated list of scopes.
     * @return bool whether this access token has all scopes
     */
    public function hasScope($scope)
    {
        if(!$this->scope) {
            return false;
        }

        $required   = explode(' ',trim($scope));
        $available  = explode(' ',$this->scope);

        return count(array_diff($required, $available))==0;
    }

    /**
     * Try to refresh this access token
     *
     * @param string $id of the client/user
     * @param OAuth2Yii\Provider\Provider
     * @return bool whether the token were successfully refreshed
     */
    public function refresh($id, $provider)
    {
        if($this->refreshToken) {
            $client     = new HttpClient;
            $url        = $provider->getTokenUrl();
            $storage    = $provider->getStorage();
            $data       = array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            );

            YII_DEBUG && Yii::trace("Refreshing {$this->type} access token '{$this->token}'", 'oauth2.accesstoken');
            $response   = $client->post($url, $data, array(), $provider->clientId, $provider->clientSecret);
            $token      = self::parseResponse($response);

            if($token===null) {
                YII_DEBUG && Yii::trace('Access token refresh failed', 'oauth2.accesstoken');
                $storage->deleteToken($id, $this->type, $provider->name);
                return false;
            } else {
                YII_DEBUG && Yii::trace('Access token refresh successful', 'oauth2.accesstoken');
                $storage->updateToken($id, $this->type, $provider->name);
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $response from a token request
     * @param OAuth2Yii\Provider\Provider
     * @param CUserIdentity|null if provided, any error will be set on this identity
     * @return null|OAuth2Yii\Component\AccessToken the access token object or null on failure
     */
    public static function parseResponse($response, $provider, $identity=null)
    {
        $data = json_decode($response, true);
        if(!isset($data['access_token'])) {
            if($identity!==null) {
                if(isset($data['error'])) { $identity->errorCode = $data['error'];
                    if(isset($data['error_description'])) {
                        $identity->errorMessage = $data['error_description'];
                    }
                } else {
                    $identity->errorCode    = 'unknown';
                    $identity->errorMessage = 'Invalid response from token URL';
                }
            }
            return null;
        }

        $config = array(
            'class'         => 'OAuth2Yii\Component\AccessToken',
            'provider'      => $provider->name,
            'token'         => $data['access_token'],
            'expires'       => time() + $data['expires_in'],
            'refreshToken'  => $data['refresh_token'],
            'scope'         => $data['scope'],
        );

        return Yii::createComponent($config);
    }
}
