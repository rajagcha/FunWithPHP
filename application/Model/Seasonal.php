<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * Class to represent a seasonal discount.
 */
class Model_Seasonal extends Model_Discount
{
	public $sid;
	public $name;
	public $items;

	public function __construct()
	{
		parent::__construct();
		$items = array();
	}

	public function hasItem($code)
	{
		foreach ($this->items as $item)
			if ($item->itemCode == $code) return true;
		return false;
	}
}
