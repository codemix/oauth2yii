Client and Server on one host
=============================

So you've set up an OAuth2 server and now want to login on that same server as a user.
You don't want to add more access checks for local users. Instead you want to reuse
the checks you've added for access token scopes.

In this case you can set up both, an OAuth2 server *and* a client on the same host.
You can show an ordinary login form and after login, the client will obtain an access
token from the server. This access token is preserved throughout the session. So for
the server part of your application, the logged in user will look like an OAuth2 client
with a valid access token.

# Configuration

## Client and Server Application Components

First you need to configure both, a server *and* a client component. Just follow the
guides for [client](client.md) and [server](server.md) configuration. In the client
part, configure your own server as provider. Make sure, that you can connect to the
token URL from localhost.

## WebUser Component

In your custom `WebUser` component you have to enable local login and configure the
name of the OAuth2 login provider:

```php
'components' => array(
    'user' => array(
        'class'             => 'OAuth2Yii\Component\WebUser',
        'allowLocalLogin'   => true,
        'loginProvider'     => 'myoauth',
    ),
```

# Usage

Just create a login action and log the user in, as it is documented in the
[client](client.md) guide.
