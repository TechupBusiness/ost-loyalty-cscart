<?php
/*
 * Source from: https://stackoverflow.com/questions/254514/php-and-enumerations
 *
 * Example:
    final class DaysOfWeek extends TypedEnum
	{
	    public static function Sunday() { return self::_create(0); }
	    public static function Monday() { return self::_create(1); }
	    public static function Tuesday() { return self::_create(2); }
	    public static function Wednesday() { return self::_create(3); }
	    public static function Thursday() { return self::_create(4); }
	    public static function Friday() { return self::_create(5); }
	    public static function Saturday() { return self::_create(6); }
	}


	function saveEvent(DaysOfWeek $weekDay, $comment)
	{
	    // store week day numeric value and comment:
	    $myDatabase->save('myeventtable',
	       array('weekday_id' => $weekDay->getValue()),
	       array('comment' => $comment));
	}

	// call the function, note: DaysOfWeek::Monday() returns an object of type DaysOfWeek
	saveEvent(DaysOfWeek::Monday(), 'some comment');


	Note that all instances of the same enum entry are the same:
		$monday1 = DaysOfWeek::Monday();
		$monday2 = DaysOfWeek::Monday();
		$monday1 === $monday2; // true


	You can also use it inside of a switch statement:
		function getGermanWeekDayName(DaysOfWeek $weekDay)
		{
		    switch ($weekDay)
		    {
		        case DaysOfWeek::Monday(): return 'Montag';
		        case DaysOfWeek::Tuesday(): return 'Dienstag';
		        // ...
		}


	You can also create an enum entry by name or value:
		$monday = DaysOfWeek::fromValue(2);
		$tuesday = DaysOfWeek::fromName('Tuesday');


	Or you can just get the name (i.e. the function name) from an existing enum entry:
		$wednesday = DaysOfWeek::Wednesday()
		echo $wednesDay->getName(); // Wednesday
 */


namespace Techup\Helper;
use ReflectionClass;
use ReflectionMethod;
use OutOfRangeException;

/**
 * Class TypedEnum
 * @package Techup\Helper
 */
abstract class TypedEnumHelper
{
	private static $_instancedValues;

	private $_value;
	private $_name;

	private function __construct($value, $name)
	{
		$this->_value = $value;
		$this->_name = $name;
	}

	/**
	 * @param $getter
	 * @param $value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	private static function _fromGetter($getter, $value)
	{
		$reflectionClass = new ReflectionClass(get_called_class());
		$methods = $reflectionClass->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);
		$className = get_called_class();

		foreach($methods as $method)
		{
			if ($method->class === $className)
			{
				$enumItem = $method->invoke(null);

				if ($enumItem instanceof $className && $enumItem->$getter() === $value)
				{
					return $enumItem;
				}
			}
		}

		throw new OutOfRangeException();
	}

	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function getAllValues() {
		$reflectionClass = new ReflectionClass(get_called_class());
		$methods = $reflectionClass->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);

		$returnArray = [];

		foreach($methods as $method)
		{
			if ($method->name !== 'fromValue' && $method->name !== 'fromName')
			{
				$enumItem = $method->invoke(null);
				$returnArray[] = $enumItem;
			}
		}

		return $returnArray;
	}

	protected static function _create($value)
	{
		if (self::$_instancedValues === null)
		{
			self::$_instancedValues = array();
		}

		$className = get_called_class();

		if (!isset(self::$_instancedValues[$className]))
		{
			self::$_instancedValues[$className] = array();
		}

		if (!isset(self::$_instancedValues[$className][$value]))
		{
			$debugTrace = debug_backtrace();
			$lastCaller = array_shift($debugTrace);

			while ($lastCaller['class'] !== $className && count($debugTrace) > 0)
			{
				$lastCaller = array_shift($debugTrace);
			}

			self::$_instancedValues[$className][$value] = new static($value, $lastCaller['function']);
		}

		return self::$_instancedValues[$className][$value];
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public static function fromValue($value)
	{
		return self::_fromGetter('getValue', $value);
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public static function fromName($value)
	{
		return self::_fromGetter('getName', $value);
	}

	public function getValue()
	{
		return $this->_value;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function __toString() {
		return $this->getValue();
	}
}