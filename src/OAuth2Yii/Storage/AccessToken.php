<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\AccessTokenInterface;

/**
 * Server storage for access tokens
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class AccessToken extends DbStorage implements AccessTokenInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getTableName()
    {
        return $this->getOAuth2()->accessTokenTable;
    }

    /**
     * Create table for this storage
     */
    protected function createTable()
    {
        $this->getDb()->createCommand()->createTable($this->getTableName(), array(
            'access_token'  => 'string NOT NULL PRIMARY KEY',
            'client_id'     => 'string NOT NULL',
            'user_id'       => 'string',
            'expires'       => 'TIMESTAMP NOT NULL',
            'scope'         => 'text',
        ));
    }

    /**
     * Required by OAuth2\Storage\AccessTokenInterfaces
     *
     * @param mixed $token the access token
     * @return null|array with keys client_id, user_id, expires and (optional) scope, null if not found
     */
    public function getAccessToken($token)
    {
        $sql = sprintf(
            'SELECT client_id,user_id,expires,scope FROM %s WHERE access_token=:token',
            $this->getTableName()
        );
        $result = $this->getDb()->createCommand($sql)->queryRow(true, array(':token'=>$token));

        if($result===false)
            return null;

        $result['expires'] = strtotime($result['expires']);

        return $result;
    }

    /**
     * Required by OAuth2\Storage\AccessTokenInterfaces
     *
     * @param mixed $token to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setAccessToken($token, $client_id, $user_id, $expires, $scope = null)
    {
        $values = array(
            'client_id'     => $client_id,
            'user_id'       => $user_id,
            'expires'       => date('Y-m-d H:i:s', $expires),
            'scope'         => $scope,
        );

        $command = $this->getDb()->createCommand();

        if($this->getAccessToken($token)===null) {
            $values['access_token'] = $token;
            return (bool)$command->insert($this->getTableName(), $values);
        } else {
            return (bool)$command->update($this->getTableName(), $values, 'access_token=:token',array(
                ':token' => $token,
            ));
        }
    }
}
