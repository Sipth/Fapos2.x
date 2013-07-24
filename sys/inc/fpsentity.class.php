<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    FpsEntity class               |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/02/27                    |
|----------------------------------------------|
|											   |
| any partial or not partial extension         |
| CMS Fapos,without the consent of the         |
| author, is illegal                           |
|----------------------------------------------|
| Любое распространение                        |
| CMS Fapos или ее частей,                     |
| без согласия автора, является не законным    |
\---------------------------------------------*/






class FpsEntity {

	//protected $add_fields = array();
	
	public function __construct($params = array())
	{
		$this->set($params);
	}
	
	
	public function set($params = array())
	{
		if (!empty($params) && is_array($params)) {
			foreach ($params as $k => $value) {
				$funcName = 'set' . ucfirst($k);
				$this->$funcName($value);
			}
		}
	}
	
	
	public function __call($method, $params)
	{
		if (false !== strpos($method, 'set')) {
			$name = str_replace('set', '', $method);
			$name = strtolower($name);
			$this->$name = $params[0];
			
		} else if (false !== strpos($method, 'get')) {
			$name = str_replace('get', '', $method);
			$name = strtolower($name);
			return (isset($this->$name)) ? $this->$name : null;
		}
		return;
	}



    protected function checkProperty($var)
    {
        //return (null === $var) ? false : true;
		if (is_object($var)) return true;
        return (!isset($this->{$var})) ? false : true;
    }


	public function asArray()
	{
		$args = get_object_vars($this);
		return $args;
	}
}
