<?php
/**
 * @package Smarty
 */
/**
 * This class is to replace the default "view" of Zend by Smarty.
 */
class MySmartyZend extends Zend_View_Abstract
{
	protected $_path;
	protected $_engine;
	
	public function __construct()
	{
		require_once("Smarty/Smarty.class.php");
		$config = Zend_Registry::get("config");
		$this->_engine = new Smarty();
		$this->_engine->template_dir = $config->Smarty->template_dir;
		$this->_engine->compile_dir = $config->Smarty->compile_dir; 
		$this->_engine->plugins_dir = array($config->Smarty->plugins_dir, "plugins");
	}
	
	public function getEngine()
	{
		return $this->_engine;
	}
	
	public function __set($key, $val)
	{
		$this->_engine->assign($key, $val);
	}
	
	public function __get($key)
	{
		return $this->_engine->get_template_vars($key);
	}
	
	public function __isset($key)
	{
		return ($this->_engine->get_template_vars($key) != null);
	}
	
	public function __unset($key)
	{
		$this->_engine->clear_assign($key);
	}
	
	public function assign($spec, $val = null)
	{
		if (is_array($spec)) {
			$this->_engine->assign($spec);
			return;
		}
		
		$this->_engine->assign($spec, $val);
	}
	
	public function clearVars()
	{
		$this->_engine->clear_all_assign();
	}
	
	public function render($tpl)
	{
		return $this->_engine->fetch(strtolower($tpl));
	}
	
	public function _run() {}
}

