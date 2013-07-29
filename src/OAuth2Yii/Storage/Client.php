<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\ClientInterface;
use \OAuth2\Storage\ClientCredentialsInterface;

/**
 * Server storage for client data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class Client extends DbStorage implements ClientInterface, ClientCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getTableName()
    {
        return $this->getOAuth2()->clientTable;
    }

    /**
     * Create table for this storage
     */
    protected function createTable()
    {
        $this->getDb()->createCommand()->createTable($this->getTableName(), array(
            'client_id'     => 'string NOT NULL PRIMARY KEY',
            'client_secret' => 'string NOT NULL',
            'redirect_uri'  => 'text NOT NULL',
        ));
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param mixed $client_id
     * @return array with keys redirect_uri, client_id and optional grant_types
     */
    public function getClientDetails($client_id)
    {
        $sql = sprintf(
            'SELECT client_id,redirect_uri FROM %s WHERE client_id=:id',
            $this->getTableName()
        );
        return $this->getDb()->createCommand($sql)->queryRow(true, array(':id'=>$client_id));
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param string $client_id
     * @param string $grant_type as defined by RFC 6749
     */
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, $details['grant_types']);
        }
        return true;
    }

    /**
     * Required by OAuth2\Storage\ClientCredentialsInterfaces
     *
     * @param string $client_id
     * @param string $client_secret
     * @return bool whether the client credentials are valid
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $sql = sprintf(
            'SELECT client_secret FROM %s WHERE client_id=:id',
            $this->getTableName()
        );
        $hash = $this->getDb()->createCommand($sql)->queryScalar(array(':id'=>$client_id));

        return md5($client_secret) === $hash;
    }
}
