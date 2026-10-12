<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department',
        'category_type',
        'unit',
        'columns',
        'rows',
        'how_to_measure',
    ];

    protected $casts = [
        'columns' => 'array',
        'rows' => 'array',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
