<?php

namespace TransferItems;

/**
 * Class Event
 *
 * @package TransferItems
 * @author Roman Merinov <merinovroman@gmail.com>
 */
class Event
{
    const UPDATE = 1;
    const DELETE = 2;
    const ADD = 3;

    public static function updateHandbook(\Bitrix\Main\Entity\Event $event)
    {
        $eventType = $event->getEventType();
        $arFields = $event->getParameter("fields");
        $idAr = $event->getParameter('id');
        TransferItemsTable::add([
            'handbook_name' => str_replace('OnAfterUpdate', '', $eventType),
            'event' => self::UPDATE,
            'handbook_element' => $idAr['ID'],
            'data' => \Bitrix\Main\Web\Json::encode($arFields)
        ]);
    }

    public static function deleteHandbook(\Bitrix\Main\Entity\Event $event)
    {
        $eventType = $event->getEventType();
        $idAr = $event->getParameter('id');
        TransferItemsTable::add([
            'handbook_name' => str_replace('OnAfterDelete', '', $eventType),
            'event' => self::DELETE,
            'handbook_element' => $idAr['ID'],
            'data' => \Bitrix\Main\Web\Json::encode($idAr)
        ]);
    }

    public static function addHandbook(\Bitrix\Main\Entity\Event $event)
    {
        $eventType = $event->getEventType();
        $arFields = $event->getParameter("fields");
        $id = $event->getParameter('id');
        TransferItemsTable::add([
            'handbook_name' => str_replace('OnAfterAdd', '', $eventType),
            'event' => self::ADD,
            'handbook_element' => $id,
            'data' => \Bitrix\Main\Web\Json::encode($arFields)
        ]);
    }
}