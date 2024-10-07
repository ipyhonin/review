<?php
use admin\widgets\AjaxModal;
use admin\modules\feedback\models\BgFeedback as Feedback;
use admin\widgets\FancyboxWidget;

$images = $baseItem->groupImagesByType();
$model->translateAll();
$gallaries = [
    'Архитектура' => Yii::t('app', 'Архитектура'),
    'Интерьер' => Yii::t('app', 'Интерьер'),
    'Планировка' => Yii::t('app', 'Планировка'),
];
?>
<section class="top-video">
    <video id="id-top-video__video" controls poster="<?= $images['Видео заставка'][0]['src'] ?>">
        <source src="<?= $images['Видео webm'][0]['src'] ?>" type="video/webm">
        <source src="<?= $images['Видео mp4'][0]['src'] ?>" type="video/mp4">
    </video>
    <h1 id="id-top-video__title" class="top-video__title">
        <?= $model->title ?>
    </h1>
</section>
<section class="dp-section">
    <div class="container">
        <div class="dp-section__subtitle m-0 text-center">
            <?= $model->description ?>
        </div>
        <div class="dp-section__action">
                <?= AjaxModal::button(
                    value: Yii::t('app', 'Получить презентацию'),
                    title: $formTitle = Yii::t('app', "Получить презентацию проекта {project}", ['project' => $model->title]),
                    subTitle: 'Заполните форму и узнайте о проекте ещё больше',
                    cssClass: 'buttn buttn_primary buttn_lg',
                    url: [
                        '/bg-request',
                        'scenario' => Feedback::SCENARIO_BG_SUPPORT,
                        'formTitle' => $formTitle,
                        'formId' => $formId = "form_recieve-presentation-{$model->slug}"
                    ],
                    options: ['id' => "{$formId}-toggle"]
                ) ?>            
        </div>
</section>
<section class="dp-section pt-0">
    <div class="container">
        <?= $this->render('_item', ['model' => $baseItem, 'category' => $model]); ?>
        <div>
</section>
<section class="gallaries">
    <img class="gallaries__background" src="<?= $images['Фон'][0]['src'] ?>">
    <div class="gallaries__container">
        <? foreach ($gallaries as $key => $title): ?>
            <? $gallary = $images[$key] ?>
            <ul class="gallaries__gallery">
                <? FancyboxWidget::begin() ?>
                    <li><a href="<?= $gallary[0]['src'] ?>"><?= $title ?></a></li>
                    <? unset($gallary[0]) ?>
                    <? foreach($gallary as $image): ?>
                        <li><a href="<?= $image['src'] ?>"></a></li>
                    <? endforeach ?>
                <? FancyboxWidget::end() ?>
            </ul>
        <? endforeach ?>
    </div>
</section>
<section class="dp-section">
    <div class="container">
        <div class="dp-section__title mt-0 mb-4">
            <?= $baseItem->data->{'modules-title'} ?>
        </div>
        <p class="mb-0">
            <?= $baseItem->data->{'modules-text'} ?>
        </p>
    </div>
</section>
<? foreach ($items as $item): ?>
    <section class="dp-section pt-0">
        <div class="container">
            <?= $this->render('_item', ['model' => $item, 'category' => $model]); ?>
        </div>
    </section>
<? endforeach ?>