<?php

namespace app\core;

/**
 * DO NOT CHANGE VALUES OF THIS CoreConstants
 * IF YOU CHANGE VALUES, ALL DATA IN DATABASE WILL BE INCORRECT
 */

class CoreConstants
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DRAFT = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_DELETED = 4;
    const STATUS_MAINTENANCE = 5;

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_DELETE = 'delete';
    const SCENARIO_DRAFT = 'draft';
    const SCENARIO_VIEW = 'view';
    const SCENARIO_COMPLETE = 'complete';
    const SCENARIO_RECEIVE = 'receive';

    const STATUS_NOT_DELETED = [
        '<>', 'status', self::STATUS_DELETED
    ];

    const DEVICE_APP = 'APP';

    public static function scenarioUpdate() {
        return [
            self::SCENARIO_UPDATE, self::SCENARIO_DELETE
        ];
    }

    public static function restrictStatus() {
        return [
            self::STATUS_DELETED,
            self::STATUS_COMPLETED,
        ];
    }

    public static function statusList() {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DELETED => 'Deleted',
            self::STATUS_MAINTENANCE => 'Maintenance',
        ];
    }

    public static function allowedStatusUpdate() {
        return [
			self::STATUS_DRAFT => [
				self::STATUS_INACTIVE,
				self::STATUS_ACTIVE,
				self::STATUS_DELETED,
				self::STATUS_MAINTENANCE
			],
			self::STATUS_ACTIVE => [
				self::STATUS_COMPLETED
			],
			self::STATUS_INACTIVE => [
				self::STATUS_ACTIVE,
				self::STATUS_DRAFT,
				self::STATUS_DELETED
			],
			self::STATUS_MAINTENANCE => [
				self::STATUS_INACTIVE,
				self::STATUS_ACTIVE,
				self::STATUS_DRAFT,
				self::STATUS_DELETED
			],
		];
    }

    public static function disallowedStatusUpdate() {
        return [
			self::STATUS_COMPLETED,
			self::STATUS_DELETED
		];
    }
}