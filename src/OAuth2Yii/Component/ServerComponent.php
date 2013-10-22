<?php
namespace OAuth2Yii\Component;

use \OAuth2Yii\Storage;

use \Yii;
use \CApplicationComponent;

/**
 * ServerComponent
 *
 * This is the OAuth2 server application component.
 */
class ServerComponent extends CApplicationComponent
{
    const STORAGE_ACCESS_TOKEN          = 'access_token';
    const STORAGE_AUTHORIZATION_CODE    = 'authorization_code';
    const STORAGE_CLIENT_CREDENTIALS    = 'client_credentials';
    const STORAGE_CLIENT                = 'client';
    const STORAGE_REFRESH_TOKEN         = 'refresh_token';
    const STORAGE_USER_CREDENTIALS      = 'user_credentials';
    const STORAGE_SCOPE                 = 'scope';

    const CLASS_ACCESS          = 'AccessToken';
    const CLASS_AUTHORIZATION   = 'AuthorizationCode';
    const CLASS_CLIENT          = 'Client';
    const CLASS_REFRESH         = 'RefreshToken';
    const CLASS_USER            = 'User';
    const CLASS_SCOPE           = 'Scope';

    /**
     * @var bool whether to enable the "Authorization Code" grant (see RFC 6749). Default is false.
     */
    public $enableAuthorization = false;

    /**
     * @var bool whether to enable the "Implicit" grant (see RFC 6749). Default is false.
     * Note, that this grant type is considered to be insecure. Use at your own risk.
     */
    public $enableImplicit = false;

    /**
     * @var bool whether to enable the "Resource Owner Password Credentials" grant (see RFC 6749). Default is false.
     */
    public $enableUserCredentials = false;

    /**
     * @var bool whether to enable the "Client Credentials" grant (see RFC 6749). Default is false.
     */
    public $enableClientCredentials = false;

    /**
     * @var string name of CDbConnection app component. Default is 'db'.
     */
    public $db = 'db';

    /**
     * @var int lifetime of the access token in seconds. Default is 3600.
     */
    public $accessTokenLifetime = 3600;

    /**
     * @var bool whether to enforce the use of a 'state'. See RFC 6749.
     * Recommended to avoid CSRF attacks.
     */
    public $enforceState = true;

    /**
     * @var bool whether supplied redirect_uri must exactly match the stored redirect URI for that client.
     * If false, only the beginning of the supplied URI must match the clients stored URI. Default is true.
     */
    public $exactRedirectUri = true;

    /**
     * @var string table name for access tokens. Default is 'oauth_access_tokens'.
     */
    public $accessTokenTable = 'oauth_access_tokens';

    /**
     * @var string table name for authorization codes. Default is 'oauth_authorization_codes'.
     */
    public $authorizationCodeTable = 'oauth_authorization_codes';

    /**
     * @var string table name for refresh tokens. Default is 'oauth_refresh_token'.
     */
    public $refreshTokenTable = 'oauth_refresh_tokens';

    /**
     * @var string table name for clients, if no $clientClass is supplied.
     */
    public $clientTable = 'oauth_clients';

    /**
     * @var string table name for users, if no $userClass is supplied.
     */
    public $userTable = 'oauth_users';

    /**
     * @var string|null the name of the client class that implements OAuth2Yii\Interfaces\Client.
     * If null a client table will be auto created.
     */
    public $clientClass;

    /**
     * @var string|null the name of the user class that implements OAuth2Yii\Interfaces\User.
     * If null an user table will be auto created.
     */
    public $userClass;

    /**
     * @var array|null list of available scopes or null/empty array if no scopes should be used.
     */
    public $scopes;

    /**
     * @var string|null|bool a string with default scope(s). If set to `null` no scope is required.
     */
    public $defaultScope;

    /**
     * @var \OAuth2\Server
     */
    protected $_server;

    /**
     * @var \OAuth2\Request
     */
    protected $_request;

    /**
     * @var array of storages for oauth2-php-server
     */
    protected $_storages = array();

    /**
     * @var array of access token data
     */
    protected $_tokenData;

    /**
     * @var array our storage classes indexed by oauth2-php-server storage names
     */
    protected $_storageMap = array(
        self::STORAGE_ACCESS_TOKEN          => self::CLASS_ACCESS,
        self::STORAGE_AUTHORIZATION_CODE    => self::CLASS_AUTHORIZATION,
        self::STORAGE_CLIENT_CREDENTIALS    => self::CLASS_CLIENT,
        self::STORAGE_CLIENT                => self::CLASS_CLIENT,
        self::STORAGE_REFRESH_TOKEN         => self::CLASS_REFRESH,
        self::STORAGE_USER_CREDENTIALS      => self::CLASS_USER,
        self::STORAGE_SCOPE                 => self::CLASS_SCOPE,
    );

    /**
     * Initialize OAuth2 PHP Server object
     */
    public function init()
    {
        $this->initStorage();

        $this->_server = new \OAuth2\Server($this->getStorages(),array(
            'access_lifetime'               => $this->accessTokenLifetime,
            'enforce_state'                 => $this->enforceState,
            'require_exact_redirect_uri'    => $this->exactRedirectUri,
        ));
    }

    /**
     * @param string|null $scope to check or null if no scope is used
     * @return bool whether the client is authorized for this request
     */
    public function checkAccess($scope=null)
    {
        $response = new \OAuth2\Response;

        YII_DEBUG && Yii::trace('Checking permission'.($scope ? " for scope '$scope'": ''),'oauth2.servercomponent');

        $value = $this->getServer()->verifyResourceRequest($this->getRequest(), $response, $scope);

        if(YII_DEBUG) {
            $p = $response->getParameters();
            $error = isset($p['error_description']) ? $p['error_description'] : 'Unknown error';
            Yii::trace($value ? 'Permission granted' : "Check failed: $error",'oauth2.servercomponent');
        }

        return $value;
    }

    /**
     * @return \OAuth2\Server object
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Gets the named storage
     *
     * @param string $name the storage name
     *
     * @throws \CException if the storage doesn't exist
     * @return object the storage
     */
    public function getStorage($name)
    {
        if(!isset($this->_storages[$name])) {
            throw new \CException("Storage '$name' is not initialized");
        }
        return $this->_storages[$name];
    }

    /**
     * @return array list of storages for oauth2-php-server, indexed by config name
     */
    public function getStorages()
    {
        return $this->_storages;
    }

    /**
     * @return mixed|null the user id if a valid access token was supplied in the request or null otherwhise
     */
    public function getUserId()
    {
        $tokenData = $this->getAccessTokenData();
        return isset($tokenData['user_id']) ? $tokenData['user_id'] : null;
    }

    /**
     * @return mixed|null the client id if a valid access token was supplied in the request or null otherwhise
     */
    public function getClientId()
    {
        $tokenData = $this->getAccessTokenData();
        return isset($tokenData['client_id']) ? $tokenData['client_id'] : null;
    }

    /**
     * @return array access token data
     */
    public function getAccessTokenData()
    {
        if($this->_tokenData===null) {
            $this->_tokenData = $this->_server->getAccessTokenData($this->getRequest());
        }
        return $this->_tokenData;
    }

    /**
     * @return bool whether any grant type is enabled
     */
    public function getCanGrant()
    {
        return $this->enableAuthorization || $this->enableImplicit || $this->enableUserCredentials || $this->enableClientCredentials;
    }

    /**
     * @return \OAuth2\Request the request object as used by OAuth2-PHP
     */
    public function getRequest()
    {
        if($this->_request===null) {
            $this->_request = \OAuth2\Request::createFromGlobals();
        }
        return $this->_request;
    }


    /**
     * Init all required storages
     */
    protected function initStorage()
    {
        $storages = array();

        if($this->enableAuthorization) {
            $storages[self::STORAGE_ACCESS_TOKEN]       = true;
            $storages[self::STORAGE_AUTHORIZATION_CODE] = true;
            $storages[self::STORAGE_REFRESH_TOKEN]      = true;
            $storages[self::STORAGE_CLIENT_CREDENTIALS] = true;
            $storages[self::STORAGE_CLIENT]             = true;
            $storages[self::STORAGE_SCOPE]              = true;
        }
        if($this->enableImplicit) {
            $storages[self::STORAGE_ACCESS_TOKEN]       = true;
            $storages[self::STORAGE_REFRESH_TOKEN]      = true;
            $storages[self::STORAGE_CLIENT_CREDENTIALS] = true;
            $storages[self::STORAGE_CLIENT]             = true;
            $storages[self::STORAGE_SCOPE]              = true;
        }
        if($this->enableUserCredentials) {
            $storages[self::STORAGE_ACCESS_TOKEN]       = true;
            $storages[self::STORAGE_REFRESH_TOKEN]      = true;
            $storages[self::STORAGE_CLIENT_CREDENTIALS] = true;
            $storages[self::STORAGE_CLIENT]             = true;
            $storages[self::STORAGE_USER_CREDENTIALS]   = true;
            $storages[self::STORAGE_SCOPE]              = true;
        }
        if($this->enableClientCredentials) {
            $storages[self::STORAGE_ACCESS_TOKEN]       = true;
            $storages[self::STORAGE_CLIENT_CREDENTIALS] = true;
            $storages[self::STORAGE_CLIENT]             = true;
            $storages[self::STORAGE_SCOPE]              = true;
        }

        foreach($storages as $name => $value) {
            $this->_storages[$name] = $this->createStorage($name);
        }
    }

    /**
     * Creates a storage with the given name
     *
     * @param string $name of the storage
     *
     * @throws \CException if the name is invalid
     * @return object the created storage
     */
    protected function createStorage($name)
    {
        static $objects = array();
        $className = $this->_storageMap[$name];
        if(isset($objects[$className])) {
            return $objects[$className];
        }

        switch($className) {
            case self::CLASS_ACCESS:
                $object = new Storage\AccessToken($this, $this->db);
                break;
            case self::CLASS_AUTHORIZATION:
                $object = new Storage\AuthorizationCode($this, $this->db);
                break;
            case self::CLASS_REFRESH:
                $object = new Storage\RefreshToken($this, $this->db);
                break;
            case self::CLASS_CLIENT:
                if($this->clientClass) {
                    $object = new Storage\CustomClient($this, $this->clientClass);
                } else {
                    $object = new Storage\Client($this, $this->db);
                }
                break;
            case self::CLASS_USER:
                if($this->userClass) {
                    $object = new Storage\CustomUser($this, $this->userClass);
                } else {
                    $object = new Storage\User($this, $this->db);
                }
                break;
            case self::CLASS_SCOPE:
                $object = new Storage\Scope($this);
                break;
            default:
                throw new \CException('Unknown storage class name');
        }

        $objects[$className] = $object;
        return $object;
    }
}
