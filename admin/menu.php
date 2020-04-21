<?

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
if ($APPLICATION->GetGroupRight("translate") <= 'D') {
    return false;
}

$aMenu = [
    "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
    "sort" => 1,                    // вес пункта меню
    "url" => "transferitems_export_import.php?lang=" . LANGUAGE_ID,  // ссылка на пункте меню
    "text" => 'Импорт/экспорт элементов справочников',       // текст пункта меню
    "title" => 'Импорт/экспорт элементов справочников', // текст всплывающей подсказки
    "icon" => "form_menu_icon", // малая иконка
    "page_icon" => "form_page_icon", // большая иконка
];

return $aMenu;