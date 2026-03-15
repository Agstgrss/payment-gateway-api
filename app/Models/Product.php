<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'amount'
    ];

    public function transactionProducts()
    {
        return $this->hasMany(TransactionProduct::class);
    }
}