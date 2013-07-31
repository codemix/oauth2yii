<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\UserCredentialsInterface;

/**
 * Server storage for user data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class CustomUser extends CustomStorage implements UserCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getInterface()
    {
        return 'OAuth2Yii\Interfaces\User';
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
        $storage    = $this->getStorage();
        $user       = $storage->queryUser();

        if($user===null) {
            return false;
        } else {
            return $storage->verifyPassword($user, $password);
        }
    }

    /**
     * Required by OAuth2\Storage\UserCredentialsInterfaces
     *
     * @param string $username
     * @return array with keys scope and user_id
     */
    public function getUserDetails($username)
    {
        $storage    = $this->getStorage();
        $user       = $storage->queryUser();

        if($user===null) {
            return false;
        } else {
            $data = array(
                'user_id' => $storage->userId($user),
            );

            if(($scope = $storage->availableScopes($user))!==null) {
                $data['scope'] = $scope;
            }

            return $data;
        }
    }

}
