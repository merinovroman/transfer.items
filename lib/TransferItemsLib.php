<?php

namespace Transfer\Items;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

/**
 * Class TransferItemsLib
 *
 * @property int $page
 * @property int $pages
 * @property bool $complete
 * @property array $allErrors
 *
 * @package Transfer\Items
 * @author Roman Merinov <merinovroman@gmail.com>
 */
class TransferItemsLib
{

    /** @var int */
    public $page;
    /** @var int */
    public $pages;
    /** @var bool */
    public $complete = false;
    /** @var array */
    public $allErrors;

    public function __construct()
    {

    }

    /**
     * @return array
     */
    public function getHighloadBlockBlockProperty(): array
    {
        $hls = $handbooksResult = [];
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


    /**
     * @param array $request
     * @return $this
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function actionExport($request)
    {
        $this->page = $_SESSION['transferitems_export']['page'] ?: 1;
        $nextPage = $this->page + 1;
        $handbookName = $request['handbook_export'];
        $rows = TransferItemsTable::getList([
            'filter' => ['handbook_name' => $handbookName],
            'order' => ['id' => 'asc'],
        ])->fetchAll();

        $changesByHandbooksChank = array_chunk($rows, Option::get('transfer.items', 'step'));
        if ($this->page == 1) {
            file_put_contents(TRANSFERITEMS_EXPORT_FILE, '', LOCK_EX);
        }
        foreach ($changesByHandbooksChank[$this->page - 1] as $changeItem) {
            $_SESSION['transferitems_export']['logs'][$changeItem['event']][] = $changeItem;
            file_put_contents(TRANSFERITEMS_EXPORT_FILE, implode(TRANSFERITEMS_CSV_DELIMETR, $changeItem) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $this->pages = count($changesByHandbooksChank);
        $_SESSION['transferitems_export']['page'] = $nextPage;
        $_SESSION['transferitems_export']['pages'] = $this->pages;

        if ($nextPage > $this->pages) {
            if (Option::get('transfer.items', 'logs')) {
                TransferItemsLogTable::add([
                    'add' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::UPDATE])),
                    'errors' => \Bitrix\Main\Web\Json::encode($_SESSION['transferitems_export']['errors'])
                ]);
            }
            unset($_SESSION['transferitems_export']);
            $this->complete = true;
        }

        return $this;
    }

    public function actionImport($request)
    {
        $this->page = $_SESSION['transferitems_import']['page'] ?: 1;
        $nextPage = $this->page + 1;
        $this->allErrors = $errorsAr = [];
        $handbookImportName = $request['handbook_import'];

        $hlblock = Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $handbookImportName]
        ])->fetch();

        $hl = Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

        $file = $_FILES['file'];
        move_uploaded_file($file['tmp_name'], TRANSFERITEMS_IMPORT_FILE);
        $csv = file_get_contents(TRANSFERITEMS_IMPORT_FILE);
        $csvAr = explode(PHP_EOL, $csv);
        $csvArChank = array_chunk($csvAr, Option::get('transfer.items', 'step'));

        foreach ($csvArChank[$this->page - 1] as $changeLine) {
            $errorUpdate = '';
            if (!$changeLine) {
                continue;
            }
            $changeAr = explode(TRANSFERITEMS_CSV_DELIMETR, $changeLine);
            if ($changeAr[4]) {
                $changeAr[4] = \Bitrix\Main\Web\Json::decode($changeAr[4]);
            }
            if ($changeAr[3] == Event::UPDATE) {
                if (!$hl::getById($changeAr[2])->fetch()) {
                    $errorUpdate = Loc::getMessage('ADMIN_ELEMENT_NOT_FOUND') . $changeAr[2];
                }
                $result = $hl::update($changeAr[2], $changeAr[4]);
            } else if ($changeAr[3] == Event::DELETE) {
                if (!$hl::getById($changeAr[2])->fetch()) {
                    $errorUpdate = Loc::getMessage('ADMIN_ELEMENT_NOT_FOUND_DEL') . $changeAr[2];
                }
                $result = $hl::delete($changeAr[2]);
            } else if ($changeAr[3] == Event::ADD) {
                $result = $hl::add($changeAr[4]);
            }

            if (!$result->isSuccess()) {
                foreach ($result->getErrorMessages() as $error) {
                    $_SESSION['transferitems_import']['errors'][] = $error;
                }
            }
            if ($errorUpdate) {
                $_SESSION['transferitems_import']['errors'][] = $errorUpdate;
            }
            if ($result->isSuccess() && !$errorUpdate) {
                $_SESSION['transferitems_import']['logs'][$changeAr[3]][] = $changeAr[2];
            }
        }

        $this->allErrors = $_SESSION['transferitems_import']['errors'];

        $this->pages = count($csvArChank);
        $_SESSION['transferitems_import']['page'] = $nextPage;
        $_SESSION['transferitems_import']['pages'] = $this->pages;

        if ($nextPage > $this->pages) {
            if (Option::get('transfer.items', 'logs')) {
                TransferItemsLogTable::add([
                    'add' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::UPDATE])),
                    'errors' => \Bitrix\Main\Web\Json::encode($_SESSION['transferitems_import']['errors'])
                ]);
            }
            $asd = $_SESSION['transferitems_import']['logs'];
            unset($_SESSION['transferitems_import']);
            $this->complete = true;
        }

        return $this;
    }
}