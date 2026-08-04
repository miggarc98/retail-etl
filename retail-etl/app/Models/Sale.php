<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'order_id',
        'date',
        'customer_id',
        'customer_name',
        'product_id',
        'product_name',
        'category',
        'quantity',
        'unit_price',
        'discount',
        'country',
        'total'
    ];

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}