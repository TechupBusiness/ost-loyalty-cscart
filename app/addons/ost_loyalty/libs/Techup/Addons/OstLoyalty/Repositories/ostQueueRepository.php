<?php

namespace Techup\Addons\OstLoyalty\Repositories;

use Techup\Helper\Singleton;
use Techup\Addons\OstLoyalty\Models\ostQueueModel;
use Techup\Addons\OstLoyalty\Enums\ostQueueStatusEnum;
use Techup\Addons\OstLoyalty\Enums\transactionTypesEnum;

class ostQueueRepository extends Singleton {

	/**
	 * @param ostQueueModel[]|ostQueueModel $ostQueueModels
	 *
	 * @return ostQueueModel[]
	 */
	public function save($ostQueueModels) {

		if(!is_array($ostQueueModels)) {
			$ostQueueModels = [ $ostQueueModels ];
		}

		$changed = [];

		foreach ($ostQueueModels as $model) {

			try {
				if ( $model->queueId > 0 ) {
					// Update
					$model->updatedTimestamp = time();
					$affectedRows            = db_query( 'UPDATE ?:ost_queue SET ?u WHERE queue_id=?i AND status!=?s AND transaction_type=?s',
						$model->fillInDb(),
						$model->queueId,
						ostQueueStatusEnum::Finished(),
						$model->transactionType
					);

					if ( $affectedRows === 1 ) {
						$changed[] = $model;
					} else {
						throw new \Exception( 'Could not update ost_queue entry with ID=' . $model->queueId );
					}
				} else {
					// Insert
					$model->createdTimestamp = time();
					$model->updatedTimestamp = $model->createdTimestamp;
					$newId = db_query( 'INSERT INTO ?:ost_queue ?e',
						$model->fillInDb()
					);

					if ( $newId > 0 ) {
						$model->queueId     = $newId;
						$changed[] = $model;
					} else {
						throw new \Exception( 'Could not insert ost_queue entry' );
					}
				}
			} catch(\Exception $ex) {
				// ToDo
			}
		}

		return $changed;
	}

	/**
	 * @param ostQueueStatusEnum $status
	 *
	 * @return ostQueueModel[]
	 */
	public function load(ostQueueStatusEnum $status) {

		/** @var ostQueueModel[] $models */
		$models = [];

		$rows = db_get_array("SELECT * FROM ?:ost_queue WHERE status=?s", $status);
		foreach ( $rows as $row ) {
			$model = new ostQueueModel();
			$model->fillFromDb($row);
			$models[] = $model;
		}

		return $models;
	}

}