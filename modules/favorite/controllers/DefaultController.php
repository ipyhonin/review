<?php
namespace admin\modules\favorite\controllers;

use Yii;
use admin\base\api\Controller;
use yii\web\NotFoundHttpException;
use admin\modules\favorite\components\Action;
use admin\modules\favorite\FavoriteModule;
use admin\modules\favorite\models\Favorite;
use admin\widgets\PNotify;
use yii\helpers\Json;
use admin\modules\favorite\widgets\FavoriteBtn;
use yii\helpers\Html;

class DefaultController extends Controller {

    public $_actions = [];

    /**
     * @inheritdoc
     */
    public function init() {
        $module = FavoriteModule::getInstance();
        $this->defaultAction = $module->getDefaultAction();
        foreach ($module->items as $type => $item) {
            $this->_actions[$type] = [
                'class' => Action::class,
                'type' => $type,
                'view' => $item['view'],
            ];
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        return $this->_actions;
    }

    public function actionIndex() {
        $module = FavoriteModule::getInstance();
        if (empty($module->indexView)) {
            throw new NotFoundHttpException(Yii::t('admin/favorite', 'Страница не найдена'));
        }
        return $this->render($module->indexView, [

        ]);
    }

    public function actionToggle($itemType, $itemId, $status) {
        if (!Yii::$app->request->isAjax) {
            return;
        }
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $favorite = new Favorite();
        $link = Html::a(Yii::t('admin', 'Перейти в избранное'), ['/favorite']);

        if ($status == 1) {
            $favorite->deleteItem($itemType, $itemId);
            $msgSuccess = Yii::t('admin', 'Элемент удален из избранного. {link}', ['link' => $link]);
        } else {
            $favorite->addItem($itemType, $itemId);
            $msgSuccess = Yii::t('admin', 'Элемент добавлен в избранное. {link}', ['link' => $link]);
        }
        
        $msgError = Yii::t('admin', 'По какой-то причине статус элемента не поменялся. Попробуйте еще раз или обратитесь в службу поддержки');
        $newStatus = (int) Favorite::status($itemType, $itemId);
        $pnotify = new PNotify();

        if ($status == $newStatus) {
            $alertOptions = $pnotify->getNotificationOptions([
                'type' => 'error',
                'text' => $msgError,
            ], false);
        } else {
            $alertOptions = $pnotify->getNotificationOptions([
                'type' => 'success',
                'text' => $msgSuccess,
            ], false);
        }
        /**
         * При разборе json на стороне клиента возникает ошибка с элементом stack.
         * Для устранения данной ошибки, присвоим данному элементу временное валидное значение,
         * а в JS установим как нужно
         */  
        $alertOptions['stack'] = 'any valid value';

        return [
            'newStatus'     => $newStatus,
            'alertOptions'  => Json::encode($alertOptions),
            'btnId'         => FavoriteBtn::btnId($itemType, $itemId),
            'newTitle'      => FavoriteBtn::btnTitle($newStatus),
        ];
    }

}