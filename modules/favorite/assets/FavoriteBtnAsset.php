<?
namespace admin\modules\favorite\assets;

class FavoriteBtnAsset extends \yii\web\AssetBundle {

    public $sourcePath = '@admin/modules/favorite/media/favorite-btn';
    public $css = [
        'favorite-btn.css',
    ];
    public $js = [
        'favorite-btn.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'admin\assets\FontAwesomeAsset',
        'admin\assets\pnotify\PNotifyAsset',
    ];    
}