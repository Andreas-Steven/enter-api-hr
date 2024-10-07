<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 0;
    const STATUS_DELETED = 4;

    public $current_password;
    public $password;
    public $repeat_password;

    const SCENARIO_LOGIN = 'login';
    const SCENARIO_CREATE = 'create';

    public static function tableName()
    {
        return 'employees';
    }

    public function rules()
    {
        return [
            [['username', 'password'], 'required', 'on' => self::SCENARIO_LOGIN],
            [['username'], 'string', 'min' => 4, 'max' => 255],
            [['username'], 'unique', 'on' => self::SCENARIO_CREATE],
            [['password'], 'string', 'min' => 6],
            [['password'], 'pattern', 'pattern' => '/^(?=.*[0-9])(?=.*[A-Z])([a-zA-Z0-9]+)$/'],
            [['detail_info'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeesQuery(get_called_class());
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $claims = \Yii::$app->jwt->parse($token)->claims();
        $uid = $claims->get('uid');
        if (!is_numeric($uid)) {
            Yii::$app->response->statusCode = 401;
            $data = [
                'code' => 401,
                'success' => false,
                'message' => 'Invalid token provided',
            ];
            return $data;
        }

        return static::findOne(['id' => $uid]);
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password)
	{
		return Yii::$app->security->validatePassword($password, $this->password_hash);
	}

    public function validateCurrentPassword()
	{
		if ( !Yii::$app->security->validatePassword($this->current_password, $this->password_hash) )
		{
			$this->addError('current_password', "Password Lama tidak sesuai");
		}
	}

}
