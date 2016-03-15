<?php
/**
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty plugin to generate a url based on controller and action.
 * The parameter 'domain' is used to generate a full http url.
 * @usage {geturl controller='c' action='a' [domain='domain name'] [route='registered route'] [...]}
 */
function smarty_function_geturl($params, $smarty)
{
	$action = isset($params['action']) ? $params['action'] : null;
	$controller = isset($params['controller']) ? $params['controller'] : null;
	$route = isset($params['route']) ? $params['route'] : null;
	$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('url');
	
	if (strlen($route) > 0) {
		unset($params['route']);
		$url = $helper->url($params, $route);
	}
	else {
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$url = rtrim($request->getBaseUrl(), '/');// . '/';
		$url .= $helper->simple($action, $controller);
	}
	
	if (isset($params['domain'])) { $url = $params['domain'] . $url; }
	
	return $url;
}
