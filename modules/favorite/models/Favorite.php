<?php

namespace admin\modules\favorite\models;

use admin\models\User;
use admin\models\UserQuery;
use Yii;
use yii\web\Cookie;

/**
 * This is the base model class for table "admin_module_favorite".
 * @property integer            $id
 * @property integer            $user_id
 * @property string             $item_class
 * @property integer            $item_id
 * @property string             $comment
 * @property \admin\models\User $user
 */
class Favorite extends \admin\base\ActiveRecord
{
    /**
     * @var string Идентификатор куки, в котором хранится информацио об избранных элементах
     */
    const COOKIE_NAME = 'favorite';

    public function addItem($itemType, $itemId)
    {
        if (Yii::$app->user->isGuest) {
            self::addCookieItem($itemType, $itemId);
            return true;
        } else {
            // Проверяем наличие, чтобы исключить ошибки исполнения, связанные с добавлением дублей
            $item = self::find()->where([
              'item_class' => self::config()[$itemType]['item_class'],
              'item_id'    => $itemId,
              'user_id'    => Yii::$app->user->id,
            ])->one();
            if (!$item) {
                $item = new Favorite();
                $item->isNewRecord = true;
                $item->item_class = self::config()[$itemType]['item_class'];
                $item->item_id = $itemId;
                $item->user_id = Yii::$app->user->id;
                return $item->save();
            } else {
                return true;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
          'id'         => Yii::t('admin/favorite', 'Id'),
          'user_id'    => Yii::t('admin/favorite', 'Id пользователя'),
          'item_class' => Yii::t('admin/favorite', 'Класс избранного элемента'),
          'item_id'    => Yii::t('admin/favorite', 'Id избранного элемента'),
          'comment'    => Yii::t('admin/favorite', 'Комментарий'),
        ];
    }

    public function deleteItem($itemType, $itemId)
    {
        if (Yii::$app->user->isGuest) {
            self::deleteCookieItem($itemType, $itemId);
            return true;
        } else {
            $item = self::find()->where([
              'item_class' => self::config()[$itemType]['item_class'],
              'item_id'    => $itemId,
              'user_id'    => Yii::$app->user->id,
            ])->one();
            if ($item) {
                return $item->delete() === false ? false : true;
            };
            return true;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Перенос данных избранных элементов из куки в базу данных
     * @return bool true - если перенесен хотя бы один элемент
     */
    public function move()
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $cookies = Yii::$app->request->cookies;
        if (!$cookies->has(self::COOKIE_NAME)) {
            return false;
        }
        $cookie = $cookies->get(self::COOKIE_NAME);
        $value = json_decode($cookie->value, true);

        $result = false;
        $newValue = $value;
        foreach ($value as $type => $ids) {
            foreach ($ids as $key => $id) {
                if ($this->addItem($type, $id)) {
                    unset($newValue[$type][$key]);
                    $result = true;
                };
            }
        }

        $cookie->value = json_encode($newValue);
        Yii::$app->response->cookies->add($cookie);

        return $result;
    }

    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return [
          'user',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
          [['user_id', 'item_class', 'item_id'], 'required'],
          [['user_id', 'item_id'], 'integer'],
          [['comment'], 'string'],
          [['item_class'], 'string', 'max' => 255],
          [
            ['user_id', 'item_class', 'item_id'],
            'unique',
            'targetAttribute' => ['user_id', 'item_class', 'item_id'],
            'message'         => Yii::t(
              'admin/favorite',
              'Запись с идентичной комбинацией полей user_id, item_class, item_id уже существует'
            ),
          ],
        ];
    }

    public static function addCookieItem($type, $id)
    {
        $cookies = Yii::$app->request->cookies;
        $cookie = $cookies->has(self::COOKIE_NAME) ? $cookies->get(self::COOKIE_NAME) : self::newCookie();
        $value = json_decode($cookie->value, true);
        $itemClass = self::config()[$type]['item_class'];
        $item = $itemClass::find()->where([$itemClass::tableName() . '.' . 'id' => $id])->one();

        if ($item) {
            $value[$type][] = $id;
            $value[$type] = array_unique($value[$type]);

            $cookie->value = json_encode($value);
            Yii::$app->response->cookies->add($cookie);
            return true;
        } else {
            return false;
        }
    }

    public static function config()
    {
        $module = Yii::$app->getModule('favorite');
        return $module->items;
    }

    public static function deleteCookieItem($type, $id)
    {
        $cookies = Yii::$app->request->cookies;
        if (!$cookies->has(self::COOKIE_NAME)) {
            return;
        }

        $cookie = $cookies->get(self::COOKIE_NAME);
        $value = json_decode($cookie->value, true);

        if (!array_key_exists($type, $value)) {
            return;
        }

        $value[$type] = array_filter($value[$type], function ($val) use ($id) {
            return ($val != $id);
        });

        $cookie->value = json_encode($value);
        Yii::$app->response->cookies->add($cookie);
    }

    public static function getCookieItems($type = null)
    {
        $cookies = Yii::$app->request->cookies;
        if (!$cookies->has(self::COOKIE_NAME)) {
            return [];
        }

        $cookie = $cookies->get(self::COOKIE_NAME);
        $value = json_decode($cookie->value, true);

        $result = [];
        if ($type) {
            $tableName = self::config()[$type]['item_class']::tableName();
            $result = self::config()[$type]['item_class']::find()->where([$tableName . '.id' => $value[$type]])->all();
        } else {
            foreach ($value as $t => $items) {
                $tableName = self::config()[$t]['item_class']::tableName();
                $result[$t] = self::config()[$t]['item_class']::find()->where([$tableName . '.id' => $items])->all();
            }
        }

        return $result;
    }

    public static function getDbItemsByType($type)
    {
        /** @var UserQuery $query */

        $query = self::config()[$type]['item_class']::find();
        /** TODO Стоит сделать метод типа reset() */
        $query->join = null;
        $query->select = null;
        $query->from = null;
        $query
          ->select('it.*')
          ->from(['it' => self::config()[$type]['item_class']::tableName()])
          ->innerJoin(['ft' => self::tableName()], 'it.id = ft.item_id')
          ->where([
            'ft.item_class' => self::config()[$type]['item_class'],
            'ft.user_id'    => Yii::$app->user->id,
          ]);
        return $query->all();
    }

    public static function getItems($type = null)
    {
        if (Yii::$app->user->isGuest) {
            return self::getCookieItems($type);
        } else {
            if ($type) {
                $result = self::getDbItemsByType($type);
            } else {
                $types = array_keys(self::config());
                foreach ($types as $t) {
                    $result[$t] = self::getDbItemsByType($t);
                    $result = array_filter($result);
                }
            }
            return $result;
        }
    }

    public static function newCookie()
    {
        return new Cookie([
          'name'   => self::COOKIE_NAME,
          'value'  => '{}',
          'expire' => time() + (365 * 24 * 60 * 60),
        ]);
    }

    public static function status($itemType, $itemId)
    {
        if (Yii::$app->user->isGuest) {
            /**
             * Если куки содержится в куках запроса и ответа, то анализируем куки ответа,
             * т.к. в нем содержатся последние изменения, которые могли быть сделаны
             * при обработке текущего запроса
             */
            $requestCookies = Yii::$app->request->cookies;
            $responseCookies = Yii::$app->response->cookies;
            if ($responseCookies->has(self::COOKIE_NAME)) {
                $cookie = $responseCookies->get(self::COOKIE_NAME);
            } elseif ($requestCookies->has(self::COOKIE_NAME)) {
                $cookie = $requestCookies->get(self::COOKIE_NAME);
            } else {
                return false;
            }

            $value = json_decode($cookie->value, true);
            if (!isset($value[$itemType])) {
                return false;
            } else {
                return (array_search($itemId, $value[$itemType]) !== false);
            }
        } else {
            return (self::find()->where([
                'user_id'    => Yii::$app->user->id,
                'item_class' => self::config()[$itemType]['item_class'],
                'item_id'    => $itemId,
              ])->one() !== null);
        }
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_module_favorite';
    }

    public static function checkCookieItems($type = null)
    {
        $cookies = Yii::$app->request->cookies;
        if (!$cookies->has(self::COOKIE_NAME)) {
            return false;
        }

        $cookie = $cookies->get(self::COOKIE_NAME);
        $value = json_decode($cookie->value, true);

        return $type ? !empty($value[$type]) : !empty($value);
    }

    public static function checkDbItems($type = null)
    {
        $query = self::find()->where(['user_id' => Yii::$app->user->id]);
        if ($type) {
            $query->andWhere(['item_class' => self::config()[$type]['item_class']]);
        }

        return !empty($query->one());
    }

    public static function checkItems($type = null)
    {
        return (Yii::$app->user->isGuest) ? self::checkCookieItems($type) : self::checkDbItems($type);
    }
}
