<?php
/**
 * @package controllers
 */
/**
 * Controller for Reports page
 */
class ReportsController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Reports Management', $this->getUrl('reports'));
	}

	public function indexAction()
	{
	}

	public function reportPurchasesAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Reports();
			if ($form->process($request)) {
				$records = $this->db->reportPurchases($form->fromDate, $form->toDate);
				$tax = 5.5;
				$subtotal = 0;
				foreach ($records as $row)
					$subtotal += $row->final_price;
				$this->view->fromDate = $form->fromDate;
				$this->view->toDate = $form->toDate;
				$this->view->records = $records;
				$this->view->subtotal = $subtotal;
				$this->view->tax = $tax;
				$this->view->total = $subtotal + $subtotal * $tax / 100.0;
				return;
			}
			$this->view->errors = $form->getErrors('form');
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}

	public function reportSalesAction()
	{
		$request = $this->getRequest();
		if ($request->isXmlHttpRequest() && $request->isPost()) {
			$form = new Model_Form_Reports();
			if ($form->process($request)) {
				$records = $this->db->reportSales($form->fromDate, $form->toDate);
				$tax = 5.5;
				$subtotal = 0;
				foreach ($records as $row)
					$subtotal += $row->final_price;
				$this->view->fromDate = $form->fromDate;
				$this->view->toDate = $form->toDate;
				$this->view->records = $records;
				$this->view->subtotal = $subtotal;
				$this->view->tax = $tax;
				$this->view->total = $subtotal + $subtotal * $tax / 100.0;
				return;
			}
			$this->view->errors = $form->getErrors('form');
			return;
		}
		$this->_helper->viewRenderer->setNoRender();
	}
}
