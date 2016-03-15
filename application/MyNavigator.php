<?php
/**
 * @package utils
 */
/**
 * This class is to build the site navigator.
 */
class MyNavigator
{
	private $_trail = array();
	
	public function addStep($title, $link = '')
	{
		$this->_trail[] = array('title' => $title, 'link' => $link);
	}
	
	public function getTrail()
	{
		return $this->_trail;
	}
	
	/**
	* Gets the last title of the trail
	*/
	public function getTitle()
	{
		if (count($this->_trail) == 0)
			return null;
		return $this->_trail[count($this->_trail) - 1]['title'];
	}
}
