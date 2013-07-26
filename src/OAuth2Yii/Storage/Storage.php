<?php
namespace OAuth2Yii\Storage;

use \Yii as Yii;
use \CException as CException;

/**
 * Base class for all storages
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
abstract class Storage
{
    /**
     * @var \OAuth2Yii\Component\ServerComponent the server component
     */
    protected $_oauth2;

    /**
     * @var \OAuth2Yii\Component\ServerComponent the server component
     */
    public function __construct(\OAuth2Yii\Component\ServerComponent $server)
    {
        $this->_server = $server;
    }

    /**
     * @return \OAuth2Yii\Component\ServerComponent the server component
     */
    public function getOauth2()
    {
        return $this->_server;
    }
}
