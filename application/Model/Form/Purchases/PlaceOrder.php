<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to place an order to vendor
 */
class Model_Form_Purchases_PlaceOrder extends Model_Form
{
	/**
	 * @override See Model_Form for more details
	 */
	public function process(Zend_Controller_Request_Abstract $request)
	{
		/** Validate date **/
		$wrong = false;
		$date = $request->getPost('date');
		if ($date == '') {
			$this->addError('form', 'Order date is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$this->date = new Zend_Date($date, 'MMM dd, yyyy');
			if (!Zend_Date::isDate($this->date)) {
				$this->addError('form', 'Order date is not a valid date');
			}
		}

		/** Validate vendor **/
		$this->vid = (int) $request->getPost('vendor');

		/** Validate item codes, qty **/
		$tmp = $request->getPost('item_codes');
		if (!$tmp) {
			$this->addError('form', 'There is no item to order');
		}
		else {
			$qty = $request->getPost('qty');
			$itemCodes = array();
			$itemQties = array();
			for ($i = 0; $i < count($tmp); $i++) {
				$code = strtoupper(trim($tmp[$i]));
				$itemQty = (int) $qty[$i];
				if ($code != '') {
					$itemCodes[] = $code;
					$itemQties[] = $itemQty;
				}
			}
			$this->itemCodes = $itemCodes;
			$this->qties = $itemQties;
		}

		/** Validate tax **/
		$this->tax = floatval($request->getPost('tax'));

		return !$this->hasError();
	}
}
