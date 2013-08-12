<?php
namespace OAuth2Yii\Provider;

use \OAuth2Yii\Storage;
use \OAuth2Yii\Component\ClientIdentity;
use \OAuth2Yii\Component\UserIdentity;
use \OAuth2Yii\Component\AccessToken;

use \Yii;
use \CComponent;
use \CException;

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
     * @var string name of a storage class for access/refresh tokens that implements
     * OAuth2Yii\Interfaces\ClientStorage. Default is `OAuth2Yii\Storage\SessionClientStorage`
     * which stores tokens in the user session. For grant type client_credentials the
     * `OAuth2Yii\Storage\GlobalStateClientStorage` may be more apropriate.
     */
    public $storageClass = 'OAuth2Yii\Storage\SessionClientStorage';

    /**
     * @var \OAuth2Yii\Interfaces\ClientStorage
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
     * @return \OAuth2Yii\Component\ClientIdentity a client identity to perform authentication
     */
    public function createClientIdentity()
    {
        return new ClientIdentity($this, $this->clientId, $this->clientSecret);
    }

    /**
     * @param string $username to authenticate
     * @param string $password
     * @return \OAuth2Yii\Component\UserIdentity a user identity to perform authentication
     */
    public function createUserIdentity($username, $password)
    {
        return new UserIdentity($this, $username, $password);
    }

    /**
     * @param string|null|boolean $id of the user or null to use the current user id (default). If `true`
     * the access token for grant type `client_credentials` is returned.
     * @return \OAuth2Yii\Component\AccessToken|null a access token object or null if no valid token could be obtained.
     * If there is an expired token and a refresh code is available, it will try to refresh
     * the token. If that fails, null is returned.
     */
    public function getAccessToken($id = null)
    {
        $type = $id===true ? AccessToken::TYPE_CLIENT : AccessToken::TYPE_USER;
        if($id===null) {
            $id = Yii::app()->user->id;
            if($id===null) {
                return null;
            }
        } elseif($id===true) {
            $id = $this->clientId;
        }

        $token = $this->getStorage()->loadToken($id, $type, $this->name);

        if($token!==null && (!$token->getIsExpired() || $token->refresh($id, $this))) {
            return $token;
        }
    }

    /**
     * @return \OAuth2Yii\Interfaces\ClientStorage the client storage class for this provider
     */
    public function getStorage()
    {
        if($this->_storage===null) {
            $this->_storage = Yii::createComponent(array('class' => $this->storageClass));
        }
        return $this->_storage;
    }

    /**
     * This is a convenience method to send Guzzle requests to a remote OAuth2 authenticated API.
     * It will add the neccessary access token to the request and then send the request. If no
     * access token is available, it will return false.
     *
     * @param string|null|boolean $id of the user or null to use the current user id (default). If `true`
     * the access token for grant type `client_credentials` is used.
     * @param bool whether the id is for a client. Default is `false`, which means, it's a user id.
     * @return \Guzzle\Http\Message\Response|bool a response object or false if no valid access token found
     */
    public function sendGuzzleRequest($request,$id = null)
    {
        $token = $this->getAccessToken($id);

        if($token===null) {
            YII_DEBUG && Yii::trace("Could not send Guzzle request: No token available",'oauth2.provider.guzzle');
            return false;
        }

        YII_DEBUG && Yii::trace("Sending Guzzle request to {$request->getUrl()} with access token '$token'",'oauth2.provider.guzzle');

        $request->addHeader('Authorization', 'Bearer '.$token->token);
        return $request->send();
    }
}
