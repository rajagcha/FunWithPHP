<?php
/**
 * @package controllers
 */
/**
 * Controller for Purchases page
 */
class PurchasesController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Purchases Management', $this->getUrl('purchases'));
	}

	public function indexAction()
	{
		$this->view->vendors = $this->db->getVendors();
		$this->view->pendingPurchases = $this->db->getPendingPurchases();
	}

	public function getReceivedPurchasesAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Purchases_GetReceived();
			if ($form->process($request)) {
				$purchases = $this->db->getReceivedPurchases($form->fromDate, $form->toDate);
				$this->view->purchases = $purchases;
			}
			else {
				$this->view->errors = $form->getErrors('form');
			}
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function receivePurchaseAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$pid = (int) $request->getPost('pid');
			$ok = $this->db->setPurchaseArrived($pid);
			$this->sendJSON(array('ok' => $ok, 'error' => 'Updating db failed'));
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function getVUPriceAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$vid = (int) $request->getPost('vid');
			$itemCode = strtoupper(trim($request->getPost('item_code')));
			$price = $this->db->getVUPriceByItemCode($vid, $itemCode);
			$this->sendJSON(array('price' => $price));
		}
	}

	public function placeOrderAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Purchases_PlaceOrder();
			if ($form->process($request)) {
				// Construc Vendor obj
				$vendor = $this->db->getVendor((int) $form->vid);
				if ($vendor == null) {
					$form->addError('form', 'Vendor does not exist');
				}
				else {
					// Check if the vendor supplies the ordered items
					$hasError = false;
					for ($i = 0; $i < count($form->itemCodes); $i++) {
						$itemCode = $form->itemCodes[$i];
						if (!$vendor->hasItem($itemCode)) {
							$hasError = true;
							$form->addError('form',
								sprintf('Vendor "%s" does not supplied item "%s"',
											$vendor->name, $itemCode));
						}
						else if ($form->qties[$i] <= 0) {	// check quantity
							$hasError = true;
							$form->addError('form',
								sprintf('Quantity for item "%s" is not valid', $itemCode));
						}
					}
					if (!$hasError) {
						// Construct a Purchase object
						$purchase = new Model_Purchase();
						$purchase->date = $form->date;
						$purchase->tax = $form->tax;
						$purchase->vendor = $vendor;

						// Construct purchased Item objects
						$usedItemCodes = array();
						for ($i = 0; $i < count($form->itemCodes); $i++) {
							$itemCode = strtoupper(trim($form->itemCodes[$i]));
							if (!in_array($itemCode, array_keys($usedItemCodes))) {
								$item = new Model_Item();
								$item->itemCode = $itemCode;
								$item->qty = $form->qties[$i];
								$item->vUPrice = $vendor->getVUPrice($item->itemCode);
								$purchase->items[] = $item;
								$usedItemCodes[$itemCode] = $item;
							}
							else {
								$usedItemCodes[$itemCode]->qty += $form->qties[$i];
							}
						}

						// Put to db
						if ($this->db->placeOrder($purchase)) {
							$this->sendJSON(array('ok' => true));
							return;
						}
						else {
							$form->addError('form', 'DB Error');
						}
					}
				}
				$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
			}
			else {
				$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
			}
		}
	}
}
