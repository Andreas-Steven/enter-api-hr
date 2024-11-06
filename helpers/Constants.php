<?php

namespace app\helpers;

class Constants
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
    const SCENARIO_COMPLETED = 'completed';
    const CODE_EXCEPTION = 'Z';
    const CODE_MAX_LENGTH = 4;
    const CSRF_VALIDATION = false;
    const AUTH_EXCEPTION = '*';
    const STATUS_NOT_DELETED = [
        '<>', 'status', self::STATUS_DELETED
    ];
    const IS_READY_ON = 1;
    const IS_READY_OFF = 0;
    const FLAG_TYPE_IN = 1;
    const FLAG_TYPE_OUT = 2;

    public static function verbsAction() {
        return [
            'index'     => ['post'],
            'data'      => ['post'],
            'create'    => ['post'],
            'update'    => ['put'],
            'delete'    => ['delete'],
        ];
    }

    public static function statusList() {
        return [
            self::STATUS_INACTIVE       => 'Inactive',
            self::STATUS_ACTIVE         => 'Active',
            self::STATUS_DRAFT          => 'Draft',
            self::STATUS_COMPLETED      => 'Completed',
            self::STATUS_DELETED        => 'Deleted',
            self::STATUS_MAINTENANCE    => 'Maintenance',
        ];
    }

    public static function scenarioUpdate() {
        return [
            self::SCENARIO_UPDATE, self::SCENARIO_DELETE
        ];
    }

    public static function productStatus() {
        return [
            self::STATUS_INACTIVE       => 'Inactive',
            self::STATUS_ACTIVE         => 'Active',
            self::STATUS_DRAFT          => 'Draft',
            self::STATUS_MAINTENANCE    => 'Maintenance',
        ];
    }

    public static function productIsReady() {
        return [
            self::IS_READY_ON => self::IS_READY_ON,
            self::IS_READY_OFF => self::IS_READY_OFF,
        ];
    }

    public static function flagTypeList() {
        return [
            self::FLAG_TYPE_IN  => 'In',
            self::FLAG_TYPE_OUT => 'Out',
        ];
    }

    public static function restrictStatus() {
        return [
            self::STATUS_DELETED,
            self::STATUS_COMPLETED,
        ];
    }
}