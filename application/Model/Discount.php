<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * Class to represent a discount object.
 */
class Model_Discount
{
	const COMING = 'Coming';
	const EXPIRED = 'Expired';
	const VALID = 'Valid';
	const USED = 'Used';
	public $fromDate;
	public $toDate;
	public $rate;

	public function __construct() {}

	public function getStatus()
	{
		$today = Zend_Date::now();
		if ($today < $this->fromDate) return Model_Discount::COMING;
		else if ($today > $this->toDate) return Model_Discount::EXPIRED;
		else return Model_Discount::VALID;
	}

	public function isEditable()
	{
		$status = $this->getStatus();
		return $status == Model_Discount::COMING;
	}

	public function isRemovable()
	{
		$status = $this->getStatus();
		return $status == Model_Discount::COMING;
	}
}
