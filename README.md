factom-api-php
===============

A Simple PHP Wrapper for any Factom API v2 calls (including calls to *factomd* and *factom-walletd*)

Find the Factom API reference documentation here: https://docs.factom.com/api

Installation
------------

Using Composer:

`composer require 1000nettles/factom-api-php`

Normal Require:

```php
require_once('FactomAPIAdapter.php');
```

How To Use
----------

#### Launch any Factom Command-Line Apps ####

The PHP Factom API integration connects to a locally running instance of *factomd* or *factom-walletd*. When you run these locally or on your server, they run as service which lets you connect usually through http://localhost:8088 (factomd) or http://localhost:8089 (factom-walletd). You can find out more about running these services here - https://docs.factom.com/cli

For *factomd*, you may be able to connect to "courtesy nodes" instead such as http://courtesy-node.factom.com, however these are not guaranteed. I have not tested if there are API limits. For more information: https://docs.factom.com/#run-enterprise-wallet-online 

#### Instantiate the Factom API Adapter ####

Notice we are supplying the full URL to version 2 of the API below.

```php
$url = 'http://localhost:8088/v2';
$adapter = new FactomAPIAdapter($url);
```

If you want to interact with the API securely, make sure you're running *factomd* with TLS mode ON - `./factomd -tls true`. You also should pass in the location to your certificate when instantiating the adapter, and change the URL to HTTPS:

```php
$url = 'https://localhost:8088/v2';
$certLocation = '~/.factom/m2/factomdAPIpub.cert';
$adapter = new FactomAPIAdapter($url, $certLocation);
```

If you want to interact with the API with a username and password, make sure you're running *factomd* with a username and password defined - `./factomd -rpcuser <username> -rpcpass <password>`. You can run this with a certificate as well if you wish.

```php
$url = 'https://localhost:8088/v2';
$username = 'user';
$password = 'password';
$adapter = new FactomAPIAdapter($url, null, $username, $password);
```

#### Run the API Method With Your Parameters! ####

API methods are outlined here: https://docs.factom.com/api

```php
$method = 'POST';
$result = $adapter->call('transaction', $method, array('hash' => '64251aa63e011f803c883acf2342d784b405afa59e24d9c5506c84f6c91bf18b'));
```

#### Firing GET Requests ####

Certain API methods use the GET method rather than POST.  For example, the 'address' method within *factom-walletd*: https://docs.factom.com/api#address. You can run this easily:

```php
$method = 'GET';
$result = $adapter->call('address', $method, array('address' => 'FA2jK2HcLnRdS94dEcU27rF3meoJfpUcZPSinpb7AwQvPRY6RL1Q'));
```

That's all there is to it. If you are getting cURL issues, it may be because you do not have the PHP cURL lib installed.

Tests
----------

Sorry! None right now.

Known Issues
----------

- Currently if you are using a secure connection (TLS) and you provide the path to a certificate, we ignore cURL self-signing certificate warnings via setting `CURLOPT_SSL_VERIFYPEER` to `false`.
- The debug API endpoints are not accessible via this wrapper
