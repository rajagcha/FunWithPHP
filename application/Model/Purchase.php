<?php
/**
 * @package models
 * @subpackage core
 */
/**
 * Class to represent a purchase object.
 */
class Model_Purchase
{
	public $pid;
	public $vendor;
	public $date;
	public $tax;
	public $arrived;
	public $items;

	public function __construct()
	{
		$this->arrived = false;
		$this->items = array();
	}

	/**
	 * Get the total final price before tax of the order.
	 * @return double total amount before tax
	 */
	public function getSubTotal()
	{
		$sum = 0.0;
		foreach ($this->items as $item) {
			$sum += $item->qty * $item->vUPrice;
		}
		return $sum;
	}

	/**
	 * Get the total charge of the order.
	 * @return double total charge
	 */
	public function getTotal()
	{
		$subTotal = $this->getSubTotal();
		return $subTotal + $subTotal * $this->tax / 100.0;
	}
}
