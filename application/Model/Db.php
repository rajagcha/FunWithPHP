<?php
/**
 * @package models
 * @subpackage db
 */
/**
 * Db interface class
 */
class Model_Db
{
	const SQL_DATE_ZFMT = 'yyyy-MM-dd';
	protected $db;

	/**
	 * Constructor
	 * @param Zend_Db_Adapter_Abstract $db
	 */
	public function __construct(Zend_Db_Adapter_Abstract $db)
	{
		$this->db = $db;
	}

	/**
	 * Used in sort by name
	 * @param Model_Vendor $v1
	 * @param Model_Vendor $v2
	 * @return int -1, 0, or 1
	 */
	static function _cmpVendor(Model_Vendor $v1, Model_Vendor $v2)
	{
		$name1 = strtoupper($v1->name);
		$name2 = strtoupper($v2->name);
		if ($name1 == $name2) { return 0; }
		return ($name1 < $name2) ? -1 : 1;
	}

	/**
	 * Get a list of vendors
	 * @param array $cond an array of conditions
	 * @return array an array of Vendor objects
	 */
	public function getVendors($cond = array())
	{
		$sql =
			'SELECT ' .
				'v.vid AS vid, v.name AS vname, phone, address, city, state, zip, ' .
				'vi.item_code AS item_code, v_uprice, ' .
				'i.name AS iname, qty, s_uprice '.
			'FROM ' .
				'Vendors v, Vendors_Items vi, Items i ' .
			'WHERE ' .
			'vi.vid = v.vid AND i.item_code = vi.item_code ';
		if (count($cond) > 0)
			$sql .= ' AND ' . join(' AND ', $cond);
		$sql .= ' ORDER BY v.vid ASC';
		$this->db->setFetchMode(Zend_Db::FETCH_ASSOC);
		$result = $this->db->fetchAll($sql);
		$vendors = array();
		$vid = -1;
		$v = new Model_Vendor();
		foreach ($result as $row) {
			if ($row['vid'] != $vid) {	// new vendor
				$vid = $row['vid'];
				$v = new Model_Vendor();
				$v->vid = $vid;
				$v->name = $row['vname'];
				$v->phone = $row['phone'];
				$v->address = $row['address'];
				$v->city = $row['city'];
				$v->state = $row['state'];
				$v->zip = $row['zip'];
				$vendors[] = $v;
			}
			$item = new Model_Item();
			$item->itemCode = $row['item_code'];
			$item->name = $row['iname'];
			$item->vUPrice = $row['v_uprice'];
			$item->sUPrice = $row['s_uprice'];
			$item->qty = $row['qty'];
			$v->suppliedItems[] = $item;
		}
		usort($vendors, array('Model_Db', '_cmpVendor'));
		return $vendors;
	}

	/**
	 * Get a vendor by vendor ID
	 * @param int $vid vendor ID
	 * @return Model_Vendor the vendor with given id or null
	 */
	public function getVendor($vid)
	{
		$cond = array(sprintf('v.vid = %d', (int) $vid));
		$vendors = $this->getVendors($cond);
		return (count($vendors) == 0) ? null : $vendors[0];
	}

	/**
	 * Insert a new vendor and the supplied items into db.
	 * @param Model_Vendor $vendor
	 * @return int the auto-generated vendor id
	 */
	public function addVendor(Model_Vendor $vendor)
	{
		// Insert vendor
		$data = array(
					'name' => $vendor->name,
					'phone' => $vendor->phone,
					'address' => $vendor->address,
					'city' => $vendor->city,
					'state' => $vendor->state,
					'zip' => $vendor->zip);
		$affectedRows = $this->db->insert('Vendors', $data);
		if ($affectedRows != 1) {
			return false;
		}
		$vid = $this->db->lastInsertId();

		// Insert supplied items to Vendors_Items
		foreach ($vendor->suppliedItems as $item) {
			$data = array(
						'vid' => $vid,
						'item_code' => $item->itemCode,
						'v_uprice' => $item->vUPrice);
			$this->db->insert('Vendors_Items', $data);
		}

		// Insert/Update supplied items in Items table
		// Perform update name, if no item code exists, perform insertion
		foreach ($vendor->suppliedItems as $item) {
			$data = array('name' => $item->name);
			$cond = $this->db->quoteInto('item_code = ?', $item->itemCode);
			$n = $this->db->update('Items', $data, $cond);
			if ($n == 0) {	// item code does not exists
				$data['qty'] = 0;
				$data['s_uprice'] = 0.0;
				$data['item_code'] = $item->itemCode;
				$this->db->insert('Items', $data);
			}
		}
		return true;
	}

	/**
	 * Checks if a phone number exists in db.
	 * @param String $phone
	 * @return bool
	 */
	public function phoneExists($phone)
	{
		$sql = $this->db->quoteInto('SELECT vid FROM Vendors WHERE phone = ?', $phone);
		$result = $this->db->fetchAll($sql);
		return count($result) != 0;
	}

	/**
	 * Gets the inventory.
	 * @return array an array of Model_Item objects.
	 */
	public function getInventory()
	{
		$sql = 'SELECT item_code, name, qty, s_uprice FROM Items ORDER BY item_code ASC';
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$ret = array();
		foreach ($result as $row) {
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$item->name = $row->name;
			$item->qty = $row->qty;
			$item->sUPrice = $row->s_uprice;
			$ret[] = $item;
		}
		return $ret;
	}

	/**
	 * Update the store unit price of an item.
	 * @param String $code item code
	 * @param double $price
	 * @return bool
	 */
	public function updateItemUPrice($code, $price)
	{
		$data = array('s_uprice' => floatval($price));
		$cond = $this->db->quoteInto('item_code = ?', trim(strtoupper($code)));
		$n = $this->db->update('Items', $data, $cond);
		return $n == 1;
	}

	/**
	 * Update name of an item.
	 * @param String $code item code
	 * @param String $name item name
	 * @return bool
	 */
	public function updateItemName($code, $name)
	{
		$data = array('name' => $name);
		$cond = $this->db->quoteInto('item_code = ?', $code);
		$n = $this->db->update('Items', $data, $cond);
		return $n == 1;
	}

	/**
	 * Gets all items of the given vendor.
	 * @param int $vid
	 * @return Model_Vendor the vendor obj with supplied items
	 */
	public function getItemsByVendor($vid)
	{
		$sql =
			'SELECT ' .
				'vi.item_code AS item_code, v_uprice, ' .
				'name, s_uprice, qty ' .
			'FROM ' .
				'Vendors_Items vi, Items i ' .
			'WHERE ' .
				'vid = %d AND i.item_code = vi.item_code ' .
			'ORDER BY vi.item_code ASC';
		$sql = sprintf($sql, $vid);
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$vendor = new Model_Vendor();
		$vendor->vid = $vid;
		foreach ($result as $row) {
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$item->name = $row->name;
			$item->qty = $row->qty;
			$item->sUPrice = $row->s_uprice;
			$item->vUPrice = $row->v_uprice;
			$vendor->suppliedItems[] = $item;
		}
		return $vendor;
	}

	/**
	 * Get vendors by item code.
	 * @param String $code item code
	 * @return array an array of vendors who have item code.
	 */
	public function getVendorsByItemCode($code)
	{
		$cond = array($this->db->quoteInto('i.item_code LIKE UPPER(?)',
												sprintf('%%%s%%', $code)));
		return $this->getVendors($cond);
	} 

	/**
	 * Get vendors by item name
	 * @param String $name name of item
	 * @return array an array of vendors who have item name.
	 */
	public function getVendorsByItemName($name)
	{
		$cond = array($this->db->quoteInto('LOWER(i.name) LIKE LOWER(?)',
												sprintf('%%%s%%', $name)));
		return $this->getVendors($cond);
	}

	/**
	 * Get purchases
	 * @param array $conds an array of conditions
	 * @return array an array of purchases.
	 */
	public function getPurchases($conds = array())
	{
		$sql =
			'SELECT ' .
				'p.pid AS pid, pdate, tax, arrived, ' .
				'i.item_code AS item_code, i.name AS iname, i.s_uprice AS s_uprice, ' .
				'pi.qty AS qty, pi.v_uprice AS v_uprice, ' .
				'v.vid AS vid, v.name AS vname ' .
			'FROM ' .
				'Purchases p, Items i, Purchases_Items pi, Vendors v ' .
			'WHERE ' .
				'v.vid = p.vid AND p.pid = pi.pid AND pi.item_code = i.item_code ';
		if (count($conds) > 0) {
			$sql .= 'AND ' . join(' AND ', $conds);
		}
		$sql .= ' ORDER BY p.pid ASC';
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$ret = array();
		$pid = -1;
		foreach ($result as $row) {
			if ($row->pid != $pid) {
				$pid = $row->pid;
				$purchase = new Model_Purchase();
				$purchase->pid = $row->pid;
				$purchase->date = new Zend_Date($row->pdate, Model_Db::SQL_DATE_ZFMT);
				$purchase->tax = $row->tax;
				$purchase->arrived = (bool) $row->arrived;

				$vendor = new Model_Vendor();
				$vendor->vid = $row->vid;
				$vendor->name = $row->vname;
				$purchase->vendor = $vendor;
				$ret[] = $purchase;
			}
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$item->name = $row->iname;
			$item->qty = $row->qty;
			$item->vUPrice = $row->v_uprice;
			$item->sUPrice = $row->s_uprice;
			$purchase->items[] = $item;
		}
		return $ret;
	}
	
	/**
	 * Get sales
	 * @param array $conds an array of conditions
	 * @return array an array of sales.
	 */
	public function getSales($conds = array())
	{
		$sql =
			'SELECT ' .
				'sale.sid AS sid, sdate, tax, ' .
				'si.item_code AS item_code, si.s_uprice AS s_uprice, si.qty AS qty, ' .
				'si.seasonal_id AS seasonal_id, si.coupon_code AS coupon_code, ' .
			   	'i.name AS iname, ' .
				'c.discount_rate AS c_discount_rate, ' . 
				'sea.name AS sea_name, sea.discount_rate AS s_discount_rate ' .
			'FROM Sales sale, Items i, Sales_Items si ' .
			'LEFT JOIN Coupons c ON c.coupon_code = si.coupon_code ' .
			'LEFT JOIN Seasonals sea ON sea.sid = si.seasonal_id ' .
			'WHERE ' .
				'sale.sid = si.sid AND si.item_code = i.item_code ';
		if (count($conds) > 0) {
			$sql .= 'AND ' . join(' AND ', $conds);
		}
		$sql .= ' ORDER BY sale.sid ASC';
		
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$ret = array();
		$sid = -1;
		foreach ($result as $row) {
			if ($row->sid != $sid) {
				$sid = $row->sid;
				$sale = new Model_Sale();
				$sale->sid = $row->sid;
				$sale->date = new Zend_Date($row->sdate, Model_Db::SQL_DATE_ZFMT);
				$sale->tax = $row->tax;
				$ret[] = $sale;
			}
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$item->name = $row->iname;
			$item->qty = $row->qty;
			$item->sUPrice = $row->s_uprice;
			if ($row->coupon_code != null) {
				$coupon = new Model_Coupon();
				$coupon->code = $row->coupon_code;
				$coupon->rate = $row->c_discount_rate;
				$item->coupon = $coupon;
			}
			if ($row->seasonal_id > 0) {
				$seasonal = new Model_Seasonal();
				$seasonal->sid = $row->seasonal_id;
				$seasonal->rate = $row->s_discount_rate;
				$seasonal->name = $row->sea_name;
				$item->seasonal = $seasonal;
			}
			$sale->items[] = $item;
		}
		return $ret;
	}

	/**
	 * Gets sales within the given dates
	 * @param Zend_Date $fromDate
	 * @param Zend_Date $toDate
	 * @return array an array of sales within the given dates
	 */
	public function getSalesWithinDates($fromDate, $toDate)
	{
		$fromDate = $fromDate->toString(Model_Db::SQL_DATE_ZFMT);
		$toDate = $toDate->toString(Model_Db::SQL_DATE_ZFMT);
		$conds = array(
			$this->db->quoteInto('sdate >= ?', $fromDate),
			$this->db->quoteInto('sdate <= ?', $toDate));
		return $this->getSales($conds);
	}

	/**
	 * Gets received purchases between two given dates.
	 * @param Zend_Date $fromDate
	 * @param Zend_Date $toDate
	 * @return array an array of received purchases between two given dates.
	 */
	public function getReceivedPurchases($fromDate, $toDate)
	{
		$fromDate = $fromDate->toString(Model_Db::SQL_DATE_ZFMT);
		$toDate = $toDate->toString(Model_Db::SQL_DATE_ZFMT);
		$conds = array(
			sprintf('arrived = %d', (int) true),
			$this->db->quoteInto('pdate >= ?', $fromDate),
			$this->db->quoteInto('pdate <= ?', $toDate));
		return $this->getPurchases($conds);
	}

	/**
	 * Gets pending purchases
	 * @return an array of pending purchases
	 */
	public function getPendingPurchases()
	{
		$conds = array(sprintf('arrived = %d', (int) false));
		return $this->getPurchases($conds);
	}

	/**
	 * Set the status of a purchase as arrived.
	 * @param int $pid Purchase ID
	 * @return bool true or false
	 */
	public function setPurchaseArrived($pid)
	{
		$sql = 'SELECT item_code, qty FROM Purchases_Items WHERE pid = ' . $pid;
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		foreach ($result as $row) {
			$sql = 'SELECT qty FROM Items WHERE item_code = ?';
			$sql = $this->db->quoteInto($sql, $row->item_code);
			$re = $this->db->fetchOne($sql);
			if ($re == '') {	// Item does not exist
				$data = array(
							'item_code' => $row->item_code,
							'qty' => (int) $row->qty);
				$n = $this->db->insert('Items', $data);
				if ($n != 1) return false;
			}
			else {
				$data = array('qty' => intval($re) + intval($row->qty));
				$n = $this->db->update('Items', $data,
					$this->db->quoteinto('item_code = ?', $row->item_code));
				if ($n != 1) return false;
			}
		}
		$data = array('arrived' => 1);
		$n = $this->db->update('Purchases', $data, sprintf('pid = %d', $pid));
		return $n == 1;
	}

	/**
	 * To create a new bill
	 * @param Model_Sale $bill
	 * @return bool true or false
	 */
	public function createBill(Model_Sale $bill)
	{
		// Insert into Sales
		$data = array(
					'sdate' => $bill->date->toString(Model_Db::SQL_DATE_ZFMT),
					'tax' => $bill->tax);
		$n = $this->db->insert('Sales', $data);
		if ($n != 1) return false;

		// Insert into Sales_Items table, update quantity of Items table, update Coupon as used if any
		$sid = $this->db->lastInsertId();
		foreach ($bill->items as $item) {
			$data = array(
						'sid' => $sid,
						'item_code' => $item->itemCode,
						's_uprice' => $item->sUPrice,
						'qty' => $item->qty);
			if ($item->seasonal != null)
				$data['seasonal_id'] = $item->seasonal->sid;
			if ($item->coupon != null)
				$data['coupon_code'] = $item->coupon->code;
			$n = $this->db->insert('Sales_Items', $data);
			if ($n != 1) return false;

			// Update quantity
			$sql = $this->db->quoteInto('SELECT qty FROM Items WHERE item_code = ?', $item->itemCode);
			$qty = (int) $this->db->fetchOne($sql);
			$data = array('qty' => $qty - $item->qty);
			$n = $this->db->update('Items', $data, $this->db->quoteInto('item_code = ?', $item->itemCode));
			if ($n != 1) return false;

			// Update coupon status
			if ($item->coupon != null) {
				$data = array('used' => 1);
				$n = $this->db->update('Coupons', $data, $this->db->quoteInto('coupon_code = ?', $item->coupon->code));
				if ($n != 1) return false;
			}
		}

		return true;
	}

	/**
	 * Gets the vendor unit price of the given item from a vendor
	 * @param int $vid vendor id
	 * @param String $code item code
	 * @return double the unit price or -1 if no item found
	 */
	public function getVUPriceByItemCode($vid, $code)
	{
		$sql = sprintf('SELECT v_uprice FROM Vendors_Items ' .
						'WHERE vid = %s AND item_code = ?', $vid);
		$sql = $this->db->quoteInto($sql, strtoupper($code));
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		if (count($result) != 1) {
			return -1;
		}
		else return floatval($result[0]->v_uprice);
	}

	/**
	 * Gets the store unit price of the given item
	 * @param String $code item code
	 * @return double the store unit price or -1 if no item found
	 */
	public function getSUPriceByItemCode($code)
	{
		$sql = 'SELECT s_uprice FROM Items WHERE item_code = ?';
		$sql = $this->db->quoteInto($sql, strtoupper($code));
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		if (count($result) != 1) {
			return -1;
		}
		else return floatval($result[0]->s_uprice);
	}

	/**
	 * Get the quantity left of an item
	 * @param String $code item code
	 * @return int the quantity left of the given item
	 */
	public function getQtyLeft($code)
	{
		$sql = $this->db->quoteInto('SELECT qty FROM Items WHERE item_code = ?', $code);
		$n = $this->db->fetchOne($sql);
		return (int) $n;
	}

	/**
	 * Insert a purchase info into db
	 * @param Model_Purchase $purchase
	 * @return bool true or false
	 */
	public function placeOrder($purchase)
	{
		$data = array(
					'vid' => $purchase->vendor->vid,
					'tax' => $purchase->tax,
					'arrived' => 0,
					'pdate' => $purchase->date->toString(Model_Db::SQL_DATE_ZFMT));
		$n = $this->db->insert('Purchases', $data);
		if ($n == 0) return false;
		$pid = $this->db->lastInsertId();
		foreach ($purchase->items as $item) {
			$data = array(
						'pid' => $pid,
						'item_code' => $item->itemCode,
						'qty' => $item->qty,
						'v_uprice' => $item->vUPrice);
			$this->db->insert('Purchases_Items', $data);
		}
		return true;
	}

	/**
	 * Add a new coupon to db
	 * @param Model_Coupon coupon object
	 * @return bool true or false
	 */
	public function addCoupon($coupon)
	{
		$data = array(
					'coupon_code' => $coupon->code,
					'item_code' => $coupon->item->itemCode,
					'from_date' => $coupon->fromDate->toString(Model_Db::SQL_DATE_ZFMT),
					'to_date' => $coupon->toDate->toString(Model_Db::SQL_DATE_ZFMT),
					'discount_rate' => $coupon->rate,
					'used' => 0);
		$n = $this->db->insert('Coupons', $data);
		return $n == 1;
	}

	/**
	 * Checks if a coupon code already exists
	 * @param String $code coupon code
	 * @return bool true or false
	 */
	public function couponExists($code)
	{
		$sql = 'SELECT COUNT(coupon_code) FROM Coupons WHERE coupon_code = ?';
		$sql = $this->db->quoteInto($sql, $code);
		$n = (int) $this->db->fetchOne($sql);
		return !($n == 0);
	}

	/**
	 * Checks if an item exists
	 * @param String $code item code
	 * @return bool true or false
	 */
	public function itemExists($code)
	{
		$sql = 'SELECT COUNT(item_code) FROM Items WHERE item_code = ?';
		$sql = $this->db->quoteInto($sql, $code);
		$n = (int) $this->db->fetchOne($sql);
		return !($n == 0);
	}

	/**
	 * Get all item codes in db
	 * @return array an array of item codes in db
	 */
	public function getItemCodes()
	{
		$sql = 'SELECT item_code FROM Items ORDER BY item_code ASC';
		$result = $this->db->fetchCol($sql);
		return $result;
	}

	/**
	 * Get coupons in db
	 * @param array $conds an array of conditions
	 * @return array an array of Model_Coupon objects
	 */
	public function getCoupons($conds = array())
	{
		$sql =
			'SELECT ' .
				'coupon_code, from_date, to_date, discount_rate, used, ' .
				'i.item_code AS item_code ' .
			'FROM ' .
				'Coupons c, Items i ' .
			'WHERE ' .
				'c.item_code = i.item_code ';
		if (count($conds) > 0) {
			$sql .= ' AND ' . join(' AND ', $conds);
		}
		$sql .= ' ORDER BY coupon_code ASC';
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$ret = array();
		foreach ($result as $row) {
			$coupon = new Model_Coupon();
			$coupon->code = $row->coupon_code;
			$coupon->fromDate = new Zend_Date($row->from_date, Model_Db::SQL_DATE_ZFMT);
			$coupon->toDate = new Zend_Date($row->to_date, Model_Db::SQL_DATE_ZFMT);
			$coupon->rate = $row->discount_rate;
			$coupon->used = (bool) $row->used;
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$coupon->item = $item;
			$ret[] = $coupon;
		}
		return $ret;
	}

	/**
	 * Get a coupon
	 * @param String $code coupon code
	 * @return Model_Coupon null if no coupon exists
	 */
	public function getCoupon($code)
	{
		$cond = array($this->db->quoteInto('coupon_code = ?', $code));
		$result = $this->getCoupons($cond);
		return (count($result) > 0) ? $result[0] : null;
	}

	/**
	 * Remove a coupon
	 * @param String $code coupon code
	 * @return bool true or false
	 */
	public function removeCoupon($code)
	{
		$n = $this->db->delete('Coupons', $this->db->quoteInto('coupon_code = ?', $code));
		return $n == 1;
	}

	/**
	 * Update discount rate of a coupon
	 * @param String $code coupon code
	 * @param double $rate discount rate
	 * @return bool true or false
	 */
	public function updateCouponRate($code, $rate)
	{
		$data = array('discount_rate' => $rate);
		$n = $this->db->update('Coupons', $data, $this->db->quoteInto('coupon_code = ?', $code));
		return $n == 1;
	}

	/**
	 * Update the item code of a coupon
	 * @param String $couponCode coupon code
	 * @param String $itemCode item code
	 * @return bool true or false
	 */
	public function updateCouponItem($couponCode, $itemCode)
	{
		$data = array('item_code' => $itemCode);
		$n = $this->db->update('Coupons', $data, $this->db->quoteInto('coupon_code = ?', $couponCode));
		return $n == 1;
	}

	/**
	 * Update the from date of a coupon
	 * @param String $code coupon code
	 * @param Zend_Date $date
	 * @return bool true or false
	 */
	public function updateCouponFromDate($code, $date)
	{
		$data = array('from_date' => $date->toString(Model_Db::SQL_DATE_ZFMT));
		$n = $this->db->update('Coupons', $data, $this->db->quoteInto('coupon_code = ?', $code));
		return $n == 1;
	}

	/**
	 * Update the to date of a coupon
	 * @param String $code coupon code
	 * @param Zend_Date $date
	 * @return bool true or false
	 */
	public function updateCouponToDate($code, $date)
	{
		$data = array('to_date' => $date->toString(Model_Db::SQL_DATE_ZFMT));
		$n = $this->db->update('Coupons', $data, $this->db->quoteInto('coupon_code = ?', $code));
		return $n == 1;
	}

	/**
	 * Add a new seasonal into db
	 * @param Model_Seasonal $seasonal
	 * @return int New inserted id or -1 if failed
	 */
	public function addSeasonal(Model_Seasonal $seasonal)
	{
		$data = array(
					'name' => $seasonal->name,
					'from_date' => $seasonal->fromDate->toString(Model_Db::SQL_DATE_ZFMT),
					'to_date' => $seasonal->toDate->toString(Model_Db::SQL_DATE_ZFMT),
					'discount_rate' => $seasonal->rate);
		$n = $this->db->insert('Seasonals', $data);
		if ($n != 1) return -1;
		$sid = $this->db->lastInsertId();
		foreach ($seasonal->items as $item) {
			$data = array(
						'sid' => $sid,
						'item_code' => $item->itemCode);
			$this->db->insert('Seasonals_Items', $data);
		}
		return $sid;
	}

	/**
	 * Get seasonal discounts in db
	 * @param array $conds an array of conditions
	 * @return array an array of Model_Seasonal objects
	 */
	public function getSeasonals($conds = array())
	{
		$sql =
			'SELECT ' .
				's.sid AS sid, name, from_date, to_date, discount_rate, ' .
				'item_code ' .
			'FROM ' .
				'Seasonals s, Seasonals_Items si ' .
			'WHERE ' .
				's.sid = si.sid ';
		if (count($conds) > 0) {
			$sql .= ' AND ' . join(' AND ', $conds);
		}
		$sql .= ' ORDER BY s.sid ASC';
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($sql);
		$ret = array();
		$sid = -1;
		foreach ($result as $row) {
			if ($row->sid != $sid) {
				$sid = $row->sid;
				$seasonal = new Model_Seasonal();
				$seasonal->sid = $row->sid;
				$seasonal->name = $row->name;
				$seasonal->fromDate = new Zend_Date($row->from_date, Model_Db::SQL_DATE_ZFMT);
				$seasonal->toDate = new Zend_Date($row->to_date, Model_Db::SQL_DATE_ZFMT);
				$seasonal->rate = $row->discount_rate;
				$ret[] = $seasonal;
			}
			$item = new Model_Item();
			$item->itemCode = $row->item_code;
			$seasonal->items[] = $item;
		}
		usort($ret, array('Model_Db', '_cmpSeasonal'));
		return $ret;
	}

	/**
	 * Used to sort an array of seasonal objects by name
	 * (asc order)
	 * @param Model_Seasonal $s1
	 * @param Model_Seasonal $s2
	 * @return int -1, 1, or 0
	 */
	static function _cmpSeasonal(Model_Seasonal $s1, Model_Seasonal $s2)
	{
		$n1 = strtolower($s1->name);
		$n2 = strtolower($s2->name);
		if ($n1 == $n2) return 0;
		return ($n1 < $n2) ? -1 : 1;
	}

	/**
	 * Get a seasonal discount
	 * @param int $sid
	 * @return Model_Seasonal a seasonal object or null
	 */
	public function getSeasonal($sid)
	{
		$cond = array(sprintf('s.sid = %d', $sid));
		$ret = $this->getSeasonals($cond);
		return (count($ret) == 1) ? $ret[0] : null;
	}

	/**
	 * Used to sort an array of seasonal objects by discount rate
	 * (desc order)
	 * @param Model_Seasonal $s1
	 * @param Model_Seasonal $s2
	 * @return int -1, 1, or 0
	 */
	static function _cmpSeasonalByRate(Model_Seasonal $s1, Model_Seasonal $s2)
	{
		if ($s1->rate == $s2->rate) return 0;
		return ($s1->rate < $s2->rate) ? 1 : -1;
	}
	/**
	 * Get a seasonal discount for the given item by date.
	 * Pick the one with highest rate if multiple seasonals
	 * @param String $code
	 * @param Zend_Date $date
	 * @return Model_Seasonal a seasonal object or null
	 */
	public function getSeasonalByItemCodeAndDate($code, Zend_Date $date)
	{
		$sqlDate = $date->toString(Model_Db::SQL_DATE_ZFMT);
		$cond = array(
			$this->db->quoteInto('from_date <= ?', $sqlDate),
		   	$this->db->quoteInto('to_date >= ?', $sqlDate));
		$ret = $this->getSeasonals($cond);
		if (count($ret) == 0) return null;
		usort($ret, array('Model_Db', '_cmpSeasonalByRate'));
		foreach ($ret as $seasonal) {
			if ($seasonal->hasItem($code)) return $seasonal;
		}
		return null;
	}

	/**
	 * Removes a seasonal discount
	 * @param int $sid seasonal id
	 * @param bool true or false
	 */
	public function removeSeasonal($sid)
	{
		$cond = sprintf('sid = %d', $sid);
		$this->db->delete('Seasonals_Items', $cond);
		$n = $this->db->delete('Seasonals', $cond);
		return $n == 1;
	}

	/**
	 * Update name of a seasonal discount
	 * @param int $sid
	 * @param String $name
	 */
	public function updateSeasonalName($sid, $name)
	{
		$data = array('name' => $name);
		$this->db->update('Seasonals', $data, sprintf('sid = %d', $sid));
	}

	/**
	 * Update discount rate of a seasonal discount
	 * @param int $sid
	 * @param double rate
	 */
	public function updateSeasonalRate($sid, $rate)
	{
		$data = array('discount_rate' => $rate);
		$this->db->update('Seasonals', $data, sprintf('sid = %d', $sid));
	}

	/**
	 * Update from date of a seasonal discount
	 * @param int $sid
	 * @param Zend_Date $date
	 */
	public function updateSeasonalFromDate($sid, $date)
	{
		$data = array('from_date' => $date->toString(Model_Db::SQL_DATE_ZFMT));
		$this->db->update('Seasonals', $data, sprintf('sid = %d', $sid));
	}

	/**
	 * Update to date of a seasonal discount
	 * @param int $sid
	 * @param Zend_Date $date
	 */
	public function updateSeasonalToDate($sid, $date)
	{
		$data = array('to_date' => $date->toString(Model_Db::SQL_DATE_ZFMT));
		$this->db->update('Seasonals', $data, sprintf('sid = %d', $sid));
	}

	/**
	 * Remove an item from a seasonal
	 * @param int $sid
	 * @param String $code item code
	 */
	public function removeItemFromSeasonal($sid, $code)
	{
		$cond = sprintf($this->db->quoteInto('sid = %d AND item_code = ?', $code), $sid);
		$this->db->delete('Seasonals_Items', $cond);
	}

	/**
	 * Add an item to a seasonal
	 * @param int $sid
	 * @param String $code item code
	 */
	public function addItemToSeasonal($sid, $code)
	{
		$data = array(
					'sid' => $sid,
					'item_code' => $code);
		$this->db->insert('Seasonals_Items', $data);
	}

	/**
	 * Check if an item exists in a seasonal
	 * @param int $sid
	 * @param String $code item code
	 * @return bool true or false
	 */
	public function itemExistsInSeasonal($sid, $code)
	{
		$sql = 'SELECT COUNT(sid) FROM Seasonals_Items WHERE sid = %d AND item_code = ?';
		$sql = sprintf($this->db->quoteInto($sql, $code), $sid);
		$n = (int) $this->db->fetchOne($sql);
		return $n != 0;
	}

	/**
	 * Get the items that are not in the list of the seasonal discount
	 * @param int $sid
	 * @return array an array of exclusive items
	 */
	public function getExclusiveItemsOfSeasonal($sid)
	{
		$cond = sprintf('si.sid = %d AND i.item_code = si.item_code', $sid);
		$select = $this->db->select()
							->from(array('i' => 'Items'), array('i_code' => 'item_code'))
							->joinLeft(array('si' => 'Seasonals_Items'), $cond, array('si_code' => 'item_code'))
							->order('i.item_code ASC');
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		$result = $this->db->fetchAll($select);
		$ret = array();
		foreach ($result as $row) {
			if ($row->si_code == null) {
				$ret[] = $row->i_code;
			}
		}
		return $ret;
	}

	/**
	 * Get the current running seasonal discount
	 * @return Model_Seasonal a seasonal object or null
	 */
	public function getCurrentSeasonal()
	{
		$date = Zend_Date::now()->toString(Model_Db::SQL_DATE_ZFMT);
		$cond = array(
					$this->db->quoteInto('from_date <= ?', $date),
					$this->db->quoteInto('to_date >= ?', $date));
		$result = $this->getSeasonals($cond);
		if (count($result) == 0) return null;
		usort($result, array('Model_Db', '_cmpSeasonalByRate'));
		return $result[0];
	}

	/**
	 * Get the neareset upcoming seasonal discount
	 * @return Model_Seasonal a seasonal object or null
	 */
	public function getUpcomingSeasonal()
	{
		$date = Zend_Date::now()->toString(Model_Db::SQL_DATE_ZFMT);
		$cond = array($this->db->quoteInto('from_date > ?', $date));
		$result = $this->getSeasonals($cond);
		$n = count($result);
		return ($n == 0) ? null : $result[$n - 1];
	}

	/**
	 * Get purchases within the given period group by item code
	 * @param Zend_Date $fromDate
	 * @param Zend_Date $toDate
	 * @return array an array of mixed object with attrs (item_code, iname, sum_qty, final_price
	 */
	public function reportPurchases(Zend_Date $from, Zend_Date $to)
	{
		$dateCond = $this->db->quoteInto('p.pdate >= ?', $from->toString(Model_Db::SQL_DATE_ZFMT));
		$dateCond = $this->db->quoteInto($dateCond . ' AND p.pdate <= ?', $to->toString(Model_Db::SQL_DATE_ZFMT));

		// SELECT pi.item_code AS item_code, i.name AS iname, SUM(pi.qty) AS sum_qty, SUM(pi.qty * v_uprice) AS final_price
		// FROM Purchases_Items pi
		// INNER JOIN Items i ON i.item_code = pi.item_code
		// INNER JOIN Purchases p ON p.pid = pi.pid AND p.pdate >= ... AND p.pdate <= ...
		// GROUP BY pi.item_code
		// ORDER BY pi.item_code ASC
		$select = $this->db->select()
							->from(array('pi' => 'Purchases_Items'),
												array('item_code' => 'pi.item_code',
														'sum_qty' => 'SUM(pi.qty)',
														'final_price' => 'SUM(pi.qty * v_uprice)'))
							->join(array('i' => 'Items'), 'i.item_code = pi.item_code', array('iname' => 'i.name'))
							->join(array('p' => 'Purchases'), 'p.pid = pi.pid AND p.arrived = 1 AND ' . $dateCond, array())
							->group('pi.item_code')
							->order(array('pi.item_code ASC'));
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		return $this->db->fetchAll($select);
	}

	/**
	 * Get sales within the given period group by item code
	 * @param Zend_Date $fromDate
	 * @param Zend_Date $toDate
	 * @return array an array of mixed object with attrs (item_code, iname, sum_qty, final_price
	 */
	public function reportSales(Zend_Date $from, Zend_Date $to)
	{
		$dateCond = $this->db->quoteInto('s.sdate >= ?', $from->toString(Model_Db::SQL_DATE_ZFMT));
		$dateCond = $this->db->quoteInto($dateCond . ' AND s.sdate <= ?', $to->toString(Model_Db::SQL_DATE_ZFMT));

		// SELECT si.item_code AS item_code, i.name AS iname, SUM(si.qty) AS sum_qty,
		// 		SUM(si.qty * si.s_uprice -
		//	 		si.qty * si.s_uprice * (IF(c.discount_rate, c.discount_rate, 0) + 
		// 									IF(sea.discount_rate, sea.discount_rate, 0)) / 100.0) AS final_price
		// FROM Sales_Items si
		// INNER JOIN Items i ON i.item_code = si.item_code
		// INNER JOIN Sales s ON s.sid = si.sid AND s.sdate >= ... AND s.sdate <= ...
		// LEFT JOIN Seasonals sea ON sea.sid = si.seasonal_id
		// LEFT JOIN Coupons c ON c.coupon_code = si.coupon_code
		// GROUP BY si.item_code
		// ORDER BY si.item_code ASC ;
		$final_price = 'SUM(si.qty * si.s_uprice - ' . 
						'si.qty * si.s_uprice * ' . 
							'(IF(c.discount_rate, c.discount_rate, 0) + ' .
							'IF(sea.discount_rate, sea.discount_rate, 0)) / 100.0)';
		$select = $this->db->select()
							->from(array('si' => 'Sales_Items'),
												array('item_code' => 'si.item_code',
														'sum_qty' => 'SUM(si.qty)',
														'final_price' => $final_price))
							->join(array('i' => 'Items'), 'i.item_code = si.item_code', array('iname' => 'i.name'))
							->join(array('s' => 'Sales'), 's.sid = si.sid AND ' . $dateCond, array())
							->joinLeft(array('sea' => 'Seasonals'), 'sea.sid = si.seasonal_id', array())
							->joinLeft(array('c' => 'Coupons'), 'c.coupon_code = si.coupon_code', array())
							->group('si.item_code')
							->order(array('si.item_code ASC'));
		$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
		return $this->db->fetchAll($select);
	}
}
