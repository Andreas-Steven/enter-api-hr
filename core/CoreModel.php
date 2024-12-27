<?php

namespace app\core;

use Yii;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;

class CoreModel 
{
    public static function getModelClassName($model): string
    {
		return StringHelper::basename(get_class($model));
	}

	public static function nullSafe(?string $value = null): ?string
	{
		return (is_string($value) && strtolower($value) === 'null') || $value === '' ? null : $value;
	}

	public static function isNullString($value): bool
	{
		return (is_null($value) || (is_string($value) && strtolower($value) === 'null'));
	}

	public static function htmlPurifier(?string $value): ?string
	{
		return $value === null ? null : self::nullSafe(strip_tags(HtmlPurifier::process($value)));
	}

	public static function contentPurifier(?string $value): ?string
	{
		return $value === null ? null : self::nullSafe(HtmlPurifier::process($value));
	}

	public static function purifyArray($array)
	{
		return is_array($array) ? array_map([__CLASS__, 'purifyObject'], $array) : null;
	}
	
	public static function purifyObject($object)
	{
		return is_null($object) ? null : array_map([__CLASS__, 'htmlPurifier'], $object);
	}

    public static function setLikeFilter(?string $value = null, ?string $field = 'name'): array
	{
		return ['ilike', $field, $value ? '%' . str_replace(' ', '%', trim($value)) . '%' : null, false];
	}

    public static function isRestrictedStatus(int $status): bool
    {
        return in_array($status, Constants::RESTRICT_STATUS_LIST, true);
    }

    public static function getStatusRules($model): array
    {
        return [
            [['status'], 'default', 'value' => Constants::STATUS_DRAFT],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys(Constants::STATUS_LIST)],
            [['status'], 'filter', 'filter' => 'intval'],
            [
                ['status'],
                function ($attribute, $params) use ($model) {
                    self::validateStatusUpdate($attribute, $params, $model);
                },
            ],
        ];
    }

	public static function getPaginationRules($model): array
    {
        return [
            [['sort_dir', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['page'], function ($attribute, $params) use ($model) {
				if ($model->$attribute <= 0) {
					$model->addError($attribute, Yii::t('core', 'pageMustBeGreaterThanZero'));
				}
            }],
        ];
    }

	public static function setPagination(?array $params, $dataProvider): array {
		$pageSize = min((int)$dataProvider->getTotalCount(), (int)($params['page_size'] ?? Yii::$app->params['pagination']['pageSize']));

		return [
			'page' => (int)($params['page'] ?? 1) - 1,
			'pageSize' => $pageSize,
			'defaultPageSize' => $pageSize,
		];
	}

	public static function setSort(?array $params): array
	{
		$sortBy = $params['sort_by'] ?? 'id';
		$sort = $params['sort_dir'] ?? 'desc';
		$sortDir = match ($sort) {
			'asc' => SORT_ASC,
			'desc' => SORT_DESC,
			default => SORT_DESC,
		};

		return [
			'defaultOrder' => [
				$sortBy => $sortDir,
			],
		];
	}

    public static function validateStatusUpdate(string $attribute, ?array $params, $model): void
    {
        $newStatus = $model->$attribute;
        $oldStatus = $model->getOldAttribute($attribute);
        $statusList = Constants::STATUS_LIST;
        $allowedStatusUpdate = Constants::ALLOWED_UPDATE_STATUS_LIST;
        $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;


        if ($model->isAttributeChanged($attribute)) {
            if (!$model->isNewRecord) {
                if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && Yii::$app->coreAPI->superAdmin()) {
                    $model->addError($attribute, Yii::t('core', 'deletedStatusChanged', ['value' => $statusList[Constants::STATUS_DELETED]]));
                    return;
                }
            }
			
			if ($oldStatus !== null) {
				if (!isset($allowedStatusUpdate[$oldStatus])) {
					$model->addError($attribute, 'Invalid status transition');
					return;
				}
	
				if (in_array($oldStatus, $disallowedStatusUpdate)) {
					$model->addError($attribute, Yii::t('core', 'disallowedStatusUpdate', ['value' => $statusList[$oldStatus]]));
					return;
				}
	
				if (!in_array($newStatus, $allowedStatusUpdate[$oldStatus])) {
					if ($model->hasErrors($attribute)) {
						$model->addError($attribute, Yii::t('core', 'cannotChangeStatus', [
							'value' => $statusList[$oldStatus],
							'newValue' => $statusList[$newStatus]
						]));
						return;
					}
				}
			}
        }
    }

    public static function validateDependencies($attribute, $params, $validator, $model, array $dependencies): void
    {
        if (!$model->isNewRecord) {
            $changedAttributes = [];
            $fields = Yii::$app->params['dependenciesUpdate'][$model->tableName()];
            $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

            foreach ($fields as $field) {
                if ($model->isAttributeChanged($field)) {
                    $changedAttributes[] = $field;
                }

                if ($field === 'status' && in_array($model->status, $disallowedStatusUpdate)) {
                    $changedAttributes[] = $field;
                }
            }

            if (!empty($changedAttributes)) {
                $dataId = $model->id;

                foreach ($dependencies as $dependency) {
					$className = 'app\models\\' . $dependency['className'];
					
                    foreach ($dependency['field'] as $field) {
                        if ($className::find()->where([$field => $dataId])->exists()) {
                            foreach ($changedAttributes as $changedAttribute) {
                                $model->addError($changedAttribute, Yii::t('core', 'updatePermission', [
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

    public static function setChangelogFilters($model, array $logDates = [], array $logUsers = []): array
	{
		$conditions = ['and'];
        
        if (!$logDates){
            $logDates = ['created_at', 'updated_at', 'deleted_at'];
        }

        if (!$logUsers){
            $logUsers = ['created_by', 'updated_by', 'deleted_by'];
        }

		foreach ($logDates as $logDate) {
			$dateValue = $model->{$logDate};

			if (!empty($dateValue)) {
				if (strpos($dateValue, ',') !== false) {
					list($startDate, $endDate) = array_map('trim', explode(',', $dateValue));

					$conditions[] = [
						'and',
						['>=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $startDate],
						['<=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $endDate],
					];
				} else {
					$conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $dateValue];
				}
			}
		}

		foreach ($logUsers as $logUser) {
			if (!empty($model->{$logUser})) {
				$conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,$logUser}'"), $model->{$logUser}];
			}
		}

		return $conditions;
	}

	public static function validateRequiredFields($model, string $attribute, ?array $requiredFields, ?array $item = null): bool
	{
		$fields = $item ?? $model->$attribute;

		$extraFields = array_diff_key($fields, array_flip($requiredFields));
		if ($extraFields) {
			$model->addError($attribute, Yii::t('core', 'extraFields', [
				'label' => $model->getAttributeLabel($attribute),
				'fields' => implode(', ', array_keys($extraFields)),
				'allowed' => implode(', ', $requiredFields),
			]));
			return true;
		}

		$missingFields = array_diff($requiredFields, array_keys($fields));
		if ($missingFields) {
			$model->addError($attribute, Yii::t('core', 'missingFields', [
				'fields' => implode(', ', $missingFields),
			]));
			return true;
		}

		return false;
	}

	public static function nullFieldValidator($model, $attribute, $item): bool {
		$nullFields = array_keys(array_filter($item, function ($value) {
			return $value === null;
		}));
		if (!empty($nullFields)) {
			$model->addError($attribute, Yii::t('core', 'nullField', [
				'label' => $model->getAttributeLabel($attribute),
				'fields' => implode(', ', $nullFields),
			]));
			return true;
		}

		return false;
	}

    public static function getChangeLog($model, bool $insert): array
	{
		$timestamp = Yii::$app->coreAPI->getTimestamp();
		$username = Yii::$app->coreAPI->getUsername();
		$changeLog = [];

		if ($insert) {
			$changeLog = [
				'created_at' => $timestamp,
				'created_by' => $username,
				'updated_at' => null,
				'updated_by' => null,
				'deleted_at' => null,
				'deleted_by' => null,
			];
		} else {
			$changeLog = $model->detail_info['change_log'] ?? [];

			if (isset($model->status)) {
				if ($model->status === Constants::STATUS_DELETED) {
					$changeLog['deleted_at'] = $timestamp;
					$changeLog['deleted_by'] = $username;
				} else {
					if ($model->getDirtyAttributes()) {
						$changeLog['updated_at'] = $timestamp;
						$changeLog['updated_by'] = $username;
					}
				}
			}
		}

		return $changeLog;
	}

	public static function getErrors($errors = [])
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

	/**
	 * Creates a MongoDB regex filter for a like query.
	 *
	 * @param string|null $value the value to search for
	 * @return array the MongoDB regex filter
	 */
	public static function mongodbLikeFilter(?string $value): array
	{
		return [
			'$regex' => str_replace(' ', '.*', $value ?? ''),
			'$options' => 'i',
		];
	}

	public static function mongodbStatusFilter(?string $status): array
	{
		return [
			'$ne' => Constants::STATUS_DELETED,
			'$eq' => (int) $status,
		];
	}

}