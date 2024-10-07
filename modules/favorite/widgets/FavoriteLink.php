<?php
namespace admin\modules\favorite\widgets;

use Yii;
use admin\modules\favorite\assets\FavoriteBtnAsset;
use yii\helpers\Html;
use yii\helpers\Url;

class FavoriteLink extends \yii\base\Widget
{
    public $cssClass = 'favorite-btn';

    public $content = '<i class="fa fa-heart"></i>';

    public $url;

    public $title;

    public function init()
    {
        if (empty($this->url)) {
            $this->url = ['/favorite'];
        }
        if (empty($this->title)) {
            $this->title = Yii::t('app', 'Перейти в избранное');
        }
        FavoriteBtnAsset::register($this->getView());
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return Html::a($this->content, $this->url, [
            'class' => $this->cssClass,
            'title' => $this->title,
        ]);
    }
}