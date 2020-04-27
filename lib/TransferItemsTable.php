<?php

namespace Transfer\Items;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class TransferItemsTable
 *
 * id int mandatory
 * handbook_name string(100) mandatory
 * event int mandatory
 * data string mandatory
 *
 * @package Transfer\Items
 * @author Roman Merinov <merinovroman@gmail.com>
 **/
class TransferItemsTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'transferitems';
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
            'handbook_name' => [
                'data_type' => 'string',
                'required' => true,
                'validation' => [__CLASS__, 'validateHandbookName'],
                'title' => Loc::getMessage('_ENTITY_HANDBOOK_NAME_FIELD'),
            ],
            'handbook_element' => [
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_HANDBOOK_ELEMENT_FIELD'),
            ],
            'event' => [
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_EVENT_FIELD'),
            ],
            'data' => [
                'data_type' => 'text',
                'required' => true,
                'title' => Loc::getMessage('_ENTITY_DATA_FIELD'),
            ],
        ];
    }

    /**
     * Returns validators for handbook_name field.
     *
     * @return array
     * @throws Main\ArgumentTypeException
     */
    public static function validateHandbookName()
    {
        return [new Main\Entity\Validator\Length(null, 100)];
    }

    /**
     * Returns validators for handbook_element field.
     *
     * @return array
     * @throws Main\ArgumentTypeException
     */
    public static function validateHandbookElement()
    {
        return [new Main\Entity\Validator\Length(null, 100)];
    }
}