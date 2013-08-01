DB Tables and custom storage
============================

# DB Tables

In order to store tokens, codes, clients and user, the server component will create some DB
tables if they don't exist:

 * `oauth_access_tokens`: Table for access tokens. Configurable in `$accessTokenTable`.
 * `oauth_authorization_codes`: Table for authorization codes. Configurable in `$authorizationCodeTable`.
 * `oauth_refresh_tokens`: Table for refresh tokens. Configurable in `$refreshTokenTable`.
 * `oauth_clients`: Table for clients. Configurable in `$clientTable`.
 * `oauth_users`: Table for users. Configurable in `$userTable`.

As you see, we even create tables for clients and users to quickly get you started. But
in real applications you probably always want to use your own schema for clients and users
(e.g. you may want to store additional information for each user).


# Custom storages

To use custom storage for your client and user data, you can configure your own storage classes
for both of them via a server component configuration option.

 * `$clientClass` a custom client storage that implements the OAuth2Yii\Interfaces\Client interface
 * `$userClass` a custom user storage that implements the OAuth2Yii\Interfaces\User interface

## User storage

This storage class must implement the `OAuth2Yii\Interfaces\User` interface. Here's a very
simple example for a custom user storage class, based on ActiveRecord.

```php
<?php
class OAuth2User implements OAuth2Yii\Interfaces\User
{
    public function queryUser($username)
    {
        return User::model()->findByAttributes(array('email'=>$username));
    }

    public function userId($user)
    {
        return $user->id;
    }

    public function availableScopes($user)
    {
        return $user->defaultScopes;
    }

    public function verifyPassword($user, $password)
    {
        return $user->password === md5($password);
    }
}
```

To use it, you'd configure it like:

```php
'components' => array(
    'oauth2' => array(
        ...
        'userClass' => 'OAuth2User',
    ),
```

## Client storage

This storage class must implement the `OAuth2Yii\Interfaces\Client` interface. Here an implementation
based on ActiveRecord.

```php
<?php
class OAuth2User implements OAuth2Yii\Interfaces\Client
{
    public function queryClient($client_id)
    {
        return Client::model()->findByAttributes(array('client_id'=>$client_id));
    }

    public function redirectUri($client)
    {
        return $client->redirect_uri;
    }

    public function grantTypes($client)
    {
        return $client->grant_types;
    }

    public function verifySecret($client, $secret)
    {
        return $client->secret === md5($secret);
    }
}
```

To use it, you'd configure it like:

```php
'components' => array(
    'oauth2' => array(
        ...
        'clientClass' => 'OAuth2Client',
    ),
```
