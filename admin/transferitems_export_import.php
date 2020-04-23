<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock;
use TransferItems\TransferItemsTable;
use TransferItems\Event;
use TransferItems\TransferItemsLogTable;
use TransferItems\TransferItemsLib;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

Loc::loadMessages(__FILE__);
Loader::includeModule('transferitems');
Loader::includeModule('iblock');
Loader::includeModule('highloadblock');
CJSCore::Init(["jquery"]);

$APPLICATION->SetTitle(Loc::getMessage("ADMIN_IMPORT_TITLE"));

$request = HttpApplication::getInstance()->getContext()->getRequest();
$aTabs = [
    ["DIV" => "edit1", "TAB" => Loc::getMessage('ADMIN_EXPORT'),],
    ["DIV" => "edit2", "TAB" => Loc::getMessage('ADMIN_IMPORT')],
];
$tabControl = new CAdminTabControl("tabControl", $aTabs);

// Обработка экспорта и импорта
if ($request['ajax'] == 'y') {
    $actionType = $request['action_type'];

    if ($actionType == 'export') { // экспорт
        $page = $_SESSION['transferitems_export']['page'] ?: 1;
        $nextPage = $page + 1;
        $handbookName = $request['handbook_export'];
        $rows = TransferItemsTable::getList([
            'filter' => ['handbook_name' => $handbookName],
            'order' => ['id' => 'asc'],
        ])->fetchAll();

        $changesByHandbooksChank = array_chunk($rows, Option::get('transferitems', 'step'));
        if ($page == 1) {
            file_put_contents(TRANSFERITEMS_EXPORT_FILE, '', LOCK_EX);
        }
        foreach ($changesByHandbooksChank[$page - 1] as $changeItem) {
            $_SESSION['transferitems_export']['logs'][$changeItem['event']][] = $changeItem;
            file_put_contents(TRANSFERITEMS_EXPORT_FILE, implode(TRANSFERITEMS_CSV_DELIMETR, $changeItem) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $pages = count($changesByHandbooksChank);
        $_SESSION['transferitems_export']['page'] = $nextPage;
        $_SESSION['transferitems_export']['pages'] = $pages;

        if ($nextPage > $pages) {
            if (Option::get('transferitems', 'logs')) {
                TransferItemsLogTable::add([
                    'add' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['transferitems_export']['logs'][Event::UPDATE])),
                    'errors' => Bitrix\Main\Web\Json::encode($_SESSION['transferitems_export']['errors'])
                ]);
            }
            unset($_SESSION['transferitems_export']);
            $complete = true;
        }

        $APPLICATION->RestartBuffer();
        CAdminMessage::ShowMessage([
            "MESSAGE" => Loc::getMessage('ADMIN_IMPORT_TITLE'),
            "DETAILS" => "#PROGRESS_BAR#",
            "HTML" => true,
            "TYPE" => "PROGRESS",
            "PROGRESS_TOTAL" => 100,
            "PROGRESS_VALUE" => round($page / $pages * 100),
        ]);

        if ($complete) {
            CAdminMessage::ShowNote(Loc::getMessage('ADMIN_END_EXPORT'));
            ?>
            <div style="margin-bottom: 30px;">
                <a target="_blank" href="<?= str_replace($_SERVER["DOCUMENT_ROOT"], "", TRANSFERITEMS_EXPORT_FILE) ?>">
                    <?= Loc::getMessage('ADMIN_EXPORT_LINK') ?>
                </a>
            </div>
            <?
        }
        die;

    } else if ($actionType == 'import') { // импорт
        $page = $_SESSION['transferitems_import']['page'] ?: 1;
        $nextPage = $page + 1;
        $allErrors = $errorsAr = [];
        $handbookImportName = $request['handbook_import'];

        $hlblock = Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $handbookImportName]
        ])->fetch();

        $hl = Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

        $file = $_FILES['file'];
        move_uploaded_file($file['tmp_name'], TRANSFERITEMS_IMPORT_FILE);
        $csv = file_get_contents(TRANSFERITEMS_IMPORT_FILE);
        $csvAr = explode(PHP_EOL, $csv);
        $csvArChank = array_chunk($csvAr, Option::get('transferitems', 'step'));

        foreach ($csvArChank[$page - 1] as $changeLine) {
            $errorUpdate = '';
            if (!$changeLine) {
                continue;
            }
            $changeAr = explode(TRANSFERITEMS_CSV_DELIMETR, $changeLine);
            if ($changeAr[4]) {
                $changeAr[4] = Bitrix\Main\Web\Json::decode($changeAr[4]);
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

        $allErrors = $_SESSION['transferitems_import']['errors'];

        $pages = count($csvArChank);
        $_SESSION['transferitems_import']['page'] = $nextPage;
        $_SESSION['transferitems_import']['pages'] = $pages;

        if ($nextPage > $pages) {
            if (Option::get('transferitems', 'logs')) {
                TransferItemsLogTable::add([
                    'add' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::ADD])),
                    'delete' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::DELETE])),
                    'update' => (int)count(array_unique($_SESSION['transferitems_import']['logs'][Event::UPDATE])),
                    'errors' => Bitrix\Main\Web\Json::encode($_SESSION['transferitems_import']['errors'])
                ]);
            }
            $asd = $_SESSION['transferitems_import']['logs'];
            unset($_SESSION['transferitems_import']);
            $complete = true;
        }

        $APPLICATION->RestartBuffer();
        CAdminMessage::ShowMessage([
            "MESSAGE" => Loc::getMessage('ADMIN_IMPORT_TITLE'),
            "DETAILS" => "#PROGRESS_BAR#",
            "HTML" => true,
            "TYPE" => "PROGRESS",
            "PROGRESS_TOTAL" => 100,
            "PROGRESS_VALUE" => round($page / $pages * 100),
        ]);

        if ($complete) {
            CAdminMessage::ShowNote(Loc::getMessage('ADMIN_END_IMPORT'));
            ?>
            <div style="margin-bottom: 30px;">
                <? foreach ($allErrors as $error) { ?>
                    <div><?= $error ?></div>
                <? } ?>
            </div>
        <? }
        die;
    }
}

/**
 * ВЫБОРКА И ПОДГОТОВКА ДАННЫХ ФОРМЫ
 */

$handbook = TransferItemsLib::getHighloadBlockBlockProperty();

$handbooksSelect = [
    "REFERENCE" => $handbook['handbookNames'],
    "REFERENCE_ID" => array_keys($handbook['handbooks'])
];

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php"); ?>

    <div class="progress_bar_transferitems"></div>
    <form id="transferitems_admin_form" method="POST" Action="<? echo $APPLICATION->GetCurPage() ?>"
          ENCTYPE="multipart/form-data" name="post_form">
        <input type="hidden" name="action_type" value="export">
        <?= bitrix_sessid_post(); ?>
        <? $tabControl->Begin(); ?>
        <? $tabControl->BeginNextTab(); ?>
        <tr>
            <td width="40%"><?= Loc::getMessage("ADMIN_HANDBOOK_SELECT_EXPORT") ?></td>
            <td width="60%"><?= SelectBoxFromArray("handbook_export", $handbooksSelect); ?></td>
        </tr>
        <? $tabControl->BeginNextTab(); ?>
        <tr>
            <td width="40%"><?= Loc::getMessage("ADMIN_HANDBOOK_SELECT_IMPORT") ?></td>
            <td width="60%"><?= SelectBoxFromArray("handbook_import", $handbooksSelect); ?></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("ADMIN_FILE_IMPORT") ?></td>
            <td><input type="file" name="transferitems_import_file" value=""/></td>
        </tr>
        <? $tabControl->Buttons(); ?>
        <input id="export_button" class="adm-btn-save" type="submit" name="export"
               value="<?= Loc::GetMessage("ADMIN_EXPORT"); ?>"/>
        <input id="import_button" class="adm-btn-save hidden" type="submit" name="import"
               value="<?= Loc::GetMessage("ADMIN_IMPORT"); ?>"/>
    </form>
<? $tabControl->End(); ?>

<? $tabControl->ShowWarnings("post_form", $message); ?>

    <script>
        $(function () {
            $('#tabControl_tabs .adm-detail-tab').on('click', function () {
                var index = $(this).index();
                $('.adm-btn-save').addClass('hidden');
                $('.adm-btn-save:eq(' + index + ')').removeClass('hidden');
                if (index == 1) {
                    $('#transferitems_admin_form').find('[name=action_type]').val('import');
                } else {
                    $('#transferitems_admin_form').find('[name=action_type]').val('export');
                }
            });

            $('#transferitems_admin_form').on('submit', function (event) {
                event.preventDefault();
                ActionGo();
                return false;
            });
        });

        var files;
        $('[name=transferitems_import_file]').on('change', function () {
            files = this.files;
        });

        function ActionGo() {
            var $form = $('#transferitems_admin_form');
            var actionType = $form.find('[name=action_type]').val();
            var data = {};
            var handbook_import = '';

            $('.progress_bar_transferitems').html('');

            if (actionType == 'export') {
                var data = $form.serialize();
            } else {
                if ($('[name=transferitems_import_file]').val() == '') {
                    alert('Файл для импорта обязателен');
                    return false;
                }
                var data = new FormData();
                data.append('file', files[0]);
                handbook_import = $form.find('[name=handbook_import]').val();
            }
            $('.adm-btn-save').addClass('disabled');
            SendAjax(data, actionType, handbook_import);
        }

        function SendAjax(data, actionType, handbook_import) {
            var params = {};
            params = {
                url: '?ajax=y&action_type=' + actionType + '&handbook_import=' + handbook_import,
                type: 'POST',
                data: data,
                success: function (html) {
                    var $procent = $(html).find('.adm-progress-bar-inner-text').text();
                    $('.progress_bar_transferitems').html(html);
                    if ($procent == '100%') {
                        $('.adm-btn-save').removeClass('disabled');
                    } else {
                        setTimeout(function () {
                            SendAjax(data, actionType, handbook_import);
                        }, 1000);
                    }
                },
                error: function (jqXHR, status, errorThrown) {
                    alert('Ошибка запроса');
                }
            };
            if (actionType == 'import') {
                params.processData = false;
                params.contentType = false;
            }
            $.ajax(params);
        }
    </script>
    <style>
        .adm-detail-title-setting, .hidden {
            display: none !important;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>