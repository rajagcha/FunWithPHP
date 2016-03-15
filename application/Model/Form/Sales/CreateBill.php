<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to  create a bill
 */
class Model_Form_Sales_CreateBill extends Model_Form
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
			$this->addError('form', 'Bill date is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$this->date = new Zend_Date($date, 'MMM dd, yyyy');
			if (!Zend_Date::isDate($this->date)) {
				$this->addError('form', 'Bill date is not a valid date');
			}
		}

		/** Validate item codes, qty, coupon_codes **/
		$tmp = $request->getPost('item_codes');
		if (!$tmp) {
			$this->addError('form', 'There is no item in the bill');
		}
		else {
			$qty = $request->getPost('qty');
			$cpCodes = $request->getPost('coupon_codes');
			$itemCodes = array();
			$itemQties = array();
			$couponCodes = array();
			for ($i = 0; $i < count($tmp); $i++) {
				$code = strtoupper(trim($tmp[$i]));
				$itemQty = (int) $qty[$i];
				$coupCode = strtoupper(trim($cpCodes[$i]));
				if ($code != '') {
					$itemCodes[] = $code;
					$itemQties[] = $itemQty;
					$couponCodes[] = $coupCode;
				}
			}
			$this->itemCodes = $itemCodes;
			$this->qties = $itemQties;
			$this->couponCodes = $couponCodes;
		}

		/** Validate tax **/
		$this->tax = floatval($request->getPost('tax'));

		return !$this->hasError();
	}
}
