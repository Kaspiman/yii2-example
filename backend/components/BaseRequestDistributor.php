<?php

namespace backend\components;

use backend\models\User;
use backend\models\Request;
use backend\models\Process;
use backend\models\Work;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\db\ActiveQuery;

/**
 * Class BaseRequestDistributor
 *
 * @package components
 */
abstract class BaseRequestDistributor extends Component
{
    /**
     * Максимальное количество процессов, которое быть создано для конкретного пользователя
     *
     * @var int
     */
    protected $max;

    /**
     * Метод создания процессов (автоматически - демоном, вручную - пользователем из интерфейсов)
     *
     * @var int
     */
    protected $method;

    /**
     * @var \yii\mutex\MysqlMutex
     */
    protected $mutex;

    /**
     * @var int
     */
    protected $mutex_time = 3;

    /**
     * BaseRequestDistributor constructor.
     *
     * @param int $max
     * @param int $method
     * @param array $config
     */
    public function __construct(int $max, int $method, array $config = [])
    {
        $this->max = $max;
        $this->method = $method;
        $this->mutex = \Yii::$app->mutex;

        parent::__construct($config);
    }

    /**
     * Получение списка пользователей, которые могут взять/получить заявку в работу
     * (фильтр по подразделению, должности, etc)
     * @return \yii\db\ActiveQuery;
     */
    abstract protected function getRingers(): ActiveQuery;

    /**
     * Получение свободной заявки для пользователя, либо выдача свободной - свободному
     * @param int $request_id
     * @return \backend\models\Request|null
     */
    abstract protected function getUntreatedRequest(int $request_id = 0): ?Request;

    /**
     * Метод пытается распределить свободному пользователю заявку: указанную им вручную либо первую свободную
     * @param int $ringer_id
     * @param int $request_id
     * @return Process
     * @throws Exception
     */
    final public function distributeUnbound(int $ringer_id, int $request_id = 0): Process
    {
        $mutex_name = StringHelper::basename(self::className()) . 'distributeUnbound';

        if (!$this->mutex->acquire($mutex_name, $this->mutex_time)) {
            throw new Exception('Не удалось в течение 3-х секунд выделить свободную заявку');
        }

        $is_ringer_unbound = ArrayHelper::exists($this->getUnboundRingers(), function ($ringer) use ($ringer_id) {
            return $ringer->id == $ringer_id;
        });

        if (!$is_ringer_unbound) {
            throw new Exception('Не удалось взять заявку в работу: пользователь имеет максимальное количество заявок либо не начал работу');
        }

        $request = $this->getUntreatedRequest($request_id);

        if (!$request) {
            throw new Exception('Не удалось найти свободную заявку');
        }

        $process = $this->createProcess($ringer_id, $request->id);

        $this->mutex->release($mutex_name);

        return $process;
    }

    /**
     * Получает работников, которые готовы к обзвону по критериям:
     * 1) Сотрудники определенного отдела
     * 2) Сотрудники, которые имеют активные работы
     * 3) Сотрудники, которые имеют неполную очередь
     *
     * @return array
     */
    final public function getUnboundRingers(): array
    {
        $busy = Process::find()
            ->andWhere([
                'status' => [Process::STATUS_ACTIVE, Process::STATUS_IN_WORK],
                'method' => $this->method,
            ])
            ->groupBy('ringer_id')
            ->having('COUNT(id) >= :max', [':max' => $this->max]);

        $query = User::find()
            ->andWhere([
                'id' => $this->getRingers()->select([User::tableName() . '.id']),
            ])
            ->andWhere([
                'id' => Work::find()->andWhere(['status' => Work::STATUS_ACTIVE])->select('ringer_id'),
            ])
            ->andWhere([
                'not in',
                'id',
                $busy->select('ringer_id'),
            ])
            ->orderBy(['RAND()' => SORT_DESC]);

        return $query->all();
    }

    /**
     * @param int $ringer_id
     * @param int $request_id
     * @return Process
     * @throws Exception
     */
    protected function createProcess(int $ringer_id, int $request_id): Process
    {
        $process = new Process();

        $process->setAttributes([
            'ringer_id' => $ringer_id,
            'request_id' => $request_id,
            'method' => $this->method,
            'status' => Process::STATUS_ACTIVE,
        ]);

        if ($this->method === Process::METHOD_DAEMON) {
            $process->created_by = User::getRobotId(); // id 1
        }

        if (!$process->save()) {
            throw new Exception(
                "Ошибка сохранения модели Process: ringer_id: $ringer_id, request_id: $request_id"
                . $process->allErrors()
            );
        }

        return $process;
    }
}
