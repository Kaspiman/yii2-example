<?php

namespace backend\models;

use yii\behaviors\BlameableBehavior;
use backend\components\ActiveRecord;

/**
 * Class Work
 *
 * Модель создается каждый раз, когда сотрудник приступает к работе (с утра),
 * является индикатором активности работы и позволяет
 * запускать процессы. Управление работами производится через WorkManager.
 * После ухода с рабочего места сотрудник завершает работу.
 * Позволяет потом подвести статистику по сотрудникам.
 *
 * @package backend\models
 * @property integer $id
 * @property integer $status
 * @property integer $ringer_id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $started_at
 * @property integer $completed_at
 * @property User $ringer
 * @property User $createdBy
 * @property User $updatedBy
 */
class Work extends ActiveRecord
{
    const STATUS_ACTIVE = 1;

    const STATUS_COMPLETE = 2;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ringing_work';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ringer_id', 'started_at'], 'required'],
            [['status', 'ringer_id', 'created_by', 'updated_by', 'started_at', 'completed_at'], 'integer'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => 'Статус',
            'ringer_id' => 'Работник',
            'created_by' => 'Кто создал',
            'updated_by' => 'Кто обновил',
            'started_at' => 'Дата начала',
            'completed_at' => 'Дата завершения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRinger()
    {
        return $this->hasOne(User::className(), ['id' => 'ringer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }
}
