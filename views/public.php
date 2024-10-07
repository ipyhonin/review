<?php
use admin\modules\block\api\Block;
use app_demo\assets\AppAsset;
use admin\models\Setting;
use app_demo\widgets\Breadcrumbs;
use yii\helpers\Url;
use admin\widgets\AjaxModal;
use admin\widgets\PNotifyAlerts;
//use admin\modules\feedback\models\BgFeedback as Feedback;
//use admin\widgets\LazyLoadContent;
use admin\widgets\Alert;

$appAsset = AppAsset::register($this);

$this->beginContent('@app/views/layouts/base.php');
?>
<header class="k7-header">
  <div class="k7-header__top">
    <div class="k7-container">
      <div class="k7-header__top-container">
        <div class="k7-header__logo">
          <a href="<?= Url::to(['/']) ?>">
            <div class="k7-logo">
              <div class="k7-logo__title">
                <?= Yii::t('app', 'Конверсия-7') ?>
              </div>
              <div class="k7-logo__caption">
                <?= Yii::t('app', 'Учебный центр') ?>
              </div>
            </div>
          </a>
        </div>
        <div class="k7-header__phone">
          <a href="tel:<?= str_replace([' ', '-', '(', ')'], '', Setting::get('contact_telephone')) ?>" class="k7-header__phone-link">
            <?= Setting::get('contact_telephone') ?>
          </a>
          <ul class="k7-header__info">
            <li>
              <?= Setting::get('contact_addressShort') ?>
            </li>
            <li>
              <?= Setting::get('contact_openingHours') ?>
            </li>
          </ul>
        </div>
        <div class="k7-header__hamburger">
          <div></div>
          <div></div>
          <div></div>
        </div>
      </div>
    </div>
  </div>
  <div class="k7-header__bot zd-scroll">
    <div class="k7-container">
      <div class="k7-header__menu-container">
        <?= $this->render('_navmenu') ?>
      </div>
    </div>
  </div>
</header>
<div class="k7-content">
  <section class="k7-breadcrumb">
    <div class="k7-container">
      <?= Breadcrumbs::widget([
        'homeLink' => [
          'label' => Yii::t('app', 'Главная'),
          'url' => ['/'],
        ],
        'links' => $this->params['breadcrumbs'] ?? [],
        'options' => [
          'class' => 'k7-breadcrumb__list',
          'itemscope' => true,
          'itemtype' => 'https://schema.org/BreadcrumbList',
        ],
      ]); ?>
    </div>
  </section>
  <?= Alert::widget() ?>
  <?/*= PNotifyAlerts::widget([
    'clientOptions' => [
    ],
  ]); */?>
  <?= $content ?>
</div>
<footer class="k7-footer">
  <div class="k7-container">
    <div class="k7-footer__container">
      <ul class="k7-footer__list k7-footer__logo">
        <li class="k7-footer__list-item">
          <a href="<?= Url::to(['/']) ?>">
            <div class="k7-logo k7-logo_light">
              <div class="k7-logo__title">
                <?= Yii::t('app', 'Конверсия-7') ?>
              </div>
              <div class="k7-logo__caption">
                <?= Yii::t('app', 'Учебный центр') ?>
              </div>
            </div>
          </a>
        </li>
        <li class="k7-footer__list-item">
          <div class="k7-footer__copyright">
            &copy;&nbsp;<?= Setting::get('contact_brand') . ', ' . date('Y') ?>
          </div>
        </li>
        <li class="k7-footer__list-item">
          <div class="k7-footer__copyright">
            <?= Yii::t('app', 'Все права защищены. Запрещается копирование и распространение материалов сайта') ?>
          </div>
        </li>
      </ul>
      <div class="k7-footer__about">
        <div class="k7-footer__list-title">
          <?= Yii::t('app', 'О компании ') ?>
        </div>
        <ul class="k7-footer__list k7-footer__list_narrow">
          <li class="k7-footer__list-item nowrap">
            <?= Setting::get('contact_name') ?>
          </li>
          <li class="k7-footer__list-item nowrap">
            <?= Yii::t('app', 'ИНН') . ': ' . Setting::get('contact_inn') ?>
          </li>
          <li class="k7-footer__list-item nowrap">
            <?= Yii::t('app', 'ОГРН') . ': ' . Setting::get('contact_ogrn') ?>
          </li>
        </ul>
      </div>
      <?/*
      <div class="k7-footer__menu">
        <div class="k7-footer__list-title">
          <?= Yii::t('app', 'Меню') ?>
        </div>
        <ul class="k7-footer__list">
          <li class="k7-footer__list-item nowrap">
            <a href="<?= Url::to(['/familyhouse']) ?>" class="k7-footer__link">
              Family House
            </a>
          </li>
          <li class="k7-footer__list-item nowrap">
            <a href="<?= Url::to(['/futurehouse']) ?>" class="k7-footer__link">
              Future House
            </a>
          </li>
          <li class="k7-footer__list-item">
            <a href="<?= Url::to(['/contacts']) ?>" class="k7-footer__link">
              <?= Yii::t('app', 'Контакты') ?>
            </a>
          </li>
        </ul>
      </div>
      */?>
      <div class="k7-footer__usefull">
        <div class="k7-footer__list-title">
          <?= Yii::t('app', 'Полезное') ?>
        </div>
        <ul class="k7-footer__list">
          <li class="k7-footer__list-item wrap">
            <a href="<?= Url::to(['/policy']) ?>" class="k7-footer__link">
              <?= Yii::t('app', 'Политика конфиденциальности') ?>
            </a>
          </li>
          <li class="k7-footer__list-item">
            <a href="<?= Url::to(['/agreement']) ?>" class="k7-footer__link">
              <?= Yii::t('app', 'Соглашение об использовании сookies') ?>
            </a>
          </li>
        </ul>
      </div>
      <div class="k7-footer__contacts">
        <div class="k7-footer__list-title">
          <?= Yii::t('app', 'Контакты') ?>
        </div>
        <ul class="k7-footer__list k7-footer__list_narrow">
          <li class="k7-footer__list-item">
            <?= Setting::get('contact_addressShort') ?>
          </li>
          <li class="k7-footer__list-item nowrap">
            <a href="tel:<?= str_replace([' ', '-', '(', ')'], '', Setting::get('contact_telephone')) ?>" class="k7-footer__link">
              <?= Setting::get('contact_telephone') ?>
            </a>
          </li>
          <li class="k7-footer__list-item nowrap">
            <a href="tel:<?= Setting::get('contact_email') ?>" class="k7-footer__link">
              <?= Setting::get('contact_email') ?>
            </a>
          </li>
          <li class="k7-footer__list-item">
            <a href="<?= Setting::get('contact_vk') ?>" class="k7-footer__link">
              <img class="k7-footer__social-icon" src="<?= $appAsset->baseUrl ?>/img/vk_bw.svg">
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</footer>
<? /** Универсальная модальная форма, с динамически подгружаемым содержанием */
//echo AjaxModal::widget();
?>
<?= admin\widgets\ScrollUp::widget() ?>
<? $this->endContent() ?>
