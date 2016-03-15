<?php
/**
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty plugin to generate a meta tag to redirect a page.
 * @usage {redirect wait=5 url='url'}
 */
function smarty_function_redirect($params, $smarty)
{
	if (!isset($params['wait']) && !isset($params['url'])) return null;
	 
	return "<META http-equiv=\"refresh\" content=\"" . $params['wait'] . 
			";URL=" . $params['url'] . "\" />"; 
}
