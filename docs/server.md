Server
======

If you want to run an OAuth2 server you have to decide which grant type(s) you
want to support. For a definition of grant types please refer to the
[OAuth2 introduction](oauth2.md). You can enable grant types in the main application component.

# Configuration of the Server Application Component

Add the application component to your `main.php` and decide, which grant types you want to enable.

```php
'components' => array(
    'oauth2' => array(
        'class'                     => 'OAuth2Yii\Component\ServerComponent',
        // Enable one or more grant types
        'enableAuthorization'       => true,
        'enableImplicit'            => true,
        'enableUserCredentials'     => true,
        'enableClientCredentials'   => true,

    ),
```

Depending on your grant types you also need to provide one or two actions:

 * Authorization code: `authorization` and `token`
 * Implicit: `authorization` and `token`
 * User credentials: `token`
 * Client credentials: `token`


# Configuration of the `token` action

This action is required by all grant types. It's available as an
[action class](http://www.yiiframework.com/doc/guide/1.1/en/basics.controller#action) that you
can configure in any controller you want. We recommend to create an `OAuthController` and
import the action as follows.

```php
<?php
class OAuthController extends CController
{
    public function actions()
    {
        return array(
            'token' => array(
                'class' => 'OAuth2Yii\Action\Token',
                // Optional: configure the name of the server component if it's not oauth2
                //'oauth2Component' => 'oauth'
            ),
        );
    }
}
```

If you use URLs in path format the URL should then be `oauth/token`. You can of course
also define a URL rule and use any URL you want. But in any case you need to tell your
clients under which URL to find your token action.


# Configuration of the `authorize` action

TODO


# Checking permissions

The main purpose of all this is of course, to check if a client has permission to access
a resource on your server. That's very similar to how you would do permission checks
in Yii. Here's a simple example:

```php
public function actionView()
{
    if(!Yii::app()->oauth2->checkAccess()) {
        throw new CHttpException(403, 'Forbidden');
    }

    // Your protected code ...
}
```

The client requesting this action will only be allowed if he passes an *access token* along
in the request header, that he obtained through one of the above grant types. The access
token must also not be expired or the permission check will fail.

If you need a more fine grained control over which clients are allowed which actions,
you can use scopes. In this case the above permission check would look like:

```php
Yii::app()->oauth2->checkAccess('photos');
```

But in order to use scopes you must list all available scopes in your OAuth2 server component
in `main.php`.

```php
'components' => array(
    'oauth2' => array(
        ...
        'scopes' => array(
            'wall',
            'profile',
            'friends',
            'photos',
        ),
        'defaultScope' => 'profile',
    ),
```

On the authorize action, you can let your users select, which of the configured scopes
they want to grant access to the client. This selection will be stored together with the
access token for this client. Whenever that client tries to access the above action it
has to send the right scope and will only be permitted if the user granted permission.

# Storage

In order to store tokens, codes, clients and user, the extension will create some DB
tables if they don't exist:

 * `oauth_access_tokens`: Table for access tokens. Configurable in `$accessTokenTable`.
 * `oauth_authorization_codes`: Table for authorization codes. Configurable in `$authorizationCodeTable`.
 * `oauth_refresh_tokens`: Table for refresh tokens. Configurable in `$refreshTokenTable`.
 * `oauth_clients`: Table for clients. Configurable in `$clientTable`.
 * `oauth_users`: Table for users. Configurable in `$userTable`.

As you see, we even create tables for clients and users to quickly get you started. But
in real applications you probably always want to use your own schema for clients and users
(e.g. you may want to store additional information for each user). To do so, you can
configure your own storage for both of them via a configuration option.

 * `$clientClass` a custom client storage that implements the OAuth2Yii\Interfaces\Client interface
 * `$userClass` a custom user storage that implements the OAuth2Yii\Interfaces\User interface

So to implement a custom storage you have to implement those interfaces. Here's a very simple example
for a custom user storage class, based on ActiveRecord.

```php
<?php
class OAuth2User implements OAuth2Yii\Interfaces\Client
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

