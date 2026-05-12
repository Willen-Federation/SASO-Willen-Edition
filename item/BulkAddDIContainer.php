<?php

namespace saso\item;

use saso\entity;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\color as colorRepo;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\repository\item as itemRepo;
use saso\repository\itemVar as itemVarRepo;
use saso\repository\size as sizeRepo;

final class BulkAddDIContainer implements DIContainer
{
    private array $post = [];
    private \DateTime $now;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->post = $post;
        $this->now  = $now;
    }

    public function flow(): View
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            $view               = new BulkAddView();
            $view->flashSuccess = $_SESSION['flash_success'] ?? null;
            $view->flashError   = $_SESSION['flash_error']   ?? null;
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
            return $view;
        }

        if (!empty($this->post['_confirm']) && $this->post['_confirm'] === '1') {
            return $this->executeImport();
        }

        return $this->parseCsv();
    }

    private function parseCsv(): View
    {
        if (
            !isset($_FILES['csv'])
            || $_FILES['csv']['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($_FILES['csv']['tmp_name'])
        ) {
            $code = $_FILES['csv']['error'] ?? 'none';
            return $this->uploadErrorView('CSVファイルのアップロードに失敗しました (code: '.$code.')');
        }

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        if ($handle === false) {
            return $this->uploadErrorView('ファイルの読み込みに失敗しました');
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $validRows   = [];
        $errorRows   = [];
        $lineNum     = 0;
        $headerDone  = false;

        while (($cols = fgetcsv($handle)) !== false) {
            $lineNum++;

            if (!$headerDone) {
                $headerDone = true;
                continue;
            }

            if ($cols === null || array_filter(array_map('trim', $cols)) === []) {
                continue;
            }

            while (count($cols) < 9) {
                $cols[] = '';
            }

            [$name, $categoryId, $price, $colors, $sizes, $pla, $plaNote, $paper, $paperNote] = $cols;

            $errors      = [];
            $nameTrimmed = trim($name);

            if ($nameTrimmed === '') {
                $errors[] = '商品名は必須です';
            } elseif (mb_strlen($nameTrimmed) > 50) {
                $errors[] = '商品名は50文字以内にしてください';
            }

            $colorList = array_values(array_filter(array_map('trim', explode('|', trim($colors)))));
            if (empty($colorList)) {
                $errors[] = '色は必須です（例: 赤|青）';
            } else {
                foreach ($colorList as $cn) {
                    if (mb_strlen($cn) > 50) {
                        $errors[] = '色名は50文字以内: '.$cn;
                    }
                }
            }

            $sizeList = array_values(array_filter(array_map('trim', explode('|', trim($sizes)))));
            if (empty($sizeList)) {
                $errors[] = 'サイズは必須です（例: S|M|L）';
            } else {
                foreach ($sizeList as $sn) {
                    if (mb_strlen($sn) > 50) {
                        $errors[] = 'サイズ名は50文字以内: '.$sn;
                    }
                }
            }

            if (!empty($colorList) && !empty($sizeList) && count($colorList) * count($sizeList) > 100) {
                $errors[] = '色数('.count($colorList).') × サイズ数('.count($sizeList).') が100を超えています';
            }

            $priceInt     = null;
            $priceTrimmed = trim($price);
            if ($priceTrimmed !== '') {
                $priceClean = str_replace(',', '', $priceTrimmed);
                if (!ctype_digit($priceClean) || strlen($priceClean) > 9) {
                    $errors[] = '価格は9桁以内の整数を入力してください';
                } else {
                    $priceInt = (int) $priceClean;
                }
            }

            $categoryIdInt     = null;
            $categoryIdTrimmed = trim($categoryId);
            if ($categoryIdTrimmed !== '') {
                if (!ctype_digit($categoryIdTrimmed)) {
                    $errors[] = '分類IDは数値を入力してください';
                } else {
                    $categoryIdInt = (int) $categoryIdTrimmed;
                }
            }

            $plaFlag           = (trim($pla) === '1');
            $paperFlag         = (trim($paper) === '1');
            $plaNoteTrimmed    = mb_substr(trim($plaNote), 0, 50);
            $paperNoteTrimmed  = mb_substr(trim($paperNote), 0, 50);

            if (!empty($errors)) {
                $errorRows[] = [
                    'row'    => $lineNum,
                    'name'   => $nameTrimmed,
                    'errors' => $errors,
                ];
            } else {
                $validRows[] = [
                    'name'       => $nameTrimmed,
                    'categoryId' => $categoryIdInt,
                    'price'      => $priceInt,
                    'colors'     => $colorList,
                    'sizes'      => $sizeList,
                    'pla'        => $plaFlag,
                    'plaNote'    => $plaFlag ? $plaNoteTrimmed : '',
                    'paper'      => $paperFlag,
                    'paperNote'  => $paperFlag ? $paperNoteTrimmed : '',
                ];
            }
        }

        fclose($handle);

        if (empty($validRows) && empty($errorRows)) {
            return $this->uploadErrorView('CSVにデータ行が見つかりませんでした');
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['bulk_import_rows']  = $validRows;
        $_SESSION['bulk_import_token'] = $token;

        $view             = new BulkAddView();
        $view->step       = 'preview';
        $view->token      = $token;
        $view->validRows  = $validRows;
        $view->errorRows  = $errorRows;
        return $view;
    }

    private function executeImport(): View
    {
        $token        = (string) ($this->post['_token'] ?? '');
        $sessionToken = (string) ($_SESSION['bulk_import_token'] ?? '');

        if ($token === '' || !hash_equals($sessionToken, $token)) {
            return $this->uploadErrorView('不正なリクエストです。もう一度CSVをアップロードしてください。');
        }

        $rows = $_SESSION['bulk_import_rows'] ?? [];
        if (empty($rows)) {
            return $this->uploadErrorView('インポートするデータがありません');
        }

        $now      = $this->now;
        $dateCode = entity\Item::makeDateCode($now);

        $transaction = new DbTransaction();
        $updater     = new DbUpdater();
        $finder      = new DbFinder();

        try {
            $transaction->begin();

            $lastSerial = (int) $finder->current(
                new itemRepo\FindLastSerialByDateCode(),
                ['now' => $dateCode]
            )->getOrElse(0);

            $serial           = $lastSerial;
            $registeredCount  = 0;

            foreach ($rows as $rowData) {
                $serial++;

                $item = new entity\Item(
                    $serial,
                    $rowData['name'],
                    $rowData['pla'],
                    $rowData['plaNote'] !== '' ? $rowData['plaNote'] : null,
                    $rowData['paper'],
                    $rowData['paperNote'] !== '' ? $rowData['paperNote'] : null,
                    $now,
                );

                $updater->exec(new itemRepo\Insert($item));

                $updater->exec(new itemVarRepo\Insert(new entity\ItemVar(
                    $item,
                    $rowData['categoryId'],
                    $rowData['price'],
                    $now,
                )));

                foreach ($rowData['colors'] as $idx => $colorName) {
                    $updater->exec(new colorRepo\Insert(new entity\Color(
                        $item,
                        sprintf('%02d', $idx),
                        $colorName,
                    )));
                }

                foreach ($rowData['sizes'] as $idx => $sizeName) {
                    $updater->exec(new sizeRepo\Insert(new entity\Size(
                        $item,
                        sprintf('%02d', $idx),
                        $sizeName,
                        $idx,
                    )));
                }

                $registeredCount++;
            }

            $transaction->commit();

            unset($_SESSION['bulk_import_rows'], $_SESSION['bulk_import_token']);

            $_SESSION['flash_success'] = $registeredCount.'件の商品を登録しました。';
            \saso\util\Redirect::redirect('item/bulkAdd/');
            exit;

        } catch (\Throwable $e) {
            $transaction->rollBack();

            $view            = new BulkAddView();
            $view->step      = 'preview';
            $view->token     = $token;
            $view->validRows = $rows;
            $view->errorRows = [[
                'row'    => 0,
                'name'   => '',
                'errors' => ['登録中にエラーが発生しました: '.$e->getMessage()],
            ]];
            return $view;
        }
    }

    private function uploadErrorView(string $message): BulkAddView
    {
        $view            = new BulkAddView();
        $view->errorRows = [['row' => 0, 'name' => '', 'errors' => [$message]]];
        return $view;
    }
}
