<?php
namespace admin\widgets;

use Yii;
use admin\models\User;
use admin\assets\ContactBtnAsset;
use admin\behaviors\UserTypesBehavior;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

class ContactBtnWidget extends \yii\base\Widget
{
    /**
     * Тип кнопки "Позвонить"
     */
    const TYPE_CALL = 0;

    /**
     * Тип кнопки "Написать"
     */
    const TYPE_WRITE = 1;
    
    /**
     * @var int Тип кнопки
     */
    public $type = self::TYPE_CALL;

    /**
     * @var string Ограничение на тип пользователя для исключения несанкционированного доступа к контактам,
     * так как для различных типов пользователей политика доступа к контактам может отличаться.
     * Тип пользователя следует определять данным параметром неявно через соответствующую данному типу роль.
     * См. admin\behaviors\UserTypeBehavior
     * Данный параметр является обязательным. Значение по умолчанию соответствует типу исполнитель (мастер).
     */
    public $userRoleFilter = -1;

    /**
     * @var int Идентификатор пользователя
     */
    public $userId;

    /**
     * @var string Заголовок кнопки
     */
    public $title = 'Позвонить';

    /**
     * @var string Значение атрибута class блока contact-btn (контейнера)
     */
    public $cssClass;

    /**
     * @var string Значение атрибута class элемента contact-btn__btn (кнопки)
     */
    public $btnCssClass;

    /**
     * @var string Значение атрибута class элемента contact-btn__popover (всплывающего окна)
     */
    public $popoverCssClass;

    /**
     * @var string Содержимое футера. Если не задано, то футер не отображается
     */
    public $footerContent = '';

    private $userObject = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->userId)) {
            throw new InvalidConfigException('userId parameter is not defined');
        }
        if (is_object($this->userId)) {
            $this->userObject = $this->userId;
            $this->userId = $this->userObject->id;
        }
        if ($this->userRoleFilter === -1) {
            $this->userRoleFilter = Yii::$app->user->types[UserTypesBehavior::USER_TYPE_MASTER]['role'];
        }
        $this->id = "cbw-{$this->userId}-{$this->type}";
        ContactBtnAsset::register($this->getView());
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        /** @var User $user */
        if (!empty($this->userObject)) {
            $user = $this->userObject;
        } else {
            $user = User::findOne($this->userId);
        }
        if (!$user) {
            //throw new NotFoundHttpException();
            return;
        }

        $roles = $user->roles;
        if (!in_array($this->userRoleFilter, $roles)) {
            // Для избежания критических ошибок, связанных с некорректным созданием фейковых заказов,
            // отказываемся от генерации исключения, заменяем на возврат пустой строки
            return '';
        }

        // Определяем, существует ли запрос показа контактов для данного экземпляра виджета
        $isContactRequest = (Yii::$app->request->isPjax && Yii::$app->request->post('cbw_config'));
        //if (Yii::$app->request->isAjax) {
        //    $showContactFor = Yii::$app->request->getQueryParam('showContactFor');
        //    $isContactRequest = ($showContactFor == $this->id);
        //}

        // Определяем условия показа сообщения о необходимости авторизации на сайте
        // ipyhonin: По решению Алексея и Ярослава закрываем контакты для всех неавторизованных пользователей
        $showAuthRequestMsg = (Yii::$app->user->isGuest);
        //$showAuthRequestMsg = ($this->userRoleFilter == 'Client' && Yii::$app->user->isGuest);

        // Определяем условия показа сообщения об ограничении доступа к контактам из-за
        // отсутствия собственных контактов в профиле
        $showAсcessDeniedMsg = (
            $this->userRoleFilter == 'Client' && 
            !Yii::$app->user->isGuest && 
            (
                empty(Yii::$app->user->identity->email) ||
                empty(Yii::$app->user->identity->phone)
            )
        );

        // Определяем условия показа контактов для отправки сообщений
        $showWriteContacts = ($this->type === self::TYPE_WRITE) && !$showAuthRequestMsg && !$showAсcessDeniedMsg;

        // Определяем условия показа сообщения о скрытии телефона
        $showHiddenPhoneMsg = ($this->type === self::TYPE_CALL && $user->hide_phone);

        // Определяем условия показа всплывающего окно
        $showPopover = $isContactRequest && (
            $showWriteContacts ||
            $showAuthRequestMsg ||
            $showHiddenPhoneMsg ||
            $showAсcessDeniedMsg
        );

        // Определяем условия показа телефона
        $showPhone = $isContactRequest && ($this->type === self::TYPE_CALL) && !$user->hide_phone && !$showAсcessDeniedMsg;

        return $this->render('contact_btn', [
            'model'                 => $user,
            'isContactRequest'      => $isContactRequest,
            'showWriteContacts'     => $showWriteContacts,
            'showAuthRequestMsg'    => $showAuthRequestMsg,
            'showHiddenPhoneMsg'    => $showHiddenPhoneMsg,
            'showAсcessDeniedMsg'   => $showAсcessDeniedMsg,
            'showPopover'           => $showPopover,
            'showPhone'             => $showPhone,
            'regRoute'              => $this->userRoleFilter == 'Client' ? ['/user/registration/master'] : ['/user/registration'],
        ]);
    }
}