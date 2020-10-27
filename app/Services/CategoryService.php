<?php
namespace App\Services;

use App\Models\Category;

class CategoryService
{
    // 这是一个递归方法
    // $parentId 参数代表要获取子类目的父类目 ID，null 代表获取所有根类目
    // $allCategories 参数代表数据库中所有的类目，如果是 null 代表需要从数据库中查询
    public function getCategoryTree($parentId = null, $allCategories = null)
    {
        if (is_null($allCategories)) {
            // 从数据库中一次性取出所有分类
            $allCategories = Category::all();
        }

        return $allCategories
            // 从所有分类中挑选父级分类 id 为 $parent_id 的分类
            ->where('parent_id', $parentId)
            // 遍历这些分类， 并用返回值构建一个新的集合
            ->map(function (Category $category) use ($allCategories){
                $data = ['id' => $category->id, 'name' => $category->name];
                if (!$category->is_directory) {
                    return $data;
                }

                // 否则调用递归调用本地的方法， 将返回值放入到 children 字段中
                $data['children'] = $this->getCategoryTree($category->id, $allCategories);

                return $data;
            });
    }
}