<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\InvoiceImport;
use App\Models\Invoice;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceController extends Controller
{
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:2048',
            ]);
            
            Excel::import(new InvoiceImport, $request->file('file'));
          

            return response()->json([
                'status' => true,
                'message' => 'Invoices imported successfully.'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            Log::error('Invoice import error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to import invoices.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getExcelData()
    {
        try {
            $data = Invoice::all();
    
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Excel data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateInvoice(Request $request, $id)
{
    try {
        $invoice = Invoice::findOrFail($id);

        $invoice->update([
            'project_id'             => $request->project_id ?? $invoice->project_id,
            'client_name'            => $request->client_name ?? $invoice->client_name,
            'tl'                     => $request->tl ?? $invoice->tl,
            'invoice_link'           => $request->invoice_link ?? $invoice->invoice_link,
            'invoice_sent_date'      => $this->convertExcelDate($request->invoice_sent_date ?? $invoice->invoice_sent_date),
            'invoice_cycle_start'    => $this->convertExcelDate($request->invoice_cycle_start ?? $invoice->invoice_cycle_start),
            'invoice_cycle_end'      => $this->convertExcelDate($request->invoice_cycle_end ?? $invoice->invoice_cycle_end),
            'bank_account_name'      => $request->bank_account_name ?? $invoice->bank_account_name,
            'invoice_status'         => $request->invoice_status ?? $invoice->invoice_status,
            'amount_usd'             => $request->amount_usd ?? $invoice->amount_usd,
            'sent_via'               => $request->sent_via ?? $invoice->sent_via,
            'invoice_release_status' => $request->invoice_release_status ?? $invoice->invoice_release_status,
            'followup_date'          => $this->convertExcelDate($request->followup_date ?? $invoice->followup_date),
            'release_amount_date'    => $this->convertExcelDate($request->release_amount_date ?? $invoice->release_amount_date),
            'release_amount_inr'     => $request->release_amount_inr ?? $invoice->release_amount_inr,
        ]);

        return response()->json(['status' => true, 'message' => 'Invoice updated successfully', 'data' => $invoice], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
    } catch (\Exception $e) {
        Log::error('Invoice update failed for ID ' . $id . ' | Error: ' . $e->getMessage());
        return response()->json(['status' => false, 'message' => 'An error occurred while updating invoice'], 500);
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
public function store(Request $request)
{
    try {
        
        $validated = $request->validate([
            'project_id'             => 'required|string|max:50',
            'client_name'            => 'nullable|string|max:100',
            'tl'                     => 'nullable|string|max:100',
            'invoice_link'           => 'nullable|string',
            'invoice_sent_date'      => 'nullable|date',
            'invoice_cycle_start'    => 'nullable|date',
            'invoice_cycle_end'      => 'nullable|date',
            'bank_account_name'      => 'nullable|string|max:100',
            'invoice_status'         => 'nullable|string|max:100',
            'amount_usd'             => 'nullable|numeric|min:0',
            'sent_via'               => 'nullable|string|max:100',
            'invoice_release_status' => 'nullable|string|max:100',
            'followup_date'          => 'nullable|date',
            'release_amount_date'    => 'nullable|date',
            'release_amount_inr'     => 'nullable|numeric|min:0',
            'currency_status'    => 'nullable',
            'release_currency_status'    => 'nullable',
        ]);

      
        $invoice = Invoice::create([
            'project_id'             => $validated['project_id'],
            'client_name'            => $validated['client_name'] ?? null,
            'tl'                     => $validated['tl'] ?? null,
            'invoice_link'           => $validated['invoice_link'] ?? null,
            'invoice_sent_date'      => $validated['invoice_sent_date'] ?? null,
            'invoice_cycle_start'    => $validated['invoice_cycle_start'] ?? null,
            'invoice_cycle_end'      => $validated['invoice_cycle_end'] ?? null,
            'bank_account_name'      => $validated['bank_account_name'] ?? null,
            'invoice_status'         => $validated['invoice_status'] ?? null,
            'amount_usd'             => $validated['amount_usd'] ?? 0,
            'sent_via'               => $validated['sent_via'] ?? null,
            'invoice_release_status' => $validated['invoice_release_status'] ?? null,
            'followup_date'          => $validated['followup_date'] ?? null,
            'release_amount_date'    => $validated['release_amount_date'] ?? null,
            'release_amount_inr'     => $validated['release_amount_inr'] ?? 0,
            'currency_status'        => $validated['currency_status'] ?? 0,
            'release_currency_status'=> $validated['release_currency_status'] ?? 0,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Invoice stored successfully',
            'data'    => $invoice
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('Invoice store failed | Error: ' . $e->getMessage());
        return response()->json([
            'status'  => false,
            'message' => 'An unexpected error occurred while storing the invoice.'
        ], 500);
    }
}

public function editInvoice($id){
    try{
        $invoice = Invoice::findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $invoice
        ],200);
    }catch(ModelNotFoundException $e){
        return response()->json([
            'status' => false,
            'message' => 'Invoice not found'
        ],404);
    }
}
public function destroy($id)
{
    try {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete(); 

        return response()->json([
            'status'  => true,
            'message' => 'Invoice deleted successfully'
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Invoice not found'
        ], 404);
    } catch (\Exception $e) {
        Log::error('Invoice delete failed | Error: ' . $e->getMessage());
        return response()->json([
            'status'  => false,
            'message' => 'An error occurred while deleting the invoice'
        ], 500);
    }
}

}
