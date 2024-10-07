<?php
use yii\widgets\MaskedInput;

$phoneFieldId = "{$modalId}__phone-field";
$getOtpBtnId = "{$modalId}__get-otp-btn";
$pjaxContainer = "#$modalId .invalid-feedback";

$fieldOptions = [
    'options' => [
        'class' => '',
    ],
    'inputOptions' => [
        'class' => 'form-control confirm-phone__otp',
    ],
    'template' => '{input}{hint}{error}',
];

$this->registerJs(<<<JS
function requestOtp() {
    let url = '$getOtpUrl?phone=' + $('#$getOtpBtnId').attr('data-phone');
    $.pjax({
        url: url,
        container: '$pjaxContainer',
        push: false,
        replace: false,
        timeout: 10000
    });
}
$(document).on('input', '#$phoneAttributeInputId', function() {
    $('#$phoneFieldId').text(this.value);
    $('#$getOtpBtnId').attr('data-phone', this.value);
})
$('#$formId').on('afterValidate', function(event, messages, errorAttributes) {
    if (
        $(this).data('yiiActiveForm').submitting &&
        errorAttributes.length == 1 &&
        errorAttributes[0].name == '$otpAttribute'
    ) {
        $('#$modalId').modal('show');
        // Удаляем сообщение об ошибке для исключения его из errorSummary
        delete messages['$otpAttributeInputId'];
    } 
    if (errorAttributes.length > 1) {
        // Удаляем сообщение об ошибке для исключения его из errorSummary
        delete messages['$otpAttributeInputId'];
    }
})
$('#$formId').on('beforeSubmit', function() {
    $('$pjaxContainer').hide();
    $('.confirm-phone__spinner').show();
})
$(document).on('show.bs.modal', '#$modalId', requestOtp);
$(document).on('click', '#$getOtpBtnId', requestOtp)
$('$pjaxContainer').on('pjax:send', function(event) {
    $(this).hide();
    $('.confirm-phone__spinner').show();
})
$('$pjaxContainer').on('pjax:complete', function(event) {
    $('.confirm-phone__spinner').hide();
    $(this).show();
})
$(document).on('input', '#$otpAttributeInputId', function() {
    let otpLen = String($(this).val()).replace(/\D/g, '').length;
    if (otpLen === 4) {
        $('#$formId').yiiActiveForm('submitForm');
    }
})
JS, $this::POS_END);
?>
<div id="<?= $modalId ?>" class="modal fade confirm-phone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-card modal-card_small">
        <div class="modal-content modal-card__window modal-card__window_center">
            <div class="modal-card__header">
                <div type="button" class="close-btn close-btn_blue" data-dismiss="modal"></div>
                <?= Yii::t('admin', 'Подтвердите номер телефона') ?>
            </div>
            <div class="modal-card__body">
                <div id="<?= $phoneFieldId ?>" class="confirm-phone__phone">
                    <?= $formModel->{$phoneAttribute} ?? '' ?>
                </div>
                <?= $form->field($formModel, $otpAttribute, $fieldOptions)->widget(MaskedInput::className(), ['mask' => '9  9  9  9']) ?>
                <svg class="confirm-phone__spinner" viewBox="0 0 50 50">
                    <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                </svg>
                <button type="button" class="btn btn_blue btn_xxsmall" id="<?= $getOtpBtnId ?>" data-phone="<?= $formModel->{$phoneAttribute} ?? '' ?>">
                    <?= Yii::t('admin', 'Получить код повторно') ?>
                </button>
            </div>
            <?/*
            <div class="modal-card__footer">
                <button type="button" class="btn btn_outline btn_regular modal-card__btn mr-2" data-dismiss="modal"><?= Yii::t('admin', 'Отменить') ?></button>
                <button type="submit" class="btn btn_green btn_regular modal-card__btn"><?= Yii::t('admin', 'Подтвердить') ?></button>
            </div>
            */?>
        </div>
    </div>
</div>
