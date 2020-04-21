<?php
define('TRANSFERITEMS_EXPORT_FILE', $_SERVER['DOCUMENT_ROOT'] . getLocalPath('modules/transferitems/files') . '/export.csv');
define('TRANSFERITEMS_IMPORT_FILE', $_SERVER['DOCUMENT_ROOT'] . getLocalPath('modules/transferitems/files') . '/import.csv');
define('TRANSFERITEMS_CSV_DELIMETR', '^^^');
Bitrix\Main\Loader::registerAutoLoadClasses(
    "transferitems",
    [
        "TransferItems\\TransferItemsTable" => "lib/transferitems.php",
        "TransferItems\\LogTable" => "lib/log.php",
        "TransferItems\\Event" => "lib/event.php",
    ]
);