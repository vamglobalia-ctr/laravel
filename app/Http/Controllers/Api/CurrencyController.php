<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function getCurrencies(){
        $rates = [
            'INR' => 88.53
            
        ];
        return response()->json([
            'success' => true,
            'base' => 'USD',
            'rates' => $rates,
        ]);
    }
}
