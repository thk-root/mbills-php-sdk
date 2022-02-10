# mBills PHP SDK

> mBills PHP SDK for easy integration into your PHP-powered websites

[![MIT License](https://img.shields.io/packagist/l/cocur/slugify.svg)](http://opensource.org/licenses/MIT)

Please check [mBills's official website](https://www.mbills.si/) and
their [documentation](https://mbillsonlinepaymentsapi.docs.apiary.io/#introduction).

## Features

- Handles authentication with the mBills API.
- Submits a payment request to the API.
- Provides logic for payment response / validation.
- Built-in nonce tokens using MySQL storage.
- PHP 7.0 or higher.

## Installation

mBills PHP SDK requires CURL, JSON and OPENSSL extensions to be present on the system. Download the files and require
MBills.class.php.

## Usage

Examples in code can be found at example.php (request payment), example_webhook.php (webhook call).

Testing example:

```php
$config = include __DIR__ . '/config.php'; //Configuration, if you wish to use it
$mbills = new MBillsTest(
    Db::getFromConfig($config), //Or use new Db(...) (required)
    MBillsAuth::getFromConfig($config), //Or use new MBillsAuth(...) (required)
    100, //Amount to be paid in cents (required)
    'EUR', //Currency (required)
    'https://YOUR_WEBSITE_URL/lib/mbills-php-sdk/example_webhook.php' //Webhook URL (required)
);
```

Production example:

```php
$config = include __DIR__ . '/config.php'; //Configuration, if you wish to use it
$mbills = new MBills(
    Db::getFromConfig($config), //Or use new Db(...) (required)
    MBillsAuth::getFromConfig($config), //Or use new MBillsAuth(...) (required)
    'Online payment', //Payment purpose (required)
    100, //Amount to be paid in cents (required)
    'EUR', //Currency (required)
    'https://YOUR_WEBSITE_URL/lib/mbills-php-sdk/example_webhook.php', //Webhook URL (required)
    'MyApp' //Application name (optional)
    //There are additional parameters, check MBills constructor and phpdocs for elaboration
);
```

Requesting payment:

```php
if($mbills->requestPayment()) { //If request was made successfully
    $deeplink = $mbills->getResponseDeeplink(); //Deep link (for mobile)
    $qr = $mbills->getResponseQR(); //QR code (for desktop)
    
    //Just an example print
    echo '<a href="' . $deeplink . '" target="_blank"><img height="150" width="150" src="' . $qr . '"/><br>Click QR code for deeplink</a>';
}
```

Response / return example:

```php
//Obtain forwarded nonce
$nonce = $_GET['nonce'] ?? null;

$mbills =
    new MbillsReturn(
        Db::getFromConfig($config),
        MBillsAuth::getFromConfig($config)
    );

//Obtain transaction ID from nonce. Second argument of the function should be set to TRUE if you want to automatically delete nonce once read
$transactionId = $mbills->getTransactionFromNonce($nonce, false);

//Check for transaction's status
echo !empty($transactionId) && $mbills->isTransactionPaid($transactionId) ? 'Transaction sucessfully paid' : 'Transaction not paid';
````

Configuration file (if you intend to use it):

```php
return [

    'api_key' => 'YOUR MBILLS API KEY', //mBills API key
    'api_secret' => 'YOUR MBILLS API SECRET', //mBills API secret
    'is_production' => false, //Whether you want to use production or testing

    'db_host' => '127.0.0.1', //Database host
    'db_name' => 'mbills', //Database name
    'db_table' => 'payment_nonce', //Database table for storing nonce
    'db_user' => 'user', //Database username
    'db_psw' => 'psw', //Database password
];
```

It's recommended you configure your MySQL database accordingly (or in similar fashion):

```sql
CREATE TABLE `payment_nonce`
(
    `id`                   int UNSIGNED NOT NULL,
    `token`                varchar(500) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `amount`               int                                                       NOT NULL,
    `date_created`         datetime                                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nonce`                varchar(500) CHARACTER SET ascii COLLATE ascii_general_ci          DEFAULT NULL,
    `transaction_id`       varchar(500) CHARACTER SET ascii COLLATE ascii_general_ci          DEFAULT NULL,
    `payment_token_number` varchar(500) CHARACTER SET ascii COLLATE ascii_general_ci          DEFAULT NULL,
    `signature`            varchar(2000) CHARACTER SET ascii COLLATE ascii_general_ci         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `payment_nonce`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `payment_nonce` MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
```

If you want to modify table name to anything else, you can do it as constructed below or in your configuration file, if
you use Db::getFromConfig:

```php
new Db('HOST', 'DBNAME', 'USER', 'PASSWORD', 'PAYMENT_NONCE_STORE_TABLE_NAME');
```

(obviously don't forget to change the table name in database & SQL)

## License

The MIT License (MIT)

Copyright (c) 2022 THK

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
