<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\AuthorizationCodeInterface;

/**
 * Storage for authorization codes
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class AuthorizationCode extends DbStorage implements AuthorizationCodeInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getTableName()
    {
        return $this->getOAuth2()->authorizationCodeTable;
    }

    /**
     * Create table for this storage
     */
    protected function createTable()
    {
        $this->getDb()->createCommand()->createTable($this->getTableName(), array(
            'authorization_code'    => 'string NOT NULL PRIMARY KEY',
            'client_id'             => 'string NOT NULL',
            'user_id'               => 'string',
            'redirect_uri'          => 'text',
            'expires'               => 'TIMESTAMP NOT NULL',
            'scope'                 => 'text',
        ));
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code authorization code to check
     * @return null|array with keys client_id, user_id, expires, redirect_uri and (optional) scope, null if not found
     */
    public function getAuthorizationCode($code)
    {
        $sql = sprintf(
            'SELECT client_id,user_id,expires,redirect_uri,scope FROM %s WHERE code=:code',
            $this->getTableName()
        );
        $result = $this->getDb()->createCommand($sql)->queryRow(true, array(':code'=>$code));

        if($result===false)
            return null;

        $result['expires'] = strtotime($result['expires']);

        return $result;
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $redirect_uri one or several URIs (space separated) to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        $values = array(
            'client_id'     => $client_id,
            'user_id'       => $user_id,
            'redirect_uri'  => $redirect_uri,
            'expires'       => date('Y-m-d H:i:s', $expires),
            'scope'         => $scope,
        );

        $command = $this->getDb()->createCommand();

        if($this->getAuthorizationCode($code)===null) {
            $values['authorization_code'] = $code;
            return (bool)$command->insert($this->getTableName(), $values);
        } else {
            return (bool)$command->update($this->getTableName(), $values, 'authorization_code=:code',array(
                ':code' => $code,
            ));
        }

        return (bool)$this->getDb()->createCommand($sql)->execute($values);
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code to expire
     */
    public function expireAuthorizationCode($code)
    {
        return $this->getDb()->createCommand()->delete($this->getTableName(), 'authorization_code=:code', array(
            ':code' => $code,
        ));
    }

}
