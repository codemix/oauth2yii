<?php

namespace OAuth2Yii\Action;

use \OAuth2Yii\Component\ServerComponent;

use \OAuth2\GrantType;
use \OAuth2\Request;

use \Yii;
use \CAction;
use \CException;
use \CWebLogRoute;
use \CProfileLogRoute;

class Token extends CAction
{
    /**
     * @var string name of the OAuth2Yii application component. Default is 'oauth2'
     */
    public $oauth2Component = 'oauth2';

    /**
     * Runs the action.
     *
     * 
     * @throws \CException if oauth is improperly configured.
     */
    public function run()
    {
        if(!Yii::app()->hasComponent($this->oauth2Component)) {
            throw new CException("Could not find OAuth2Yii/Server component '{$this->oauth2Component}'");
        }

        $oauth2     = Yii::app()->getComponent($this->oauth2Component); /* @var \OAuth2Yii\Component\ServerComponent $oauth2 */
        $server     = $oauth2->getServer();

        if(!$oauth2->getCanGrant()) {
            throw new CException("No grant types enabled");
        }

        if($oauth2->enableAuthorization) {
            $authorizationStorage = $oauth2->getStorage(ServerComponent::STORAGE_AUTHORIZATION_CODE);
            $server->addGrantType(new GrantType\AuthorizationCode($authorizationStorage));
        }

        if($oauth2->enableClientCredentials) {
            $clientStorage = $oauth2->getStorage(ServerComponent::STORAGE_CLIENT_CREDENTIALS);
            $server->addGrantType(new GrantType\ClientCredentials($clientStorage));
        }

        if($oauth2->enableUserCredentials) {
            $userStorage = $oauth2->getStorage(ServerComponent::STORAGE_USER_CREDENTIALS);
            $server->addGrantType(new GrantType\UserCredentials($userStorage));
            $refreshStorage = $oauth2->getStorage(ServerComponent::STORAGE_REFRESH_TOKEN);
            $server->addGrantType(new GrantType\RefreshToken($refreshStorage));
        }

        // Disable any potential output from Yii logroutes
        foreach(Yii::app()->log->routes as $r) {
            if($r instanceof \CWebLogRoute || $r instanceof CProfileLogRoute) {
                $r->enabled=false;
            }
        }
        YII_DEBUG && Yii::trace('Handling access token/authorization code request','oauth2.token');
        $request = Request::createFromGlobals();
        $server->handleTokenRequest($request)->send();
    }
}
