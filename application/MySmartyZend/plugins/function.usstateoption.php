<?php
/**
 * @package Smarty
 * @subpackage plugins
 */
/**
 * To create a combo box of US states. Value of each item is what is displayed.
 * @usage {usstateoption name='name' [id='id'] [class='class'] [selected='STATE']}
 */
function smarty_function_usstateoption($param, $smarty)
{
	if (!isset($param['name'])) {
		return "[Smarty Error][usstateoption] missing param 'name'";
	}
	$states = array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
					'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
					'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
					'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
					'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY');
	$options = '';
	foreach ($states as $val) {
		$options .= sprintf('<option value="%s"', $val);
		if (isset($param['selected']) && $param['selected'] == $val)
			$options .= ' selected="selected"';
		$options .= sprintf('>%s</option>', $val);
	}

	$select = sprintf('<select name="%s"', $param['name']);
	if (isset($param['id']))
		$select .= sprintf(' id="%s"', $param['id']);
	if (isset($param['class']))
		$select .= sprintf(' class="%s"', $param['class']);
	$select .= sprintf('>%s</select>', $options);
	return $select;
}
?>
