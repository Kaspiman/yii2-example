<?php

namespace backend\controllers;

use backend\models\Request;
use backend\models\Process;
use backend\components\BaseRequestDistributor;
use backend\components\ExampleRequestDistributor;
use backend\components\controllers\BaseRingingController;

/**
 * Class ExampleRingingController
 *
 * @package backend\controllers
 */
class ExampleRingingController extends BaseRingingController
{
    const MAX_REQUESTS = 10;

    /**
     * @inheritdoc
     */
    protected function createDistributor(): BaseRequestDistributor
    {
        return new ExampleRequestDistributor(
            self::MAX_REQUESTS,
            Process::METHOD_USER
        );
    }

    /**
     * @inheritdoc
     */
    protected function getBasePoolQuery()
    {
        return parent::getBasePoolQuery()->andWhere([
            Request::tableName() . '.status' => Request::STATUS_CONFIRMED,
            Request::tableName() . '.type' => Request::TYPE_PRE_APPROVED,
        ]);
    }
}
