<?php

namespace backend\models;

use backend\components\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * Class Process
 *
 * Модель процесса, которая "запускается" над заявкой, пользователь либо демон создает процесс
 * пользователь двигает процесс по статусам, ведет работу, комментирует и завершает.
 * После завершения процесса (достижения последнего статуса) заявка считается обработанной и заканчивает свой бизнес-путь
 *
 * @package backend\models
 * @property integer $id
 * @property integer $method
 * @property integer $type
 * @property integer $status
 * @property string $comment
 * @property integer $request_id
 * @property integer $ringer_id
 * @property integer $result_id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $started_at
 * @property integer $completed_at
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $createdBy
 * @property User $updatedBy
 * @property User $ringer
 * @property Request $request
 */
class Process extends ActiveRecord
{
    const STATUS_ACTIVE = 1;

    const STATUS_IN_WORK = 2;

    const STATUS_COMPLETE = 3;

    const METHOD_DAEMON = 0;

    const METHOD_USER = 1;

    const TYPE_DECLINED = 0;

    const TYPE_APPROVED = 10;

    const SCENARIO_COMPLETE = 'complete';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::className(),
            TimestampBehavior::className(),
        ];
    }

    /**
     * @return $this
     */
    public function start()
    {
        $this->status = self::STATUS_IN_WORK;

        $this->started_at = time();

        return $this;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function complete(int $type)
    {
        $this->type = $type;

        $this->status = self::STATUS_COMPLETE;

        $this->completed_at = time();

        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function addComment(?string $comment)
    {
        if (!empty($comment)) {
            if (!empty($this->comment)) {
                $this->comment .= ' | ' . $comment;
            } else {
                $this->comment = $comment;
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ringing_process';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['method', 'status', 'request_id', 'ringer_id'], 'required'],
            [
                [
                    'method',
                    'status',
                    'type',
                    'request_id',
                    'ringer_id',
                    'result_id',
                    'created_by',
                    'updated_by',
                    'started_at',
                    'completed_at',
                    'created_at',
                    'updated_at',
                ],
                'integer',
            ],
            [['comment'], 'string'],
            [
                ['result_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Result::className(),
                'targetAttribute' => ['result_id' => 'id'],
            ],
            [
                [
                    'result_id',
                ],
                'required',
                'on' => [self::SCENARIO_COMPLETE],
            ],
            [
                ['ringer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::className(),
                'targetAttribute' => ['ringer_id' => 'id'],
            ],
            [
                ['request_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Request::className(),
                'targetAttribute' => ['request_id' => 'id'],
            ],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::className(),
                'targetAttribute' => ['created_by' => 'id'],
            ],
            [
                ['updated_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::className(),
                'targetAttribute' => ['updated_by' => 'id'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'method' => 'Метод распределения',
            'status' => 'Статус',
            'type' => 'Тип',
            'comment' => 'Комментарий',
            'request_id' => 'Заявка',
            'ringer_id' => 'Звонарь',
            'result_id' => 'Результат обработки',
            'created_by' => 'Процесс создан',
            'updated_by' => 'Кто обновил',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
            'started_at' => 'Начало работы сотрудника',
            'completed_at' => 'Конец работы сотрудника',
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


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest()
    {
        return $this->hasOne(Request::className(), ['id' => 'request_id']);
    }
}
