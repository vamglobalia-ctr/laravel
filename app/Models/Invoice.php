<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';


    protected $fillable = [
        'project_id',
        'client_name',
        'tl',
        'invoice_link',
        'invoice_sent_date',
        'invoice_cycle_start',
        'invoice_cycle_end',
        'bank_account_name',
        'invoice_status',   
        'amount_usd',
        'sent_via',
        'invoice_release_status',
        'followup_date',
        'release_amount_date',
        'release_amount_inr',
    ];
}
