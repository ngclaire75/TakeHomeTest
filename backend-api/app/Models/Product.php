<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'price',
        'provider_type',
        'location',
        'vendor',
        'unit_sold',
        'tkdn_value',
        'dynamic_attributes',
        'detail_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tkdn_value' => 'decimal:2',
        'unit_sold' => 'integer',
        'dynamic_attributes' => 'array',
    ];

    protected $hidden = [
        'search_vector',
    ];
}
