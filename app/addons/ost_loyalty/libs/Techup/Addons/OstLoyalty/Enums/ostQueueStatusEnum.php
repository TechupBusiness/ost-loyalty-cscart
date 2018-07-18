<?php

namespace Techup\Addons\OstLoyalty\Enums;

use Techup\Helper\TypedEnumHelper;

final class ostQueueStatusEnum extends TypedEnumHelper
{
	public static function Waiting() { return self::_create('W'); }
	public static function Disabled() { return self::_create('D'); }
	public static function Error() { return self::_create('E'); }
	public static function Finished() { return self::_create('F'); }
}