<?php

	namespace MBills\SDK;

	use PDO;
	use PDOException;

	/**
	 * Db
	 *
	 * @package MBills\SDK
	 * @author THK <tilen@thk.si>
	 * @author Kajetan Žist <kajetan@enjoi.si>
	 * @license http://www.opensource.org/licenses/MIT The MIT License
	 */
	class Db {


		/**
		 * @var null|PDO PDO instance
		 */
		private $instance = null;


		/**
		 * @var string name of the table where nonces are stored
		 */
		private $storageTbl = 'payment_nonce';


		/**
		 * Db constructor
		 *
		 * @param string|null $dbHost
		 * @param string|null $dbName
		 * @param string|null $dbUser
		 * @param string|null $dbPassword
		 * @param string|null $storageTbl
		 */
		public function __construct(

			?string $dbHost = null,
			?string $dbName = null,
			?string $dbUser = null,
			?string $dbPassword = null,
			?string $storageTbl = null

		) {

			if(!empty($storageTbl)) $this->storageTbl = $storageTbl;

			//Create PDO instance, if given
			if(!empty($dbHost) && !empty($dbName)) {
				$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
				];
				try {
					$this->instance = new PDO($dsn, $dbUser, $dbPassword, $options);
				} catch(PDOException $e) {
					throw new PDOException($e->getMessage(), (int) $e->getCode());
				}
			}
		}


		/**
		 * @param PDO $instance
		 *
		 * @return $this
		 */
		public function setPDO(PDO $instance) : Db {
			$this->instance = $instance;
			return $this;
		}


		/**
		 * @return PDO|null
		 */
		public function get() : ?PDO {
			return $this->instance;
		}


		/**
		 * @return string fetches storage table name
		 */
		public function getStorageTbl() : string {
			return $this->storageTbl;
		}


		/**
		 * @param array $config
		 *
		 * @return Db
		 */
		public static function getFromConfig(array $config) : Db {
			return new Db($config['db_host'], $config['db_name'], $config['db_user'], $config['db_psw'], $config['db_table']);
		}
	}
