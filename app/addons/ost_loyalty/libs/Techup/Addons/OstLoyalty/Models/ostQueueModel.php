<?php

namespace Techup\Addons\OstLoyalty\Models;

use Techup\Addons\OstLoyalty\Enums\transactionTypesEnum;
use Techup\Addons\OstLoyalty\Enums\ostQueueStatusEnum;

class ostQueueModel {

	/** @var integer */
	public $queueId;

	/** @var $userId */
	public $userId;

	/** @var $actionId */
	public $actionId;

	/** @var $actionName */
	public $actionName;

	/** @var $value */
	public $value;

	/** @var ostQueueStatusEnum */
	public $status;

	/** @var integer */
	public $createdTimestamp;

	/** @var integer */
	public $updatedTimestamp;

	/**
	 * @param array $row
	 */
	public function fillFromDb($row) {
		$this->status = $row['status'];
		$this->queueId = $row['queue_id'];
		$this->userId = $row['user_id'];
		$this->actionId = $row['action_id'];
		$this->actionName = $row['action_name'];
		$this->value = $row['value'];
		$this->status = $row['status'];
		$this->updatedTimestamp = $row['updated_ts'];
		$this->createdTimestamp = $row['created_ts'];
	}

	/**
	 * @param string[] $exclude_fields
	 * @return array
	 */
	public function fillInDb($exclude_fields = []) {
		$row = [];

		if(!in_array('queue_id', $exclude_fields)) $row['queue_id'] = $this->queueId;
		if(!in_array('user_id', $exclude_fields)) $row['user_id'] = $this->userId;
		if(!in_array('action_id', $exclude_fields)) $row['action_id'] = $this->actionId;
		if(!in_array('action_name', $exclude_fields)) $row['action_name'] = $this->actionName;
		if(!in_array('value', $exclude_fields))	$row['value'] = $this->value;
		if(!in_array('status', $exclude_fields))	$row['status'] = $this->status;
		if(!in_array('updated_ts', $exclude_fields)) $row['updated_ts'] = $this->updatedTimestamp;
		if(!in_array('created_ts', $exclude_fields)) $row['created_ts'] = $this->createdTimestamp;

		return $row;
	}

}