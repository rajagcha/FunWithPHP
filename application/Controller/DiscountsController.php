<?php
/**
 * @package controllers
 */
/**
 * Controller for Discounts page
 */
class DiscountsController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Discounts Management', $this->getUrl('discounts'));
	}

	public function indexAction()
	{
		$itemCodes = $this->db->getItemCodes();
		$jsonItemCodes = array();
		foreach ($itemCodes as $code)
			$jsonItemCodes[$code] = $code;
		$this->view->coupons = $this->db->getCoupons();
		$this->view->itemCodes = $itemCodes;
		$this->view->jsonItemCodes = json_encode($jsonItemCodes);
		$this->view->seasonals = $this->db->getSeasonals();
	}

	public function addCouponAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Discounts_AddCoupon();
			if ($form->process($request)) {
				// Check if coupon code already exists
				if ($this->db->couponExists($form->couponCode)) {
					$form->addError('form', sprintf('Coupon "%s" already exists', $form->couponCode));
				}
				// Check if item code exists
				if (!$this->db->itemExists($form->itemCode)) {
					$form->addError('form', sprintf('Item code "%s" does not exist', $form->itemCode));
				}
				// If no error, put into db
				if (!$form->hasError()) {
					$coupon = new Model_Coupon();
					$coupon->code = $form->couponCode;
					$coupon->rate = $form->rate;
					$coupon->fromDate = $form->fromDate;
					$coupon->toDate = $form->toDate;
					$coupon->used = false;
					$item = new Model_Item();
					$item->itemCode = $form->itemCode;
					$coupon->item = $item;
					if ($this->db->addCoupon($coupon)) {
						$this->sendJSON(array('ok' => true, 'coupon_code' => $coupon->code));
						return;
					}
					$form->addError('form', 'DB Error');
				}
			}
			$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
		}
	}

	public function getCouponAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$coupon = $this->db->getCoupon(strtoupper(trim($request->getPost('coupon_code'))));
			$this->view->coupon = $coupon;
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function removeCouponAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			if ($this->db->removeCoupon(strtoupper(trim($request->getPost('coupon_code'))))) {
				$this->sendJSON(array('ok' => true));
			}
			else {
				$this->sendJSON(array('ok' => false));
			}
		}
	}

	public function updateCouponRateAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$code = strtoupper(trim($request->getPost('coupon_code')));
			$rate = floatval($request->getPost('rate'));
			$coupon = $this->db->getCoupon($code);
			if ($coupon == null) {
				$this->view->ok = false;
				$this->view->msg = 'DB Error';
			}
			else if ($rate > 100 || $rate <= 0) {
				$this->view->ok = false;
				$this->view->rate = $coupon->rate;
			}
			else if ($this->db->updateCouponRate($code, $rate)) {
				$this->view->ok = true;
				$this->view->rate = $rate;
			}
			else {
				$this->view->ok = false;
				$this->view->rate = $coupon->rate;
			}
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function updateCouponItemAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$couponCode = strtoupper(trim($request->getPost('coupon_code')));
			$itemCode = strtoupper(trim($request->getPost('item_code')));
			$coupon = $this->db->getCoupon($couponCode);
			if ($this->db->updateCouponItem($couponCode, $itemCode)) {
				$this->sendJSON(array('item_code' => $itemCode));
			}
			else {
				$this->sendJSON(array('item_code' => $coupon->item->itemCode));
			}
		}
	}

	public function updateCouponDateAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$couponCode = strtoupper(trim($request->getPost('coupon_code')));
			$dateType = $request->getPost('date_type');
			$date = new Zend_Date($request->getPost('date'), 'MMM dd, yyyy');
			$coupon = $this->db->getCoupon($couponCode);
			$today = Zend_Date::now();
			if ($dateType == 'from') {
				if ($date > $coupon->toDate) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => '"From date" can\'t be later than "To date"',
									'date' => $coupon->fromDate->toString('MMM dd, yyyy')));
				}
				else if ($date <= $today) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => 'Manually making the coupon valid is not allowed',
									'date' => $coupon->fromDate->toString('MMM dd, yyyy')));
				}
				else if ($this->db->updateCouponFromDate($couponCode, $date)) {
					$this->sendJSON(array(
									'ok' => true,
									'date' => $date->toString('MMM dd, yyyy')));
				}
				else {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => 'DB Error',
									'date' => $coupon->fromDate->toString('MMM dd, yyyy')));
				}
			}
			else if ($dateType == 'to') {
				if ($date < $coupon->fromDate) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => '"From date" can\'t be later than "To date"',
									'date' => $coupon->toDate->toString('MMM dd, yyyy')));
				}
				else {
					if ($this->db->updateCouponToDate($couponCode, $date)) {
						$this->sendJSON(array(
										'ok' => true,
										'date' => $date->toString('MMM dd, yyyy')));
					}
					else {
						$this->sendJSON(array(
										'ok' => false,
										'errors' => 'DB Error',
										'date' => $coupon->toDate->toString('MMM dd, yyyy')));
					}
				}
			}
		}
	}

	public function addSeasonalAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Discounts_AddSeasonal();
			if ($form->process($request)) {
				$seasonal = new Model_Seasonal();
				$seasonal->name = $form->seasonal_name;
				$seasonal->rate = $form->seasonal_rate;
				$seasonal->fromDate = $form->fromDate;
				$seasonal->toDate = $form->toDate;
				foreach ($form->items as $code) {
					$item = new Model_Item();
					$item->itemCode = $code;
					$seasonal->items[] = $item;
				}
				$sid = $this->db->addSeasonal($seasonal);
				if ($sid > 0) {
					$this->sendJSON(array('ok' => true));
					return;	
				}
				$form->addError('form', 'DB Error');
			}
			$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
		}
	}

	public function removeSeasonalAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			if ($this->db->removeSeasonal($sid)) {
				$this->sendJSON(array('ok' => true));
			}
			else {
				$this->sendJSON(array('ok' => false, 'errors' => 'DB Error'));
			}
		}
	}

	public function updateSeasonalNameAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$name = trim($request->getPost('name'));
			$seasonal = $this->db->getSeasonal($sid);
			if ($name == '') {
				$this->sendJSON(array('name' => $seasonal->name));
			}
			else {
				$this->db->updateSeasonalName($sid, $name);
				$this->sendJSON(array('name' => $name));
			}
		}
	}

	public function updateSeasonalRateAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$rate = doubleval($request->getPost('rate'));
			$seasonal = $this->db->getSeasonal($sid);
			if ($rate <= 0 || $rate > 100) {
				$this->view->rate = $seasonal->rate;
			}
			else {
				$this->db->updateSeasonalRate($sid, $rate);
				$this->view->rate = $rate;
			}
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function updateSeasonalDateAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$dateType = $request->getPost('date_type');
			$date = new Zend_Date($request->getPost('date'), 'MMM dd, yyyy');
			$seasonal = $this->db->getSeasonal($sid);
			$today = Zend_Date::now();
			if ($dateType == 'from') {
				if ($date > $seasonal->toDate) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => '"From date" can\'t be later than "To date"',
									'date' => $seasonal->fromDate->toString('MMM dd, yyyy')));
				}
				else if ($date <= $today) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => 'Manually making seasonal discount valid is not allowed',
									'date' => $seasonal->fromDate->toString('MMM dd, yyyy')));
				}
				else {
					$this->db->updateSeasonalFromDate($sid, $date);
					$this->sendJSON(array(
									'ok' => true,
									'date' => $date->toString('MMM dd, yyyy')));
				}
			}
			else if ($dateType == 'to') {
				if ($date < $seasonal->fromDate) {
					$this->sendJSON(array(
									'ok' => false,
									'errors' => '"From date" can\'t be later than "To date"',
									'date' => $seasonal->toDate->toString('MMM dd, yyyy')));
				}
				else {
					$this->db->updateSeasonalToDate($sid, $date);
					$this->sendJSON(array(
									'ok' => true,
									'date' => $date->toString('MMM dd, yyyy')));
				}
			}
		}
	}

	public function removeItemFromSeasonalAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$code = strtoupper(trim($request->getPost('item_code')));
			$this->db->removeItemFromSeasonal($sid, $code);
		}
	}

	public function addItemToSeasonalAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$code = strtoupper(trim($request->getPost('item_code')));
			if ($this->db->itemExists($code) && !$this->db->itemExistsInSeasonal($sid, $code)) {
				$this->db->addItemToSeasonal($sid, $code);
			}
		}
	}

	public function getExclusiveItemsOfSeasonalAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$sid = (int) $request->getPost('sid');
			$itemCodes = $this->db->getExclusiveItemsOfSeasonal($sid);
			$this->view->itemCodes = $itemCodes;
			$this->view->sid = $sid;
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}
}
