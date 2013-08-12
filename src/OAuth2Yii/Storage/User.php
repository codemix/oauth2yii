<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\UserCredentialsInterface;

/**
 * Server storage for user data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class User extends DbStorage implements UserCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getTableName()
    {
        return $this->getOAuth2()->userTable;
    }

    /**
     * Create table for this storage
     */
    protected function createTable()
    {
        $this->getDb()->createCommand()->createTable($this->getTableName(), array(
            'username'      => 'string NOT NULL PRIMARY KEY',
            'password'      => 'string NOT NULL',
            'first_name'    => 'string',
            'last_name'     => 'string',
        ));
    }

    /**
     * Required by OAuth2\Storage\UserCredentialsInterfaces
     *
     * @param mixed $username
     * @param mixed $password
     * @return bool whether credentials are valid
     */
    public function checkUserCredentials($username, $password)
    {
        $sql = sprintf(
            'SELECT password FROM %s WHERE username=:username',
            $this->getTableName()
        );
        $crypted = $this->getDb()->createCommand($sql)->queryScalar(array(':username'=>$username));

        return $crypted === md5($password);
    }

    /**
     * Required by OAuth2\Storage\UserCredentialsInterfaces
     *
     * @param string $username
     * @return array with keys scope and user_id
     */
    public function getUserDetails($username)
    {
        $sql = sprintf(
            'SELECT username as user_id, scope FROM %s WHERE username=:username',
            $this->getTableName()
        );
        return $this->getDb()->createCommand($sql)->queryRow(true, array(':username'=>$username));
    }

}
