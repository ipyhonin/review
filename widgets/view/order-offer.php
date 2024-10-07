<?php

use admin\models\Setting;
use admin\widgets\CategoryInputWidget;
use admin\widgets\ConfirmPhoneWidget;
use admin\widgets\FilesWidget;
use kartik\datecontrol\DateControl;
use kartik\form\ActiveForm;
use kartik\widgets\Typeahead;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\MaskedInput;

$formId = 'id-order-offer-form';
$fieldOptions = [
  'options'      => [
    'class' => 'sk-form-group',
  ],
  'inputOptions' => [
    'class' => 'form-control sk-form-control',
  ],
  'labelOptions' => [
    'class' => 'sk-form-group__label',
  ],
];
$requiredFieldOptions = [
  'options'      => [
    'class' => '',
  ],
  'inputOptions' => [
    'class' => 'form-control',
  ],
  'template'     => '{input}{hint}{error}',
];
$locationIdHiddenInputCssClass = 'order-offer__location-id';

$dateControlOptions = [
  'type'          => 'date',
  'saveFormat'    => 'php:U',
  'readonly'      => true,
  'widgetOptions' => [
    'options'       => [
      'class'       => 'sk-datecontrol',
      'placeholder' => Yii::t('admin', 'Введите дату'),
    ],
    'pluginOptions' => [
      'autoclose' => true,
    ],
    'pickerIcon'    => '<svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 20H2C1.46957 20 0.960859 19.7893 0.585786 19.4142C0.210714 19.0391 0 18.5304 0 18V4C0 3.46957 0.210714 2.96086 0.585786 2.58579C0.960859 2.21071 1.46957 2 2 2H4V0H6V2H12V0H14V2H16C16.5304 2 17.0391 2.21071 17.4142 2.58579C17.7893 2.96086 18 3.46957 18 4V18C18 18.5304 17.7893 19.0391 17.4142 19.4142C17.0391 19.7893 16.5304 20 16 20ZM2 8V18H16V8H2ZM2 4V6H16V4H2ZM14 16H12V14H14V16ZM10 16H8V14H10V16ZM6 16H4V14H6V16ZM14 12H12V10H14V12ZM10 12H8V10H10V12ZM6 12H4V10H6V12Z" fill="#234C9E"/></svg>',
    'removeIcon'    => '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.40864 4.99915L9.70449 1.71268C9.89261 1.52453 9.99829 1.26935 9.99829 1.00326C9.99829 0.737182 9.89261 0.481998 9.70449 0.293849C9.51636 0.105701 9.26122 0 8.99517 0C8.72913 0 8.47398 0.105701 8.28586 0.293849L5 3.59031L1.71414 0.293849C1.52602 0.105701 1.27087 2.36436e-07 1.00483 2.38419e-07C0.738783 2.40401e-07 0.483635 0.105701 0.295513 0.293849C0.107391 0.481998 0.00170517 0.737182 0.00170517 1.00326C0.00170517 1.26935 0.107391 1.52453 0.295513 1.71268L3.59136 4.99915L0.295513 8.28562C0.201875 8.3785 0.127553 8.48901 0.0768329 8.61077C0.0261132 8.73253 0 8.86313 0 8.99503C0 9.12693 0.0261132 9.25753 0.0768329 9.37929C0.127553 9.50105 0.201875 9.61156 0.295513 9.70444C0.388386 9.7981 0.49888 9.87243 0.620622 9.92316C0.742363 9.97388 0.872943 10 1.00483 10C1.13671 10 1.26729 9.97388 1.38903 9.92316C1.51077 9.87243 1.62127 9.7981 1.71414 9.70444L5 6.40799L8.28586 9.70444C8.37873 9.7981 8.48923 9.87243 8.61097 9.92316C8.73271 9.97388 8.86329 10 8.99517 10C9.12706 10 9.25764 9.97388 9.37938 9.92316C9.50112 9.87243 9.61161 9.7981 9.70449 9.70444C9.79812 9.61156 9.87245 9.50105 9.92317 9.37929C9.97389 9.25753 10 9.12693 10 8.99503C10 8.86313 9.97389 8.73253 9.92317 8.61077C9.87245 8.48901 9.79812 8.3785 9.70449 8.28562L6.40864 4.99915Z" fill="#EE4136"/></svg>',
    'pickerButton'  => ['class' => ''],
    'removeButton'  => ['class' => ''],
  ],
];

$currencySymbol = Yii::$app->formatter->numberFormatterSymbols[NumberFormatter::CURRENCY_SYMBOL];
$activeOrderBtnClass = 'order-offer__active-order-btn';
$activeOrderHiddenInputId = Html::getInputId($orderOfferForm, 'activeOrderId');
$descInputId = Html::getInputId($order, 'description');
$budgetInputId = Html::getInputId($order, 'budget');

$this->registerJs(
  <<<JS
$('.$activeOrderBtnClass').click(function() {
    let orderId = $(this).data('orderId');
    $('#$activeOrderHiddenInputId').val(orderId);
})
$('#$descInputId').inputmask()
$('#$budgetInputId').inputmask()
JS,
  $this::POS_LOAD
);
?>
<?
$form = ActiveForm::begin([
  'id'                     => $formId,
    /**
     * Отключаем валидацию на стороне клиента и включаем Ajax валидацию,
     * т.к. данная композитная форма содержит две взаимоисключающие дочерние формы -
     * формы регистрации и входа. Если активирована одна из данных форм,
     * то валидацию другой необходимо отключить. Этот контроль проще производить на стороне сервера
     */
  'enableClientValidation' => false,
  'enableAjaxValidation'   => true,
  'validationUrl'          => $validationUrl,
  'action'                 => $action,
  'fieldConfig'            => [
    'errorOptions' => ['encode' => false],
  ],
]);
?>
<!-- Модальная форма Предложить заказ -->
<div class="modal modal_bg_blue fade" id="<?= $modalId ?>" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered cast-modal-dialog modal-xxl">
    <div class="modal-content cast-modal-content">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true" class="modal__close">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                  d="M8.40864 6.99915L13.7045 1.71268C13.8926 1.52453 13.9983 1.26935 13.9983 1.00326C13.9983 0.737182 13.8926 0.481998 13.7045 0.293849C13.5164 0.105701 13.2612 0 12.9952 0C12.7291 0 12.474 0.105701 12.2859 0.293849L7 5.59031L1.71414 0.29385C1.52602 0.105701 1.27087 2.36436e-07 1.00483 2.38419e-07C0.738783 2.40401e-07 0.483635 0.105701 0.295513 0.29385C0.107391 0.481998 0.00170517 0.737182 0.00170517 1.00326C0.00170517 1.26935 0.107391 1.52453 0.295513 1.71268L5.59136 6.99915L0.295513 12.2856C0.201875 12.3785 0.127553 12.489 0.0768329 12.6108C0.0261132 12.7325 0 12.8631 0 12.995C0 13.1269 0.0261132 13.2575 0.0768329 13.3793C0.127553 13.501 0.201875 13.6116 0.295513 13.7044C0.388386 13.7981 0.49888 13.8724 0.620622 13.9232C0.742363 13.9739 0.872943 14 1.00483 14C1.13671 14 1.26729 13.9739 1.38903 13.9232C1.51077 13.8724 1.62127 13.7981 1.71414 13.7044L7 8.40799L12.2859 13.7044C12.3787 13.7981 12.4892 13.8724 12.611 13.9232C12.7327 13.9739 12.8633 14 12.9952 14C13.1271 14 13.2576 13.9739 13.3794 13.9232C13.5011 13.8724 13.6116 13.7981 13.7045 13.7044C13.7981 13.6116 13.8724 13.501 13.9232 13.3793C13.9739 13.2575 14 13.1269 14 12.995C14 12.8631 13.9739 12.7325 13.9232 12.6108C13.8724 12.489 13.7981 12.3785 13.7045 12.2856L8.40864 6.99915Z"
                  fill="#00A0D1"/>
            </svg>
        </span>
      </button>
      <div class="modal-body">
        <div class="sk-form-row modal__header">
          <div class="modal__title">
              <?= $offerMode ? Yii::t('admin', 'Предложить заказ') : Yii::t('admin', 'Создать заказ') ?>
          </div>
            <?
            if (!empty($activeOrders)): ?>
              <ul class="nav nav-pills" id="offerTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <a class="nav-link nav-link_tab active" id="new-tab" data-toggle="pill" href="#new-form" role="tab"
                     aria-controls="new-form" aria-selected="true">
                      <?= Yii::t('admin', 'Новый') ?>
                  </a>
                </li>
                <li class="nav-item" role="presentation">
                  <a class="nav-link nav-link_tab" id="active-tab" data-toggle="pill" href="#active-form" role="tab"
                     aria-controls="active-form" aria-selected="false">
                      <?= Yii::t('admin', 'Ранее опубликованный') ?>
                  </a>
                </li>
              </ul>
            <?
            endif; ?>
        </div>
        <div class="tab-content" id="offerTabContent">
          <div id="new-form" class="tab-pane fade show active modal__tab" role="tabpanel" aria-labelledby="new-tab">
            <div class="sk-form-row">
              <!-- Title -->
              <div class="sk-form-group order-offer__title">
                <label class="sk-form-group__label sk-form-group__label_with-hint">
                  <span><?= $order->getAttributeLabel('title') ?></span>
                  <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                </label>
                  <?= $form->field($order, 'title', $requiredFieldOptions)->textInput([
                    'placeholder' => Yii::t('admin', 'Опишите кратко что нужно сделать'),
                  ]) ?>
              </div>
              <!-- Period -->
              <div class="sk-form-group order-offer__period">
                <label class="sk-form-group__label sk-form-group__label_with-hint">
                  <span><?= Yii::t('admin', 'Срок выполнения работ') ?></span>
                </label>
                <div class="sk-form-row order-offer__period-inputs">
                    <?= $form->field($order, 'period_1', $requiredFieldOptions)->widget(
                      DateControl::classname(),
                      array_merge($dateControlOptions, [
                        'displayFormat' => 'php:' . Yii::t('admin', 'с') . ' d/m/Y',
                        'type'          => DateControl::FORMAT_DATE,
                      ])
                    ); ?>
                    <?= $form->field($order, 'period_2', $requiredFieldOptions)->widget(
                      DateControl::classname(),
                      array_merge($dateControlOptions, [
                        'displayFormat' => 'php:' . Yii::t('admin', 'до') . ' d/m/Y',
                        'type'          => DateControl::FORMAT_DATE,
                      ])
                    ); ?>
                </div>
              </div>
            </div>
            <div class="sk-form-row">
              <div class="order-offer__description-etc">
                <!-- Description -->
                <div class="sk-form-group">
                  <label class="sk-form-group__label sk-form-group__label_with-hint">
                    <span><?= $order->getAttributeLabel('description') ?></span>
                    <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                  </label>
                    <?= $form->field($order, 'description', $requiredFieldOptions)->textarea([
                      'class'          => 'form-control order-offer__description',
                      'placeholder'    => Yii::t('admin', 'Опишите подробно работы, которые нужно выполнить'),
                      'data-inputmask' => "'regex': '.{1,1500}', 'placeholder': ''",
                    ]) ?>
                </div>
                <div>
                  <div class="sk-form-row">
                    <!-- Budget -->
                    <div class="sk-form-group order-offer__budget">
                      <label class="sk-form-group__label">
                        <span><?= $order->getAttributeLabel('budget') ?></span>
                      </label>
                      <div class="order-offer__budget-input-container">
                          <?= Html::activeTextInput($order, 'budget', [
                            'class'          => 'form-control order-offer__budget-input',
                            'data-inputmask' => "'alias': 'decimal', 'groupSeparator': ',', 'autoGroup': 'true'",
                          ]) ?>
                        <span class="order-offer__budget-input-before"><?= Yii::t('admin', 'до') ?></span>
                        <span class="order-offer__budget-input-after"><?= $currencySymbol ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="order-offer__category-etc">
                <!-- Category -->
                <div class="sk-form-group">
                  <label class="sk-form-group__label sk-form-group__label_with-hint">
                    <span><?= $order->getAttributeLabel('category_id') ?></span>
                    <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                  </label>
                    <?= CategoryInputWidget::widget([
                      'form'      => $form,
                      'model'     => $order,
                      'attribute' => 'category_id',
                      'query'     => \admin\modules\catalog\models\Category::find()->addOrderBy('tree, lft'),
                      'options'   => $requiredFieldOptions,
                      'multiple'  => false,
                    ]); ?>
                </div>
                <!-- Location -->
                <div class="sk-form-group">
                  <label class="sk-form-group__label sk-form-group__label_with-hint">
                    <span><?= $order->getAttributeLabel('location_id') ?></span>
                    <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                  </label>
                    <?= Typeahead::widget([
                      'id'            => 'order-offer__w1',
                      'name'          => '',
                      'options'       => [
                        'placeholder'  => Yii::$app->location->title,
                        'class'        => 'sk-form-control',
                        'autocomplete' => 'off',
                      ],
                      'pluginOptions' => [
                        'highlight' => false,
                      ],
                      'dataset'       => [
                        [
                          'remote'    => [
                            'url'      => Url::to(array_merge(['/location/suggestions'], ['onlyUsed' => false])) . '&q=%QUERY',
                              //'url' => Url::to(['/location/suggestions']) . '?q=%QUERY',
                            'wildcard' => '%QUERY',
                          ],
                          'display'   => 'inputContent',
                          'templates' => [
                            'suggestion' => new JsExpression("Handlebars.compile('<p>{{{suggestionLabel}}}</p>')"),
                            'empty'      => '<p class="tt-suggestion error">' .
                              Yii::t('admin', 'Совпадений не найдено. Если Вы искали город, попробуйте выбрать регион') .
                              '</p>',
                          ],
                          'limit'     => 20,
                        ],
                      ],
                      'pluginEvents'  => [
                        'typeahead:select' => "function(event, suggestion) { $('.$locationIdHiddenInputCssClass').val(suggestion.locationId); }",
                      ],
                      'scrollable'    => true,
                    ]); ?>
                    <?= Html::tag('div', Yii::t('admin', 'Начните ввод и выберите из списка'), ['class' => 'sk-form-group__hint']); ?>
                    <?= $form->field($order, 'location_id', $requiredFieldOptions)->hiddenInput([
                        'class' => $locationIdHiddenInputCssClass,
                        'value' => Yii::$app->location->id,
                      ]
                    ) ?>
                    <?
                    if (Yii::$app->user->isGuest): ?>
                        <?= Html::activeHiddenInput($registrationForm, 'location_id', [
                          'class' => $locationIdHiddenInputCssClass,
                          'value' => Yii::$app->location->id,
                        ]) ?>
                    <?
                    endif; ?>
                </div>
                <!-- Address -->
                  <?= $form->field($order, 'address_etc', $fieldOptions)->textInput([
                    'placeholder' => Yii::t('admin', 'Укажите район, улицу, номер дома, станцию метро и т.п.'),
                  ]) ?>
              </div>
            </div>
            <div class="row">
              <div class="col-6">
                <!--Фотографии-->
                  <?= FilesWidget::widget([
                    'form'         => $form,
                    'model'        => $order,
                    'attribute'    => 'photos',
                    'fieldOptions' => $fieldOptions,
                  ]); ?>
              </div>
              <div class="col-6">
                <!--Документы-->
                  <?= FilesWidget::widget([
                    'form'         => $form,
                    'model'        => $order,
                    'attribute'    => 'documents',
                    'fieldOptions' => $fieldOptions,
                  ]); ?>
              </div>
            </div>
            <div class="order-offer__registration-etc">
                <?
                // Скрытое поле, значение которого меняется JS скриптом в зависимости от выбранной формы - регистрации или входа
                echo Html::activeHiddenInput($orderOfferForm, 'loginFormEnabled', [
                  'id' => 'id-login-form-enabled',
                ])
                ?>
                <?
                if (Yii::$app->user->isGuest): ?>
                  <ul class="nav nav-pills" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                      <a class="nav-link nav-link_tab nav-link_icon nav-link_icon-registration <?= ($orderOfferForm->loginFormEnabled) ?
                        '' : 'active' ?>" id="registration-tab" data-toggle="pill" href="#registration-form" role="tab"
                         aria-controls="registration-form" aria-selected="true">
                          <?= Yii::t('admin', 'Регистрация') ?>
                      </a>
                    </li>
                    <li class="nav-item" role="presentation">
                      <a class="nav-link nav-link_tab nav-link_icon nav-link_icon-login <?= ($orderOfferForm->loginFormEnabled) ? 'active' :
                        '' ?>" id="login-tab" data-toggle="pill" href="#login-form" role="tab" aria-controls="login-form"
                         aria-selected="false">
                          <?= Yii::t('admin', 'Вход') ?>
                      </a>
                    </li>
                  </ul>
                    <?
                    /*<a href="#" class="modal__btn-login"><?= Yii::t('admin', 'Вход, если ранее регистрировались') ?></a>*/ ?>
                  <div class="tab-content order-offer__registration-etc-forms" id="myTabContent">
                    <!-- Форма регистрации -->
                    <div id="registration-form" class="tab-pane fade  <?= ($orderOfferForm->loginFormEnabled) ? '' : 'show active' ?>"
                         role="tabpanel" aria-labelledby="registration-tab">
                      <div class="sk-form-row">
                        <div class="sk-form-group">
                          <label class="sk-form-group__label sk-form-group__label_with-hint">
                            <span><?= $registrationForm->getAttributeLabel('name') ?></span>
                            <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                          </label>
                            <?= $form->field($registrationForm, 'name', $requiredFieldOptions)->textInput() ?>
                        </div>
                        <div class="sk-form-group">
                          <label class="sk-form-group__label sk-form-group__label_with-hint">
                            <span><?= $registrationForm->getAttributeLabel('phone') ?></span>
                            <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                          </label>
                            <?= $form->field($registrationForm, 'phone', $requiredFieldOptions)->widget(
                              MaskedInput::className(),
                              ['mask' => '+7 (999) 999-99-99']
                            ) ?>
                        </div>
                        <div class="sk-form-group">
                          <label class="sk-form-group__label sk-form-group__label_with-hint">
                            <span><?= $registrationForm->getAttributeLabel('email') ?></span>
                            <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                          </label>
                            <?= $form->field($registrationForm, 'email', $requiredFieldOptions)->widget(MaskedInput::class, [
                              'clientOptions' => [
                                'alias' => 'email',
                              ],
                            ]) ?>
                        </div>
                          <?
                          if (!Setting::get('registrationPasswordGenerate')): ?>
                            <div class="sk-form-group">
                              <label class="sk-form-group__label sk-form-group__label_with-hint">
                                <span><?= $registrationForm->getAttributeLabel('password') ?></span>
                                <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                              </label>
                                <?= $form->field($registrationForm, 'password', $requiredFieldOptions)->passwordInput() ?>
                            </div>
                          <?
                          endif; ?>
                      </div>
                    </div>
                    <!-- Форма входа -->
                    <div id="login-form" class="tab-pane fade <?= ($orderOfferForm->loginFormEnabled) ? 'active show' : '' ?>"
                         role="tabpanel" aria-labelledby="login-tab">
                      <div class="sk-form-row">
                        <div class="sk-form-group">
                          <label class="sk-form-group__label sk-form-group__label_with-hint">
                            <span><?= $loginForm->getAttributeLabel('username') ?></span>
                            <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                          </label>
                            <?= $form->field($loginForm, 'username', $requiredFieldOptions)->textInput() ?>
                        </div>
                        <div class="sk-form-group">
                          <label class="sk-form-group__label sk-form-group__label_with-hint">
                            <span><?= $loginForm->getAttributeLabel('password') ?></span>
                            <span class="sk-form-group__label-hint"><?= Yii::t('admin', 'обязательное поле') ?></span>
                          </label>
                            <?= $form->field($loginForm, 'password', $requiredFieldOptions)->passwordInput() ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?
                endif; ?>
            </div>
            <div class="sk-form-row order-offer__footer">
              <div class="modal__checkbox">
                <input id="modal-check" type="checkbox" class="modal__checkbox-input">
                <label for="modal-check" class="modal__checkbox-label"><?= Yii::t(
                      'admin',
                      'Скрывать контакты и получать отклики через сервис'
                    ) ?></label>
              </div>
                <?
                $link =
                  Html::a(
                    Yii::t('admin', 'персональных данных'),
                    Url::to(['/page/usloviya-peredachi-i-obrabotki-dannyh']),
                    ['target' => '_blank']
                  ); ?>
              <p class="modal__text-policy"><?= Yii::t(
                    'admin',
                    'Нажимая на кнопку, вы даете согласие на обработку своих {link}',
                    ['link' => $link]
                  ) ?></p>
              <div class="order-offer__submit-btn-container">
                  <?= Html::submitButton($offerMode ? Yii::t('admin', 'Предложить') : Yii::t('admin', 'Создать'), [
                    'class' => 'btn btn_green btn_regular order-offer__submit-btn',
                  ]) ?>
              </div>
            </div>
          </div>
            <?
            if (!empty($activeOrders)): ?>
              <div id="active-form" class="tab-pane fade modal__tab" role="tabpanel" aria-labelledby="active-tab">
                <div class="modal__block modal__block_tab-orders">
                  <div class="modal__block-title modal__block-title_tab-two"><?= Yii::t('admin', 'Заказы в работе') ?></div>
                  <span class="modal__orders-count"><?= count($activeOrders) ?></span>
                </div>
                  <?
                  foreach ($activeOrders as $item): ?>
                    <div class="modal__order-wrapper">
                      <div class="modal__order-number-wrapper">
                        <p class="modal__order-title"><?= Yii::t('admin', 'Номер и дата') ?></p>
                        <p class="modal__order-number"><?= '№' . $item->id ?></p>
                        <p class="modal__order-date"><?= Yii::$app->formatter->asDate($item->created_at) ?></p>
                      </div>
                      <div class="modal__order-desc-wrapper">
                        <p class="modal__order-title"><?= Yii::t('admin', 'Название заказа') ?></p>
                        <p class="modal__order-desc"><?= $item->title ?></p>
                      </div>
                      <div class="modal__order-deatline-wrapper">
                        <p class="modal__order-title"><?= Yii::t('admin', 'Срок выполнения заказа') ?></p>
                        <p class="modal__order-date">
                            <?
                            if (!empty($item->period_1) || !empty($item->period_2)) {
                                echo !empty($item->period_1) ?
                                  Yii::t('admin', 'c') . ' ' . Yii::$app->formatter->asDate($item->period_1) . ' ' : '';
                                echo !empty($item->period_2) ? Yii::t('admin', 'до') . ' ' . Yii::$app->formatter->asDate($item->period_2) :
                                  '';
                            } else {
                                echo Yii::t('admin', '(не задано)');
                            }
                            ?>
                        </p>
                      </div>
                      <div class="order-offer__active-order-offer-form">
                          <?= Html::submitButton(Yii::t('admin', 'Предложить'), [
                            'class'         => "btn btn_green btn_small $activeOrderBtnClass",
                            'data-order-id' => $item->id,
                          ]); ?>
                      </div>
                    </div>
                  <?
                  endforeach; ?>
                  <?= Html::activeHiddenInput($orderOfferForm, 'activeOrderId', [
                    'id' => $activeOrderHiddenInputId,
                  ]) ?>
              </div>
            <?
            endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?
if (Yii::$app->user->isGuest): ?>
  <!--Модальная форма подтверждения телефона-->
    <?= ConfirmPhoneWidget::widget([
      'form'      => $form,
      'formModel' => $registrationForm,
      'formId'    => $formId,
    ]); ?>
<?
endif; ?>
<?
ActiveForm::end(); ?>
