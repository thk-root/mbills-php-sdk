<?php

	use MBills\SDK\Db;
	use MBills\SDK\MBillsAuth;
	use MBills\SDK\MBillsTest;
	use MBills\SDK\MBillsWithItems;

	require_once __DIR__ . '/src/MBills.class.php';

	$config = include __DIR__ . '/config.php';
	if(empty($config)) die('Please check your config file. Use config.example.php as a blueprint.');

	//Test connection example
	$mbills =
		new MBillsTest(
			Db::getFromConfig($config),
			MBillsAuth::getFromConfig($config),
			100,
			'EUR',
			$config['webhook']
		);

	//This should only be used for onboarding / testing connection or access
	echo 'Connection to mBills: ' . ($mbills->test() ? 'OK' : 'FAIL') . '<br><br>';

	//Normal test example
	$mbills =
		new MBillsTest(
			Db::getFromConfig($config),
			MBillsAuth::getFromConfig($config),
			100,
			'EUR',
			$config['webhook']
		);

	//Example with items
	$mbills =
		new MBillsWithItems(
			Db::getFromConfig($config),
			MBillsAuth::getFromConfig($config),
			'',
			'EUR',
			$config['webhook']
		);

	$mbills->addItem('Pepsi', 120);

	if($mbills->requestPayment()) {

		$deeplink = $mbills->getResponseDeeplink();

	} else {

		die('Payment initialization failed');

	}

	echo '<a href="' . $deeplink . '" target="_blank"><img height="150" width="150" src="' . $mbills->getResponseQR() . '"/><br>Click QR code for deeplink</a>';
