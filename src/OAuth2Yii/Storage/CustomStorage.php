<?php
namespace OAuth2Yii\Storage;

use \Yii as Yii;
use \CException as CException;

/**
 * Base class for storages with custom DB class
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
abstract class CustomStorage extends Storage
{
    protected $_storage;

    /**
     * @return string the interface name that the custom storage class must implement
     */
    protected abstract function getInterfaces();

    /**
     * @param \OAuth2Yii\Component\Server the server component
     * @param string name of the custom storage class
     */
    public function __construct(\OAuth2Yii\Component\ServerComponent $server, $className)
    {
        parent::__construct($server);
        $this->_storage = new $className;
        if(!is_a($this->_storage, $this->getInterfaces())) {
            throw new CException('Class must implement {$this->getInterfaces}');
        }
    }

    /**
     * @return object the custom storage class that implements the respective interface
     */
    public function getStorage()
    {
        return $this->_storage;
    }
}
