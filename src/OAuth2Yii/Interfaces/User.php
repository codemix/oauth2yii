<?php
namespace OAuth2Yii\Interfaces;

/**
 * Client
 *
 * This is the interface that custom user data storages must implement.
 */
interface User
{
    /**
     * @return array|object|null must return a user representation for
     * the given username. This can be an arbitrary object or array
     * that will be passed back to other methods of this interface.
     * So it should contain all the data that will be read out there.
     * If the user does not exist, null must be returned.
     */
    public function getUser($username);

    /**
     * @param array|object $user the user data retrieved from getUser()
     * @return string the user id that will be stored with authorization codes or access tokens
     */
    public function getUserId($user);

    /**
     * @param array|object $user the user data retrieved from getUser()
     * @return string|null space separated scopes for this user or null for all scopes
     */
    public function getScope($user);

    /**
     * @param array|object $user the user data retrieved from getUser()
     * @param string $password the user password
     * @return bool wether the password is valid for this user
     */
    public function authenticate($user, $password);
}
