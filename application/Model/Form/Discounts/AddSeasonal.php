<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to add a new seasonal discount
 */
class Model_Form_Discounts_AddSeasonal extends Model_Form
{
	/**
	 * @override See Model_Form for more details
	 */
	public function process(Zend_Controller_Request_Abstract $request)
	{
		/** Validate seasonal name **/
		$this->seasonal_name = trim($request->getPost('seasonal_name'));
		if ($this->seasonal_name == '') {
			$this->addError('form', 'Seasonal name cannot be empty');
		}

		/** Validate discount rate **/
		$this->seasonal_rate = floatval($request->getPost('seasonal_rate'));
		if ($this->seasonal_rate <= 0 || $this->seasonal_rate > 100) {
			$this->addError('form', 'Discount rate must be > 0 and <= 100');
		}

		/** Validate date **/
		$wrong = false;
		$fromDate = $request->getPost('seasonal_from_date');
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
		$toDate = $request->getPost('seasonal_to_date');
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

		/** Validate selected items **/
		$selectedItems = $request->getPost('seasonal_items');
		if (count($selectedItems) == 0) {
			$this->addError('form', 'No item is selected');
		}
		else {
			if (!is_array($selectedItems))	$selectedItems = array($selectedItems);
			$tmp = array();
			foreach ($selectedItems as $code) {
				$tmp[] = strtoupper(trim($code));
			}
			$this->items = $tmp;
		}

		return !$this->hasError();
	}
}
