<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\InvoiceImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
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
}
