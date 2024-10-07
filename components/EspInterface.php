<?php

namespace admin\modules\subscribe\components;

use admin\modules\subscribe\models\Subscriber;

interface EspInterface
{
    /**
     * Один из возможных результатов выполнения метода subscribe().
     * Означает, что возникла непредвиденная ошибка выполнения
     */
    const SUBSCRIBE_RESULT_ERROR = 0;

    /**
     * Один из возможных результатов выполнения метода subscribe().
     * Описывает одну из следующих ситуаций:
     * 1) Адрес добавлен в список рассылки, отправлен запрос для подтверждения адреса
     * 2) Адрес уже существует в списке рассылки, но до сих пор не подтвержден.
     * Отправлен повторный запрос для подтверждения адреса
     */
    const SUBSCRIBE_RESULT_CONFIRMATION_REQUESTED = 1;

    /**
     * Один из возможных результатов выполнения метода subscribe().
     * Означает, что данный адрес уже существует и подтвержден
     */
    const SUBSCRIBE_RESULT_ALREADY_ACTIVE = 2;

    /**
     * Один из возможных результатов выполнения метода subscribe().
     * Означает, что по некоторым причинам подписка невозможна.
     * Например, адрес может быть заблокирован.
     */
    const SUBSCRIBE_RESULT_REFUSED = 3;

    /**
     * @param Subscriber $model
     * @return int Возвращает одно из следующих значений:
     * SUBSCRIBE_RESULT_ALREADY_ACTIVE
     * SUBSCRIBE_RESULT_CONFIRMATION_REQUESTED
     * SUBSCRIBE_RESULT_REFUSED
     * SUBSCRIBE_RESULT_ERROR
     */
    public function subscribe($model);

    /**
     * Обновляет локальные данные подписчиков в соответствии с данными
     * на стороне ESP
     */
    public function export($data = null);

    /**
     * Возвращает ответ ESP сервера на последний запрос
     */
    public function getResponse();

    /**
     * Т.к. запрос (вебхук) с данными для экспорта контактов приходит извне,
     * мы вынуждены отключать для него CSRF проверку. Поэтому необходим
     * дополнительный контроль данного типа запросов.
     * Возвращает true, если запросу можно доверять. Иначе возвращает false
     */
    public function filterExportRequest();
}