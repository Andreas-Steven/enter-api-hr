<?php

namespace app\components;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;
use app\components\CustomException;

class CoreAPI 
{
	function modelClassName($model) {
		return StringHelper::basename(get_class($model));
	}

	function nullSafe($value=null) {
		return (is_string($value) && strtolower($value) === 'null') || $value === '' ? null : $value;
	}

	function nullString($value) {
		return (is_null($value) || (is_string($value) && strtolower($value) === 'null')) ? true : false;
	}

	public function searchGetErrors($errors)
    {
        $result = [];
        foreach ($errors as $attribute => $errorMessages) {
            foreach ($errorMessages as $errorMessage) {
                $result[] = [
                    'field' => $attribute,
                    'message' => $errorMessage,
                ];
            }
        }
        return $result;
    }

	public function findModel($model, $params, $where=[])
	{
		if (isset($params['id'])) {
			if (($dataset = $model::find()->where(array_merge(['id' => $params['id']], $where))->one()) !== null) {
				return $dataset;
			}
		} else {
			if (($dataset = $model::find()->where($where)->one()) !== null) {
				return $dataset;
			}
		}
		
		return null;
	}

	public function mongodbSyncFailed($model)
	{
		if (isset($model->id)) {
			if ($model->load(['sync' => 1], '')) {
				$model->save();
			}
		}
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

		if (! empty($paramsDiff)) {
			foreach ($paramsDiff as $key => $value) {
				$fields[] = [
					'field' => $key,
					'message' => Yii::t('rule', 'fieldNotValid', ['field' => $key]),
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
		$username = self::username();
		$change_log = [];

		$detailInfoAddition = self::detailInfoAddition($model, $change_log, $timestamp, $username);
		
		if ($insert) {
			$change_log = [
				'created_at' => $timestamp,
				'created_by' => $username,
				'updated_at' => null,
				'updated_by' => null,
				'deleted_at' => null,
				'deleted_by' => null,
			];

			$change_log = self::detailInfoAddition($model, $change_log, null, null);
			// $this->status = Constants::STATUS_DRAFT;
		} else {
			$change_log = $model['detail_info']['change_log'];

			if (isset($model->status)) {
				if ($model->status == Constants::STATUS_DELETED) {
					$change_log['deleted_at'] = $timestamp;
					$change_log['deleted_by'] = $username;
				} else {
					$change_log['updated_at'] = $timestamp;
					$change_log['updated_by'] = $username;
	
					$change_log = self::detailInfoAddition($model, $change_log, null, null);
				}
			}
		}

		$model->change_log = $change_log;
		return $model->change_log;
	}

	public function superAdmin() 
    {
		$roles = Yii::$app->session->get('roles', []);

		if (!in_array('superadmin', $roles)) {
			return true;
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
		$cleanText = $this->nullSafe($cleanText);
	
		return $cleanText;
	}

	public function validateDependencies($attribute, $params, $validator, $model, $dependencies)
    {
		if (!$model->isNewRecord) {
			$changedAttributes = [];
			$fields = Yii::$app->params['fieldUpdateRestriction'][$model->tableName()];
			$disallowedStatusUpdate = Constants::disallowedStatusUpdate();

			foreach ($fields as $field) {
				if ($model->isAttributeChanged($field)) {
					$changedAttributes[] = $field;
				}

				if ($field == 'status') {
					if ($model->isAttributeChanged($field) && in_array($model->status, $disallowedStatusUpdate)) {
						$changedAttributes[] = $field;
					}
				}
			}

			if (!empty($changedAttributes)) {
				$dataId = $model->id;

				foreach ($dependencies as $dependency) {
					$className = $dependency['class'];

					foreach ($dependency['field'] as $field) {
						if ($className::find()->where([$field => $dataId])->exists()) {
							foreach ($changedAttributes as $changedAttribute) {
								$model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
									'label' => $model->getAttributeLabel($changedAttribute),
									'tableName' => $model->tableName(),
								]));
							}

							return;
						}
					}
				}
			}
		}
    }

	public function validateDependenciesBackup($attribute, $params, $validator, $model, $dependencies)
    {
		$isDataUsed = false;

		if (!$model->isNewRecord) {
            $changedAttributes = [];
            $fields = Yii::$app->params['fieldUpdateRestriction'][$model->tableName()];
			$disallowedStatusUpdate = Constants::disallowedStatusUpdate();

            foreach ($fields as $field) {
                if ($model->isAttributeChanged($field)) {
                    $changedAttributes[] = $field;
                }

                if ($field == 'status') {
                    if ($model->isAttributeChanged($field) && in_array($model->status, $disallowedStatusUpdate)) {
                        $changedAttributes[] = $field;
                    }
                } 
            }

            if (!empty($changedAttributes)) {
                $dataId = $model->id;

                foreach ($dependencies as $dependency) {
					$className = $dependency['class'];

                    if (count($dependency['field']) > 1) {
                        $query = $className::find();
                        foreach ($dependency['field'] as $field) {
                            $query->orWhere([$field => $dataId]);
                        }
                        $isDataUsed = $isDataUsed || $query->exists();
                    } else {
                        $isDataUsed = $isDataUsed || $className::find()->where([$dependency['field'][0] => $dataId])->exists();
                    }
				}

				// $isDataUsed = Purchase::find()->where(['vendor_id' => $dataId])->exists() ||
                //     MasterData::find()->where(['vendor_id' => $dataId])->exists();

                if ($isDataUsed && self::superAdmin()) {
                    foreach ($changedAttributes as $changedAttribute) {
                        $model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                            'label' => $model->getAttributeLabel($changedAttribute),
                            'tableName' => $model->tableName(),
                        ]));
                    }
                }
            }
        }
    }

	public function validateStatusUpdate($attribute, $params, $model)
    {
        $newStatus = $model->$attribute;
        $oldStatus = $model->getOldAttribute($attribute);
        $statusList = Constants::statusList();
		$allowedStatusUpdate = Constants::allowedStatusUpdate();
        $disallowedStatusUpdate = Constants::disallowedStatusUpdate();

        if (!$model->isNewRecord && $model->isAttributeChanged($attribute)) {
            $oldStatus = $model->getOldAttribute($attribute);
            $newStatus = $model->$attribute;

            if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && self::superAdmin()) {
                $model->addError($attribute, Yii::t('rule', 'deletedStatusChanged', ['status' => $statusList[Constants::STATUS_DELETED]]));
                return;
            }
        } else if ($oldStatus !== $newStatus){
			if ($oldStatus !== null) {
				if (in_array($oldStatus, $disallowedStatusUpdate)) {
					$model->addError($attribute, Yii::t('rule', 'disallowedStatusUpdate', ['status' => $statusList[$oldStatus]]));
				} elseif (!isset($allowedStatusUpdate[$oldStatus])) {
					$model->addError($attribute, 'Invalid status transition');
				} elseif (!in_array($newStatus, $allowedStatusUpdate[$oldStatus])) {
					$model->addError($attribute, Yii::t('rule', 'cannotChangeStatus', [
						'status' => $statusList[$oldStatus],
						'newStatus' => $statusList[$newStatus]
					]));
				}
			}
        }
    }

	public function validatePage($attribute, $params, $model)
	{
		if ($model->$attribute <= 0) {
			$model->addError($attribute, 'Page must be greater than 0');
		}
	}

	// public function validateInteger($attribute, $params, $model)
	// {
	// 	if (!is_numeric($model->$attribute) || intval($model->$attribute) != $model->$attribute || intval($model->$attribute) <= 0) {
	// 		$this->addError($attribute, Yii::t('app', '{attribute} must be an integer.', ['attribute' => $this->getAttributeLabel('id')]));
	// 		return;
	// 	}
	// }

	function setPagination($params, $dataProvider) {
		$page = isset($params['page']) ? $params['page'] : 1;
		$page_size = isset($params['page_size']) ? (int) $params['page_size'] : (int) Constants::DEFAULT_PAGE_SIZE;
		$totalCount = (int) $dataProvider->getTotalCount();
	
		if ($totalCount < $page_size) {
			$page_size = $totalCount;

			return [
				'page' => $page - 1,
				// 'pageSize' => $page_size,
				// 'defaultPageSize' => $page_size,
			];
		}

		return [
			'page' => $page - 1,
			'pageSize' => $page_size,
			'defaultPageSize' => $page_size,
		];
	}

	function setSort($params) {
        $sort = isset($params['sort']) ? $params['sort'] : SORT_DESC;
        $sortBy = isset($params['sort_by']) ? $params['sort_by'] : 'id';
        $sort = $sort === 'asc' ? SORT_ASC : SORT_DESC;

        return [
            'defaultOrder' => [$sortBy => $sort],
        ];
    }

	public function statusRules($model) {
        return [
            [['status'], 'default', 'value' => Constants::STATUS_DRAFT],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], function ($attribute, $params) use ($model) {
                return Yii::$app->coreAPI->validateStatusUpdate($attribute, $params, $model);
            }],
        ];
    }

	// public function mongodbResult($model, $params, $where) 
    // {
	// 	$model->load([$this->modelClassName($model) => $params]);
        
	// 	if ($unavailableParams = $this->unavailableParams($model->rules(), $params)) {
	// 		return $unavailableParams;
	// 	}

	// 	$filters = [
	// 		'where' => [],
	// 		'sort' => [],
	// 		'limit' => $model->page_size,
	// 		'offset' => ($model->page - 1) * $model->page_size,
	// 		'orderBy' => $model->sort,
	// 		'indexBy' => $model->sort_by,
	// 	];

	// 	if (isset($model->id)) {
	// 		$filters['where']['id'] = (int) $model->id;
	// 	}
		
	// 	if (isset($model->name)) {
	// 		$filters['where']['name'] = ['$regex' => str_replace(' ', '.*', $model->name), '$options' => 'i'];
	// 	}

	// 	if (isset($model->status)) {
	// 		$filters['where']['status'] = ['$ne' => 4, '$eq' => (int) $model->status];
	// 	}

	// 	if ($where) {
	// 		$filters['where'] = array_merge($filters['where'], $where);
	// 	}
        
    //     return Yii::$app->mongodb->search($model, $filters['where']);
    // }

	function changelogDateFilters($model, $fields = ['created_at', 'updated_at', 'deleted_at'])
	{
		$conditions = ['and'];

		foreach ($fields as $field) {
			$fieldValue = $model->$field;

			if (!empty($fieldValue) && strpos($fieldValue, ',') !== false) {
				$dates = explode(',', $fieldValue);
				if (count($dates) == 2) {
					$start_date = trim($dates[0]);
					$end_date = trim($dates[1]);

					$conditions[] = ['>=', new \yii\db\Expression("(detail_info #>> '{change_log,$field}')::date"), $start_date.'T00:00:00Z'];
					$conditions[] = ['<=', new \yii\db\Expression("(detail_info #>> '{change_log,$field}')::date"), $end_date];
				} else {
					$conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{change_log,$field}')::date"), $fieldValue];
				}
			} else {
				$conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{change_log,$field}')::date"), $fieldValue];
			}
		}

		return $conditions;
	}

	function changelogFilters($model)
	{
		$conditions = ['and'];

		if (!empty($model->created_by)) {
			$conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $model->created_by];
		}

		if (!empty($model->updated_by)) {
			$conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $model->updated_by];
		}

		if (!empty($model->deleted_by)) {
			$conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $model->deleted_by];
		}

		return $conditions;
	}

	function nameFilter($name) {
		if ($name){
            $patternName = '%' . str_replace(' ', '%', trim($name)) . '%';
			return ['ilike', 'name', $patternName, false];
        }

		return ['ilike', 'name', $name, false];
	}
}