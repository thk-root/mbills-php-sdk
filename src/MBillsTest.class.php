<?php

	namespace MBills\SDK;

	/**
	 * MBillsTest
	 *
	 * @package MBills\SDK
	 * @author THK <tilen@thk.si>
	 * @author Kajetan Žist <kajetan@enjoi.si>
	 * @license http://www.opensource.org/licenses/MIT The MIT License
	 */
	class MBillsTest extends MBills {


		/**
		 * MBillsTest constructor
		 *
		 * @param Db $db
		 * @param MBillsAuth $auth
		 * @param int $amountCents
		 * @param string $currency
		 * @param string $webhook
		 * @param string $paymentPurpose
		 */
		public function __construct(Db $db, MBillsAuth $auth, int $amountCents, string $currency, string $webhook, string $paymentPurpose = 'Testing MBills online payment') {
			parent::__construct(
				$db,
				$auth,
				$paymentPurpose,
				$amountCents,
				$currency,
				$webhook
			);
		}

	}
