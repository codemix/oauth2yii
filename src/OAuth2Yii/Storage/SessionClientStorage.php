<?php
namespace OAuth2Yii\Storage;

use OAuth2Yii\Interfaces\ClientStorage;

use \Yii;

/**
 * SessionClientStorage
 *
 * This is a session based client storage for access tokens
 */
class SessionClientStorage implements ClientStorage
{
    /**
     * @param string $provider name of provider
     * @param string $type one of AccessToken::TYPE_(USER|CLIENT)
     * @return string the session key to use for the token
     */
    protected function getKey($provider, $type)
    {
        return '__accessToken_'.$type.'_'.$provider;
    }

    /**
     * @param string $username unique name of the user
     * @param \OAuth2Yii\Component\AccessToken $accessToken the token object to store
     */
    public function saveToken($username, $accessToken)
    {
        $key = $this->getKey($accessToken->provider, $accessToken->type);
        Yii::app()->session->add($key, $accessToken);
    }

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string $type type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param string $provider name of provider
     * @return null|\OAuth2Yii\Component\AccessToken the access token stored for this client/user or null if not found
     */
    public function loadToken($id,$type,$provider)
    {
        $key = $this->getKey($provider, $type);
        return Yii::app()->session->itemAt($key);
    }

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string $type type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param \OAuth2Yii\Component\AccessToken the new token object to store instead
     */
    public function updateToken($id, $type, $accessToken)
    {
        $key = $this->getKey($accessToken->provider, $type);
        Yii::app()->session->add($key, $accessToken);
    }

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string $type type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param string $provider name of provider
     */
    public function deleteToken($id, $type, $provider)
    {
        $key = $this->getKey($provider, $type);
        Yii::app()->session->remove($key);
    }
}
