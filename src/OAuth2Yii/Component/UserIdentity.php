<?php
namespace OAuth2Yii\Component;

use \Yii;

/**
 * UserIdentity
 *
 * This represents the identity of a user on the OAuth2 server
 */
class UserIdentity extends Identity
{
    /**
     * @return bool whether the user could be authenticated against the OAuth2 server
     */
    public function authenticate()
    {
        $provider   = $this->getProvider();
        $client     = new HttpClient;
        $url        = $provider->getTokenUrl();
        $data   = array(
            'grant_type'    => 'password',
            'username'      => $this->username,
            'password'      => $this->password,
        );

        YII_DEBUG && Yii::trace("Requesting access token for user from $url", 'oauth2.accesstoken');
        $response   = $client->post($url, $data, array(), $provider->clientId, $provider->clientSecret);
        $token      = AccessToken::parseResponse($response, $provider, $this);

        if($token===null) {
            YII_DEBUG && Yii::trace('Access token request for user failed: '.$response, 'oauth2.accesstoken');
            return false;
        } else {
            YII_DEBUG && Yii::trace("Received access token '{$token->token}' for user", 'oauth2.accesstoken');
            $this->errorCode = self::ERROR_NONE;
            $token->type = AccessToken::TYPE_USER;
            $provider->getStorage()->saveToken($this->username,$token);
            return true;
        }
    }
}
