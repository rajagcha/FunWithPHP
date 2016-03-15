<?php
/**
 * @package controllers
 */
/**
 * An abstract class for controllers
 * @abstract
 */
class MyControllerAction extends Zend_Controller_Action
{
	public $db;
	public $siteNav;	// site navigator
	public $messenger;	// FlashMessenger obj
	
	public function init()
	{
		// Setup db conn.
		$this->db = new Model_Db(Zend_Registry::get('db'));
		
		// Set up site navigator
		$this->siteNav = new MyNavigator();
		$this->siteNav->addStep('Home', $this->getUrl('/'));
		
		// Setup FlashMessenger obj
		#$this->messenger = $this->_helper->_flashMessenger;
	}
	
	/**
	 * Set redirect meta header for template
	 *
	 * @param int $wait
	 * @param String $url
	 */
	public function setRedirect($wait, $url)
	{
		$this->view->setRedirect = true;
		$this->view->redWait = $wait;
		$this->view->redUrl = $url;
	}
	
	/**
	 * Makes the path to controller/action
	 * 
	 * @param String $controller
	 * @param String $action
	 * @return String path to controller/action 
	 */
	public function getUrl($controller = null, $action = null)
	{
		$url = rtrim($this->getRequest()->getBaseUrl(), '/');// . '/';
		$url .= $this->_helper->url->simple($action, $controller);
		return $url;
	}
	
	/**
	 * Gets the custom routed url
	 *
	 * @param String $route
	 * @param array $options
	 * @return String the custom routed url
	 */
	public function getCustomUrl($route, array $options)
	{
		return $this->_helper->url->url($options, $route);
	}
	
	/**
	 * Sends JSON object to Ajax code.
	 *
	 * @param mixed $data
	 * @param bool $encoded if true, uses encode method; otherwise, just echo.
	 */
	public function sendJSON($data, $encoded = true)
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->getResponse()->setHeader('content-type', 'application/json');
		echo ($encoded) ? Zend_Json::encode($data) : $data;
	}
	
	/**
	 * This method is invoked after the controler is done.
	 * 
	 * @see Zend_Controller_Action::postDispatch()
	 */
	public function postDispatch()
	{
		$this->view->currentSeasonal = $this->db->getCurrentSeasonal();
		$this->view->comingSeasonal = $this->db->getUpcomingSeasonal();
		$this->view->siteNav = $this->siteNav;
		$this->view->title = $this->siteNav->getTitle();
	}
}
