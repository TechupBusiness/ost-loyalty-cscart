<?php

namespace Techup\Addons\OstLoyalty\Repositories;

use Techup\SimpleTokenApi\Enums\KindEnum;
use Techup\SimpleTokenApi\Models\ActionModel;

class ActionDefinitionRepository {

	/**
	 * @return ActionModel[]
	 */
	public static function getActionTemplates() {
		$actions = [];

		$model = new ActionModel();
		$model->name = 'userRegistration';
		$model->kind = KindEnum::company_to_user();
		$model->currency = 'USD';
		$model->arbitrary_amount = false;
		$actions[] = $model;

		$model = new ActionModel();
		$model->name = 'orderReward';
		$model->kind = KindEnum::company_to_user();
		$model->currency = 'BT';
		$model->arbitrary_amount = true;
		$actions[] = $model;

		$model = new ActionModel();
		$model->name = 'orderRewardDeny';
		$model->kind = KindEnum::user_to_company();
		$model->currency = 'BT';
		$model->arbitrary_amount = true;
		$actions[] = $model;

		$model = new ActionModel();
		$model->name = 'orderPay';
		$model->kind = KindEnum::user_to_company();
		$model->currency = 'BT';
		$model->arbitrary_amount = true;
		$actions[] = $model;

		$model = new ActionModel();
		$model->name = 'orderPayRefund';
		$model->kind = KindEnum::company_to_user();
		$model->currency = 'BT';
		$model->arbitrary_amount = true;
		$actions[] = $model;

		return $actions;
	}

	/**
	 * @return array
	 */
	public static function getActionNameArray() {
		$actions = self::getActionTemplates();
		$array = [];
		foreach ($actions as $action) {
			$array[] = $action->name;
		}

		return $array;
	}

	/**
	 * @param $name
	 *
	 * @return null|ActionModel
	 */
	public static function getTemplate($name) {
		$actions = self::getActionTemplates();
		foreach ($actions as $action) {
			if($action->name==$name) {
				return $action;
			}
		}
		return null;
	}
}