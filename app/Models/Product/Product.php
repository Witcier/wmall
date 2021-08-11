<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    const TYPE_NORMAL = 1;
    const TYPE_CROWDFUNDING = 2;
    const TYPE_SKILL = 3;

    public static $typeMap = [
        self::TYPE_NORMAL       => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
        self::TYPE_SKILL        => '秒杀商品',
    ];

    protected $fillable = [
        'title', 'long_title', 'description', 'image', 'on_sale', 'rating', 'sold_count', 'review_count', 'price',
    ];

    protected $casts = [
        'on_sale' => 'boolean',
    ];

    public function skus()
    {
        return $this->hasMany(Sku::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function crowdfunding()
    {
        return $this->hasOne(Crowdfunding::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function getImageUrlAttribute()
    {
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }

        return \Storage::disk('public')->url($this->attributes['image']);
    }

    public function getGroupedPropertiesAttribute()
    {
        return $this->properties
            ->groupBy('name')
            ->map(function ($properties) {
                return $properties->pluck('value')->all();
            });
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($model) {
            $params = [
                'index' => 'products',
                'type'  => '_doc', 
                'id'    => $model->id,
            ];

            app('es')->delete($params);
        });
    }

    public function toESArray()
    {
        $arr = Arr::only($this->toArray(), [
            'id', 'type', 'title', 'long_title', 'on_sale', 'rating', 'sold_count', 'review_count', 'price',
        ]);
        $arr['category_id'] = $this->product_category_id;
        $arr['category'] = $this->category ? explode(' - ', $this->category->full_name) : '';
        $arr['category_path'] = $this->category ? $this->category->path : '';
        $arr['description'] = strip_tags($this->description);
        $arr['skus'] = $this->skus->map(function (Sku $sku) {
            return Arr::only($sku->toArray(), [
                'title', 'description', 'price',
            ]);
        });
        $arr['properties'] = $this->properties->map(function (Property $property) {
            return array_merge(Arr::only($property->toArray(), ['name', 'value']), [
                'search_value' => $property->name . ':' . $property->value,
            ]);
        });

        return $arr;
    }
}
