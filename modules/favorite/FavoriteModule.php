<?
namespace admin\modules\favorite;

use Yii;
use yii\base\InvalidConfigException;

class FavoriteModule extends \yii\base\Module
{
    /**
     * @var array Конфигурация избранных элементов.
     * Является обязательным св-вом.
     * Формат элемента массива:
     * 'type' => [ // Тип избранных элементов. Используется как action id страницы со списком элементов данного типа
     *    'item_class'   => ..., // PHP класс данного типа элементов
     *    'view'    => ..., // Шаблон страницы со списком избранных элементов данного типа
     *    'default', // Опционально. Является ли страница избранных элементов данного типа страницей по умолчанию
     * ]
     * Если значение default не определено ни для одного из элементов, 
     * то типом по умолчанию будет считаться первый в списке.
     */
    public $items;

    /**
     * @var string Шаблон страницы, на которой собраны избранные элементы всех типов.
     * Является опциональным св-вом.
     * Если данное св-во определено, то данная страница будет отображаться по корневому маршруту модуля. 
     * Иначе - по корневому маршруту модуля будет отображена страница типа избранных элементов по умолчанию.
     */
    public $indexView;

    /**
     * @inheritdoc
     */
    public function init() {
        if (!isset($this->items)) {
            throw new InvalidConfigException(Yii::t('admin/favorite', 'Свойство "items" должно быть установлено'));
        }

        Yii::$app->i18n->translations['admin/favorite'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'ru-RU',
            'basePath'       => '@admin/modules/favorite/messages',
        ];

        parent::init();
    }

    public function getDefaultAction() {
        if (!empty($this->indexView)) {
            return 'index';
        } else {
            $i = 0;
            foreach ($this->items as $type => $item) {
                if ((array_search('default', $item) !== false) || $i == 0) {
                    $result = $type;
                }
                $i++;
            }
            return $result;
        }
    }
}