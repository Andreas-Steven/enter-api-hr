<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "brand".
 *
 * @property int $id
 * @property string $name
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class Brand extends \yii\db\ActiveRecord
{
    public $change_log;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'brand';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['detail_info'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
            [['status'], 'default', 'value' => 2],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'filter', 'filter' => 'intval'],
            [['name', 'status'], 'validateDependencies', 'on' => Constants::scenarioUpdate()],
            [['status'], 'validateStatusChange', 'on' => Constants::scenarioUpdate()],
        ];
    }


    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\BrandQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\BrandQuery(get_called_class());
    }

    public function validateDependencies($attribute, $params, $validator)
    {
        if (!$this->isNewRecord) {
            $changedAttributes = [];
            $fields = ['name', 'status'];

            foreach ($fields as $field) {
                if ($this->isAttributeChanged($field)) {
                    $changedAttributes[] = $field;
                }

                if ($field == 'status') {
                    if ($this->isAttributeChanged($field) && Yii::$app->coreAPI->restrictStatusUpdate($this->status)) {
                        $changedAttributes[] = $field;
                    }
                } 
            }

            if (!empty($changedAttributes)) {
                $brandId = $this->id;

                $isBrandUsed = Product::find()->where(['brand_id' => $brandId])->exists();

                // if ($isBrandUsed && !Yii::$app->coreAPI->superAdmin()) {
                if ($isBrandUsed) {
                    foreach ($changedAttributes as $changedAttribute) {
                        $this->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                            'label' => $this->getAttributeLabel($changedAttribute),
                            'model' => $this->tableName(),
                        ]));
                    }
                }
            }
        }
    }

    public function validateStatusChange($attribute, $params, $validator)
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute)) {
            $oldStatus = $this->getOldAttribute($attribute);
            $newStatus = $this->$attribute;

            if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && !Yii::$app->coreAPI->superAdmin()) {
                $this->addError($attribute, Yii::t('app', 'deletedStatusChange', ['status' => Constants::statusList()[Constants::STATUS_DELETED]]));
            }
        }
    }

    /**
     * Custom validation method to check if brand_id is used in the Product model.
     */
    public function validateBrandUsage($attribute, $params)
    {
        if (Product::find()->where(['brand_id' => $this->id])->exists()) {
            $this->addError($attribute, 'Cannot update brand name because it is used in the other products.');
        }
    }


    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];

            return true;
        }

        return false;
    }
}
