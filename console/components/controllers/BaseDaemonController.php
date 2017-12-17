<?php

namespace console\components\controllers;

use yii\console\Exception;
use yii\console\Controller;

/**
 * Реализация демона, может запускаться из консоли вручную, может управляться через supervisord
 * Class BaseDaemonController
 *
 * @package console\components\controllers
 */
abstract class BaseDaemonController extends Controller
{
    const EVENT_BEFORE_JOB = 'EVENT_BEFORE_JOB';

    const EVENT_AFTER_JOB = 'EVENT_AFTER_JOB';

    const EVENT_BEFORE_ITERATION = 'event_before_iteration';

    const EVENT_AFTER_ITERATION = 'event_after_iteration';

    /**
     * @var boolean
     */
    private static $stopFlag = false;

    /**
     * @var int
     */
    protected $sleep = 5;

    /**
     * @return array
     */
    abstract protected function defineJobs(): array;

    /**
     * @param $job
     * @return bool
     */
    abstract protected function doJob($job): bool;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->initSignalHandlers();
    }

    /**
     * Список базовых сигналов демона
     *
     * @return array
     */
    private function getSignals(): array
    {
        return [
            SIGTERM => 'stop',
            SIGINT => 'stop',
            SIGQUIT => 'stop',
        ];
    }

    /**
     * @throws Exception
     */
    private function initSignalHandlers()
    {
        foreach ($this->getSignals() as $signal => $method) {
            if (!pcntl_signal($signal, [self::className(), $method])) {
                \Yii::error(self::className() . ': ' . 'Ошибка установки обработчика сигнала ' . $signal);
                throw new Exception('Ошибка установки обработчика сигнала ' . $signal);
            }
        }
    }

    /**
     *
     */
    final private static function stop()
    {
        self::$stopFlag = true;
    }

    /**
     * Основной цикл демона
     * @return int
     */
    final public function actionIndex()
    {
        while (!self::$stopFlag) {
            $this->trigger(self::EVENT_BEFORE_ITERATION);

            $jobs = $this->defineJobs();

            if ($jobs && !empty($jobs)) {
                while (($job = $this->defineJobExtractor($jobs)) !== null) {
                    pcntl_signal_dispatch();

                    $this->trigger(self::EVENT_BEFORE_JOB);

                    try {
                        $this->doJob($job);
                    } catch (\Exception $exception) {
                        $this->err($exception->getMessage());
                        \Yii::error(
                            'Ошибка демона ' . self::className() . ' при выполнении работы: ' . $exception->getMessage(),
                            'daemon'
                        );
                    }

                    $this->trigger(self::EVENT_AFTER_JOB);
                }
            } else {
                sleep($this->sleep);
            }

            pcntl_signal_dispatch();

            $this->trigger(self::EVENT_AFTER_ITERATION);
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @param array $jobs
     * @return mixed
     */
    protected function defineJobExtractor(array &$jobs)
    {
        return array_shift($jobs);
    }
}
