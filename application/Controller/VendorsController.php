<?php
/**
 * @package controllers
 */
/**
 * Controller for Vendors page
 */
class VendorsController extends MyControllerAction
{
	public function init()
	{
		parent::init();
		$this->siteNav->addStep('Vendors Management', $this->getUrl('vendors'));
	}

    public function indexAction()
    {
		$vendors = $this->db->getVendors();
		$this->view->vendors = $vendors;
	}

	public function addVendorAction()
	{
		$form = new Model_Form_Vendor();
		$request = $this->getRequest();
		$isAjax = $request->isXmlHttpRequest();

		if ($request->isPost()) {
			if (!$isAjax) {
				$form->addError('form', 'You need to enable Javascript');
			}
			else if ($form->process($request)) {
				$phoneExists = $this->db->phoneExists($form->phone);
				if (!$phoneExists) {
					// Construct Vendor object
					$vendor = new Model_Vendor();
					$vendor->name = trim($form->name);
					$vendor->phone = trim($form->phone);
					$vendor->address = trim($form->address);
					$vendor->city = trim($form->city);
					$vendor->state = trim($form->state);
					$vendor->zip = trim($form->zip);
					
					// Construct supplied item objects
					$suppliedItems = array();
					$usedItemCodes = array();
					for ($i = 0; $i < count($form->item_codes); $i++) {
						$itemCode = strtoupper(trim($form->item_codes[$i]));
						if (in_array($itemCode, $usedItemCodes)) {
							continue;
						}
						$usedItemCodes[] = $itemCode;
						$item = new Model_Item();
						$item->itemCode = $itemCode;
						$item->name = trim($form->item_names[$i]);
						$item->vUPrice = trim($form->v_uprices[$i]);
						$suppliedItems[] = $item;
					}
					$vendor->suppliedItems = $suppliedItems;

					// Add new vendor to db
					$ok = $this->db->addVendor($vendor);
					if ($ok) {
						$this->sendJSON(array('ok' => true));
						return;
					}
					$form->addError('form', 'DB Error');
				}
				else {
					$form->addError('form', 'Phone number already exists');
				}
			}
		}

		if ($form->hasError()) {
			$this->sendJSON(array('ok' => false, 'errors' => $form->getErrors('form')));
		}
		else {
			// View
			$this->view->form = $form;
		}
	}
}
