<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\ClientInterface;
use \OAuth2\Storage\ClientCredentialsInterface;

/**
 * Server storage for client data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class CustomClient extends CustomStorage implements ClientInterface, ClientCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getInterface()
    {
        return 'OAuth2Yii\Interfaces\Client';
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param mixed $client_id
     * @return array with keys redirect_uri, client_id and optional grant_types
     */
    public function getClientDetails($client_id)
    {
        $storage    = $this->getStorage();
        $client     = $storage->queryClient($client_id);

        if($client===null) {
            return null;
        } else {
            $data = array(
                'redirect_uri'  => $storage->redirectUri($client),
                'client_id'     => $client_id,
            );

            if(($grantTypes = $storage->grantTypes($client))!==array()) {
                $data['grant_types'] = $grantTypes;
            }

            return $data;
        }
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param string $client_id
     * @param string $grant_type as defined by RFC 6749
     *
     * @return bool true if the grant type is supported by this client identifier
     */
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, $details['grant_types']);
        }
        return true;
    }

    /**
     * Required by OAuth2\Storage\ClientCredentialsInterfaces
     *
     * @param string $client_id
     * @param string $client_secret
     * @return bool whether the client credentials are valid
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $storage    = $this->getStorage();
        $client     = $storage->queryClient($client_id);

        if($client===null) {
            return false;
        } else {
            return $storage->verifySecret($client, $client_secret);
        }
    }
}
