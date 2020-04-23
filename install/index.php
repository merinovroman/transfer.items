<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use TransferItems\TransferItemsTable;
use TransferItems\TransferItemsLogTable;

Loc::loadMessages(__FILE__);

class TransferItems extends CModule
{

    var $MODULE_ID = 'transferitems';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    public function TransferItems()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('TRANSFERITEMS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('TRANSFERITEMS_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->InstallFiles();
    }

    public function doUninstall()
    {
        $this->uninstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            TransferItemsTable::getEntity()->createDbTable();
            TransferItemsLogTable::getEntity()->createDbTable();
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(TransferItemsTable::getTableName());
            $connection->dropTable(TransferItemsLogTable::getTableName());
        }
    }

    public function UnInstallEvents()
    {
        $handbookOptions = explode(',', Option::get($this->MODULE_ID, 'handbooks'));
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach ($handbookOptions as $handbooksName) {
            $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterUpdate', $this->MODULE_ID, "TransferItems\Event", "updateHandbook");
            $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterDelete', $this->MODULE_ID, "TransferItems\Event", "deleteHandbook");
            $eventManager->UnRegisterEventHandler("", $handbooksName . 'OnAfterAdd', $this->MODULE_ID, "TransferItems\Event", "addHandbook");
        }
        return true;
    }

    public function InstallFiles()
    {
        $re = \CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . getLocalPath('modules/' . $this->MODULE_ID . '/install/admin'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);

        return true;
    }

    public function UnInstallFiles()
    {
        \DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . getLocalPath('modules/' . $this->MODULE_ID . '/install/admin'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');

        return true;
    }
}