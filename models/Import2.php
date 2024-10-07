<?

namespace admin\modules\yml\models;

use admin\behaviors\UserTypesBehavior;
use admin\models\User;
use admin\models\UserCategory;
use moonland\phpexcel\Excel;
use Yii;

//use admin\modules\yml\widgets\Excel;

/**
 * Данный класс содержит изменения, связанные с реализацией проекта СтройКуратор
 */
class Import2 extends Import
{

    public $allImportCategories = [];
    public $update = 0;

    public static function getUsersLoadMap()
    {
        $businessTypeValues = array_combine(
          array_keys(User::getBusinessTypes()),
          array_column(User::getBusinessTypes(), 'label')
        );

        unset($businessTypeValues[User::BUSINESS_TYPE_CLIENT]);
        $businessTypeValues = array_flip(array_map('mb_strtolower', $businessTypeValues));

        return [
          'email'             => [
            'label' => 'E-mail',
            'help'  => Yii::t('admin/yml', 'Обязательное поле (не может быть пустым).'),
          ],
          'phone'             => [
            'label' => 'Телефон',
            'help'  => Yii::t('admin/yml', 'Обязательное поле (не может быть пустым).'),
          ],
          'name'              => 'Имя',
          'surname'           => 'Фамилия',
          'middle_name'       => 'Отчество',
          'business_type'     => [
            'label'  => 'Тип пользователя',
            'values' => $businessTypeValues,
            'help'   => Yii::t('admin/yml', 'Поле должно иметь одно из перечисленных значений.'),
          ],
          'full_entity_name'  => 'Название компании',
          'short_entity_name' => 'Краткое название компании',
          'region'            => [
            'label' => 'Регион',
            'help'  => Yii::t('admin/yml', 'Регион должен быть предварительно зарегистрирован в системе.'),
          ],
          'location'          => [
            'label' => 'Город',
            'help'  => Yii::t('admin/yml', 'Город должен быть предварительно зарегистрирован в системе.'),
          ],
          'address_etc'       => 'Адрес компании',
          'photologo'         => [
            'label' => 'Фото или логотип',
            'help'  => Yii::t(
              'admin/yml',
              'Файл изображения должен быть доступен в сети Интернет.<br>Можно указать либо абсолютную ссылку на файл (т.е. с указанием протокола, например: https://site.ru/image.jpg),<br>либо относительную ссылку (если файлы были предварительно загружены на сервер, например: uploads/image.jpg).<br>Допустимые форматы: PNG/JPG.'
            ),
          ],
          'description'       => 'Описание',
          'roles'             => [
            'label'  => 'Тип профиля',
            'values' => [
              'исполнитель' => Yii::$app->user->types[UserTypesBehavior::USER_TYPE_MASTER]['role'],
              'технадзор'   => Yii::$app->user->types[UserTypesBehavior::USER_TYPE_SUPERVISOR]['role'],
              'куратор'     => Yii::$app->user->types[UserTypesBehavior::USER_TYPE_KURATOR]['role'],
            ],
            'help'   => Yii::t('admin/yml', 'Поле должно иметь одно из перечисленных значений.'),
          ],
          'categories'        => [
            'label' => 'Категории',
            'help'  => Yii::t(
              'admin/yml',
              'Значением поля должен быть список импортируемых категорий, перечисленных через ";" (см. вкладку "Сопоставление категорий").'
            ),
          ],
          'status'            => [
            'label'  => 'Статус',
            'values' => [
              'включен'  => User::STATUS_ON,
              'выключен' => User::STATUS_OFF,
            ],
            'help'   => Yii::t('admin/yml', 'Поле должно иметь одно из перечисленных значений.'),
          ],
        ];
    }

    //Добавление пользователей
    public static function loadUsersFromExcelFile($fileInput, $update = null)
    {
        try {
        // Импорт содержимого файла в PHP массив
        $array = Excel::import([
          'fileName' => $fileInput,
        ]);

        // Чтение строк таблицы
        $rows = $array['fileName'];

        // Инициализация
        $errors = [];
        $warnings = [];
        $all_amount = 0;
        $added_amount = 0;
        $updated_amount = 0;
        $loadMap = self::getUsersLoadMap();
        $keyAttribute = 'phone';
        $uniqueAttributes = [
          $keyAttribute => 'validateFilterPhone',
          'email'       => 'validateFilterEmail',
        ];

        // Валидация формата импортируемой таблицы
        // _ToDo_

        // Жадная загрузка для оптимизации производительности
        $allImportCategories = ImportMappingElement::find()->with('userCategories')->indexBy('name')->asArray()->all();

        /**
         * Последовательно читаем строки таблицы, сохраняем в БД новые записи
         * или обновляем существующие
         */
        foreach ($rows as $key => $row) {
            // Пропускаем пустые строки с пробелами
            if (trim(implode($row)) == '') {
                break;
            }

            $all_amount++;
            $isNew = false;

            // Валидация и нормализация уникальных атрибутов модели
            $user = new User();
            $validationResult = true;
            foreach ($uniqueAttributes as $attr => $validator) {
                if (is_array($loadMap[$attr])) {
                    $user->$attr = $row[$loadMap[$attr]['label']];
                } else {
                    $user->$attr = $row[$loadMap[$attr]];
                }
                $validationResult = $validationResult && $user->$validator($attr, $update);
            }

            /**
             * Валидация и преобразование поля со списком категорий,
             * используя данные сопоставления категорий
             */
            $error = '';
            $categories = static::validateConvertImportCategories(
              $row[$loadMap['categories']['label']],
              $allImportCategories,
              $error
            );

            if ($categories) {
                $row[$loadMap['categories']['label']] = $categories;
                if ($error !== '') {
                    $warnings[] =
                      '[row ' .
                      ($key + 2) .
                      '] : [' .
                      (is_array($loadMap[$keyAttribute]) ? $row[$loadMap[$keyAttribute]['label']] : $row[$loadMap[$keyAttribute]]) .
                      '] : [' .
                      $loadMap['categories']['label'] .
                      '] : ' .
                      $error;
                }
            } else {
                $user->addError('categories', $error);
                $validationResult = false;
            }

            // Обработка всех прочих полей импорта
            if ($validationResult) {
                $_user = User::find()->where([$keyAttribute => $user->$keyAttribute])->one();
                if (!$_user) {
                    $isNew = true;
                } else {
                    foreach ($uniqueAttributes as $attr => $validator) {
                        $_user->$attr = $user->$attr;
                    }

                    if ($user->hasErrors()) {
                        $_user->addErrors($user->getErrors());
                    }

                    $user = $_user;
                }

                $user->scenario = 'import';

                /**
                 * Устанавливаем значения атрибутов модели, кроме уникальных,
                 * которые мы уже установили и проверили
                 */
                foreach ($loadMap as $attribute => $column) {
                    if (!array_key_exists($attribute, $uniqueAttributes)) {
                        if (is_array($column)) {
                            if (isset($column['values'])) /**
                             * Если у поля есть предопределенный список выбора,
                             * транслируем элементы списков выбора в термины программы
                             */ {
                                $row[$column['label']] = strtr(mb_strtolower($row[$column['label']]), $column['values']);
                            }
                            $user->{$attribute} = $row[$column['label']];
                        } else {
                            $user->{$attribute} = $row[$column];
                        }
                    }
                }
                $user->setLocation($row[$loadMap['location']['label']]);
                $user->setRegion($row[$loadMap['region']['label']]);
                $user->profile_status = $user::PROFILE_STATUS_UNCONFIRMED;
                /**
                 * Для обновления локации с использованием св-в 'location' и 'region',
                 * необходимо сбросить значение локации
                 */
                if (!$isNew) {
                    $user->location_id = '';
                }

                // Валидируем значения атрибутов модели, кроме уникальных, которые мы уже проверили
                if ($user->validate() &&
                  $user->validateCategories('categories') &&
                  $user->validateRoles('roles') &&
                  $user->validateRegionAndLocation('location') &&
                  $user->validatePhotologo('photologo')
                ) {
                    // Генерация случайного пароля для новых пользователей
                    if ($isNew) {
                        $user->password = substr(uniqid(md5(rand()), true), 0, 8);
                    }

                    // Производим запись в БД
                    if ($user->save()) {
                        if ($isNew) {
                            $added_amount++;
                        } else {
                            $updated_amount++;
                        }
                    }
                }
            }
            $validationErrors = $user->getErrors();
            foreach ($validationErrors as $_attribute => $_errors) {
                $label = is_array($loadMap[$_attribute]) ? $loadMap[$_attribute]['label'] : $loadMap[$_attribute];
                $errors[] =
                  '[row ' .
                  ($key + 2) .
                  '] : [' .
                  (is_array($loadMap[$keyAttribute]) ? $row[$loadMap[$keyAttribute]['label']] : $row[$loadMap[$keyAttribute]]) .
                  '] : [' .
                  $label .
                  '] : ' .
                  implode(' ; ', $_errors);
            }
        }

        // ipyhonin: Запись данных логирования 
        $result['all_amount'] = $all_amount;
        $result['added_amount'] = $added_amount;
        $result['updated_amount'] = $updated_amount;
        $result['errors'] = $errors;
        $result['warnings'] = $warnings;

        return $result;
        } catch (\Exception $e) {
            Yii::$app->getSession()->setFlash('error' , 'Не реализовано! Обращайтесь к разработчикам.');
            Yii::$app->controller->goBack();
        }
    }

    public static function validateConvertImportCategories($importCategories, $allImportCategories, &$error)
    {
        $categories = [];
        $wrongImportCategories = [];

        if (trim($importCategories) === '') {
            $error = Yii::t('admin/yml', 'Поле является обязательным и не может быть пустым.');
            return false;
        }

        $importCategories = explode(';', $importCategories);

        foreach ($importCategories as $icat) {
            $icat = trim($icat);
            if ($icat <> '') {
                if (isset($allImportCategories[$icat])) {
                    $cats = $allImportCategories[$icat]['userCategories'];
                    $categories = array_merge($categories, array_column($cats, 'id'));
                } elseif (UserCategory::findOne(['title' => $icat])) {
                    $cat = UserCategory::findOne(['title' => $icat]);
                    $categories[] = $cat->id;
                } else {
                    $wrongImportCategories[] = $icat;
                }
            }
        }

        if ($wrongImportCategories !== []) {
            if ($categories !== []) {
                $error =
                  Yii::t(
                    'admin/yml',
                    'В списке категорий присутствуют невалидные значения (' . implode('; ', $wrongImportCategories) . ').'
                  );
            } else {
                $error = Yii::t('admin/yml', 'В списке категорий отсутствуют валидные значения.');
                return false;
            }
        }

        $categories = array_unique($categories);
        return implode(',', $categories);
    }
}
