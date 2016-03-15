<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to add a new coupon
 */
class Model_Form_Discounts_AddCoupon extends Model_Form
{
	/**
	 * @override See Model_Form for more details
	 */
	public function process(Zend_Controller_Request_Abstract $request)
	{
		/** Validate coupon code **/
		$this->couponCode = strtoupper(trim($request->getPost('coupon_code')));
		if ($this->couponCode == '') {
			$this->addError('form', 'Empty coupon code is invalid');
		}

		/** Validate Item code **/
		$this->itemCode = strtoupper(trim($request->getPost('item_code')));

		/** Validate discount rate **/
		$this->rate = floatval($request->getPost('rate'));
		if (!($this->rate > 0 && $this->rate <= 100)) {
			$this->addError('form', 'Discount rate must be a number > 0 and <= 100');
		}

		/** Validate date **/
		$wrong = false;
		$fromDate = $request->getPost('from_date');
		if ($fromDate == '') {
			$this->addError('form', '"From date" is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$fromDate = new Zend_Date($fromDate, 'MMM dd, yyyy');
			$this->fromDate = $fromDate;
			if (!Zend_Date::isDate($fromDate)) {
				$this->addError('form', '"From date" is not a valid date');
				$wrong = true;
			}
		}
		$toDate = $request->getPost('to_date');
		if ($toDate == '') {
			$this->addError('form', '"To date" is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$toDate = new Zend_Date($toDate, 'MMM dd, yyyy');
			$this->toDate = $toDate;
			if (!Zend_Date::isDate($toDate)) {
				$this->addError('form', '"To date" is not a valid date');
			}
			else if ($toDate < Zend_Date::now()) {
				$this->addError('form', '"To date" can\'t be ealier than today');
			}
			else if ($fromDate > $toDate) {
				$this->addError('form', '"From date" can\t be later than "To date"');
			}
		}

		return !$this->hasError();
	}
}
