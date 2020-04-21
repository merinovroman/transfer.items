<?php

namespace TransferItems;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * id int mandatory
 * add int mandatory
 * update int mandatory
 * delete int mandatory
 *
 * @package TransferItems
 * @author Roman Merinov <merinovroman@gmail.com>
 **/
class LogTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'transferitems_log';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            'id' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('_ENTITY_ID_FIELD'),
            ],
            'add' => [
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_ADD_FIELD'),
            ],
            'update' => [
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_UPDATE_FIELD'),
            ],
            'delete' => [
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_DELETE_FIELD'),
            ],
            'errors' => [
                'data_type' => 'text',
                'title' => Loc::getMessage('_ENTITY_ERRORS_FIELD'),
            ],
        ];
    }
}