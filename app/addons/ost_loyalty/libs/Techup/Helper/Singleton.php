<?php

namespace Techup\Helper;


interface ISingleton {
	/**
	 * @return ISingleton
	 */
	public static function getInstance();
}

abstract class Singleton implements ISingleton {

	private static $_instances = [];

	/**
	 * @return $this
	 */
	final public static function getInstance() {
		$className = get_called_class();
		self::$_instances[$className] = isset(self::$_instances[$className]) ? self::$_instances[$className] : new static();
		return self::$_instances[$className];
	}

}