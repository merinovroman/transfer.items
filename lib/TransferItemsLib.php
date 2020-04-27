<?php

namespace Transfer\Items;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

/**
 * Class TransferItemsLib
 *
 * @package Transfer\Items
 * @author Roman Merinov <merinovroman@gmail.com>
 */
class TransferItemsLib
{

    /**
     * @return array
     */
    public function getHighloadBlockBlockProperty(): array
    {
        $hls = $handbooksResult =[];
        $hlblocks = \Bitrix\Highloadblock\HighloadBlockTable::getList();
        while ($hl = $hlblocks->fetch()) {
            $hls[$hl['TABLE_NAME']] = $hl['NAME'];
        }

        $properties = \CIBlockProperty::GetList([], ["USER_TYPE" => "directory"]);
        while ($prop_fields = $properties->GetNext()) {
            $handbooksResult['handbooks'][$hls[$prop_fields['USER_TYPE_SETTINGS']['TABLE_NAME']]] = $prop_fields['NAME'];
            $handbooksResult['handbookNames'][] = $prop_fields['NAME'];
        }

        return $handbooksResult;
    }
}