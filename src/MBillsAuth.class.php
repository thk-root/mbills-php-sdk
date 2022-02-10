<?php

	namespace MBills\SDK;


	/**
	 * MBillsAuth
	 *
	 * @package MBills\SDK
	 * @author THK <tilen@thk.si>
	 * @author Kajetan Žist <kajetan@enjoi.si>
	 * @license http://www.opensource.org/licenses/MIT The MIT License
	 */
	class MBillsAuth {


		/**
		 * @var string API key
		 */
		private $apiKey;


		/**
		 * @var string API secret
		 */
		private $apiSecret;


		/**
		 * @var bool whether we're using production or testing server
		 */
		private $isProduction;


		/**
		 * @param string $apiKey
		 * @param string $apiSecret
		 * @param bool $isProduction
		 */
		public function __construct(

			string $apiKey,
			string $apiSecret,
			bool   $isProduction

		) {

			$this->apiKey = $apiKey;
			$this->apiSecret = $apiSecret;
			$this->isProduction = $isProduction;

		}


		/**
		 * @return string
		 */
		public function getApiKey() : string {
			return $this->apiKey;
		}


		/**
		 * @return string
		 */
		public function getApiSecret() : string {
			return $this->apiSecret;
		}


		/**
		 * @return bool
		 */
		public function isProduction() : bool {
			return $this->isProduction;
		}


		/**
		 * @param array $config
		 *
		 * @return MBillsAuth
		 */
		public static function getFromConfig(array $config) : MBillsAuth {
			return new MBillsAuth($config['api_key'], $config['api_secret'], $config['is_production']);
		}
	}
