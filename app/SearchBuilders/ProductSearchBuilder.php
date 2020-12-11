<?php
namespace App\SearchBuilders;

use App\Models\Category;

class ProductSearchBuilder
{
    // 初始构建查询
    protected $params = [
        'index' => 'products',
        'type' => '_doc',
        'body' => [
            'query' => [
                'bool' => [
                    'filter' => [],
                    'must' => [],
                ],
            ],
        ],
    ];

    public function paginate($size, $page)
    {
        $this->params['body']['from'] = ($page - 1) * $size;
        $this->params['body']['size'] = $size;

        return $this;
    }

    public function onSale()
    {
        $this->params['body']['query']['bool']['filter'][] = ['term' => ['status' => true]];

        return $this;
    }

    // 按分类筛选商品
    public function category(Category $category)
    {
        if ($category->is_directory) {
            $this->params['body']['query']['bool']['filter'][] = [
                'prefix' => ['category_path' => $category->path.$category->id.'-'],
            ];
        } else {
            $this->params['body']['query']['bool']['filter'][] = [
                'term' => ['category_id' => $category->id],
            ];
        }

        return $this;
    }

    // 添加搜索关键字
    public function keywords($keywords)
    {
        // 如果参数不是数组则转换为数组
        $keywords = is_array($keywords) ? $keywords : [$keywords];

        foreach ($keywords as $keyword) {
            $this->params['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                        'title^3',
                        'long_title^2',
                        'category^2',
                        'description',
                        'skus.title',
                        'skus.description',
                        'properties.value',
                    ],
                ],
            ];
        }

        return $this;
    }

    public function aggregateProperties()
    {
        $this->params['body']['aggs'] = [
            'properties' => [
                'nested' => [
                    'path' => 'properties',
                ],
                'aggs' => [
                    'properties' => [
                        'terms' => [
                            'field' => 'properties.name',
                        ],
                        'aggs' => [
                            'value' => [
                                'terms' => [
                                    'field' => 'properties.value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this;
    }

    // 添加一个按商品属性筛选的条件
    public function propertyFilter($name, $value, $type = 'filter')
    {
        $this->params['body']['query']['bool'][$type][] = [
            'nested' => [
                'path' => 'properties',
                'query' => [
                    ['term' => ['properties.search_value' => $name.':'.$value]],
                ],
            ],
        ];

        return $this;
    }

    // 添加排序
    public function orderBy($field, $direction)
    {
        if (!isset($this->params['body']['sort'])) {
            $this->params['body']['sort'] = [];
        }

        $this->params['body']['sort'][] = [$field => $direction];

        return $this;
    }

    public function minShouldMatch($count)
    {
        $this->params['body']['query']['bool']['minimum_should_match'] = (int)$count;

        return $this;
    }

    // 返回构造好的参数
    public function getParams()
    {
        return $this->params;
    }
}
