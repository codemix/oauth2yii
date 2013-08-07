<?php
namespace OAuth2Yii\Interfaces;

/**
 * User
 *
 * This is the interface that custom user data storages must implement.
 */
interface User
{
    /**
     * @param string $username the user name
     *
     * @return array|object|null must return a user representation for
     * the given username. This can be an arbitrary object or array
     * that will be passed back to other methods of this interface.
     * So it should contain all the data that will be read out there.
     * If the user does not exist, null must be returned.
     */
    public function queryUser($username);

    /**
     * @param array|object $user the user data retrieved from queryUser()
     * @return string the user id that will be stored with authorization codes or access tokens
     */
    public function userId($user);

    /**
     * @param array|object $user the user data retrieved from queryUser()
     * @return string|null space separated scopes for this user or null for all scopes
     */
    public function availableScopes($user);

    /**
     * @param array|object $user the user data retrieved from queryUser()
     * @param string $password the user password
     * @return bool whether the password is valid for this user
     */
    public function verifyPassword($user, $password);
}
