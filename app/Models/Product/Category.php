<?php

namespace App\Models\Product;

use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, ModelTree;

    protected $table = 'product_categories';

    protected $orderColumn = 'id';

    protected $titleColumn = 'name';

    protected $fillable = [
        'name', 'is_directory', 'level', 'path',
    ];

    protected $casts = [
        'is_directory' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (!$category->parent_id) {
                $category->level = 1;
                $category->path = '-';
                $category->is_directory = true;
            } else {
                $category->level = $category->parent->level + 1;
                $category->path = $category->parent->path . $category->parent_id . '-';
                $category->is_directory = false;
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    public function getPathIdsAttribute()
    {
        return array_filter(explode('-', trim($this->path, '-')));
    }

    public function getAncestorsAttribute()
    {
        return Category::query()
            ->whereIn('id', $this->path_ids)
            ->orderBy('level')
            ->get();
    }

    public function getFullNameAttribute()
    {
        return $this->ancestors
            ->pluck('name')
            ->push($this->name)
            ->implode(' - ');
    }

    public function hasChildren()
    {
        return self::where('parent_id', $this->id)->exists();
    }
}
