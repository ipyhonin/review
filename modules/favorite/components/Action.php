<?php
namespace admin\modules\favorite\components;

use yii\base\Action as BaseAction;
use admin\modules\favorite\models\Favorite;

class Action extends baseAction {

    /**
     * @var string Тип избранных элементов
     */
    public $type;

    /**
     * @var string Шаблон страницы со списком избранных элементов данного класса
     */
    public $view;

    /**
     * @inheritdoc
     */
    public function init() {
        if (!isset($this->type) || !isset($this->view)) {
            throw new InvalidConfigException(Yii::t('admin/favorite', 'Свойства "type" и "view" должны быть установлены'));
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run() {
        $items = Favorite::getItems($this->type);
        return $this->controller->render($this->view, [
            'items' => $items,
        ]);
    }
}