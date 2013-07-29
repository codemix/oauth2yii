<?php
namespace OAuth2Yii\Storage;

use \Yii as Yii;
use \CException as CException;

/**
 * Base class for CDbConnection based server storages
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
abstract class DbStorage extends Storage
{
    protected $_db;

    /**
     * @return string name of the DB table
     */
    protected abstract function getTableName();

    /**
     * Create table for this storage
     */
    protected abstract function createTable();

    /**
     * @param \OAuth2Yii\Component\Server the server component
     * @param string $db id of the CDbConnection component
     */
    public function __construct(\OAuth2Yii\Component\ServerComponent $server, $db)
    {
        parent::__construct($server);

        if(!Yii::app()->hasComponent($db)) {
            throw new CException("Unknown component '$db'");
        }

        $this->_db      = Yii::app()->getComponent($db, $this);

        if(!in_array($this->getTableName(), $this->_db->getSchema()->getTableNames())) {
            $this->createTable();
        }
    }

    /**
     * @return CDbConnection to use for this storage
     */
    public function getDb()
    {
        return $this->_db;
    }
}
