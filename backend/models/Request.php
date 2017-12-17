<?php

namespace backend\models;

use backend\components\ActiveRecord;

/**
 * Class Request
 * Большая модель заявки, хранит все данные о пользователе и его желании взять кредит, реализация опущена, много полей
 * и валидации, статусы и реляции, неинтересна по сути
 * @package backend\models
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property int $type
 * @property ... etc
 */
class Request extends ActiveRecord
{

}
