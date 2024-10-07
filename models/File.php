<?

namespace admin\models;

use admin\helpers\Image;
use admin\helpers\Upload;
use admin\modules\comment\moderation\enums\Status;
use admin\modules\comment\moderation\ModerationBehavior;
use Yii;
use yii\web\UploadedFile;

class File extends \admin\base\ActiveRecord
{
    const CATEGORY_DOCUMENTS = 'documents';
    const CATEGORY_PERSONAL_DOCUMENTS = 'personalDocs';
    const CATEGORY_PHOTOS = 'photos';
    const DEFAULT_ICON = '<i class="bi bi-file-earmark"></i>';
    const FILES_UPLOADS_DIR = 'files';
    const ICONS = [
      'jpg'  => '<i class="bi bi-filetype-jpg"></i>',
      'jpeg' => '<i class="bi bi-filetype-jpeg"></i>',
      'png'  => '<i class="bi bi-filetype-png"></i>',
      'gif'  => '<i class="bi bi-filetype-gif"></i>',
      'svg'  => '<i class="bi bi-filetype-svg"></i>',
      'doc'  => '<i class="bi bi-filetype-doc"></i>',
      'docx' => '<i class="bi bi-filetype-docx"></i>',
      'xls'  => '<i class="bi bi-filetype-xls"></i>',
      'xlsx' => '<i class="bi bi-filetype-xlsx"></i>',
      'ppt'  => '<i class="bi bi-filetype-ppt"></i>',
      'pptx' => '<i class="bi bi-filetype-pptx"></i>',
      'mov'  => '<i class="bi bi-filetype-mov"></i>',
      'mp4'  => '<i class="bi bi-filetype-mp4"></i>',
      'mp3'  => '<i class="bi bi-filetype-mp3"></i>',
      'wav'  => '<i class="bi bi-filetype-wav"></i>',
      'txt'  => '<i class="bi bi-filetype-txt"></i>',
      'zip'  => '<i class="bi bi-file-earmark-zip"></i>',
      'rar'  => '<i class="bi bi-file-earmark-zip"></i>',
      'tar'  => '<i class="bi bi-file-earmark-zip"></i>',
      'gzip' => '<i class="bi bi-file-earmark-zip"></i>',
      'gz'   => '<i class="bi bi-file-earmark-zip"></i>',
      '7z'   => '<i class="bi bi-file-earmark-zip"></i>',
      'avi'  => '<i class="bi bi-file-earmark-play"></i>',
      'mpg'  => '<i class="bi bi-file-earmark-play"></i>',
      'mkv'  => '<i class="bi bi-file-earmark-play"></i>',
      '3gp'  => '<i class="bi bi-file-earmark-play"></i>',
      'webm' => '<i class="bi bi-file-earmark-play"></i>',
      'wmv'  => '<i class="bi bi-file-earmark-play"></i>',
    ];
    const PERSONAL_DOCS_DIR = '@app/userdata/files';
    const PHOTO_THUMB_HEIGHT = 120;
    const PHOTO_THUMB_HEIGHT_LG = 240;
    /**
     * @var UploadedFile Экземпляр загружаемого файла. Требуется для инициализации св-ва link
     */
    public $fileInstance;
    public static $categories = [
      self::CATEGORY_PHOTOS,
      self::CATEGORY_DOCUMENTS,
      self::CATEGORY_PERSONAL_DOCUMENTS,
    ];
    public static $moderationBtnsConfig = [
      Status::APPROVED => [
        'label'       => 'Подтвердить',
        'activeLabel' => 'Подтверждено',
        'cssClass'    => 'moderation-btn_approved',
      ],
      Status::REJECTED => [
        'label'       => 'Отклонить',
        'activeLabel' => 'Отклонено',
        'cssClass'    => 'moderation-btn_rejected',
      ],
    ];

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();
        @unlink(Yii::getAlias('@webroot') . $this->link);
    }

    public function behaviors()
    {
        return [
          [
            'class' => ModerationBehavior::class,
          ],
        ];
    }

    public function getBasename()
    {
        return basename($this->link);
    }

    public function getExt()
    {
        return pathinfo($this->link, PATHINFO_EXTENSION);
    }

    public function getDirName()
    {
        return pathinfo($this->link, PATHINFO_DIRNAME);
    }

    public function getFileName()
    {
        return $this->getDirName() . '/' . pathinfo($this->link, PATHINFO_FILENAME);
    }

    public function getIcon()
    {
        $ext = $this->ext;
        return (array_key_exists($ext, static::ICONS)) ? static::ICONS[$ext] : static::DEFAULT_ICON;
    }

    public function getThumb($height = null)
    {
        if (!preg_match('/^image.*/', $this->type)) {
            return $this->link;
        }

        if ($height === null) {
            $height = static::PHOTO_THUMB_HEIGHT;
        }

        $private = ($this->category == self::CATEGORY_PERSONAL_DOCUMENTS);
        $link = $private ? static::PERSONAL_DOCS_DIR . $this->link : $this->link;
        $thumb = Image::thumb($link, null, $height, false);

        return $pivate ?
          str_replace('\\', '/', str_replace(Yii::getAlias(static::PERSONAL_DOCS_DIR), '', $thumb)) :
          $thumb;
    }

    public function getThumbLg()
    {
        return $this->getThumb(static::PHOTO_THUMB_HEIGHT_LG);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->isNewRecord) {
            $this->initLink();
        }
        parent::init();
    }

    public function initLink()
    {
        if (
          isset($this->fileInstance) &&
          ($this->fileInstance instanceof UploadedFile) &&
          !empty($this->item_class) &&
          //!empty($this->item_id) &&
          !empty($this->category)
        ) {
            $private = ($this->category === self::CATEGORY_PERSONAL_DOCUMENTS);
            $dir = implode([
              $private ? static::PERSONAL_DOCS_DIR : static::FILES_UPLOADS_DIR,
              DIRECTORY_SEPARATOR,
              str_replace('\\', '-', $this->item_class),
              DIRECTORY_SEPARATOR,
              $this->item_id ?? 'temp',
              DIRECTORY_SEPARATOR,
              $this->category,
            ]);
            $link = Upload::file($this->fileInstance, $dir, false);
            $this->link = $private ?
              str_replace(str_replace('\\', '/', Yii::getAlias(static::PERSONAL_DOCS_DIR)), '', $link) :
              $link;
        }
    }

    /**
     * Перемещает файл и изменяет ссылку на него если меняются данные связанного объекта 
     * @internal Было добавлено в связи с необходимостью сохранять файлы до сохранения записи заказа
     * при использовании многошаговой формы
     */
    public function updateLink($save = true)
    {
        if (
          empty($this->item_class) ||
          empty($this->item_id) ||
          empty($this->category)
        ) {
          return;
        }

        $private = ($this->category === self::CATEGORY_PERSONAL_DOCUMENTS);

        $oldLocation = ($private ? 
          Yii::getAlias(static::PERSONAL_DOCS_DIR) :
          Yii::getAlias('@webroot')) . $this->link;
        
        $dir = implode([
          $private ? static::PERSONAL_DOCS_DIR : static::FILES_UPLOADS_DIR,
          DIRECTORY_SEPARATOR,
          str_replace('\\', '-', $this->item_class),
          DIRECTORY_SEPARATOR,
          $this->item_id,
          DIRECTORY_SEPARATOR,
          $this->category,
        ]);
        $newLocation = Upload::getUploadPath($dir) . DIRECTORY_SEPARATOR . $this->getBasename();
        $newLink = Upload::getLink($newLocation);
        $newLink = $private ?
          str_replace(str_replace('\\', '/', Yii::getAlias(static::PERSONAL_DOCS_DIR)), '', $newLink) :
          $newLink;
        
        rename($oldLocation, $newLocation);
        $this->link = $newLink;
        if ($save) {
          $this->save(false);
        }
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
          [['id', 'link', 'item_class', 'item_id', 'category', 'title', 'description', 'order_num', 'attribute'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_files';
    }
}