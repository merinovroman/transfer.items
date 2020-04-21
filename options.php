<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use TransferItems\Event;

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

Loc::loadMessages(__FILE__);
Loader::includeModule($module_id);
Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

$hls = [];
$handbooks = [];

$hlblocks = Bitrix\Highloadblock\HighloadBlockTable::getList();
while ($hl = $hlblocks->fetch()) {
    $hls[$hl['TABLE_NAME']] = $hl['NAME'];
}

$properties = CIBlockProperty::GetList([], ["USER_TYPE" => "directory"]);
while ($prop_fields = $properties->GetNext()) {
    $handbooks[$hls[$prop_fields['USER_TYPE_SETTINGS']['TABLE_NAME']]] = $prop_fields['NAME'];
}

$aTabs = [
    [
        "DIV" => "edit",
        "TAB" => Loc::getMessage("TRANSFERITEMS_OPTIONS_TAB_NAME"),
        "TITLE" => Loc::getMessage("TRANSFERITEMS_OPTIONS_TAB_NAME"),
        "OPTIONS" => [
            [
                "logs",
                Loc::getMessage("TRANSFERITEMS_LOGS"),
                "",
                ["checkbox"]
            ],
            [
                "handbooks",
                Loc::getMessage("TRANSFERITEMS_HANDBOOKS"),
                '',
                ["multiselectbox", $handbooks]
            ],
            [
                "step",
                Loc::getMessage("TRANSFERITEMS_STEP"),
                '5',
                ["text"]
            ]
        ]
    ]
];

if ($request->isPost() && check_bitrix_sessid()) {

    $eventManager = \Bitrix\Main\EventManager::getInstance();

    foreach ($aTabs as $aTab) {
        foreach ($aTab["OPTIONS"] as $arOption) {
            $optionValue = $request->getPost($arOption[0]);
            if ($request["apply"]) {
                if ($arOption[0] == "handbooks") {
                    foreach ($handbooks as $handbooksName => $handbooksVal) {
                        $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterUpdate', $module_id, "TransferItems\Event", "updateHandbook");
                        $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterDelete', $module_id, "TransferItems\Event", "deleteHandbook");
                        $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterAdd', $module_id, "TransferItems\Event", "addHandbook");
                    }

                    foreach ($optionValue as $val) {
                        $eventManager->registerEventHandler("", $val . 'OnAfterUpdate', $module_id, "TransferItems\Event", "updateHandbook");
                        $eventManager->registerEventHandler("", $val . 'OnAfterDelete', $module_id, "TransferItems\Event", "deleteHandbook");
                        $eventManager->registerEventHandler("", $val . 'OnAfterAdd', $module_id, "TransferItems\Event", "addHandbook");
                    }
                }

                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            } elseif ($request["default"]) {
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }
}

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin(); ?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">
    <? foreach ($aTabs as $aTab) {

        if ($aTab["OPTIONS"]) {

            $tabControl->BeginNextTab();

            __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
        }
    }

    $tabControl->Buttons();
    ?>

    <input type="submit" name="apply" value="<? echo(Loc::GetMessage("TRANSFERITEMS_OPTIONS_INPUT_APPLY")); ?>"
           class="adm-btn-save"/>
    <input type="submit" name="default" value="<? echo(Loc::GetMessage("TRANSFERITEMS_OPTIONS_INPUT_DEFAULT")); ?>"/>

    <? echo(bitrix_sessid_post()); ?>
</form>

<? $tabControl->End(); ?>
