<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\UserCredentialsInterface;

/**
 * Storage for user data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class CustomUser extends CustomStorage implements UserCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getInterfaces()
    {
        return 'OAuth2Yii\Interfacess\User';
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
        $user       = $storage->getUser();

        if($user===null) {
            return false;
        } else {
            return $storage->authenticate($user, $password);
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
        $user       = $storage->getUser();

        if($user===null) {
            return false;
        } else {
            $data = array(
                'user_id' => $storage->getUserId($user),
            );

            if(($scope = $storage->getScope($user))!==null) {
                $data['scope'] = $scope;
            }

            return $data;
        }
    }

}
