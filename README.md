# Xero-facade-PHP

This file is to be used as a complement of this package:

https://github.com/calcinai/xero-php

To install using composer:

composer require calcinai/xero-php

Set your Config file:

```
/**
 * NEW XERO ACCOUNT DATA
 */
'xero_account_1' => [
    'xero' => [
        // API versions can be overridden if necessary for some reason.
        //'core_version'     => '2.0',
        //'payroll_version'  => '1.0',
        //'file_version'     => '1.0'
    ],
    'oauth' => [
        'callback'          => env('XERO_CALLBACK', 'http://website.com'),
        'consumer_key'      => env('XERO_CONSUMER_KEY', 'xxxxxx'),
        'consumer_secret'   => env('XERO_CONSUMER_SECRET', 'xxxxxxx'),
        //If you have issues passing the Authorization header, you can set it to append to the query string
        //'signature_location'    => \XeroPHP\Remote\OAuth\Client::SIGN_LOCATION_QUERY
        //For certs on disk or a string - allows anything that is valid with openssl_pkey_get_(private|public)
        'rsa_private_key'  => env('XERO_CERT_PATH', 'file:///var/www/html/cert/privatekey.pem')
    ],
    //These are raw curl options.  I didn't see the need to obfuscate these through methods
    'curl' => [
        CURLOPT_USERAGENT   => 'Xero Sync System',
    ]
],


```

// Add The facade, set your shh keys and use it

:)
