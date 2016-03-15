<?php
/**
 * @package controllers
 */
/**
 * Controller for Discounts page
 */
class SalesController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Sales Management', $this->getUrl('sales'));
	}

	public function indexAction()
	{
		$this->view->itemCodes = $this->db->getItemCodes();
	}

	public function createBillAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Sales_CreateBill();
			if ($form->process($request)) {
				// Construct a Sale object
				$bill = new Model_Sale();
				$bill->date = $form->date;
				$bill->tax = $form->tax;

				// Check item_codes, qty, coupon_codes
				$usedItemCodes = array();
				for ($i = 0; $i < count($form->itemCodes); $i++) {
					$coupon = null;
					$itemCode = $form->itemCodes[$i];
					if ($form->qties[$i] <= 0) {	// check quantity
						$form->addError('form',
							sprintf('Quantity for item "%s" is not valid', $itemCode));
						break;
					}
					else {	// check coupon
						if ($form->couponCodes[$i] != '') {
							$coupon = $this->db->getCoupon($form->couponCodes[$i]);
							if ($coupon == null) {
								$form->addError('form',
									sprintf('Coupon code "%s" is invalid', $form->couponCodes[$i]));
								break;
							}
							else if ($coupon->used) {
								$form->addError('form',
									sprintf('Coupon code "%s" has already been used', $coupon->code));
								break;
							}
							else if ($itemCode != $coupon->item->itemCode) {
								$form->addError('form',
									sprintf('Coupon code "%s" is not associated with the item code "%s"', $coupon->code, $itemCode));
								break;
							}
							else if ($bill->date > $coupon->toDate) {
								$form->addError('form',
									sprintf('Coupon code "%s" has expired', $coupon->code));
								break;
							}
							else if ($bill->date < $coupon->fromDate) {
								$form->addError('form',
									sprintf('Coupon code "%s" is not applicable now', $coupon->code));
								break;
							}
						}

						// Check if there is any seasonal
						$seasonal = $this->db->getSeasonalByItemCodeAndDate($itemCode, $bill->date);
						
						// Init. item object
						if (!in_array($itemCode, array_keys($usedItemCodes))) {
							$item = new Model_Item();
							$item->itemCode = $itemCode;
							$item->qty = $form->qties[$i];
							$item->sUPrice = $this->db->getSUPriceByItemCode($item->itemCode);
							if ($coupon != null) {
								$item->coupon = $coupon;
							}
							if ($seasonal != null && $seasonal->hasItem($itemCode)) {
								$item->seasonal = $seasonal;
							}
							$bill->items[] = $item;
							$usedItemCodes[$itemCode] = $item;
						}
						else {
							if ($usedItemCodes[$itemCode]->coupon != null && $coupon != null &&
									$usedItemCodes[$itemCode]->coupon->code != $coupon->code) {
								$form->addError('form',
									sprintf('One item can only use one coupon. ' .
											'The item code "%s" uses two different coupons', $itemCode));
							   break;
							}
							else if ($usedItemCodes[$itemCode]->coupon == null && $coupon != null) {
								$usedItemCodes[$itemCode]->coupon = $coupon;
							}
							$usedItemCodes[$itemCode]->qty += $form->qties[$i];
						}
					}
				}

				foreach ($bill->items as $item) {
					if ($item->qty > $this->db->getQtyLeft($item->itemCode)) {
						$form->addError('form',
							sprintf('Quantity for item "%s" is over the one left in stock', $item->itemCode));
						break;
					}
				}

				if (!$form->hasError()) {
					// Put into db
					if ($this->db->createBill($bill)) {
						$this->sendJSON(array('ok' => true));
						return;
					}
					$form->addError('form', 'DB Error');
				}
				$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
			}
			else {
				$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
			}
		}
	}

	public function getSUPriceAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$itemCode = strtoupper(trim($request->getPost('item_code')));
			$price = $this->db->getSUPriceByItemCode($itemCode);
			$this->sendJSON(array('price' => $price));
		}
	}

	public function getCouponRateAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$itemCode = strtoupper(trim($request->getPost('item_code')));
			$couponCode = strtoupper(trim($request->getPost('coupon_code')));
			$coupon = $this->db->getCoupon($couponCode);
			$rate = 0;
			if ($coupon != null && $coupon->item->itemCode == $itemCode) {
				$rate = $coupon->rate;
			}
			$this->sendJSON(array('rate' => floatval($rate)));
		}
	}

	public function getSeasonalRateAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$itemCode = strtoupper(trim($request->getPost('item_code')));
			$date = $request->getPost('date');
			$rate = 0;
			if ($date != '') {
				$date = new Zend_Date($date, 'MMM dd, yyyy');
				$seasonal = $this->db->getSeasonalByItemCodeAndDate($itemCode, $date);
				if ($seasonal != null && $seasonal->hasItem($itemCode)) {
					$rate = $seasonal->rate;
				}
			}
			$this->sendJSON(array('rate' => floatval($rate)));
		}
	}

	public function getSalesAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Sales_ViewSales();
			if ($form->process($request)) {
				$sales = $this->db->getSalesWithinDates($form->fromDate, $form->toDate);
				$this->view->sales = $sales;
			}
			else {
				$this->view->errors = $form->getErrors('form');
			}
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}
}
