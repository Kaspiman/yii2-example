<?php

namespace components\controllers;

use yii\base\Controller;
use backend\components\BaseRequestDistributor;
use backend\components\WorkManager;
use backend\models\Request;
use backend\models\Process;

/**
 * Базовый класс CRUD-интерфейса для сотрудников отделов, которые допущены к работе с заявками
 * Все лишнее - опущено, оставлены значащие экшены управления
 * Class BaseRingingController
 * @package backend\components\controllers
 */
abstract class BaseRingingController extends Controller
{
    /**
     * @return BaseRequestDistributor
     */
    abstract protected function createDistributor(): BaseRequestDistributor;

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionAppoint($id)
    {
        $request = $this->findRequest($id);

        $distributor = $this->createDistributor();

        $process = $distributor->distributeUnbound(\Yii::$app->user->getId(), $request->id);

        if ($process) {
            return $this->redirect(['view', 'id' => $process->id]);
        }

        \Yii::$app->session->setFlash(
            'warning',
            'Невозможно взять в работу заявку: превышено максимальное количество, обработайте уже назначенные Вам заявки'
        );

        return $this->redirect('index');
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionStartWork()
    {
        $workManager = new WorkManager(\Yii::$app->user->getId());

        $workManager->start();

        return $this->redirect(\Yii::$app->request->referrer);
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     */
    public function actionCompleteWork()
    {
        $workManager = new WorkManager(\Yii::$app->user->getId());

        $workManager->complete();

        return $this->redirect(\Yii::$app->request->referrer);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    protected function getBasePoolQuery()
    {
        return Request::find()
            ->joinWith(['source', 'city'])
            ->andWhere([
                'not in',
                Request::tableName() . '.id',
                Process::find()->select(['request_id'])->column(),
            ]);
    }
}
