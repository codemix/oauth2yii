<?php
namespace OAuth2Yii\Component;

use \Yii;
use \CUserIdentity;

/**
 * Identity
 *
 * This is the base class for identities
 */
class Identity extends CUserIdentity
{
    protected $provider;

    /**
     * @return OAuth2Yii\Provider\Provider for this identity
     * @param string $username
     * @param string $password
     */
    public function __construct($provider, $username, $password)
    {
        $this->provider = $provider;
        parent::__construct($username, $password);
    }

    /**
     * @return OAuth2Yii\Provider\Provider for this identity
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
