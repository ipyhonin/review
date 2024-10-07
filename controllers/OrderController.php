<?

namespace admin\modules\order\controllers\api;

use admin\models\api\LoginForm;
use admin\models\api\RegistrationForm;
use admin\models\Setting;
use admin\models\User;
use admin\modules\order\models\Order;
use admin\modules\order\models\OrderOfferForm;
use kartik\form\ActiveForm;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use ipyhonin\wizflow\WizardPlayAction;
use admin\modules\order\models\UserFormsStep;
use yii\helpers\Html;

class OrderController extends \admin\base\api\Controller
{
    public function actions()
    {
        return [
            'wizflow' => [
                'class'             => WizardPlayAction::class,
                'wizardManagerName' => 'wizflowOrderCreation',
                'finishActionId'    => 'wizflow-finish',
            ],
        ];
    }

    public function actionCreate()
    {
        $order = new Order();
        $order->loadDefaultValues(); // Для присвоения статусу заказа значения по умолчанию на основе схемы данных
        $user = new User();
        $user->scenario = 'create';
        $ifLoginSuccess = false;

        // Если пользователь авторизован, то отправляем его на форму создания заказа в личном кабинете
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/account/orders/create']);
        }

        if ($user->load(Yii::$app->request->post()) && $order->load(Yii::$app->request->post()) && $order->validate()) {
            // Если такой пользователь существует, то выполняем для него попытку входа
            if (User::findByUsername($user->email)) {
                $loginForm = new LoginForm();
                $loginForm->load(Yii::$app->request->post(), $user->formName());
                $ifLoginSuccess = $loginForm->login();
                $user->addErrors($loginForm->errors);
            } // Если такой пользователь не существует, то регистрируем его и выполняем вход
            else {
                if ($user->validate('phone')) { // ipyhonin: SK-26-ошибка-при-создании-заказа
                    $registrationForm = new RegistrationForm();
                    $registrationForm->load(Yii::$app->request->post(), $user->formName());
                    // Значение обязательного для пользователя поля Локация берем из заказа
                    $registrationForm->location_id = $order->location_id;
                    $user = $registrationForm->registration();
                    $user->addErrors($registrationForm->errors);
                    if ($user && Yii::$app->user->login($user, Setting::get('auth_time') ? Setting::get('auth_time') : null)) {
                        Yii::$app->session->setFlash(
                          'success',
                          Yii::t('admin', 'Вы успешно зарегистрированы на сайте. Регистрационные данные отправлены на Ваш e-mail')
                        );
                        $ifLoginSuccess = true;
                    }
                }
            }

            /**
             * После успешного входа проверяем наличие необходимых прав, создаем заказ и
             * отправляем пользователя на страницу со списком заказов в личном кабинете
             */
            if ($ifLoginSuccess) {
                if (Yii::$app->user->can('createOrder')) {
                    if ($order->save()) {
                        return $this->redirect(['/account/orders']);
                    }
                } else {
                    throw new ForbiddenHttpException(Yii::t('admin', 'У вас недостаточно прав для выполнения данного действия!'));
                }
            }
        }

        return $this->render(Yii::$app->getModule('admin')->activeModules['order']->settings['createOrderFormViewFile'], [
          'order' => $order,
          'user'  => $user,
        ]);
    }

    public function actionOfferValidation()
    {
        $orderOfferForm = new OrderOfferForm();
        $orderOfferForm->load(Yii::$app->request->post());
        $errors = ActiveForm::validate($orderOfferForm);

        $models = [];
        if (!$orderOfferForm->activeOrderId) {
            $models[] = new Order();
            if (Yii::$app->user->isGuest) {
                $models[] = ($orderOfferForm->loginFormEnabled) ? new LoginForm() : new RegistrationForm();
            }
        }

        foreach ($models as $model) {
            $model->load(Yii::$app->request->post());
            $errors = array_merge($errors, ActiveForm::validate($model));
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $errors;
    }

    public function actionSendOffer($orderId, $userId)
    {
        $order = Order::find()->where([Order::tableName().'.'.'id' => $orderId])->andWhere([
          'status' => [
            Order::STATUS_PUBLISHED,
            Order::STATUS_MODIFIED,
            Order::STATUS_OFFER,
          ],
        ])->one();
        $user = User::findOne($userId);

        if (!$order) {
            throw new BadRequestHttpException(Yii::t('admin', 'Активный заказ с номером {orderId} не найден.', ['orderId' => $orderId]));
        }
        if (!$user) {
            throw new BadRequestHttpException(Yii::t('admin', 'Пользователь с номером {userId} не найден.', ['userId' => $userId]));
        }

        $order->sendOffer($user);
    }

    public function actionWizflowFinish()
    {
        $wizardManager = Yii::$app->wizflowOrderCreation;
        $userFormsStepId = "OrderCreationWorkflow/step6";
        $wizardPath = $wizardManager->getWizState()['path'];
        $isGuest = isset($wizardPath[$userFormsStepId]);
        $messages = [];
    	
        /** Инициализация модели форм пользователя */
        if ($isGuest) {
            $userFormsStepModel = new UserFormsStep($wizardPath[$userFormsStepId]);
            unset($wizardPath[$userFormsStepId]);
        }

        /** Обработка активной формы пользователя */
        if ($isGuest && $userFormsStepModel->validate()) {
            $userForm = $userFormsStepModel->getActiveUserFormModel();
            
            if ($userFormsStepModel->activeUserFormName == 'RegistrationForm') {
                /** Обработка формы регистрации */
                $user = $userForm->registration();
                if ($user && Yii::$app->user->login($user, Setting::get('auth_time') ? Setting::get('auth_time') : null)) {
                    $messages[] = Yii::t('admin', 'Вы зарегистрированы на сайте. Регистрационные данные отправлены на Ваш e-mail.');
                }
            } elseif ($userFormsStepModel->activeUserFormName == 'LoginForm') {
                /** Обработка формы входа */
                if (!$userForm->login()) {
                    $messages[] = Yii::t('admin', 'Произошла непредвиденная ошибка при входе на сайт.');
                };
            }
        }

        /** Создание заказа */
        if (!Yii::$app->user->isGuest) {
            if (!Yii::$app->user->can('createOrder')) {
                $messages[] = Yii::t('admin', 'У вас недостаточно прав для выполнения данного действия! Заказ может быть создан только заказчиком.');
            } else {
                /** Инициализация модели заказа */
                $order = new Order();
                $order->loadDefaultValues(); // Для присвоения статусу заказа значения по умолчанию на основе схемы данных
                foreach ($wizardPath as $workflowStatusId => $attributes) {
                    $order->setAttributes($attributes);
                }
                $order->setScenario('notWorkflow');

                if ($order->save()) {
                    $link = Html::a($order->id, ['/account/orders/update', 'id' => $order->id], ['class' => 'd-inline']);
                    $messages[] = Yii::t('admin', 'Создан заказ №{link}', ['link' => $link]);
                } else {
                    $messages[] = Yii::t('admin', 'При создании заказа возникла непредвиденная ошибка. Попробуйте повторить попытку.');
                }
            }
        }

        $renderMethod = (Yii::$app->request->isAjax) ? 'renderAjax' : 'render';
        return $this->$renderMethod('@admin/modules/order/views/workflows/createOrder/stepfinal', [
    		'messages' => $messages,
    	]);
    }

    public function actionUserFormsStepValidation()
    {
        $model = new UserFormsStep();
        $model->load(Yii::$app->request->post());
        $model->validateActiveUserForm = false;
        $errors = ActiveForm::validate(
            $model,
            $model->forms[$model->activeUserFormName]['model']
        );

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $errors;
    }
}
