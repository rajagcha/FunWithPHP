<?php
/**
 * @package controllers
 */
/**
 * Controller for error handler
 */
class ErrorController extends MyControllerAction
{
    public function errorAction()
    {
		$errors = $this->getRequest()->getParam('error_handler');
        
        switch ($errors->type) {
        	// 404 error -- controller or action not found 
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->_forward('error404');
				return;
                
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
			default:
                $this->_helper->viewRenderer->setNoRender();
                $this->getResponse()->setHttpResponseCode(500);
                echo "Internal Error";
                echo "<p>" . $errors->exception->getMessage() . "</p>";
                break;
        }
        
        // clear the response already rendered
    	$this->getResponse()->clearBody();
    	
    	// log
    	//Zend_Registry::get('sysLogger')->crit($errors->exception->getMessage());
    }
    
    public function error404Action()
    {
    	$request = $this->getRequest();
    	$error = $request->getParam('error_handler');
    	$uri = $request->getRequestUri();
    	
    	// Log this event
    	//Zend_Registry::get('userLogger')->info('404 error: ' . $uri);
    	
    	// Pass vars to templater
    	$this->getResponse()->setHttpResponseCode(404);
    	$this->siteNav->addStep('404 File Not Found');
    	$this->view->requestedUri = $uri;
    }
}
