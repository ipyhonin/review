<?php
namespace admin\widgets;

use Yii;
use kartik\widgets\FileInput;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use admin\models\File;
use yii\web\JsExpression;

$fileInputClass = 'js-fileinput';
$msgDeleteConfirm = Yii::t('account', 'Вы уверены, что желаете удалить этот файл?');
$msgModerateConfirm = Yii::t('account', 'Вы уверены, что желаете изменить статус документа?');
$widgetId = $this->context->id;

$this->registerJs(<<<JS
$('.file-input').on('click', '.moderation-btn', function() {
    let aborted = !window.confirm('$msgModerateConfirm');
    if (aborted) return;

    let btn = $(this);
    let container = btn.closest('.file-input__moderation-btns');
    let key = container.data('key');
    let status = btn.data('status');
    let pjaxContainer = '#' + container.attr('id');
    $.pjax({
        url: '/files/moderate?id=' + key + '&status=' + status,
        container: pjaxContainer,
        push: false,
        timeout: 10000,
        scrollTo: false
    });
})
$('.$fileInputClass').on('filebeforedelete', function() {
    var aborted = !window.confirm('$msgDeleteConfirm');
    return aborted;
})
JS, $this::POS_LOAD);

if ($theme == File::CATEGORY_PERSONAL_DOCUMENTS) {
    $this->registerJs(<<<JS
let config = $('.$fileInputClass').fileinput('getPreview').config;
config.forEach(function(item, i, arr) {
    let selector = '#id-file-input__moderation-btns_' + item['key'] + ' > button[data-status="' + item['status'] + '"]';
    $(selector).addClass('active').attr('disabled', true).text(item['activeLabel']);
})
JS, $this::POS_END);
}

$previewHeight = $lg ? File::PHOTO_THUMB_HEIGHT_LG : File::PHOTO_THUMB_HEIGHT;
$previewHeight .= 'px';

$this->registerCss(<<<CSS
#$widgetId .theme-photos .kv-file-content {
    height: $previewHeight;
}
CSS);

$previewAttribute = $lg ? 'thumbLg' : 'thumb';

$commonOptions = [
    'initialPreview' => ArrayHelper::getColumn($model->{$attribute}, $previewAttribute),
    'initialPreviewAsData' => true,
    'initialPreviewConfig' => ArrayHelper::toArray($model->{$attribute}, [
        File::className() => [
            'key' => 'id',
            'zoomData' => ($attribute === File::CATEGORY_PERSONAL_DOCUMENTS) ? function ($file) {
                return Url::to(['/files/download', 'id' => $file->id]);
            } : 'link',
            'filename' => function ($file) {
                return basename($file->link);
            },
            'caption' => function ($file) {
                return basename($file->link);
            },
            'downloadUrl' => ($theme === File::CATEGORY_PERSONAL_DOCUMENTS) ? function ($file) {
                return Url::to(['/files/download', 'id' => $file->id]);
            } : 'link',
            'filetype' => 'type',
            'status' => 'status',
            'activeLabel' => function ($file) {
                return $file::$moderationBtnsConfig[$file->status]['activeLabel'];
            },
        ],
    ]),
    'deleteUrl' => $readonly ? null : Url::to('/files/delete'),
    'overwriteInitial' => false,
    'maxFileSize' => 10 * 1024,
    'dropZoneEnabled' => false,
    'showCaption' => false,
    'showClose' => false,
    'showUpload' => $showUpload,
    'showBrowse' => !$readonly,
    'showRemove' => !$readonly,
    'layoutTemplates' => [
        'main2' =>  <<<HTML
{preview}
<div class="kv-upload-progress kv-hidden"></div>
<div class="clearfix"></div>
<span class="{class}">
{browse}{remove}{upload}
</span>
HTML,
    ],
    'browseClass' => '',
    'removeClass' => '',
    'uploadClass' => '',
    'browseLabel' => Yii::t('account', 'Добавить...'),
    'removeLabel' => Yii::t('account', 'Отменить'),
    'uploadLabel' => Yii::t('account', 'Сохранить'),
    'browseIcon' => '<i class="bi-folder2-open"></i>&nbsp;&nbsp;',
    'removeIcon' => '<i class="bi-trash"></i>&nbsp;&nbsp;',
    'uploadIcon' => '<i class="bi-upload"></i>&nbsp;&nbsp;',
    'mainClass' => 'file-main',
    'frameClass' => '',
    'buttonLabelClass' => '',
    'fileActionSettings' => [
        'showDrag' => false,
        'showRotate' => false,
        'showRemove' => !$readonly,
        'removeClass' => $readonly ? 'd-none' : '',
        'removeIcon' => '',
    ],
    'previewZoomButtonIcons' => [
        'prev' => '<i class="bi bi-chevron-left"></i>',
        'next' => '<i class="bi bi-chevron-right"></i>',
    ],
];

$specificOptions[File::CATEGORY_PHOTOS] = [
    'theme' => File::CATEGORY_PHOTOS,
    'allowedFileExtensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
    'layoutTemplates' => [
        'footer' => <<<HTML
<div class="file-thumbnail-footer">
    {actions}{indicator}
</div>
HTML,
        'modal' => <<<HTML
<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
<div class="modal-content">
    <div class="modal-body">
        <button type="button" class="file-zoom-dialog__close" data-dismiss="modal"></button>
        <div class="kv-zoom-body file-zoom-content {zoomFrameClass}"></div>
        <div class="kv-zoom-description"></div>
        <div class="kv-zoom-nav">{prev}{next}</div>
    </div>
    <div class="modal-footer">
        <button type="button" data-dismiss="modal">Назад</button>
    </div>
</div>
</div>
HTML,
        'actions' => <<<HTML
<div class="file-actions">
    <div class="file-footer-buttons">
        {zoom} {delete} {other}
    </div>
</div>
<div class="clearfix"></div>
HTML,
    ],
    'fileActionSettings' => [
        'showDownload' => false,
        'zoomClass' => '',
        'zoomIcon' => '<i class="bi bi-arrows-fullscreen"></i>',
        //'indicatorNew' => '<i class="bi bi-plus-circle-fill sk-color_yellow"></i>',
        'indicatorNew' => Yii::t('admin', 'Не сохранен'),
    ],
    'previewZoomButtonIcons' => [
        'prev' => '<i class="bi bi-chevron-left"></i>',
        'next' => '<i class="bi bi-chevron-right"></i>',
    ],
    'previewZoomButtonClasses' => [
        'prev' => 'btn btn_blue btn_xsmall',
        'next' => 'btn btn_blue btn_xsmall',
    ],
];

$specificOptions[File::CATEGORY_DOCUMENTS] = [
    'theme' => File::CATEGORY_DOCUMENTS,
    'preferIconicPreview' => true,
    'layoutTemplates' => [
        'footer' => <<<HTML
<div class="file-thumbnail-footer">
    <div class="file-footer-caption" title="{caption}">{caption}</div>
    <div class="file-footer-actions">
        {indicator}{actions}
    </div>
</div>
HTML,
    ],
    'fileActionSettings' => [
        'showZoom' => false,
        'downloadClass' => '',
        'downloadIcon' => Yii::t('admin', 'скачать'),
        'indicatorNew' => Yii::t('admin', 'не сохранен'), 
    ],
    'previewFileIcon' => File::DEFAULT_ICON,
    'previewFileIconSettings' => File::ICONS,
    'previewSettings' => [
        'other' => ['width' => 'auto', 'height' => 'auto'],
    ],
    'previewSettingsSmall' => [
        'other' => ['width' => 'auto', 'height' => 'auto'],
    ],
];

$moderationBtns = $this->render('@admin/views/api/files/moderation_btns');

$specificOptions[File::CATEGORY_PERSONAL_DOCUMENTS] = ArrayHelper::merge($specificOptions[File::CATEGORY_PHOTOS], [
    'theme' => File::CATEGORY_PERSONAL_DOCUMENTS,
    'otherActionButtons' => <<<HTML
<div id="id-file-input__moderation-btns_{key}" class="file-input__moderation-btns" {dataKey}>
    $moderationBtns
</div>
HTML,
]);
?>
<div id="<?= $widgetId ?>">
    <? if ($form): ?>
        <?= $form->field($model, "{$attribute}" . ($multiple ? '[]' : ''), ArrayHelper::merge($fieldOptions, [
            'inputOptions' => [
                'class' => "$fileInputClass",
            ],
        ]))->widget(FileInput::classname(), [
            'options' => [
                'multiple' => $multiple,
            ],
            'pluginOptions' => ArrayHelper::merge($commonOptions, $specificOptions[$theme]),
        ]); ?> 
    <? else: ?>
        <?= FileInput::widget([
            'model' => $model,
            'attribute' => "{$attribute}" . ($multiple ? '[]' : ''),
            'options' => [
                'multiple' => $multiple,
            ],
            'pluginOptions' => ArrayHelper::merge($commonOptions, $specificOptions[$theme]),
        ]); ?>
    <? endif; ?>
</div> 
