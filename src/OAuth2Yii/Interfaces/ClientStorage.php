<?php
namespace OAuth2Yii\Interfaces;

use OAuth2Yii\Component\AccessToken;

/**
 * ClientStorage
 *
 * This is the interface that custom token storages on OAuth2 clients must implement.
 */
interface ClientStorage
{
    /**
     * Save the AccessToken object for a user or client.
     *
     * Depending on which grant types you use, you can receive AccessTokens for either a
     * client or a user here. So if you e.g. use username/password and client credentials
     * you need to make sure, that you store the access tokens under their ID and type.
     * This is neccessary because in loadToken() you must be able to query by id and type,
     * where the same ID value could appear as clientId and user id.
     *
     * Also note, that for user tokens, $id will be the username that was used for authentication
     * with the OAuth2 server. But in loadToken() you will receive the user id that is stored
     * as Yii::app()->user->id. So you should query for the Id of this username here and store
     * the token under this Id in your permanent storage.
     *
     * @param string $id client Id or username for users
     * @param OAuth2Yii\Component\AccessToken the token object to store
     */
    public function saveToken($id, $accessToken);

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param string $provider name of provider
     * @return null|OAuth2Yii\Component\AccessToken the access token stored for this client/user or null if not found
     */
    public function loadToken($id, $type, $provider);

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param OAuth2Yii\Component\AccessToken the new token object to store instead
     */
    public function updateToken($id, $type, $accessToken);

    /**
     * @param string $id of the client/user. For a user this is usually Yii::app()->user->id.
     * @param string type of token. One of AccessToken::TYPE_(CLIENT|USER).
     * @param string $provider name of provider
     */
    public function deleteToken($id, $type, $provider);
}
