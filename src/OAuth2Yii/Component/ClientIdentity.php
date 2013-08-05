<?php
namespace OAuth2Yii\Component;

use \Yii;

/**
 * ClientIdentity
 *
 * This represents the identity of a client on the OAuth2 server
 */
class ClientIdentity extends Identity
{
    /**
     * @return bool whether the client could be authenticated against the OAuth2 server
     */
    public function authenticate()
    {
        $provider   = $this->getProvider();
        $client     = new HttpClient;
        $url        = $provider->getTokenUrl();
        $data       = array('grant_type' => 'client_credentials');

        YII_DEBUG && Yii::trace("Requesting access token for client from $url", 'oauth2.accesstoken');
        $response   = $client->post($url, $data, array(), $this->username, $this->password);
        $token      = AccessToken::parseResponse($response, $provider, $this);

        if($token===null) {
            YII_DEBUG && Yii::trace('Access token request for client failed: '.$response, 'oauth2.accesstoken');
            return false;
        } else {
            YII_DEBUG && Yii::trace("Received access token '{$token->token}' for client", 'oauth2.accesstoken');
            $this->errorCode = self::ERROR_NONE;
            $token->type = AccessToken::TYPE_CLIENT;
            $provider->getStorage()->saveToken($token);
            return true;
        }
    }
}
