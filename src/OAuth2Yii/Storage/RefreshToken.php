<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\RefreshTokenInterface;
use \Yii;

/**
 * Serer storage for refresh tokens
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class RefreshToken extends DbStorage implements RefreshTokenInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getTableName()
    {
        return $this->getOAuth2()->refreshTokenTable;
    }

    /**
     * Create table for this storage
     */
    protected function createTable()
    {
        YII_DEBUG && Yii::trace("Creating refresh token table '{$this->getTableName()}'", 'oauth.storage.refreshtoken');
        $this->getDb()->createCommand()->createTable($this->getTableName(), array(
            'refresh_token' => 'string NOT NULL PRIMARY KEY',
            'client_id'     => 'string NOT NULL',
            'user_id'       => 'string',
            'expires'       => 'TIMESTAMP NOT NULL',
            'scope'         => 'text',
        ));
    }

    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token refresh token
     * @return array with keys refresh_token, client_id, user_id, expires and scope
     */
    public function getRefreshToken($token)
    {
        $sql = sprintf(
            'SELECT refresh_token,client_id,user_id,expires,scope FROM %s WHERE refresh_token=:token',
            $this->getTableName()
        );
        $result = $this->getDb()->createCommand($sql)->queryRow(true, array(':token'=>$token));

        if($result===false)
            return null;

        YII_DEBUG && Yii::trace(
            sprintf("Refresh token '%s' found. client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $result['client_id'],
                $result['user_id'],
                $result['expires'],
                $result['scope']
            ),
            'oauth2.storage.refreshtoken'
        );

        $result['expires'] = strtotime($result['expires']);
    }

    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setRefreshToken($token, $client_id, $user_id, $expires, $scope = null)
    {
        $values = array(
            'client_id'     => $client_id,
            'user_id'       => $user_id,
            'expires'       => date('Y-m-d H:i:s', $expires),
            'scope'         => $scope,
        );

        YII_DEBUG && Yii::trace(
            sprintf("Saving refresh token '%s'. client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $client_id,
                $user_id,
                $expires,
                $scope
            ),
            'oauth2.storage.refreshtoken'
        );

        $command = $this->getDb()->createCommand();

        if($this->getRefreshToken($token)===null) {
            $values['refresh_token'] = $token;
            return (bool)$command->insert($this->getTableName(), $values);
        } else {
            return (bool)$command->update($this->getTableName(), $values, 'refresh_token=:token',array(
                ':token' => $token,
            ));
        }
    }


    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token to unset
     * @return bool whether token was removed
     */
    public function unsetRefreshToken($token)
    {
        YII_DEBUG && Yii::trace("Deleting refresh token '$token'", 'oauth.storage.refreshtoken');
        return $this->getDb()->createCommand()->delete($this->getTableName(), 'refresh_token=:token', array(
            ':token' => $token,
        ));
    }
}
