<?php
namespace admin\modules\favorite\widgets;

use admin\base\ActiveRecord;
use Yii;
use admin\modules\favorite\assets\FavoriteBtnAsset;
use yii\base\InvalidConfigException;
use admin\modules\favorite\models\Favorite;
use yii\helpers\Html;
use admin\modules\favorite\FavoriteModule;

use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

class FavoriteBtn extends \yii\base\Widget
{
    /**
     * @var string Тип избранного элемента. Обязательное св-во
     */
    public $itemType;

    /**
     * @var int Идентификатор избранного элемента. Обязательное св-во
     */
    public $itemId;

    /**
     * @var bool Статус элемента/кнопки (true - элемент избран, false - элемент не избран).
     * По умолчанию статус определяется автоматически
     */
    public $status;

    public function init()
    {
        if (empty($this->itemType) || empty($this->itemId)) {
            throw new InvalidConfigException(Yii::t('admin', 'Параметры itemType и itemId должны быть определены'));
        }
        $this->status = $this->status ?? Favorite::status($this->itemType, $this->itemId);
        FavoriteBtnAsset::register($this->getView());
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return Html::button('<i class="fa fa-heart"></i>', [
            'id'    => self::btnId($this->itemType, $this->itemId),
            'type'  => 'button',
            'class' => 'favorite-btn js-favorite-btn',
            'data'  => [
                'item-type' => $this->itemType,
                'item-id'   => $this->itemId,
                'status'    => (int) $this->status,
            ],
            'title' => self::btnTitle($this->status),
        ]);
    }

    public static function btnId($itemType, $itemId)
    {
        return "id-favorite-btn_{$itemType}-{$itemId}";
    }

    public static function btnTitle($status)
    {
        return ($status == 1) ? Yii::t('admin', 'Удалить из избранного') : Yii::t('admin', 'Добавить в избранное');
    }
}