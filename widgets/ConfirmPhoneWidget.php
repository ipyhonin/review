<?php
namespace admin\widgets;

use Yii;
use yii\widgets\ActiveForm;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use admin\assets\ConfirmPhoneAsset;

class ConfirmPhoneWidget extends \yii\base\Widget
{
    /**
     * @var string CSS id модального окна
     */
    public $modalId;

    /**
     * @var ActiveForm Экземпляр формы. Обязательный параметр
     */
    public $form;

    /**
     * @var string CSS id формы. Обязательный параметр
     */
    public $formId;

    /**
     * @var Model Экземпляр модели формы
     */
    public $formModel;

    /**
     * @var string Атрибут модели для номера телефона
     */
    public $phoneAttribute = 'phone';

    /**
     * @var string CSS id атрибута модели для номера телефона
     */
    public $phoneAttributeInputId;

    /**
     * @var string Атрибут модели для одноразового пароля
     */
    public $otpAttribute = 'otp_phone';

    /**
     * @var string CSS id атрибута модели для одноразового пароля
     */
    public $otpAttributeInputId;

    /**
     * @var string Url для отправки запроса на получение одноразового пароля
     */
    public $getOtpUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!isset($this->modalId)) {
            $this->modalId = "id-phone-confirm-modal_{$this->getId()}";
        }

        if (empty($this->form)) {
            throw new InvalidConfigException("'form' parameter is not defined");
        }

        if (empty($this->formId)) {
            throw new InvalidConfigException("'formId' parameter is not defined");
        }

        if (empty($this->formModel)) {
            throw new InvalidConfigException("'formModel' parameter is not defined");
        }

        if (!isset($this->phoneAttributeInputId)) {
            $this->phoneAttributeInputId = Html::getInputId($this->formModel, $this->phoneAttribute);
        }

        if (!isset($this->otpAttributeInputId)) {
            $this->otpAttributeInputId = Html::getInputId($this->formModel, $this->otpAttribute);
        }

        if (!isset($this->getOtpUrl)) {
            $this->getOtpUrl = Url::to(['/user/send-otp']);
        }

        ConfirmPhoneAsset::register($this->getView());

        Yii::$app->session->remove('phone-otp__phone');
        Yii::$app->session->remove('phone-otp__otp');

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('confirm_phone', [
            'modalId'               => $this->modalId,
            'form'                  => $this->form,
            'formId'                => $this->formId,
            'formModel'             => $this->formModel,
            'phoneAttributeInputId' => $this->phoneAttributeInputId,
            'otpAttribute'          => $this->otpAttribute,
            'otpAttributeInputId'   => $this->otpAttributeInputId,
            'getOtpUrl'             => $this->getOtpUrl,
            'phoneAttribute'        => $this->phoneAttribute,
        ]);
    }
}