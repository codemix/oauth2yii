<?php
namespace OAuth2Yii\Provider;

use \OAuth2Yii\Storage;
use \OAuth2Yii\Component\ClientIdentity;
use \OAuth2Yii\Component\UserIdentity;
use \OAuth2Yii\Component\AccessToken;

use \Yii;
use \CComponent;

abstract class Provider extends CComponent
{
    /**
     * @var string unique name of this provider
     */
    public $name;

    /**
     * @var string client ID
     */
    public $clientId;

    /**
     * @var string client secret
     */
    public $clientSecret;

    /**
     * @var string|null name of a custom token storage class for access/refresh tokens or null for
     * built in session based storage. The storage class must implement OAuth2Yii\Interfaces\ClientStorage.
     */
    public $storageClass;

    /**
     * @var OAuth2Yii\Interfaces\TokenStorage
     */
    protected $_storage;

    /**
     * @return string the authorization URL of this provider
     */
    abstract public function getAuthorizationUrl();

    /**
     * @return string the token URL of this provider
     */
    abstract public function getTokenUrl();

    /**
     * Init this provider
     */
    public function init()
    {
    }

    /**
     * @return OAuth2Yii\Component\ClientIdentity a client identity to perform authentication
     */
    public function createClientIdentity()
    {
        return new ClientIdentity($this, $this->clientId, $this->clientSecret);
    }

    /**
     * @param string $username to authenticate
     * @param string $password
     * @return OAuth2Yii\Component\UserIdentity a user identity to perform authentication
     */
    public function createUserIdentity($username, $password)
    {
        return new UserIdentity($this, $username, $password);
    }

    /**
     * @param string|null $clientId a client id or null to load the current user's access token.
     * @return OAuth2Yii\Component|AccessToken|null a access token object or null if no valid token could be obtained.
     * If there is an expired token and a refresh code is available, it will try to refresh
     * the token. If that fails, again null is returned.
     */
    public function getAccessToken($clientId = null)
    {
        if($clientId===null) {
            $id = Yii::app()->user->id;
            $type = AccessToken::TYPE_USER;
        } else {
            $id = $clientId;
            $type = AccessToken::TYPE_CLIENT;
        }

        $token = $this->getStorage()->loadToken($id, $type, $this->name);

        if($token!==null && (!$token->getIsExpired() || $token->refresh($id, $this))) {
            return $token;
        }
    }

    /**
     * @var OAuth2Yii\Interfaces\ClientTokenStorage the client storage class for this provider
     */
    public function getStorage()
    {
        if($this->_storage===null) {
            if($this->storageClass===null) {
                $this->_storage = new Storage\SessionClientStorage();
            } else {
                $this->_storage = new $this->storageClass;
            }
        }
        return $this->_storage;
    }

    /**
     * This is a convenience method to send Guzzle requests to a remote OAuth2 authenticated API.
     * It will add the neccessary access token to the request and then send the request. If no
     * access token is available, it will return false.
     *
     * @param Guzzle\Http\Message\Request $request a Guzzle request object
     * @param string|null $clientId a client id or null to load the current user's access token.
     * @return Guzzle\Http\Message\Response|false a response object or false if no valid access token found
     */
    public function sendGuzzleRequest($request,$clientId = null)
    {
        $token = $this->getAccessToken($clientId);

        if($token===null) {
            YII_DEBUG && Yii::trace("Could not send Guzzle request: No token available",'oauth2.provider.guzzle');
            return false;
        }

        YII_DEBUG && Yii::trace("Sending Guzzle request to {$request->getUrl()} with access token '$token'",'oauth2.provider.guzzle');

        $request->addHeader('Authorization', 'Bearer '.$token->token);
        return $request->send();
    }
}
