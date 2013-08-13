Client
======

The client implementation also supports all main grant types.

# Configuration

## Client Application Component

Add the application component to your `main.php` and configure one or more OAuth2 providers.
At the time being we only support OAuth2Yii servers - but we'll extend soon to support many
well known OAuth2 providers.

```php
'components' => array(
    'oauth2client' => array(
        'class'                     => 'OAuth2Yii\Component\ClientComponent',
        'providers' => array(
            'myoauth' => array(
                // So far only generic OAuth2 providers are supported
                'class'     => 'OAuth2Yii\Provider\Generic',

                // You need client credentials for your OAuth2 server.
                // In the default server setup they are stored in oauth2_clients table.
                'clientId'      => 'Your client id',
                'clientSecret'  => 'Your client secret',

                // At minimum you need the token URL
                'tokenUrl'      => 'http://example.com/oauth/token',
            ),
        ),
    ),
```

# Usage

## Grant Type "Resource Owner" - Authenticate by username/password

As explained before this grant type should only be used if you trust your client.
In this case you'd ask your visitors for username and password and can then
authenticate against the OAuth2 server.

> **Note:** On the server, the client needs to have `password` in the list of
> `grantTypes` in the `oauth2_clients` table.

Here is an example how you would integrate OAuth2 authentication with the default
`LoginForm` that is created by `yiic webapp`.

```php
public function authenticate($attribute,$params)
{
    if(!$this->hasErrors())
    {
        $provider = Yii::app()->oauth2client->getProvider('myoauth');
        $this->_identity = $provider->createUserIdentity($this->username,$this->password);

        // Optional: Set scopes that you want assigned to the access token
        $identity->scope = 'profile photos';

        if(!$this->_identity->authenticate())
            $this->addError('password','Incorrect username or password.');
    }
}
```

You have to change 2 lines, that's all. Now the user's will be authenticated against your
OAuth2 server and logged in on your site on success.


## Grant Type "Client Credentials" - Authenticate by clientId/clientSecret

If you want to authenticate yourself as a client to the OAuth2 server, it works basically
the same as for the user.

> **Note:** On the server, the client needs to have `client_credentials` in the list of
> `grantTypes` in the `oauth2_clients` table.

```php
$provider = Yii::app()->oauth2client->getProvider('myoauth');
$identity = $provider->createClientIdentity();

// Optional: Set scopes that you want assigned to the access token
$identity->scope = 'profile photos';

if($identity->authenticate()) {
    // Client authentication was successful.
}
```

For this grant type it's very likely, that you want to share the same access token
throughout the application (in contrast to storing a new access token for every user).
Therefore you can use the `GlobalStateClientStorage` class:

```php
'components' => array(
    'oauth2client' => array(
        'class'                     => 'OAuth2Yii\Component\ClientComponent',
        'providers' => array(
            'myoauth' => array(
                'class'         => 'OAuth2Yii\Provider\Generic',
                'storageClass'  => 'OAuth2Yii\Storage\GlobalStateClientStorage'
```

## Sending requests to the server

After authentication you eventually want to send requests to some protected resources
on the server. You could build the requests manually if you want and retrieve the access
token after authentication:

```php
$accessToken = Yii::app()->oauth2client->getProvider('myoauth')->getAccessToken();

// Use the access token in your custom requests
```

But in case you're using [Guzzle](http://guzzlephp.org/) it's even simpler. We do the dirty
work for you and add the access token at the right place for you. Here's an example.

```php

$client     = new Guzzle\Http\Client('http://example.com');
$request    = $client->get('api/view');
$provider   = Yii::app()->oauth2client->getProvider('myoauth');

$response   = $provider->sendGuzzleRequest($request);

if($response===false) {
    // Your access token is not valid anymore. So you probably
    // want to redirect to the login page.
    Yii::app()->user->logout();
    Yii::app()->user->loginRequired();
}
```

You also don't have to take care of refreshing the access token if it expired. If it has
a refresh token, the extension will try to refresh it automatically. Only if all fails,
`sendGuzzleRequest()` will return false.

> **Note**: You may want to try/catch `Guzzle\Http\Exception\ClientErrorResponseException`
> from the `sendGuzzleRequest()`, just in case the request fails for other reasons, e.g. a 404.
