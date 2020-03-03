# Xero-facade-PHP

This file is to be used as a complement of this package:

https://github.com/calcinai/xero-php

To install using composer:

composer require calcinai/xero-php

Set your Config file:

## Example

```
$config = new \stdClass;
$config->name = 'Xero Account 1';
$config->xero_consumer_key = env('XERO_CONSUMER_KEY', 'xxxxxx');
$config->xero_consumer_secret = env('XERO_CONSUMER_SECRET', 'xxxxxxx');
$config->note = new \stdClass;
$config->xero_callback = env('XERO_CALLBACK', 'http://website.com');

$xero = new XeroFacade();
$xero->getContacts

// dont forget to set the path to your cert:
env('XERO_CERT_PATH') 

```


// Add The facade, set your shh keys and use it

:)
