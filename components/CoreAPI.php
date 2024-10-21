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
	
	public function findExisiting($model, $params)
	{
		$existing = null;

		if(isset($params['id'])) {
			$existing = $model::find()->where(['id' => $params['id']])->one();
		} elseif(isset($params['date'])) {
			$existing = $model::find()->where(['date' => $params['date']])->one();			
		} elseif(isset($params['name']) && isset($params['location_id'])) {
			$existing = $model::find()->where(['name' => $params['name']])->andWhere(['location_id' => $params['location_id']])->one();			
		}
		
		if ($existing != null) {
			return $existing;
		}

		return false;
	}	

	public function sanitizeEmptyFields($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = array_filter($value, function ($val) {
                    return $val !== "";
                });
            } else {
                if ($value === "") {
                    $data[$key] = null;
                }
            }
        }

        return $data;
    }

	public function sanitizeData($data, $type)
	{
		$mappings = [
			'item' => [
				'price' => 'floatval',
				'condition' => 'intval',
				'department.id' => 'intval',
			],
			'attendance' => [
				'employee_id' => 'intval',
				'device.id' => 'intval',
				'work_time.id' => 'intval',
				'department.id' => 'intval',
			],
		];
	
		if (isset($mappings[$type])) {
			foreach ($mappings[$type] as $key => $function) {
				$keys = explode('.', $key);
				$temp = &$data;

				foreach ($keys as $k) {
					if (isset($temp[$k])) {
						$temp = &$temp[$k];
					} else {
						continue 2;
					}
				}

				$temp = $function($temp);
			}
		}
	
		return $data;
	}

	public function insertEmployeeDetail($data, $model)
	{
		$department = $model->getDepartment($data['department']['id']);
		$data['department']['id'] = $department['department']['id'];
		$data['department']['name'] = $department['department']['name'];
		
		$workTime = $model->getWorkTime($data['work_time']['id']);
		$data['work_time']['id'] = $workTime['work_time']['id'];
		$data['work_time']['entry_time'] = $workTime['work_time']['entry_time'];
		$data['work_time']['home_time'] = $workTime['work_time']['home_time'];
	
		return $data;
	}
}