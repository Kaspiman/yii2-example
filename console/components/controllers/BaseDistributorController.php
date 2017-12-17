<?php

namespace console\components\controllers;

use backend\components\BaseRequestDistributor;
use backend\models\Process;

/**
 * Class BaseDistributorController
 *
 * @package console\components\controllers
 */
abstract class BaseDistributorController extends BaseDaemonController
{
    /**
     * @var BaseRequestDistributor
     */
    protected $distributor;

    /**
     * @return BaseRequestDistributor
     */
    abstract protected function createDistributor(): BaseRequestDistributor;

    /**
     * @inheritdoc
     */
    final public function init()
    {
        parent::init();

        $this->distributor = $this->createDistributor();
    }

    /**
     * @return \backend\models\User[]
     */
    final protected function defineJobs(): array
    {
        return \Yii::$app->db->useMaster(function () {
            return $this->distributor->getUnboundRingers();
        });
    }

    /**
     * @param \backend\models\User $user
     * @return bool
     * @throws \yii\base\Exception
     */
    final protected function doJob($user): bool
    {
        $process = $this->distributor->distributeUnbound($user->id);

        return $process instanceof Process;
    }
}
