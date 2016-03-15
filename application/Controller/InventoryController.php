<?php
/**
 * @package controllers
 */
/**
 * Controller for Inventory page
 */
class InventoryController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Inventory Management', $this->getUrl('inventory'));
	}
	
    public function indexAction()
    {
		$items = $this->db->getInventory();
		$vendors = $this->db->getVendors();
		$this->view->items = $items;
		$this->view->vendors = $vendors;
	}

	public function updateItemUPriceAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$isAjax = $request->isXmlHttpRequest();
		$isPost = $request->isPost();
		if ($isAjax && $isPost) {
			$itemCode = trim(strtoupper($request->getPost('item_code')));
			$price = floatval($request->getPost('s_uprice'));
			if ($this->db->updateItemUPrice($itemCode, $price)) {
				echo sprintf('%.2f', $price);
			}
			else {
				echo 'DB Error';
			}
		}
	}

	public function updateItemNameAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$request = $this->getRequest();
		$isAjax = $request->isXmlHttpRequest();
		$isPost = $request->isPost();
		if ($isAjax && $isPost) {
			$itemCode = trim(strtoupper($request->getPost('name_item_code')));
			$itemCode = substr($itemCode, strpos($itemCode, '_') + 1);
			$name = trim($request->getPost('name'));
			if ($this->db->updateItemName($itemCode, $name)) {
				echo htmlspecialchars($name);
			}
			else {
				echo 'DB Error';
			}
		}
	}

	public function getItemsByVendorAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$vid = (int) $request->getPost('vid');
			if ($vid <= 0) return;
			$vendor = $this->db->getItemsByVendor($vid);
			$this->view->vendor = $vendor;
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function searchAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$keyword = $request->getPost('keyword');
			if ($keyword == '') {
				$this->view->error = 'Keyword is required';
				return;
			}
			$criteria = (int) $request->getPost('criteria');
			if ($criteria == 1) {
				$this->view->vendors = $this->db->getVendorsByItemCode($keyword);
				return;
			}
			else if ($criteria == 2) {
				$this->view->vendors = $this->db->getVendorsByItemName($keyword);
				return;
			}
		}
		$this->_helper->viewRenderer->setNoRender();
	}
}
