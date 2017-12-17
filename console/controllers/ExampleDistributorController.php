<?php

namespace console\controllers;

use backend\components\ExampleRequestDistributor;
use backend\components\BaseRequestDistributor;
use backend\models\Process;
use console\components\controllers\BaseDistributorController;

/**
 * Class ExampleDistributorController
 *
 * @package console\controllers
 */
class ExampleDistributorController extends BaseDistributorController
{
    const MAX_REQUESTS = 10;

    /**
     * @inheritdoc
     */
    protected function createDistributor(): BaseRequestDistributor
    {
        return new ExampleRequestDistributor(
            self::MAX_REQUESTS,
            Process::METHOD_DAEMON
        );
    }
}
