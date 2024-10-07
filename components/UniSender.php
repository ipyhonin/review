<?php

namespace admin\modules\subscribe\components;

use Yii;
use yii\base\BaseObject;
use admin\modules\subscribe\models\Subscriber;
use Unisender\ApiWrapper\UnisenderApi;
use yii\helpers\Url;
use admin\helpers\Mail;
use admin\modules\subscribe\helpers\ContactsExportFile;

class UniSender extends BaseObject implements EspInterface
{
    public $apiKey;

    public $platform;

    public $listId = '6';

    public $exportNotifyUrl;

    protected $api;

    protected $exportTaskId;

    protected $_response;

    /**
     * @inheritdoc
     */
    public function init() {
        $this->platform =  $this->platform ?? $_SERVER['HTTP_HOST'];
        $this->exportNotifyUrl = $this->exportNotifyUrl ?? Url::to(['/subscribe/export'], true);
        $this->api = new UnisenderApi($this->apiKey, 'UTF-8', 4, null, false, $this->platform);
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * @inheritdoc
     */
    public function subscribe($model) {
        /** Инициализация */
        $params = [
            'list_ids' => $this->listId,
            'fields' => [
                'name' => $model->username,
                'email' => $model->email,
            ],
            'double_optin' => 4,
            'overwrite' => 2,
        ];

        /** Проверяем наличие подписчика с данным адресом */
        $result = $this->api->getContact(['email' => $model->email]);
        $this->_response = $result;
        $result = json_decode($result, true);

        /** Если такой подписчик НЕ существует */
        if (isset($result['error'])) {

            $result2 = $this->api->subscribe($params);
            $this->_response = $result2;
            $result2 = json_decode($result2, true);
            return isset($result2['result']['person_id']) ? self::SUBSCRIBE_RESULT_CONFIRMATION_REQUESTED : self::SUBSCRIBE_RESULT_REFUSED;
        
        /** Если такой подписчик существует */
        } elseif (isset($result['result'])) {
            
            /** и если его адрес подтвержден */
            if (in_array($result['result']['email']['status'], ['new', 'active'])) {
        
                return self::SUBSCRIBE_RESULT_ALREADY_ACTIVE;
        
            /** и если его адрес еще НЕ подтвержден */
            } elseif ($result['result']['email']['status'] == 'invited') {
        
                $result2 = $this->api->subscribe($params);
                $this->_response = $result2;
                $result2 = json_decode($result2, true);
                return isset($result2['result']['person_id']) ? self::SUBSCRIBE_RESULT_CONFIRMATION_REQUESTED : self::SUBSCRIBE_RESULT_REFUSED;
            
            /** Для любого другого статуса, например, заблокирован и т.п. */
            } else {

                /** отклоняем запрос */
                return self::SUBSCRIBE_RESULT_REFUSED;

            }

        /** При непредвиденном ответе считаем, что произошел какой-то сбой в обработке запроса */
        } else {

            return self::SUBSCRIBE_RESULT_ERROR;

        }
    }

    /**
     * @inheritdoc
     */
    public function export($data = null) {
        /**
         * Если данных нет, то отправляем запрос на предоставление
         * данных для экспорта
         */
        if ($data === null) {
            $params = [
                'notify_url' => $this->exportNotifyUrl,
                'list_id' => $this->listId,
            ];
            $result = $this->api->taskExportContacts($params);
            $this->_response = $result;
            $result = json_decode($result, true);

            if (isset($result['result']['task_uuid'])) {
                $this->exportTaskId = $result['result']['task_uuid'];
                return true;
            } else {
                return false;
            }
        /**
         * Экспорт
         */
        } else {
            $data = json_decode($data, true);
            
            $fileToDownload = $data['result']['file_to_download'] ?? false;

            if (!$fileToDownload) {
                return false;
            }

            return ContactsExportFile::save($fileToDownload);
            
            return Mail::send(
                'igor.pyhonin@mail.ru',
                'EXPORT CONTACTS',
                '@admin/modules/subscribe/mail/ru/export_contacts',
                ['data' => $fileToDownload],
            );
        }
    }

    public function filterExportRequest()
    {
        $request = Yii::$app->request;

        if (
            str_contains($request->getUserAgent(), 'UniSender')
            && str_contains($request->getUserAgent(), 'Webhook')
        ) {
            return true;
        }

        return false;
    }
}