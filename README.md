oauth2yii
=========

An OAuth2 client / server extension for the Yii framework.

# NOTE: PARTS OF THIS EXTENSION ARE STILL INCOMPLETE!!

Missing:

 * Support for Authorization Code Grant on client / server

# Introduction

This extension is a wrapper around [OAuth 2.0 Server PHP](http://bshaffer.github.io/oauth2-server-php-docs/)
that makes it easy to add OAuth2 authentication to your application.

Before you can start using this extension you need to understand some basics about how
OAuth2 works.  You can read all the details in [RFC 6749](http://tools.ietf.org/html/rfc6749)
but for a quick start we try to explain the main concepts [here](docs/oauth2.md).


# Installation

We recommend to install the extension with [composer](http://getcomposer.org/). Add this to
the `require` section of your `composer.json`:

    'codemix/oauth2yii' : 'dev-master'

> Note: There's no stable version yet.

You also need to include composer's autoloader on top of your `index.php`:

    require_once __DIR__.'/protected/vendor/autoload.php';

Make sure to fix the path to your composer's `vendor` directory. Finally you also need to
configure an `alias` in your `main.php`:

```
$vendor = realpath(__DIR__.'/../vendor');
return array(
    'alias' => array(
        'OAuth2Yii' => $vendor.'/codemix/oauth2yii/src/OAuth2Yii',
    ),
    ...
```

# Configuration

## Server

If you want to run an OAuth2 server you have to decide which of the above grant type or types you
want to support. You can enable them in the main application component.

### Configure Server Application Component

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

As explained above you also need to provide one or two actions. The actions required for each grant
type are:

 * Authorization code: `authorization` and `token`
 * Implicit: `authorization` and `token`
 * User credentials: `token`
 * Client credentials: `token`


### Configure the `token` action

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


### Configure the `authorize` action

TODO


### Checking permissions

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

### Storage

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

## Client

The client implementation also supports all main grant types.

### Configure Client Application Component

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

### Grant Type "Resource Owner" - Authenticate by username/password

As explained above this grant type should only be used if you trust your client.
In this case you'd ask your visitors for username and password and can then
authenticate against the OAuth2 server.

Here is an example how you would integrate OAuth2 authentication with the default
`LoginForm` that is created by `yiic webapp`.

```php
public function authenticate($attribute,$params)
{
    if(!$this->hasErrors())
    {
        $provider = Yii::app()->oauth2client->getProvider('myoauth');
        $this->_identity = $provider->createUserIdentity($this->username,$this->password);

        if(!$this->_identity->authenticate())
            $this->addError('password','Incorrect username or password.');
    }
}
```

You have to change 2 lines, that's all. Now the user's will be authenticated against your
OAuth2 server and logged in on your site on success.


### Grant Type "Client Credentials" - Authenticate by clientId/clientSecret

If you want to authenticate yourself as a client to the OAuth2 server, it works basically
the same as for the user. Again the main code is very simple:

```php
$provider = Yii::app()->oauth2client->getProvider('myoauth');
$identity = $provider->createClientIdentity();

if($identity->authenticate()) {
    // Client authentication was successful.
}
```

### Sending requests to the server

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
