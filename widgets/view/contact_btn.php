<?php
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\form\ActiveForm;

$c = $this->context;

echo Html::beginTag('div', [
    'id'                    => $c->id,
    'class'                 => "contact-btn $c->cssClass",
    'data-pjax-container'   => '',
]);
if (!$isContactRequest) {
    $form = ActiveForm::begin([
        //'action' => array_merge(Yii::$app->request->getQueryParams(), [0 => '', 'showContactFor' => $c->id]),
        'method' => 'post',
        'action' => ['/show-contact'],
        'options' => ['class' => 'contact-btn__form'],
    ]);
}
if ($showPhone) {
    $number = str_replace('-', '', filter_var($model->phone, FILTER_SANITIZE_NUMBER_INT));
    echo Html::a($model->phone, 'tel:' . $number, [
        'class' => "contact-btn__btn $c->btnCssClass",
        'data-pjax' => 0,
    ]);
} else {
    echo Html::button($c->title, [
        'type' => (!$isContactRequest) ? 'submit' : 'button',
        'class' => "contact-btn__btn $c->btnCssClass",
        'data' => ($showPopover) ? [
            'toggle' => 'collapse',
            'target' => "#{$c->id} .contact-btn__popover",
        ] : [],
        'area-expanded' => ($showPopover) ? 'true' : 'false',
    ]);
}
?>
<? if ($showPopover): ?>
    <div class="contact-btn__popover-container">
        <div class="contact-btn__popover collapse show <?= $c->popoverCssClass ?>">
            <div class="contact-btn__popover-body">
                <? if ($showAuthRequestMsg): ?>
                    <?
                        echo Yii::t('admin', 'Для просмотра контактов необходимо') . ' ' .
                        Html::a(Yii::t('admin', 'авторизоваться'), ['/user/login'], ['rel' => 'nofollow', 'data-pjax' => 0]) . ' ' .
                        Yii::t('admin', 'или') . ' ' .
                        Html::a(Yii::t('admin', 'зарегистрироваться'), $regRoute, ['rel' => 'nofollow', 'data-pjax' => 0]) . '.';
                    ?>
                <? elseif ($showAсcessDeniedMsg): ?>
                    <?= Yii::t('admin', 'Доступ к контактам закрыт, т.к. в вашем профиле не определен телефон и/или email') ?>
                <? elseif ($showHiddenPhoneMsg): ?>
                    <?= Yii::t('admin', 'Пользователь скрыл телефон') ?>
                <? elseif ($showWriteContacts): ?>
                    <div class="contact-btn__msg-btns-container">
                        <? if ($model->whatsapp && !$model->hide_phone) echo Html::a('WhatsApp', 'https://wa.me/' . $model->whatsapp, ['class' => 'contact-btn__msg-btn contact-btn__msg-btn_whatsapp', 'data-pjax' => 0]) ?>
                        <? if ($model->viber  && !$model->hide_phone) echo Html::a('Viber', 'viber://chat?number=' . $model->viber, ['class' => 'contact-btn__msg-btn contact-btn__msg-btn_viber', 'data-pjax' => 0]) ?>
                        <? if ($model->telegram) echo Html::a('Telegram', 'https://t.me/' . $model->telegram, ['class' => 'contact-btn__msg-btn contact-btn__msg-btn_telegram', 'data-pjax' => 0]) ?>
                        <?= Html::a('Email', 'mailto:' . $model->email, ['class' => 'contact-btn__msg-btn contact-btn__msg-btn_email', 'data-pjax' => 0]) ?>
                    </div>
                <? endif; ?>
            </div>
            <? if ($footerContent): ?>
                <div class="contact-btn__popover-footer">
                    <?= $footerContent ?>
                </div>
            <? endif; ?>
            <button id="<?= "{$c->id}__close-btn" ?>" type="button" class="close-btn close-btn_blue contact-btn__close-btn" data-target="<?= "#{$c->id} .contact-btn__popover" ?>" area-expanded="true"></button>
        </div>
    </div>
<? endif; ?>
<?
if (!$isContactRequest) {
    echo Html::hiddenInput('cbw_config', json_encode(get_object_vars($c)));
    ActiveForm::end();
}
echo Html::endTag('div');
?>

