<?php

namespace Src\Services;

use Src\ChocoClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class FetchCustomersDataService implements FetchServiceInterface
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

            // Fetch customers data from terminals
            $customers = $this->chocoClient->getCustomersData(array_keys($terminalIdsNames));

            $date = date('Ymd_H:i');
            $storageDir = __DIR__  . "/../../storage/customers";
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }
            $filePath = "$storageDir/$date.xlsx";

            // Create the spreadsheet and fill in data
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers for the columns
            $sheet->setCellValue('A1', 'Customer ID');
            $sheet->setCellValue('B1', 'Имя');
            $sheet->setCellValue('C1', 'телефон');
            $sheet->setCellValue('D1', 'Общая сумма оплат');
            $sheet->setCellValue('E1', 'Всего оплат');
            $sheet->setCellValue('F1', 'Средний чек');
            $sheet->setCellValue('G1', 'Средняя выручка с клиента за последние 3 месяца');
            $sheet->setCellValue('H1', 'Роздано бонусов');
            $sheet->setCellValue('I1', 'Оплачено бонусами');
            $sheet->setCellValue('J1', 'Первая оплата');

            $sheet->setCellValue('K1', 'Последняя оплата');
            $sheet->setCellValue('L1', 'Сумма последней оплаты');
            $sheet->setCellValue('M1', 'Предпоследняя оплата');
            $sheet->setCellValue('N1', 'Сумма предпоследней оплаты');


            // Start populating rows with customer data
            $row = 2;
            foreach ($customers as $customer) {
                $details = $customer['details']['attributes'];
                $paymentHistory = $customer['payment_history'];

                // Extract last and second latest payments (if available)
                $latestPayment = $this->extractPayment($paymentHistory, 0);
                $secondLatestPayment = $this->extractPayment($paymentHistory, 1);

                // Populate customer data
                $sheet->setCellValue("A{$row}", $details['user_id']);
                $sheet->setCellValue("B{$row}", $details['full_name']);
                $sheet->setCellValue("C{$row}", $details['phone']);
                $sheet->setCellValue("D{$row}", $details['statistics']['turnover'] ?? '');
                $sheet->setCellValue("E{$row}", $details['statistics']['orders_count'] ?? '');
                $sheet->setCellValue("F{$row}", $details['statistics']['average_bill'] ?? '');
                $sheet->setCellValue("G{$row}", $details['statistics']['average_revenue'] ?? '');

                $sheet->setCellValue("H{$row}", $details['statistics']['total_given_cashback'] ?? '');
                $sheet->setCellValue("I{$row}", $details['statistics']['total_payment_from_balance'] ?? '');
                $sheet->setCellValue("J{$row}", $details['first_payment_date'] ?? '');

                // Populate payment details
                $sheet->setCellValue("K{$row}", $latestPayment['created_at'] ?? '');
                $sheet->setCellValue("L{$row}", $latestPayment['amount'] ?? '');
                $sheet->setCellValue("M{$row}", $secondLatestPayment['created_at'] ?? '');
                $sheet->setCellValue("N{$row}", $secondLatestPayment['amount'] ?? '');

                $row++;
            }

            // Save the Excel file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            echo "Customer data has been saved to $filePath \n";
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Extracts a payment from the history at the given index.
     *
     * @param array $paymentHistory The payment history array.
     * @param int $index The index of the payment to extract.
     * @return array|null The extracted payment or null if not available.
     */
    protected function extractPayment(array $paymentHistory, int $index): ?array
    {
        if (!isset($paymentHistory[$index]['attributes'][0]['transaction'])) {
            return null;
        }

        return $paymentHistory[$index]['attributes'][0]['transaction'];
    }
}
