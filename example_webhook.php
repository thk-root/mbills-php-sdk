<?php

	use MBills\SDK\Db;
	use MBills\SDK\MBillsAuth;
	use MBills\SDK\MbillsReturn;

	require_once __DIR__ . '/src/MBills.class.php';

	$config = include __DIR__ . '/config.php';
	if(empty($config)) die('Please check your config file. Use config.example.php as a blueprint.');

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
