<?php

	namespace MBills\SDK;

	require_once __DIR__ . '/Db.class.php';
	require_once __DIR__ . '/MBillsAuth.class.php';
	require_once __DIR__ . '/MBillsTest.class.php';
	require_once __DIR__ . '/MBillsWithItems.class.php';
	require_once __DIR__ . '/MBillsReturn.class.php';

	use PDO;

	/**
	 * MBills
	 *
	 * @package MBills\SDK
	 * @author THK <tilen@thk.si>
	 * @author Kajetan Žist <kajetan@enjoi.si>
	 * @license http://www.opensource.org/licenses/MIT The MIT License
	 */
	class MBills {


		/**
		 * @var Db|null database instance
		 */
		private $db;


		/**
		 * @var string|null client ID
		 */
		private $clientId;


		/**
		 * @var string|null client secret key
		 */
		private $clientSecret;


		/**
		 * @var bool whether we're using production version or not
		 */
		private $isProduction;


		/**
		 * @var array API urls
		 */
		private $apiUrls = ['https://mbills-demo-web.mbills.si', 'https://api.mbills.si'];


		/**
		 * @var array list of supported currencies
		 */
		private const SUPPORTED_CURRENCIES = ['EUR'];


		/**
		 * @var string transaction creation endpoint key
		 */
		private const ENDPOINT_CREATE_TRANSACTION = 'transaction/sale';


		/**
		 * @var string test webhook endpoint key
		 */
		private const ENDPOINT_TEST_WEBHOOK = 'system/testwebhook';


		/**
		 * @var string test endpoint key
		 */
		private const ENDPOINT_TEST = 'system/test';


		/**
		 * @var string test endpoint key
		 */
		private const ENDPOINT_TRANSACTION_STATUS = 'transaction/#/status';


		/**
		 * @var string|null active API url
		 */
		private $apiUrl;


		/**
		 * @var string API path
		 */
		private const API_PATH = '/MBillsWS/API/v1/';


		/** @var int sale    none (timeout)    -2 [timeout] */
		public const TRANSACTION_STATUS_TIMEOUT = -2;


		/** @var int sale    user rejects    -1 [rejected] */
		public const TRANSACTION_STATUS_USER_REJECT = -1;


		/** @var int sale (capture = true)    user pays    3 [paid] */
		public const TRANSACTION_STATUS_USER_PAID = 3;


		/** @var int sale (capture = false)    user confirms    2 [authorized] */
		public const TRANSACTION_STATUS_USER_CONFIRMED = 2;


		/** @var int void    n/a    4 [voided] */
		public const TRANSACTION_STATUS_VOIDED = 4;


		/** @var int capture    n/a    3 [paid] */
		public const TRANSACTION_STATUS_CAPTURED = 3;


		/**
		 * @var string|null URL to which request will be redirected upon response
		 * Example: https://api.yoursite.com/mbills-php-sdk/example_return.php
		 */
		private $webhookUrl;


		/**
		 * @var string|null app name
		 * Example: My application
		 */
		private $appName;


		/**
		 * @var string Purpose of the payment, that will be displayed in the mBills application during the confirmation
		 *     of the payment. Example: Online payment
		 */
		private $paymentPurpose;


		/**
		 * @var string|null The order id for this payment in your system. Together with channelid this should be the
		 *     unique id within your system. Example: 124134987
		 */
		private $orderId;


		/**
		 * @var string|null Reference id used for payment in some countries and needs to be according to country rules.
		 *     Country rules are as follow:
		 *        - Slovenia: see here. If you do not provide the data, a default date of payment will be used prefixed
		 *            with SI00 (model). I.e. SI0015092015
		 */
		private $paymentReference;


		/**
		 * @var int Amount of the transaction in hundreds.
		 * Example: 1000
		 */
		private $amountCents;


		/**
		 * @var string Currency of the transaction
		 * Example: EUR
		 */
		private $currency;


		/**
		 * @var string|null If you have multiple shops/points within your organization on the same API key you can use
		 *     this to guarantee uniqueness for your order ids. Example: Shop1
		 */
		private $channelId;


		/**
		 * @var null|array latest request's data
		 */
		private $latestResponse = null;


		/**
		 * @var null|array items list. If empty, only total amount is used
		 */
		private $items;


		/**
		 * MBills constructor
		 *
		 * @param Db $db
		 * @param MBillsAuth $auth
		 * @param string $paymentPurpose
		 * @param int $amountCents
		 * @param string $currency
		 * @param string|null $webhook
		 * @param string|null $appName
		 * @param string|null $orderId
		 * @param string|null $paymentReference
		 * @param string|null $channelId
		 * @param bool $useItems
		 */
		public function __construct(

			Db         $db,
			MBillsAuth $auth,
			string     $paymentPurpose,
			int        $amountCents,
			string     $currency,
			?string    $webhook = null,
			?string    $appName = null,
			?string    $orderId = null,
			?string    $paymentReference = null,
			?string    $channelId = null,
			bool       $useItems = false

		) {

			$this->db = $db;
			$this->clientId = $auth->getApiKey();
			$this->clientSecret = $auth->getApiSecret();
			$this->isProduction = $auth->isProduction();
			$this->apiUrl = $this->apiUrls[$this->isProduction ? 1 : 0];
			$this->appName = $appName;
			$this->webhookUrl = $webhook;
			$this->paymentPurpose = $paymentPurpose;
			$this->currency = strtoupper($currency);
			$this->orderId = $orderId;
			$this->paymentReference = $paymentReference;
			$this->channelId = $channelId;
			$this->amountCents = $amountCents;
			$this->items = $useItems ? [] : null;
		}


		/**
		 * Fetches payment redirect
		 *
		 * @return bool
		 */
		public function requestPayment() : bool {

			//Reset latest response
			$this->latestResponse = null;

			//Invalid currency
			if(!in_array($this->getCurrency(), self::SUPPORTED_CURRENCIES)) {
				return false;
			}

			//Use items, process them
			if($this->items !== null) {

				$amount = 0;
				$itemNames = [];
				$displayPrice = false;

				foreach($this->items as $item) {
					$amount += $item['total_price'];
					$itemNames[] = $item['quantity'] . 'x ' . $item['name'] . ($displayPrice ? (' (' . number_format($item['price'] / 100, 2, ',', '.') . '€)') : '');
				}

				//Generate purpose from purpose prefix + items
				$purpose = (!empty($this->getPaymentPurpose()) ? ($this->getPaymentPurpose() . ' -- ') : '') . implode(', ', $itemNames);

			} else {

				$amount = $this->amountCents;
				$purpose = $this->getPaymentPurpose();

			}

			//Minimal allowed is 0.1 €
			if($amount < 10) {
				return false;
			}

			//Create nonce
			$requestPaymentToken = $this->generatePaymentToken();
			$nonce = $this->generateNonce();

			//Store the token
			$storedNonceId = $this->storeToken($nonce, $requestPaymentToken, $amount);
			if(empty($storedNonceId)) return false;

			//Create complete API URL
			$url = $this->getApiUrlWithPath() . self::ENDPOINT_CREATE_TRANSACTION;

			//Create auth token
			$authToken = $this->generateAuthToken($url, $requestPaymentToken);

			//Submit charges
			$res =
				$this->post($url, $authToken,
					[
						'amount' => $amount,
						'currency' => $this->getCurrency(),
						'purpose' => $purpose,
						'paymentreference' => $this->getPaymentReference(),
						'orderid' => $this->getOrderId(),
						'channelid' => $this->getChannelId(),
						'applicationname' => $this->getAppName(),
						'webhookurl' => $this->getWebhookWithNonce($nonce)
					]
				);

			//Response sucessfully obtained, update nonce
			if($res !== null && $res['statusdescription'] === 'OK') {

				//Update token and prevent going forwards in case update failed
				if(!$this->updateTokenOnRequest($storedNonceId, $res['transactionid'], $res['paymenttokennumber'], $res['auth']['signature'])) {
					return false;
				}

				//Store response
				$this->latestResponse = array_merge($res, [
					'deeplink_url' => ($this->isProduction() ? 'mbills' : 'mbillsdemo') . '://www.mbills.si/dl/?type=1&token=' . $res['paymenttokennumber'],
					'request_nonce' => $nonce
				]);

				return true;

			} else {

				//Delete nonce
				/** @noinspection SqlDialectInspection */
				$this
					->getDb()
					->get()
					->prepare('DELETE FROM `' . $this->getDb()->getStorageTbl() . '` WHERE `id`=:id')
					->execute([
						'id' => $storedNonceId
					]);
			}

			return false;
		}


		/**
		 * @param string $url
		 * @param string|null $nonce
		 *
		 * @return string
		 */
		private function generateAuthToken(string $url, ?string $nonce) : string {
			if($nonce === null) $nonce = $this->generatePaymentToken();
			$username = $this->getClientId() . '.' . $nonce . '.' . time();
			$password = hash('sha256', $username . $this->getClientSecret() . $url);
			return base64_encode($username . ':' . $password);
		}


		/**
		 * Stores nonce token
		 *
		 * @param string $nonce
		 * @param string $token
		 * @param int $amountCents
		 *
		 * @return int|null
		 */
		public function storeToken(string $nonce, string $token, int $amountCents) : ?int {

			$pdo = $this->getDb()->get();

			/** @noinspection SqlDialectInspection */
			$stmt =
				$pdo
					->prepare(
						'INSERT INTO `' . $this->getDb()->getStorageTbl() . '`(`token`, `amount`, `nonce`) VALUES (:token, :amount, :nonce)'
					);

			$stmt->bindParam(':token', $token);
			$stmt->bindParam(':nonce', $nonce);
			$stmt->bindParam(':amount', $amountCents, PDO::PARAM_INT);

			return $stmt->execute() ? $pdo->lastInsertId() : null;
		}


		/**
		 * Updates nonce data
		 *
		 * @param int $nonceId
		 * @param string $transactionId
		 * @param string $paymentTokenNumber
		 * @param string $signature
		 *
		 * @return bool
		 */
		public function updateTokenOnRequest(int $nonceId, string $transactionId, string $paymentTokenNumber, string $signature) : bool {

			/** @noinspection SqlDialectInspection */
			$stmt =
				$this
					->getDb()
					->get()
					->prepare(
						'UPDATE `' . $this->getDb()->getStorageTbl() . '` SET `transaction_id`=:tid, `payment_token_number`=:pn, `signature`=:sig WHERE `id`=:id'
					);

			$stmt->bindParam(':tid', $transactionId);
			$stmt->bindParam(':pn', $paymentTokenNumber);
			$stmt->bindParam(':sig', $signature);
			$stmt->bindParam(':id', $nonceId, PDO::PARAM_INT);

			return $stmt->execute();
		}


		/**
		 * Makes a post request to the API
		 *
		 * @param string $url
		 * @param string $authToken
		 * @param array $data
		 *
		 * @return array|null
		 */
		private function post(string $url, string $authToken, array $data) : ?array {

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);

			if(count($data) > 0) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($ch, CURLOPT_POST, true);
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Authorization: Basic ' . $authToken
			]);

			$response = curl_exec($ch);
			curl_close($ch);

			return empty($response) || $response[0] !== '{' ? null : json_decode($response, true);
		}


		/**
		 * Tests webhook call
		 */
		public function testWebhook() : void {
			$url = $this->getApiUrlWithPath() . self::ENDPOINT_TEST_WEBHOOK;
			$this->post($url, $this->generateAuthToken($url, null), []);
		}


		/**
		 * Tests connection call
		 *
		 * @return bool
		 */
		public function test() : bool {
			$url = $this->getApiUrlWithPath() . self::ENDPOINT_TEST;
			$response = $this->post($url, $this->generateAuthToken($url, null), []);
			return $response !== null && isset($response['transactionid']);
		}


		/**
		 * @param string $transactionId
		 *
		 * @return array|null
		 */
		public function getTransactionData(string $transactionId) : ?array {
			if(empty($transactionId)) return null;
			$url = $this->getApiUrlWithPath() . str_replace('#', $transactionId, self::ENDPOINT_TRANSACTION_STATUS);
			$response = $this->post($url, $this->generateAuthToken($url, null), []);
			return $response !== null ? $response : null;
		}


		/**
		 * @param string $transactionId
		 *
		 * @return bool
		 */
		public function isTransactionPaid(string $transactionId) : bool {

			$data = $this->getTransactionData($transactionId);
			if($data === null) return false;

			return ($data['status'] ?? null) == self::TRANSACTION_STATUS_USER_PAID;
		}


		/**
		 * @param string $nonce
		 * @param bool $automaticallyDeleteNonce
		 *
		 * @return string|null
		 */
		public function getTransactionFromNonce(string $nonce, bool $automaticallyDeleteNonce = true) : ?string {

			if(empty($nonce)) return null;

			/** @noinspection SqlDialectInspection */
			$stmt =
				$this
					->getDb()
					->get()
					->prepare(
						'SELECT `transaction_id` AS t, `id` AS id FROM `' . $this->getDb()->getStorageTbl() . '` WHERE `nonce`=:nonce'
					);

			$stmt->bindParam(':nonce', $nonce);
			if(!$stmt->execute()) return null;

			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			$nonceData = empty($data) ? null : $data;

			if($nonceData === null) return null;

			//Delete nonce
			if($automaticallyDeleteNonce) {
				$this
					->getDb()
					->get()
					->prepare('DELETE FROM `' . $this->getDb()->getStorageTbl() . '` WHERE `id`=:id')
					->execute([
						'id' => $nonceData['id']
					]);
			}

			return $nonceData['t'];
		}


		/**
		 * @return $this
		 */
		public function addItem(string $itemName, int $itemPriceCents, int $quantity = 1) : MBills {

			if($this->items !== null) {
				$this->items[] = [
					'name' => $itemName,
					'price' => $itemPriceCents,
					'total_price' => $itemPriceCents * $quantity,
					'quantity' => $quantity
				];
			}

			return $this;
		}


		/**
		 * @return string|null
		 */
		public function getClientId() : ?string {
			return $this->clientId;
		}


		/**
		 * @return string|null
		 */
		public function getClientSecret() : ?string {
			return $this->clientSecret;
		}


		/**
		 * @return string|null
		 */
		public function getApiUrl() : ?string {
			return $this->apiUrl;
		}


		/**
		 * @return string
		 */
		public function getApiUrlWithPath() : string {
			return $this->apiUrl . self::API_PATH;
		}


		/**
		 * @return string|null
		 */
		public function getWebhook() : ?string {
			return $this->webhookUrl;
		}


		/**
		 * @param string $nonce
		 *
		 * @return string|null
		 */
		public function getWebhookWithNonce(string $nonce) : ?string {

			$wh = $this->getWebhook();
			if(empty($wh)) return null;

			$wh .= (strpos($wh, '?') === false ? '?' : '&') . 'nonce=' . $nonce;
			return $wh;
		}


		/**
		 * @return Db
		 */
		public function getDb() : Db {
			return $this->db;
		}


		/**
		 * @return string|null
		 */
		public function getAppName() : ?string {
			return $this->appName;
		}


		/**
		 * @return string|null
		 */
		public function getPaymentPurpose() : ?string {
			return $this->paymentPurpose;
		}


		/**
		 * @return string|null
		 */
		public function getOrderId() : ?string {
			return $this->orderId;
		}


		/**
		 * @return string|null
		 */
		public function getPaymentReference() : ?string {
			return $this->paymentReference;
		}


		/**
		 * @return int
		 */
		public function getAmountCents() : int {
			return $this->amountCents;
		}


		/**
		 * @return string
		 */
		public function getCurrency() : string {
			return $this->currency;
		}


		/**
		 * @return string|null
		 */
		public function getChannelId() : ?string {
			return $this->channelId;
		}


		/**
		 * Generates numeric payment token
		 *
		 * @return int
		 */
		private function generatePaymentToken() : int {
			return rand(10000000, 1999999999);
		}


		/**
		 * Generates nonce
		 *
		 * @return string|null
		 */
		private function generateNonce() : ?string {
			return bin2hex(openssl_random_pseudo_bytes(30));
		}


		/**
		 * @return bool
		 */
		public function isProduction() : bool {
			return $this->isProduction;
		}


		/**
		 * @param string $key
		 *
		 * @return mixed|null
		 */
		public function getResponse(string $key) {
			return $this->latestResponse === null || !isset($this->latestResponse[$key]) ? null : $this->latestResponse[$key];
		}


		/**
		 * @return string|null
		 */
		public function getReponseNonce() : ?string {
			return $this->getResponse('request_nonce');
		}


		/**
		 * @return string|null
		 */
		public function getResponseDeeplink() : ?string {
			return $this->getResponse('deeplink_url');
		}


		/**
		 * @param bool $svg
		 *
		 * @return string|null
		 */
		public function getResponseQR(bool $svg = true) : ?string {
			$paymentToken = $this->getResponse('paymenttokennumber');
			if(empty($paymentToken)) return null;
			return 'https://qr' . ($this->isProduction() ? '' : 'demo') . '.mbills.si/qr/' . ($svg ? 'svg' : 'png') . '/type1/' . $paymentToken;
		}
	}
