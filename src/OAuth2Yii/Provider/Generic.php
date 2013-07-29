<?php
namespace OAuth2Yii\Provider;

use \Yii;
use \CComponent;

class Generic extends Provider
{
    public $authorizationUrl;

    public $tokenUrl;

    /**
     * @return string the authorization URL of this provider
     */
    public function getAuthorizationUrl()
    {
        if(empty($this->authorizationUrl)) {
            throw new CException('No authorization URL configured');
        }
        return $this->authorizationUrl;
    }

    /**
     * @return string the token URL of this provider
     */
    public function getTokenUrl()
    {
        if(empty($this->tokenUrl)) {
            throw new CException('No token URL configured');
        }
        return $this->tokenUrl;
    }
}
