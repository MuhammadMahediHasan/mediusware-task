<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = ['product_variant_one', 'product_variant_two', 'product_variant_three', 'price', 'stock', 'product_id'];

    public function product_variant_one_data(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_one', 'id');
    }

    public function product_variant_two_data(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_two', 'id');
    }

    public function product_variant_three_data(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_three', 'id');
    }
}
