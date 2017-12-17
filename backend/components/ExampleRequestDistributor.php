<?php

namespace backend\components;

use backend\models\User;
use backend\models\Request;
use backend\models\Process;
use yii\db\ActiveQuery;

/**
 * Class ExampleRequestDistributor
 *
 * @package backend\components
 */
class ExampleRequestDistributor extends BaseRequestDistributor
{
    /**
     * @inheritdoc
     */
    protected function getUntreatedRequest(int $request_id = 0): ?Request
    {
        $query = Request::find()
            ->andWhere([
                'type' => Request::TYPE_PRE_APPROVED,
                'status' => Request::STATUS_CONFIRMED,
            ])
            ->andWhere([
                'not in',
                'id',
                Process::find()->select(['request_id']),
            ])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($request_id) {
            $query->andWhere([
                'id' => $request_id,
            ]);
        }

        return $query->one();
    }

    /**
     * @inheritdoc
     */
    protected function getRingers(): ActiveQuery
    {
        return User::find()->andWhere([]); // Выборка пользователей по подразделению опущена
    }
}
