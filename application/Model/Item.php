<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * This class represents an item object.
 */
class Model_Item
{
	public $itemCode;
	public $name;
	public $sUPrice;
	public $vUPrice;
	public $qty;
	public $coupon;
	public $seasonal;

	public function __construct()
	{
		$this->coupon = null;
		$this->seasonal = null;
	}

	public function computeStoreFinalPrice()
	{
		$finalPrice = $this->qty * $this->sUPrice;
		$discount = 0;
		if ($this->coupon != null)
			$discount += $this->coupon->rate;
		if ($this->seasonal != null)
			$discount += $this->seasonal->rate;
		return $finalPrice - $finalPrice * $discount / 100.0;
	}
}
