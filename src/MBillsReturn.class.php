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
	class MBillsReturn extends MBills {


		/**
		 * MBillsReturn constructor
		 *
		 * @param Db $db
		 * @param MBillsAuth $auth
		 */
		public function __construct(Db $db, MBillsAuth $auth) {
			parent::__construct(
				$db,
				$auth,
				'',
				100,
				'EUR'
			);
		}

	}
