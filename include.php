<?php
define('TRANSFERITEMS_EXPORT_FILE', $_SERVER['DOCUMENT_ROOT'] . getLocalPath('modules/transferitems/files') . '/export.csv');
define('TRANSFERITEMS_IMPORT_FILE', $_SERVER['DOCUMENT_ROOT'] . getLocalPath('modules/transferitems/files') . '/import.csv');
define('TRANSFERITEMS_CSV_DELIMETR', '^^^');
Bitrix\Main\Loader::registerAutoLoadClasses(
    "transferitems",
    [
        "TransferItems\\TransferItemsTable" => "lib/TransferItemsTable.php",
        "TransferItems\\TransferItemsLogTable" => "lib/TransferItemsLogTable.php",
        "TransferItems\\Event" => "lib/event.php",
        "TransferItems\\TransferItemsLib" => "lib/TransferItemsLib.php",
    ]
);