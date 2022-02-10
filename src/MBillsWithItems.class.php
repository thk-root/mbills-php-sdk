<?php

	namespace MBills\SDK;

	/**
	 * MBillsWithItems
	 *
	 * @package MBills\SDK
	 * @author THK <tilen@thk.si>
	 * @author Kajetan Žist <kajetan@enjoi.si>
	 * @license http://www.opensource.org/licenses/MIT The MIT License
	 */
	class MBillsWithItems extends MBills {


		/**
		 * MBillsWithItems constructor
		 *
		 * @param Db $db
		 * @param MBillsAuth $auth
		 * @param string $paymentPurpose
		 * @param string $currency
		 * @param string $webhook
		 * @param string|null $appName
		 * @param string|null $orderId
		 * @param string|null $paymentReference
		 * @param string|null $channelId
		 */
		public function __construct(
			Db         $db,
			MBillsAuth $auth,
			string     $paymentPurpose,
			string     $currency,
			string     $webhook,
			?string    $appName = null,
			?string    $orderId = null,
			?string    $paymentReference = null,
			?string    $channelId = null
		) {
			parent::__construct(
				$db,
				$auth,
				$paymentPurpose,
				0,
				$currency,
				$webhook,
				$appName,
				$orderId,
				$paymentReference,
				$channelId,
				true
			);
		}
	}
