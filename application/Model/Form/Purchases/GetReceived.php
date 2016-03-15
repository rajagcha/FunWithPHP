<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to get received purchases
 */
class Model_Form_Purchases_GetReceived extends Model_Form
{
	/**
	 * @override See Model_Form for more details
	 */
	public function process(Zend_Controller_Request_Abstract $request)
	{
		/** Validate dates **/
		$wrong = false;
		$fromDate = trim($request->getPost('from_date'));
		if ($fromDate == '') {
			$this->addError('form', '"From date" is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$this->fromDate = new Zend_Date($fromDate, 'MMM dd, yyyy');
			if (!Zend_Date::isDate($this->fromDate)) {
				$this->addError('form', '"From date" is not a valid date');
				$wrong = true;
			}
		}

		$toDate = trim($request->getPost('to_date'));
		if ($toDate == '') {
			$this->addError('form', '"To date" is not a valid date');
			$wrong = true;
		}
		if (!$wrong) {
			$toDate = $request->getPost('to_date');
			$this->toDate = new Zend_Date($toDate, 'MMM dd, yyyy');
			if (!Zend_Date::isDate($this->toDate)) {
				$this->addError('form', '"To date" is not a valid date');
				$wrong = true;
			}
		}
		if (!$wrong) {
			if ($this->fromDate > $this->toDate) {
				$this->addError('form', '"From date" can\'t be later than "To date"');
			}
		}
		return !$this->hasError();
	}
}
