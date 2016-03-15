<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * Class to represent a coupon.
 */
class Model_Coupon extends Model_Discount
{
	public $code;
	public $used;
	public $item;

	public function __construct()
	{
		parent::__construct();
		$this->used = false;
		$this->item = null;
	}

	public function getStatus()
	{
		if ($this->used) return Model_Discount::USED;
		else return parent::getStatus();
	}

	public function isRemovable()
	{
		$status = $this->getStatus();
		if ($status == Model_Discount::EXPIRED) return true;
		else return parent::isRemovable();
	}
}
