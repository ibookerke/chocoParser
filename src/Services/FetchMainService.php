<?php

namespace Src\Services;

use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\ChocoClient;

class FetchMainService implements FetchServiceInterface
{

    protected ChocoClient $chocoClient;

    public function __construct(string $token)
    {
        $this->chocoClient = new ChocoClient($token);
    }

    public function handle(): void
    {

        try {
            $terminals = $this->chocoClient->getTerminals();

            $terminalIdsNames = array_column($terminals, 'name', 'id');
            $allFilialData = $this->chocoClient->getAllFilialData(array_keys($terminalIdsNames));

            $date = date('Ymd_H:i');
            $storageDir = __DIR__  . "/../../storage/main";
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }
            $filePath = "$storageDir/$date.xlsx";

            // Create the spreadsheet and fill in data
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers for the columns
            $sheet->setCellValue('A1', 'Филиал');
            $sheet->setCellValue('B1', 'Оборота получено');
            $sheet->setCellValue('C1', 'изменение оборота');
            $sheet->setCellValue('D1', 'Оплат прошло');
            $sheet->setCellValue('E1', 'изменение оплат');
            $sheet->setCellValue('F1', 'Средний чек');
            $sheet->setCellValue('G1', 'изменение среднего чека');
            $sheet->setCellValue('H1', 'гости(всего)');
            $sheet->setCellValue('I1', 'гости(новые)');
            $sheet->setCellValue('J1', 'гости(постоянные)');
            $sheet->setCellValue('K1', 'гости(остальные)');
            $sheet->setCellValue('L1', 'Rating');
            $sheet->setCellValue('M1', 'количество отзывов');

            // Start populating rows
            $row = 2;
            foreach ($allFilialData as $filial) {
                $sheet->setCellValue("A{$row}", $terminalIdsNames[$filial['id']] ?? '');
                $sheet->setCellValue("B{$row}", $filial['indexes']['turnover']['sum'] ?? '');
                $sheet->setCellValue("C{$row}", ($filial['indexes']['turnover']['progress'] ?? '' ) . '%');
                $sheet->setCellValue("D{$row}", $filial['indexes']['transactions']['sum'] ?? '');
                $sheet->setCellValue("E{$row}", ($filial['indexes']['transactions']['progress'] ?? '') . '%');
                $sheet->setCellValue("F{$row}", $filial['indexes']['average_check']['sum'] ?? '');
                $sheet->setCellValue("G{$row}", ($filial['indexes']['average_check']['progress'] ?? '') . '%');
                $sheet->setCellValue("H{$row}", $filial['guests']['all'] ?? '');
                $sheet->setCellValue("I{$row}", $filial['guests']['new'] ?? '');
                $sheet->setCellValue("J{$row}", $filial['guests']['regular'] ?? '');
                $sheet->setCellValue("K{$row}", $filial['guests']['other'] ?? '');
                $sheet->setCellValue("L{$row}", $filial['reviews']['info']['rating'] ?? '');
                $sheet->setCellValue("M{$row}", $filial['reviews']['info']['count'] ?? '');
                $row++;
            }

            // Save the Excel file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            echo "Data has been saved to $filePath \n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

}