<?php
namespace OAuth2Yii\Component;

use \OAuth2Yii\Storage;

use \Yii;
use \CApplicationComponent;
use \CExcpetion;

/**
 * ClientComponent
 *
 * This is the OAuth2 client application component.
 */
class ClientComponent extends CApplicationComponent
{
    /**
     * @var array provider configurations indexed by a custom provider name. Each
     * entry should contain a `class` property. If it's missing, the class name
     * is autogenerated from the index key.
     *
     *  array(
     *      'google' => array(
     *          // No 'class' specified, so OAuth2Yii\Provider\Google is assumed
     *          'clientId'      => 'Your Google client id',
     *          'clientSecret'  => 'Your Google client secret',
     *      ),
     *      'myapi' => array(
     *          // A generic OAuth2 provider e.g. to work with OAuth2Yii servers
     *          'class'             => 'OAuth2Yii\Providers\Generic'
     *          'clientId'          => 'Your client id',
     *          'clientSecret'      => 'Your client secret',
     *          'tokenUrl'          => 'http://myapi.com/token',
     *          'authorizationUrl'  => 'http://myapi.com/authorize',
     *      )
     *  );
     */
    public $providers = array();

    /**
     * @var array concreted providers
     */
    protected $_p = array();

    /**
     * @param string $name provider name as configured in $providers
     * @return \OAuth2Yii\Provider\Provider
     */
    public function getProvider($name)
    {
        if(!isset($this->_p[$name])) {
            $this->_p[$name] = $this->createProvider($name);
        }

        return $this->_p[$name];
    }

    /**
     * Create and init an OAuth2 provider
     *
     * @param string $name of provider
     *
     * @throws \CException if the configuration is missing
     * @return \OAuth2Yii\Provider\Provider
     */
    protected function createProvider($name)
    {
        if(!isset($this->providers[$name])) {
            throw new \CException("Missing configuration for provider '$name'");
        }

        $config = $this->providers[$name];
        $config['name'] = $name;

        if(!isset($config['class'])) {
            $config['class'] = 'OAuth2Yii\\Provider\\'.ucfirst($name);
        }

        $provider = Yii::createComponent($config);
        $provider->init();
        return $provider;
    }
}
