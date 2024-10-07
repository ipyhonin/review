<?

namespace admin\modules\order\widgets;

use admin\models\api\LoginForm;
use admin\models\api\RegistrationForm;
use admin\models\Setting;
use admin\models\User;
use admin\modules\order\assets\OrderOfferAsset;
use admin\modules\order\models\Order;
use admin\modules\order\models\OrderOfferForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;

class OrderOfferWidget extends Widget
{
    /**
     * @var array|string URL обработчика формы.
     * Данный параметр будет обработан вызовом метода yii\helpers\Url::to()
     * @internal ipyhonin: PT89 (Наладить форму регистрации на главной странице)
     */
    public $action = '';
    /**
     * @var admin\models\User Экземпляр модели исполнителя, которому необходимо отправить предложение заказа.
     * Является обязательным параметром в режиме прямого предложения заказа
     */
    public $masterModel;
    /**
     * @var bool Нужно ли активировать режим прямого предложения заказа
     */
    public $offer = true;
    /**
     * @var array|string URL обработчика, который будет осуществлять Ajax-валидацию полей формы.
     * Данный параметр будет обработан вызовом метода yii\helpers\Url::to()
     */
    public $validationUrl = '/order/offer-validation';

    public function init()
    {
        parent::init();

        if ($this->offer && empty($this->masterModel)) {
            throw new InvalidConfigException("Required 'masterModel' parameter has not been set");
        }

        OrderOfferAsset::register($this->getView());
    }

    public function run()
    {
        // Инициализация данных
        $registrationForm = null;
        $loginForm = null;
        $activeOrders = [];

        if (Yii::$app->user->isGuest) {
            $registrationForm = new RegistrationForm();
            $loginForm = new LoginForm();
        } elseif ($this->offer) {
            $user = User::findOne(Yii::$app->user->id);
            $activeOrders = $user->getOrders()
              ->where([
                'status' => [
                  Order::STATUS_PUBLISHED,
                  Order::STATUS_MODIFIED,
                  Order::STATUS_OFFER,
                ],
              ])->all();
        }

        $orderOfferForm = new OrderOfferForm();

        $order = new Order();
        if ($this->offer) {
            $order->status = Order::STATUS_OFFER;
        } else {
            $order->loadDefaultValues(); // Присвоение статусу заказа значения по умолчанию на основе схемы данных
            $statusList = $order->statusList;
            $order->status = Order::STATUS_ON_REVIEW;
        }

        /**
         * Обработка форм
         */
        if ($orderOfferForm->load(Yii::$app->request->post()) && $orderOfferForm->validate()) {
            /**
             * Обработка форм пользователя
             */
            if (Yii::$app->user->isGuest) {
                /**
                 * Обработка формы регистрации
                 */
                if (!$orderOfferForm->loginFormEnabled) {
                    if ($registrationForm->load(Yii::$app->request->post()) && $registrationForm->validate()) {
                        $user = $registrationForm->registration();

                        if ($user && Yii::$app->user->login($user, Setting::get('auth_time') ? Setting::get('auth_time') : null)) {
                            Yii::$app->session->setFlash(
                              'success',
                              Yii::t('admin', 'Вы зарегистрированы на сайте. Регистрационные данные отправлены на Ваш e-mail')
                            );
                        }
                    }
                /**
                 * Обработка формы входа
                 */
                } else {
                    if ($loginForm->load(Yii::$app->request->post()) && $loginForm->validate()) {
                        $loginForm->login();
                    }
                }
            }

            if (!Yii::$app->user->isGuest) {
                if ($orderOfferForm->activeOrder) {
                    $orderOfferForm->activeOrder->sendOffer($this->masterModel);
                    $orderOfferForm->activeOrderId = null;
                    $orderOfferForm->activeOrder = null;
                } else {
                    if (!Yii::$app->user->can('createOrder')) {
                        throw new ForbiddenHttpException(Yii::t('admin', 'У вас недостаточно прав для выполнения данного действия!'));
                    }
                    /**
                     * Обработка формы нового заказа
                     */
                    if ($order->load(Yii::$app->request->post())) {
                        if ($order->save()) {
                            $link = Html::a($order->id, ['/account/orders/update', 'id' => $order->id], ['class' => 'd-inline']);
                            Yii::$app->session->addFlash('success', Yii::t('admin', 'Создан заказ №{link}', ['link' => $link]));
                            if ($this->offer) {
                                $order->sendOffer($this->masterModel);
                            }
                            // Заново инициализируем экземпляр заказа для того чтобы очистить поля формы
                            unset($order);
                            $order = new Order();
                            if ($this->offer) {
                                $order->status = Order::STATUS_OFFER;
                            } else {
                                $order->loadDefaultValues(); // Присвоение статусу заказа значения по умолчанию на основе схемы данных
                                $statusList = $order->statusList;
                                $order->status = Order::STATUS_ON_REVIEW;
                            }
                        } else {
                            Yii::$app->session->addFlash(
                              'error',
                              Yii::t('admin', 'При создании заказа возникла непредвиденная ошибка. Попробуйте повторить попытку.')
                            );
                        }
                    }
                }
            }
        }

        return $this->render('order-offer', [
          'modalId'          => $this->id,
          'orderOfferForm'   => $orderOfferForm,
          'order'            => $order,
          'registrationForm' => $registrationForm,
          'loginForm'        => $loginForm,
          'activeOrders'     => $activeOrders,
          'validationUrl'    => $this->validationUrl,
          'masterId'         => $this->offer ? $this->masterModel->id : null,
          'offerMode'        => $this->offer,
          'statusList'       => $statusList,
        ]);
    }
}