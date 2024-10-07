<?php
namespace admin\widgets;

use Yii;
use admin\assets\FilesAsset;
use yii\base\InvalidConfigException;
use yii\widgets\ActiveForm;
use admin\models\File;

class FilesWidget extends \yii\base\Widget
{
    /**
     * @var ActiveForm Экземпляр формы. Если данный параметр определен,
     * то элемент ввода будет представлен в виде поля данной формы
     */
    public $form;

    /**
     * @var string Модель
     */
    public $model;

    /**
     * @var string Атрибут модели
     */
    public $attribute;

    /**
     * @var string Определяет, допускаются ли операции над файлами 
     */
    public $readonly = false;

    /**
     * @var string Тема 
     */
    public $theme;

    /**
     * @var string Определяет, показывать ли кнопку Сохранить (загрузить)
     */
    public $showUpload = false;

    /**
     * @var string Параметны поля формы
     */
    public $fieldOptions = [];

    /**
     * @var bool Если true, то картинки превью будут отображаться в увеличенном размере
     */
    public $lg = false;

    /**
     * @var bool Возможность выбора нескольких файлов
     */
    public $multiple = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->model)) {
            throw new InvalidConfigException('model parameter is not defined');
        }
        if (empty($this->attribute)) {
            throw new InvalidConfigException('attribute parameter is not defined');
        }
        if (!isset($this->theme)) {
            $this->theme = $this->attribute;
        }
        FilesAsset::register($this->getView());
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('files', [
            'form' => $this->form,
            'model' => $this->model,
            'attribute' => $this->attribute,
            'theme' => $this->theme,
            'readonly' => $this->readonly,
            'showUpload' => $this->showUpload,
            'fieldOptions' => $this->fieldOptions,
            'lg' => $this->lg,
            'multiple' => $this->multiple,
        ]);
    }
}