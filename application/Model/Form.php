<?php
/**
 * @package models
 * @subpackage forms
 */
/**
 * An abstract class to process forms. It will hold data submitted for validation
 * and other data manipulation.
 * @abstract
 */
abstract class Model_Form
{
	protected $_errors;
	protected $_vals;
	protected $_validateOnly;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_errors = array();
		$this->_vals = array();
		$this->_validateOnly = false;
	}
	
	/**
	 * This method should be implemented in subclasses to process the form.
	 * @param Zend_Controller_Request_Abstract $request
	 * @return bool
	 */
	abstract public function process(Zend_Controller_Request_Abstract $request);
	
	/**
	 * Sets if the validatio process done by Ajax or server-side
	 * @param bool $flag If true, Ajax is used.
	 */
	public function setValidateOnly($flag)
	{
		$this->_validateOnly = (bool) $flag;
	}

	/**
	 * Gets values from $this->_vals
	 * @param $name
	 * @return unknown_type
	 */
	public function __get($name)
	{
		return array_key_exists($name, $this->_vals) ? $this->_vals[$name] : null;
	}
	
	/**
	 * Sets values for $this->_vals
	 * @param $name
	 * @param $val
	 * @return unknown_type
	 */
	public function __set($name, $val)
	{
		$this->_vals[$name] = $val;
	}
	
	/**
	 * Checks if errors occur
	 * @param $key error type
	 * @return bool. If error type is provided, it will only check for that specific error type.
	 */
	public function hasError($key = null)
	{
		if ($key === null)	return count($this->_errors) > 0;
		return array_key_exists($key, $this->_errors);
	}
	/**
	 * Adds an error type and error msg.
	 * @param $key error type
	 * @param $val error message
	 */
	public function addError($key, $val)
	{
		if (array_key_exists($key, $this->_errors)) {
			$this->_errors[$key][] = $val;
		}
		else {
			$this->_errors[$key] = array($val);
		}
	}
	
	/**
	 * Gets errors of an error type. If null is provided, return all errors.
	 * @param $key error type
	 * @return an array of errors of the given type. If no error, return an
	 * 			empty array. If error type is given, return error msg of that specific error type.
	 */
	public function getErrors($key = null)
	{
		if ($key === null) {
			return $this->_errors;
		}
		
		if ($this->hasError($key))	return $this->_errors[$key];
		
		return array();
	}
}
