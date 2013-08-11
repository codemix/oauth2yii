<?php
namespace OAuth2Yii\Component;

use \Yii;
use \CWebUser;
use \CException;
use \CHttpCookie;

/**
 * WebUser
 *
 * This class represents a user on the server that authenticated with a valid OAuth2 access token.
 * Optionally users can also login on the OAuth2 server directly, in which case it wraps the
 * access token handling transparently.
 */
class WebUser extends CWebUser
{
    /**
     * @var bool whether to allow local logins on the server. If users logged in locally, an access
     * token will be stored for them in the session and injected into all requests. This way you can
     * use the same OAuth2 access check mechanism even for locally logged in users.
     */
    public $allowLocalLogin = false;

    /**
     * @var string name of the oauth2 server component. Default is 'oauth2'.
     */
    public $oauth2 = 'oauth2';

    /**
     * @var string name of the oauth2 client component to use for login. Default is 'oauth2client'.
     */
    public $loginComponent = 'oauth2client';

    /**
     * @var string|null name of the oauth2 provider to use for login. Required for `$allowLocalLogin`.
     */
    public $loginProvider;

    /**
     * @var string name of the cookie that identifies users that logged in locally.
     */
    public $localLoginCookie = 'oauth2local';

    /**
     * @var CActiveRecord the user record of the currently logged in user
     */
    protected $_model = false;

    /**
     * @return int|null the user id if a valid access token was supplied or null otherwhise
     */
    public function getId()
    {
        $app    = Yii::app();
        $oauth2 = $app->getComponent($this->oauth2);

        if($oauth2===null) {
            throw new \CException("Invalid OAuth2Yii server component '{$this->oauth2}'");
        }

        if($this->allowLocalLogin && $app->request->cookies->itemAt($this->localLoginCookie)!==null) {
            $id = parent::getId();

            if($id!==null) {
                // The user logged in through this site. We need to inject the OAuth2 access token
                // into the request header, to make ourselves think, we have a valid API client.
                $oauth2Client = Yii::app()->getComponent($this->loginComponent);
                if($oauth2Client===null) {
                    throw new \CException("Invalid OAuth2Yii client component '{$this->oauth2Client}'");
                }
                if($this->loginProvider===null) {
                    throw new \CException('No OAuth2 login provider configured');
                }
                $accessToken = $oauth2Client->getProvider($this->loginProvider)->getAccessToken($id);
                if($accessToken===null) {
                    return null;
                }
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken->token;
            }
        }

        return $oauth2->getUserId();
    }

    /**
     * Create cookie that marks local users
     *
     * @param boolean $fromCookie whether the login is based on cookie.
     */
    protected function afterLogin($fromCookie)
    {
        if($this->allowLocalLogin) {
            $cookies = Yii::app()->request->cookies;
            if($cookies->itemAt($this->localLoginCookie)===null) {
                $cookie = new CHttpCookie($this->localLoginCookie,1);
                $cookie->expire = time() + 3600 * 24 * 10;
                $cookies->add($this->localLoginCookie, $cookie);
            }
        }
    }

    /**
     * Delete cookie that marks local user
     */
    protected function afterLogout()
    {
        if($this->allowLocalLogin) {
            Yii::app()->request->cookies->remove($this->localLoginCookie);
        }
    }

    /**
     * @return bool whether the has not supplied a valid access token and thus is seen as a guest
     */
    public function getIsGuest()
    {
        return $this->getId()===null;
    }
}
