<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'status',
        'rating',
        'sold_count',
        'review_count',
        'price',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        //如果 image 字段本身就已经是完整的 url 就直接返回
        if (Str::startsWith($this->attributes['image'],['http://','https://'])) {
            return $this->attributes['image'];
        }

        return \Storage::disk('public')->url($this->attributes['image']);
    }

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
}
