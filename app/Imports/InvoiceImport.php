<?php

namespace App\Imports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
class InvoiceImport implements ToCollection, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
 
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                Invoice::create([
                    'project_id'             => $row['project_id'],
                    'client_name'            => $row['client_name'] ?? null,
                    'tl'                     => $row['tl'] ?? null,
                    'invoice_link'           => $row['invoice_link'] ?? null,
                    'invoice_sent_date'      => $this->convertExcelDate($row['invoice_sent_date'] ?? null),
                    'invoice_cycle_start'    => $this->convertExcelDate($row['invoice_cycle_start'] ?? null),
                    'invoice_cycle_end'      => $this->convertExcelDate($row['invoice_cycle_end'] ?? null),
                    'bank_account_name'      => $row['bank_account_name'] ?? null,
                    'invoice_status'         => $row['invoice_status'] ?? null,
                    'amount_usd'             => $row['amount_usd'] ?? 0,
                    'sent_via'               => $row['sent_via'] ?? null,
                    'invoice_release_status' => $row['invoice_release_status'] ?? null,
                    'followup_date'          => $this->convertExcelDate($row['followup_date'] ?? null),
                    'release_amount_date'    => $this->convertExcelDate($row['release_amount_date'] ?? null),
                    'release_amount_inr'     => $row['release_amount_inr'] ?? 0,
                ]);
            } catch (\Exception $e) {
                Log::error('Invoice import failed for row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
            }
        }
    }
    
    private function convertExcelDate($value)
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
    
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Invalid date format: ' . json_encode($value));
            return null;
        }
    }
    
    

    
}
