<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * Class to process the form to add a new vendor.
 */
class Model_Form_Vendor extends Model_Form
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @override See Model_Form class for more details
	 */
	public function process(Zend_Controller_Request_Abstract $request)
	{
		/** Validate vendor name **/
		$this->name = trim($request->getPost('name'));
		if ($this->name == '') {
			$this->addError('form', 'Vendor name is required');
		}

		/** Validate phone **/
		$this->phone = trim($request->getPost('phone'));
		if ($this->phone == '') {
			$this->addError('form', 'Phone number is required');
		}
		else if (strlen($this->phone) != 10 || !is_numeric($this->phone)) {
			$this->addError('form', 'Phone number is invalid');
		}
		
		/** Validate address, city, state, and zip **/
		$this->address = trim($request->getPost('address'));
		$this->city = trim($request->getPost('city'));
		$this->state = $request->getPost('state');
		$this->zip = trim($request->getPost('zip'));
		if ($this->address == '') {
			$this->addError('form', 'Address is required');
		}
		if ($this->city == '') {
			$this->addError('form', 'City is required');
		}
		if (strlen($this->state) != 2) {
			$this->addError('form', 'State is invalid');
		}
		if ($this->zip == '') {
			$this->addError('form', 'Zip is required');
		}
		else if (strlen($this->zip) != 5 || !is_numeric($this->zip)) {
			$this->addError('form', 'Zip is invalid');
		}

		/** Validate supplied items **/
		$tmp = $request->getPost('item_codes');
		if (!$tmp) {
			$this->addError('form', 'Supplied items are required');
		}
		else {
			$tmp = $request->getPost('item_codes');
			$itemCodes = array();
			foreach ($tmp as $code) {
				$code = trim($code);
				$itemCodes[] = $code;
			}
			$this->item_codes = $itemCodes;
			foreach ($itemCodes as $code) {
				if ($code == '') {
					$this->addError('form', 'Some Item Code is empty');
					break;
				}
			}
			$tmp = $request->getPost('item_names');
			$itemNames = array();
			foreach ($tmp as $name) {
				$name = trim($name);
				$itemNames[] = $name;
			}
			$this->item_names = $itemNames;
			foreach ($itemNames as $name) {
				if ($name == '') {
					$this->addError('form', 'Some Item Name is empty');
					break;
				}
			}
			$tmp = $request->getPost('v_uprices');
			$prices = array();
			foreach ($tmp as $p) {
				$p = floatval($p);
				$prices[] = $p;
			}
			$this->v_uprices = $prices;
			foreach ($prices as $p) {
				if ($p <= 0) {
					$this->addError('form', 'Some unit price is invalid');
				}
			}
		}

		/** Done validation **/
		return !$this->hasError();
	}
}
