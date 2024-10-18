<?php

namespace app\components;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use app\components\CustomException;

class GeneralAffairCoreAPI 
{
	public function insertItem($data, $model)
	{
		$data = $this->sanitizeEmptyFields($data);
		$data = $this->populateDefaultValues($data);

		if (!empty($data)) {
			$data = $this->sanitizeData($data, 'item');
			$data['department']['name'] = $model->getDepartmentName($data['department']['id']);
		}

		return ['items' => [$data]];
	}

	// public function shouldCreateEmptyItems($params)
	// {
	// 	$requiredFields = ['name', 'location_id', 'category_id'];
	// 	foreach ($requiredFields as $field) {
	// 		if (empty($params[$field])) {
	// 			return false;
	// 		}
	// 	}
		
	// 	return count($params) === count($requiredFields);
	// }

	private function populateDefaultValues($data)
	{
		if (empty($data['price'])) {
			$defaultValues = [];
		} else {
			$defaultValues = [
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
			];
		}

		$data = array_merge($defaultValues, $data);

		return $data;
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

	public function filterItems($items, $params)
	{
		$items = array_filter($items, function ($item) {
			return isset($item['status']) && $item['status'] != Constants::STATUS_DELETED;
		});

		if (!empty($params['item']['items']['code'])) {
			$code = $params['item']['items']['code'];
			return array_filter($items, function ($item) use ($code) {
				return isset($item['code']) && $item['code'] == $code;
			});
		}

		if (!empty($params['item']['items']['department']['id'])) {
			$departmentId = $params['item']['items']['department']['id'];
			return array_filter($items, function ($item) use ($departmentId) {
				return isset($item['department']) && $item['department']['id'] == $departmentId;
			});
		}

		return $items;
	}
}