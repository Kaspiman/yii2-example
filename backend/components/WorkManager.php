<?php

namespace backend\components;

use backend\models\work;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class WorkManager
 *
 * @package backend\components
 */
class WorkManager extends Component
{
    /**
     * ID пользователя - звонаря
     * @var int
     */
    protected $ringer_id;

    /**
     * WorkManager constructor.
     *
     * @param int $ringer_id
     * @param array $config
     */
    public function __construct(int $ringer_id, array $config = [])
    {
        $this->ringer_id = $ringer_id;

        parent::__construct($config);
    }

    /**
     * @return Work
     * @throws Exception
     */
    public function start(): Work
    {
        if ($this->hasActiveWork()) {
            throw new Exception('У пользователя ' . $this->ringer_id . ' уже есть активная работа');
        }

        $work = new Work();

        $work->setAttributes([
            'ringer_id' => $this->ringer_id,
            'started_at' => time(),
            'status' => Work::STATUS_ACTIVE,
        ]);

        if (!$work->save()) {
            throw new Exception('Не удалось сохранить модель Work: ' . $work->allErrors());
        }

        return $work;
    }

    /**
     * Завершение работы, активные процессы пользователя закрываются, заявки возвращаются в список свободных
     * @return \backend\models\Work
     * @throws \yii\base\Exception
     */
    public function complete(): Work
    {
        $work = $this->getActiveWork();

        if (!$work) {
            throw new Exception('У пользователя ' . $this->ringer_id . ' не найдена активная работа');
        }

        $work->setAttributes([
            'status' => Work::STATUS_COMPLETE,
            'completed_at' => time(),
        ]);

        if (!$work->save()) {
            throw new Exception('Не удалось сохранить модель Work: ' . $work->allErrors());
        }

        $processes = Process::find()
            ->andWhere([
                'status' => [Process::STATUS_ACTIVE, Process::STATUS_IN_WORK],
                'ringer_id' => $this->ringer_id,
            ])
            ->all();

        foreach ($processes as $process) {
            $process->delete();
        }

        return $work;
    }

    /**
     * @return bool
     */
    public function hasActiveWork(): bool
    {
        $work = $this->getActiveWork();

        return $work instanceof Work;
    }

    /**
     * @return \backend\models\Work|null
     */
    protected function getActiveWork(): ?Work
    {
        return Work::find()
            ->andWhere(['ringer_id' => $this->ringer_id, 'status' => Work::STATUS_ACTIVE])
            ->one();
    }
}
