<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'status',
        'parent_id',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Review::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Review::class, 'parent_id')->with('user');
    }

    public function approvedReplies()
    {
        return $this->hasMany(Review::class, 'parent_id')->where('status', 'approved')->with('user');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeParentsOnly($query)
    {
        return $query->whereNull('parent_id');
    }
}
