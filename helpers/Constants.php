<?php

namespace app\helpers;

class Constants extends CoreConstants
{
    const CODE_EXCEPTION = 'Z';
    const CODE_MAX_LENGTH = 4;
    const IS_READY_ON = 1;
    const IS_READY_OFF = 0;
    const FLAG_TYPE_IN = 1;
    const FLAG_TYPE_OUT = 2;
    const IS_MASTER_DATA = 1;
    const IS_NOT_MASTER_DATA = 0;

    public static function appRoles() {
        return [
            'enterEDC',
            'enterMD',
            'enterSSO',
        ];
    }

    public static function isMd() {
        return [
            self::IS_MASTER_DATA => self::IS_MASTER_DATA,
            self::IS_NOT_MASTER_DATA => self::IS_NOT_MASTER_DATA,
        ];
    }

    public static function productStatus() {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_MAINTENANCE => 'Maintenance',
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
            self::FLAG_TYPE_IN => 'In',
            self::FLAG_TYPE_OUT => 'Out',
        ];
    }
}