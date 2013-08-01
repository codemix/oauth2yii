OAuth2
======

The main idea is that a **resource owner** (a.k.a. end user) hosts some data at a **server**,
and can grant permission to a **client** (a.k.a. a third party) to access this data or parts of it.
Therefore the client can obtain an **access token** which represents this permission.

So we have three roles involved:

 * **User**: The end user that has a `username` and `password` at the server.
 * **Server**: A website where the end user has an account and stored data.
 * **Client**: A third party website that wants to access user data from the server.
   Clients have to register with the server first and receive a `client_id` and `client_secret`.

OAuth2 defines four different flows or *grant types* for how to get an access token. All four
are supported by this extension. Let's look at each of them.

### 1. Authorization Code

This is the famous *"Login with your FB account"* type: A client site wants to authenticate its
users through another server and access the users data on that other website.

The basic flow here is:

 1. Client site redirects the user to the server site
 1. User logs in there and is asked to grant permission to the client
 1. User is redirected back to client site with an *authorization code* in the URL
 1. Client site can use this *authorization token*  to request an *access token* directly from the server
 1. Client site also receives the expiry time of the access token and optionally a *refresh token*
 1. With the *access token* the client can now access some of the user's data on the server
 1. Client can request a new access token with the refresh token

The server here has to provide two main actions:

 * **authorize**: This is a page, where the user first has to login and is then asked for permission
   to grant access to the client. This can either be a simple question like *"Do you allow website
   Foo to authenticate with us?"* or involve the selection of **scopes** like *"Which of the following
   permissions do you want to grant to website Foo?"*. After this, the action will redirect the
   user back to the client and append an *authorization code* to the client's redirect URL.
 * **token**: This is the action where the client can exchange the *authorization code* against the
   final *access token*.


### 2. Implicit

This is for pure Javascript applications that run in a browser. Note, that this grant type is considered
to be insecure and should be avoided. There's no client involved in the communication to the server.

 1. User is redirected to the server site via javascript
 1. User logs in there and is asked to grant permission to the application
 1. User is redirected back to client site with an *access token* as URL parameter

The server here has to provide only one action:

 * **authorize**: This is the same page as in the step above, asking the user for permission.
   This time the URL that the user is redirect to afterwards will contain the *access token*
   right away. But it's a URL hash parameter so that the client server can't read it.


### 3. Resource Owner Password

If the client is a trusted entity, e.g. part of the providers enterprise then it can be
trusted to ask the users for their credentials.

 1. Client POSTs the user's credentials directly to the authorization provider
 1. Client recieves an *access token* in response and optionally a refresh token

The server here again has to provide only one action:

 * **token**: The client will send the username and password to this action and receive an
   access token in return.


### 4. Client Credentials

The last grant type is used if the client has to authenticate itself against the server
to manage it's own data. Here no user is involved and the access token only allow the
client to access its own data.

 1. Client POSTs its own credentials directly to the authorization provider
 1. Client recieves an access token in response


The server here again has to provide only one action:

 * **token**: The client will send his client_id and client_secret to this action and receive an
   access token in return.

