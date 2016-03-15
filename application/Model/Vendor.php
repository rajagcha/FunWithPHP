<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * This class represents a vendor object.
 */
class Model_Vendor
{
	public $vid;
	public $name;
	public $phone;
	public $address, $city, $state, $zip;
	public $suppliedItems;

	public function __construct()
	{
		$this->suppliedItems = array();
	}

	/**
	 * Get the phone number in the format (123) 456-7890
	 * @return String formated phone number
	 */
	public function getPhone()
	{
		return sprintf('(%s) %s-%s', substr($this->phone, 0, 3),
			substr($this->phone, 3, 3), substr($this->phone, 6));
	}

	/**
	 * Checks if this vendor supplies the given item code
	 * @param String $code item code
	 * @return bool true or false
	 */
	public function hasItem($code)
	{
		foreach ($this->suppliedItems as $item) {
			if ($item->itemCode == strtoupper(trim($code))) return true;
		}
		return false;
	}

	/**
	 * Get the vendor unit price of the given item
	 * @param String $code item code
	 * @return double vendor unit price or -1 if this vendor does not
	 * 			supply the item code.
	 */
	public function getVUPrice($code)
	{
		foreach ($this->suppliedItems as $item) {
			if ($item->itemCode == strtoupper(trim($code)))
				return $item->vUPrice;
		}
		return -1;
	}
}
