<?

namespace admin\behaviors;

use admin\base\ActiveRecord as AdminBaseActiveRecord;
use admin\models\File;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\validators\FileValidator;
use yii\web\UploadedFile;
use Yii;

/**
 * Данное поведение добавляет классу-владельцу три предопределенных свойства:
 * 1) photos - Файлы со специфичными правилами валидации, свойственными изображениям (т.е. валидация формата)
 * 2) personalDocs - Файлы со специфичными правилами доступа, свойственными приватным документам
 * (т.е. доступ по прямой ссылке закрыт, для доступа к документам необходимо обладать определенными правами).
 * 3) documents - Файлы с общими правилами доступа и валидации.
 * 
 * Каждое из перечисленных свойств - это массив объектов класса admin\models\File.
 * 
 * Предполагается, что класс владелец является наследником admin\base\ActiveRecord.
 * 
 * Данный класс инкапсулирует все необходимые операции над файлами и встраивает их
 * в цикл жизни admin\base\ActiveRecord.
 * 
 * TODO ipyhonin: Необходимо обобщить поведение так, чтобы программист мог определять произвольное
 * число свойств, с произвольными именами, определяя лишь для каждого категорию.
 * Кроме этого, необходимо все API файлов (поведение, виджет, модель, миграцию и т.п.) поместить в отдельный модуль
 */
class FilesBehavior extends Behavior
{
    public $fileAttributes = [
        'documents' => [
            'category' => 'documents',
            'multiple' => true,
        ],
        'personalDocs' => [
            'category' => 'personalDocs',
            'multiple' => true,
        ],
        'photos' => [
            'category' => 'photos',
            'multiple' => true,
        ],
    ];

    /**
     * @var bool Дает возможность сохранять файлы по событию EVENT_AFTER_LOAD
     * @internal Св-во было добавлено в связи с необходимостью сохранять файлы до сохранения записи заказа
     * при использовании многошаговой формы
     */
    public $saveFilesOnLoad = false;

    /**
     * @var array Массив идентификаторов записей таблицы admin_files (т.е. объектов класса File),
     * которые были записаны последним вызовом метода saveFiles()
     * @internal Св-во было добавлено в связи с необходимостью сохранять файлы до сохранения записи заказа
     * при использовании многошаговой формы
     */
    public $savedFiles = [];

    private $_fileInstances = [];
    private $owner_class;

    public function __get($name)
    {
        $attributes = array_keys($this->fileAttributes);
        if (in_array($name, $attributes)) {
            return $this->getFiles($name);
        } else {
            return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        $attributes = array_keys($this->fileAttributes);
        if (in_array($name, $attributes)) {
            $this->_fileInstances[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Каскадное удаление всех связанных записей таблицы admin_files
     */
    public function deleteAllFiles($attributeOrEvent = null)
    {
        $query = File::find()->where([
          'item_class' => $this->owner_class,
          'item_id'    => $this->owner->id,
        ]);
        if (!empty($attributeOrEvent) && is_string($attributeOrEvent)) {
            $query->andWhere(['attribute' => $attributeOrEvent]);
        }
        $files = $query->all();

        // Не используем deleteAll(), т.к. оно не генерирует событие EVENT_AFTER_DELETE,
        // а у нас по этому событию удаляются файлы с файловой системы
        foreach ($files as $file) {
            $file->delete();
        }
    }

    public function afterOwnerInit()
    {
        $this->owner_class = get_class($this->owner);
    }

    public function afterOwnerLoad()
    {
        $attributes = array_keys($this->fileAttributes);
        foreach ($attributes as $attr) {
            $this->initFiles($attr);
        }
        
        if ($this->saveFilesOnLoad) {
            $this->saveFiles();
        }
    }

    public function afterOwnerSave()
    {
        if ($this->saveFilesOnLoad && !empty($this->savedFiles)) {
            $this->updateSavedFiles();
        } else {
            $this->saveFiles();
        }
    }

    public function afterOwnerValidate()
    {
        $validator = new FileValidator([
          'maxSize'  => 10 * 1024 * 1024,
          'except'   => ['import'],
        ]);

        $photosValidator = new FileValidator([
          'maxSize'   => 10 * 1024 * 1024,
          'except'    => ['import'],
          'mimeTypes' => ['image/png', 'image/jpeg', 'image/svg+xml', 'image/gif'],
        ]);

        $attributes = array_keys($this->fileAttributes);
        foreach ($attributes as $attr) {
            if (
              (array_key_exists($attr, $this->_fileInstances)) &&
              (!empty($this->_fileInstances[$attr]))
            ) {
                if ($this->fileAttributes[$attr]['category'] === File::CATEGORY_PHOTOS) {
                    $photosValidator->maxFiles = 0; //$this->fileAttributes[$attr]['multiple'] ? 0 : 1;
                    $photosValidator->validateAttribute($this->owner, $attr);
                } else {
                    $validator->maxFiles = 0; //$this->fileAttributes[$attr]['multiple'] ? 0 : 1;
                    $validator->validateAttribute($this->owner, $attr);
                }
            }
        }
    }

    public function canGetProperty($name, $checkVars = true)
    {
        $attributes = array_keys($this->fileAttributes);
        return in_array($name, $attributes) || parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        $attributes = array_keys($this->fileAttributes);
        return in_array($name, $attributes) || parent::canSetProperty($name, $checkVars);
    }

    public function events()
    {
        return [
          ActiveRecord::EVENT_INIT                => 'afterOwnerInit',
          ActiveRecord::EVENT_AFTER_DELETE        => 'deleteAllFiles',
          ActiveRecord::EVENT_AFTER_INSERT        => 'afterOwnerSave',
          ActiveRecord::EVENT_AFTER_UPDATE        => 'afterOwnerSave',
          ActiveRecord::EVENT_AFTER_VALIDATE      => 'afterOwnerValidate',
          AdminBaseActiveRecord::EVENT_AFTER_LOAD => 'afterOwnerLoad',
        ];
    }

    public function getFiles($attribute)
    {
        return (array_key_exists($attribute, $this->_fileInstances)) ?
          $this->_fileInstances[$attribute] :
          $this->readFiles($attribute);
    }

    public function initFiles($attribute, $model = null)
    {
        $model = $model ?? $this->owner;
        $fileInstances = UploadedFile::getInstances($model, $attribute);
        if ($fileInstances) {
            $this->_fileInstances[$attribute] = $fileInstances;
        }
    }

    public function readFiles($attribute)
    {
        return $this->owner->hasMany(File::class, ['item_id' => 'id'])->where([
          'item_class' => $this->owner_class,
          'attribute'  => $attribute,
        ]);
    }

    public function saveFiles()
    {
        if (empty($this->_fileInstances)) {
            return;
        }
        $this->savedFiles = [];
        foreach ($this->_fileInstances as $attr => $fileInstances) {
            // Если допускается только один файл, то удаляем все предыдущие
            if (!$this->fileAttributes[$attr]['multiple']) {
                $this->deleteAllFiles($attr);
            }
            foreach ($fileInstances as $fileInstance) {
                $fileObj = new File([
                  'item_class'   => $this->owner_class,
                  'item_id'      => $this->owner->id,
                  'attribute'    => $attr,
                  'category'     => $this->fileAttributes[$attr]['category'],
                  'fileInstance' => $fileInstance,
                  'type'         => $fileInstance->type,
                ]);
                $fileInstance->saveAs($fileObj->link);
                if ($fileObj->save(false)) {
                    $this->savedFiles[] = $fileObj->id;
                };
            }
        }
        $this->_fileInstances = [];
    }

    /**
     * @internal Добавлено в связи с необходимостью сохранять файлы до сохранения записи заказа
     * при использовании многошаговой формы
     */
    public function updateSavedFiles()
    {
        if (empty($this->savedFiles)) {
            return;
        }
        
        $models = File::find()->where(['id' => $this->savedFiles])->all();

        foreach ($models as $model) {
            $model->item_id = $this->owner->id;
            $model->updateLink(true);
        }
    }
}

