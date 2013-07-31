<?php
namespace OAuth2Yii\Interfaces;

/**
 * Client
 *
 * This is the interface that custom client data storages must implement.
 */
interface Client
{
    /**
     * @param string $client_id as given to the client
     * @return array|object|null must return a client representation for
     * the given client_id. This can be an arbitrary object or array
     * that will be passed back to other methods of this interface.
     * So it should contain all the data that will be read out there.
     * If the client does not exist, null must be returned.
     */
    public function queryClient($client_id);

    /**
     * @param array|object $client the client data retrieved from queryClient()
     * @return string the redirect URI for that client
     */
    public function redirectUrl($client);

    /**
     * @param array|object $client the client data retrieved from queryClient()
     * @return array list of allowed grant_types types for this client. See RFC 6749 for list of grant_types.
     * Can be an empty array to allow all configured grant types.
     */
    public function grantTypes($client);

    /**
     * @param array|object $client the client data retrieved from queryClient()
     * @param string $client_secret as given to the client
     * @return bool whether the secret is valid for this client
     */
    public function verifySecret($client, $client_secret);
}
