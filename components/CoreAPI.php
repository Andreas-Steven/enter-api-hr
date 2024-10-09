<?php

namespace app\components;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use app\components\CustomException;

class CoreAPI 
{
	public function findModel($model, $params, $where=[])
	{
		if (isset($params['id'])) {
			if (($dataset = $model::find()->where(array_merge(['id' => $params['id']], $where))->one()) !== null) {
				return $dataset;
			}
		}
		
		return null;
	}

	public function unavailableParams($rules, $params) 
	{
		$fields = [];
		$allowedParams = [];
		unset($params['id']);
		
		foreach ($rules as $rule) {
			$allowedParams = array_merge($allowedParams, $rule[0]);
		}

		$paramsDiff = array_diff_key($params, array_flip($allowedParams));
		// $params = array_intersect_key($params, array_flip($allowedParams));

		if (! empty($paramsDiff)) {
			foreach ($paramsDiff as $key => $value) {
				$fields[] = [
					'field' => $key,
					'message' => Yii::t('app', 'fieldNotValid', ['field' => $key]),
				];
			}

			$data = (object) [
				'error' => true,
				'params' => $fields,
			];

			return $data;
		}
	}

	public static function detailInfoAddition($model, $change_log, $timestamp=null, $username=null) {
		$tableName = $model->tableName();

		switch ($tableName) {
			case 'delivery_order':
				$change_log['received_at'] = $timestamp;
				$change_log['received_by'] = $username;
			break;
			case 'purchase':
				$change_log['approved_at'] = $timestamp;
				$change_log['approved_by'] = $username;
			break;
			
			default:
				
			break;
		}

		return $change_log;
	}

	public function timestamp(){
		return gmdate("Y-m-d\TH:i:s\Z");
	}

	public function username() {
		if ($username = Yii::$app->session->get('username')) {
			return $username;
		}
		return 'admin';
	}

	public function changeLog($model, $insert)
	{
		$timestamp = self::timestamp();
		$username = Yii::$app->session->get('username');
		$change_log = [];
		
		if ($insert) {
			$change_log = [
				'created_at' => $timestamp,
				'created_by' => $username,
				'updated_at' => null,
				'updated_by' => null,
				'deleted_at' => null,
				'deleted_by' => null,
			];
		} else {
			$change_log = $model['detail_info']['change_log'];

			if ($model->status == Constants::STATUS_DELETED) {
				$change_log['deleted_at'] = $timestamp;
				$change_log['deleted_by'] = $username;
			} else {
				$change_log['updated_at'] = $timestamp;
				$change_log['updated_by'] = $username;
			}
		}

		$model->change_log = $change_log;
		return $model->change_log;
	}
	public function validateStatusTransition($model, $params)
    {
		$completedStatus = Constants::STATUS_COMPLETED;
        $activeStatus = Constants::STATUS_ACTIVE;

		if (isset($params['status'])){
			$allowedTransitions = [
				'0' => [1, 4],
				'2' => [1, 4],
				'5' => [1, 4],
				'1' => [3],
				'3' => [],
				// '4' => ['superadmin'], // Special case for superadmin
			];
	
			$currentStatus = $model->getOldAttribute('status');
			$newStatus = (int) $params['status'];
			var_dump($currentStatus, $newStatus);

            // Check if the current status is completed or active
            if (in_array($currentStatus, [$completedStatus, $activeStatus])) {
				foreach ($params as $key => $value) {
                    if (! in_array($key, ['status', 'id'])) {
                        // unset($params[$key]);
						$model->addError('status', 'Cannot update other fields when status is ' . Constants::statusList()[$currentStatus] . '. Only status can be updated.');
						return $model;
                    }
                }

				$model->addError('status', 'Cannot update the status to ' . Constants::statusList()[$newStatus] . ' if the data is already '. Constants::statusList()[$activeStatus] . ' or '. Constants::statusList()[$completedStatus] . '.');
				return $model;
			}

			// Check if current status allows the new status
			if (isset($allowedTransitions[$currentStatus])) {
				// if ($newStatus == 4 && Yii::$app->user->can('superadmin')) {
				//     // Allow transition if user has superadmin permission
				//     return;
				// }
	
				if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
					$model->addError('status', 'Invalid status transition 0.');
					return $model;
				}
			} else {
				$model->addError('status', 'Invalid status transition 1.');
				return $model;
			}
		}
	}

	public function superAdmin() 
    {
		$roles = Yii::$app->session->get('roles', []);

		if (!in_array('superadmin', $roles)) {
			return false;
		}

		return true;
    }

	public static function restrictStatusUpdate($status) 
    {
		if (isset($status)) {
			if (in_array($status, Constants::restrictStatus())) {
				return true;
			}
		}

		return false;
    }

	function htmlPurifier($value){
		$dirtyHtml = HtmlPurifier::process($value);
		$cleanText = strip_tags($dirtyHtml);

		return $cleanText;
	}

	public function exisitingData($model, $params)
	{
		if(isset($params['id'])) {
			$existing = $model::find()->where(['id' => $params['id']])->one();
		} elseif(isset($params['date'])) {
			$existing = $model::find()->where(['date' => $params['date']])->one();			
		} elseif(isset($params['name'])) {
			$existing = $model::find()->where(['name' => $params['name']])->one();			
		}

		if ($existing) {
			return $existing;
		}
		
		return false;
	}

	// General Affair Section
	public function insertItem($data, $model)
	{
		$item = array_merge([
			'code' => null,
			'price' => null,
			'condition' => null,
			'buying_date' => null,
			'tax_date' => null,
			'inspection_date' => null,
			'description' => null,
			'image' => [],
			'status' => 2,
			'department' => [
				'id' => null,
				'name' => null
			],
			'item_change_log' => [
				'created_at' => self::timestamp(),
				'created_by' => Yii::$app->session->get('username'),
				'deleted_by' => null,
				'deleted_at' => null,
				'updated_at' => null,
				'updated_by' => null
			]
		], $data);

		$item['condition'] = (int) $item['condition'];
		$item['department']['id'] = (int) $item['department']['id'];
		$item['department']['name'] = $model->getDepartmentNameById($item['department']['id']);
		$item['price'] = floatval($item['price']);
		
		return ['items' => [$item]];
	}

	public function itemChangeLog($items)
	{
		$timestamp = self::timestamp();
		$username = Yii::$app->session->get('username');
		
		$item_change_log = $items['item_change_log'];
		
		if ($items['status'] == Constants::STATUS_DELETED) {
			$item_change_log['deleted_at'] = $timestamp;
			$item_change_log['deleted_by'] = $username;
		} else {
			$item_change_log['updated_at'] = $timestamp;
			$item_change_log['updated_by'] = $username;
		}

		$items['item_change_log'] = $item_change_log;
		
		return $items;
	}

	public function calculateValue($model)
	{
		$quantity = $total_price = $index = 0;

		foreach ($model['items'] as $key => $value) {
			if (is_array($value) && array_key_exists('price', $value) && $value['status'] != Constants::STATUS_DELETED) {
				$total_price += $value['price'];
				$quantity++;
				$index = $key;
			}
		}

		$model['quantity'] = $quantity;
		$model['total_price'] = $total_price;

		return $model;
	}

	public function findIndex($code, $items)
	{
		foreach ($items as $key => $value) {
			if ($value['code'] == $code) {
				return $key;
			}
		}
	
		return null;
	}

	// Absensi Section
	public function insertAttendance($attendance, $model)
	{
		if (isset($model->attendance)) {
			$attendance = array_merge([
				'employee_id' => null,
				'start_time' => null,
				'end_time' => null,
				'info' => [
					'in' => false,
					'out' => false,
					'detail' => []
				],
				'device' => [
					'id' => null,
					'name' => null
				],
				'work_time' => [
					'id' => null,
					'start_time' => null,
					'home_time' => null
				],
				'location' => [
					'lat' => null,
					'long' => null,
					'image' => []
				]
			], $attendance);
		}
	
		$workTimeData = $model->getWorkTimeData($attendance['work_time']['id']);
		$attendance['device']['name'] = $model->getDeviceName($attendance['device']['id']);
		$attendance['employee_id'] = (int) $attendance['employee_id'];
		$attendance['device']['id'] = (int) $attendance['device']['id'];
		$attendance['work_time']['id'] = (int) $attendance['work_time']['id'];
		$attendance['work_time']['entry_time'] = $workTimeData['entry_time'];
		$attendance['work_time']['home_time'] = $workTimeData['home_time'];

		$attendance = $this->changeInfoField($attendance);

		return [$attendance];
	}

	public function changeInfoField($attendance)
	{
		if (!empty($attendance['start_time'])) {
			$attendance['info']['in'] = true;
		}

		if (!empty($attendance['end_time'])) {
			$attendance['info']['out'] = true;
		}

		if (!empty($attendance['start_time']) && !empty($attendance['work_time']['entry_time'])) {
			$startTime = strtotime($attendance['start_time']);
			$entryTime = strtotime($attendance['work_time']['entry_time']);

			if ($startTime > $entryTime) {
				$attendance['info']['detail'][] = "Telat Masuk";
			}
		}

		if (!empty($attendance['end_time']) && !empty($attendance['work_time']['home_time'])) {
			$endTime = strtotime($attendance['end_time']);
			$homeTime = strtotime($attendance['work_time']['home_time']);

			if ($endTime < $homeTime) {
				$attendance['info']['detail'][] = "Pulang Cepat";
			}
		}

		if (empty($attendance['end_time']) && !empty($attendance['start_time']) && date('Y-m-d') != date('Y-m-d', strtotime($attendance['start_time']))) {
			$attendance['info']['detail'][] = "Tidak Absen Pulang";
		}

		return $attendance;
	}

	public function insertBlacklist($existing, $employee_id, $date, $start_time)
	{
		$blacklistEntry = [
			'employee_id' => (int) $employee_id,
			'date' => $date,
			'time' => $start_time,
			'change_log' => [
				'created_at' => self::timestamp(),
				'created_by' => Yii::$app->session->get('username')
			]
		];

		$blacklist = $existing->blacklist;
		$blacklist[] = $blacklistEntry;

		return $blacklist;
	}

	public function updateAttendance($attendance, $employee_id, $end_time)
	{
		foreach ($attendance as &$item) {
			if (isset($item['employee_id']) && $item['employee_id'] == $employee_id) {
				$item['end_time'] = $end_time;
				$item = $this->changeInfoField($item);
				break;
			}
		}
		return $attendance;
	}
}