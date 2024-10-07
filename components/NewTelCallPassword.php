<?php

namespace admin\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class NewTelCallPassword extends Component
{

    /**
     * Тип ошибки, когда обнаружены проблемы запроса. Например, возникли проблемы аутентификации/авторизации,
     * невалидны значения параметров и т.п.
     */
    const ERROR_BAD_REQUEST = 3;
    /**
     * Тип ошибки, когда код ответа сервера не равен 20х
     */
    const ERROR_BAD_RESPONSE = 1;
    /**
     * Ошибка, когда невалидна подпись ответа сервера
     */
    const ERROR_INVALILD_SERVER_SIGNATURE = 2;
    /**
     * Ошибки возникшие во время выполнения запрашиваемого метода
     */
    const ERROR_METHOD_FAILURE = 4;

    /**
     * Непредвиденные ошибки
     */
    const ERROR_UNKNOWN = 5;
    private $_httpClient;
    private $time;
    /**
     * @var string Базовый адрес запросов
     */
    public $baseUrl = 'https://api.new-tel.net/';
    /**
     * @var string Ссылка для оповещения (web-hook) об окончании вызова
     */
    public $callbackLink;
    /**
     * @var array Данные ошибки, при неудачной обработке запроса на отправку получателю одноразового пароля.
     * Ключ - тип ошибки, значение - сообщение об ошибке
     */
    public $error;
    /**
     * @var string Ключ CallPassword API для авторизации запросов
     */
    public $key;
    /**
     * @var string Одноразовый пароль, который вернул сервер при успешной обработке запроса
     * на отправку получателю одноразового пароля
     */
    public $password;
    /**
     * @var integer Время ожидания ответа пользователя в секундах
     */
    public $timeout;
    /**
     * @var string Относительный адрес запросов
     */
    public $url = 'call-password/start-password-call';
    /**
     * @var string Ключ CallPassword API для подписи запросов
     */
    public $writeKey;

    public function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = Yii::createObject([
              'class'          => Client::className(),
              'baseUrl'        => $this->baseUrl,
              'requestConfig'  => [
                'method'  => 'POST',
                'url'     => $this->url,
                'format'  => Client::FORMAT_JSON,
                'headers' => [
                  'accept' => 'application/json',
                ],
              ],
              'responseConfig' => [
                'format' => Client::FORMAT_JSON,
              ],
            ]);
        }
        return $this->_httpClient;
    }

    public function getSignature($data, $time)
    {
        return hash('sha256', "{$this->url}\n{$time}\n{$this->key}\n{$data}\n{$this->writeKey}");
    }

    public function getToken($data, $time)
    {
        $signature = $this->getSignature($data, $time);
        return "{$this->key}{$time}{$signature}";
    }

    /**
     * Отправляет запрос серверу на отсылку получателю одноразового пароля
     * @param string $phone    Обязательный номер телефона назначения, в любом формате, содержащий от 9 до 15 цифр
     * @param string $password Опциональный одноразовый пароль (4 десятичные цифры), которые будут передаваться в виде
     *                         последних цифр номера. Если пароль не будет указан, то он будет сгенерирован сервером
     * @return bool Результат выполнения. Положительным (true) является результат, при котором ответ сервера
     *                         содержит пароль
     */
    public function send($phone, $password = null)
    {
        $this->error = null;
        $this->password = null;
        $phone = str_replace(['+', '-'], '', filter_var($phone, FILTER_SANITIZE_NUMBER_INT));

        $data = array_filter([
          'dstNumber'    => $phone,
          'pin'          => $password,
          'timeout'      => $this->timeout,
          'callbackLink' => $this->callbackLink,
        ]);

        $request = $this->getHttpClient()->createRequest()->setData($data)->prepare();

        $this->time = time();
        $token = $this->getToken($request->getContent(), $this->time);

        $request->headers->set('Authorization', "Bearer $token");

        $response = $request->send();

        Yii::info('-------- RESPONSE:' . print_r($response->data, true));

        if (!$response->isOk) {
            $this->error[static::ERROR_BAD_RESPONSE] =
              Yii::t('admin', 'Ошибка сервера. Код ответа: {code}', ['code' => $response->statusCode]);
            return false;
        } elseif ($response->data['status'] == 'error') {
            $this->error[static::ERROR_BAD_REQUEST] =
              Yii::t('admin', 'Ошибка обработки запроса: {message}', ['message' => $response->data['message']]);
            return false;
        } elseif ($this->getSignature($response->content, $this->time) !== $response->headers->get('Signature')) {
            $this->error[static::ERROR_INVALILD_SERVER_SIGNATURE] = Yii::t('admin', 'Невалидная подпись сервера');
            return false;
        } elseif ($response->data['data']['result'] == 'error') {
            $this->error[static::ERROR_METHOD_FAILURE] =
              Yii::t('admin', 'Ошибка выполнения метода: {message}', ['message' => $response->data['data']['message']]);
            return false;
        } elseif (!isset($response->data['data']['callDetails']['pin'])) {
            $this->error[static::ERROR_UNKNOWN] = Yii::t('admin', 'Одноразовый пароль не определен');
            return false;
        } else {
            $this->password = $response->data['data']['callDetails']['pin'];
            return true;
        }
    }
}