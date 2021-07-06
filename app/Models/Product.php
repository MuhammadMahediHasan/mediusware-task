<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function validation(): array
    {
        return [
            'title' => 'required',
            'sku' => 'required',
            'description' => 'sometimes'
        ];
    }

    public function product_variation_price(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\Models\ProductVariantPrice', 'product_id', 'id');
    }
}
