<?php

use Illuminate\Database\Seeder;

class AdminTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // base tables
        Dcat\Admin\Models\Menu::truncate();
        Dcat\Admin\Models\Menu::insert(
            [
                [
                    "id" => 1,
                    "parent_id" => 0,
                    "order" => 1,
                    "title" => "Index",
                    "icon" => "feather icon-bar-chart-2",
                    "uri" => "/",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 2,
                    "parent_id" => 0,
                    "order" => 7,
                    "title" => "Admin",
                    "icon" => "feather icon-settings",
                    "uri" => "",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 3,
                    "parent_id" => 2,
                    "order" => 8,
                    "title" => "Users",
                    "icon" => "",
                    "uri" => "auth/users",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 4,
                    "parent_id" => 2,
                    "order" => 9,
                    "title" => "Roles",
                    "icon" => "",
                    "uri" => "auth/roles",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 5,
                    "parent_id" => 2,
                    "order" => 10,
                    "title" => "Permission",
                    "icon" => "",
                    "uri" => "auth/permissions",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 6,
                    "parent_id" => 2,
                    "order" => 11,
                    "title" => "Menu",
                    "icon" => "",
                    "uri" => "auth/menu",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 7,
                    "parent_id" => 2,
                    "order" => 12,
                    "title" => "Operation log",
                    "icon" => "",
                    "uri" => "auth/logs",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 8,
                    "parent_id" => 0,
                    "order" => 2,
                    "title" => "用户管理",
                    "icon" => "fa-address-book",
                    "uri" => "/users",
                    "created_at" => "2020-09-27 16:53:46",
                    "updated_at" => "2020-09-27 16:54:23"
                ],
                [
                    "id" => 9,
                    "parent_id" => 0,
                    "order" => 3,
                    "title" => "商品管理",
                    "icon" => "fa-align-justify",
                    "uri" => "/products",
                    "created_at" => "2020-09-28 10:19:27",
                    "updated_at" => "2020-09-28 10:20:03"
                ],
                [
                    "id" => 10,
                    "parent_id" => 0,
                    "order" => 5,
                    "title" => "订单管理",
                    "icon" => "fa-align-justify",
                    "uri" => "orders",
                    "created_at" => "2020-10-10 10:47:04",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 11,
                    "parent_id" => 0,
                    "order" => 6,
                    "title" => "优惠卷管理",
                    "icon" => "fa-align-justify",
                    "uri" => "/coupon_codes",
                    "created_at" => "2020-10-14 14:05:36",
                    "updated_at" => "2020-10-21 13:50:35"
                ],
                [
                    "id" => 12,
                    "parent_id" => 0,
                    "order" => 4,
                    "title" => "商品类目管理",
                    "icon" => "fa-align-justify",
                    "uri" => "/categories",
                    "created_at" => "2020-10-21 13:49:53",
                    "updated_at" => "2020-10-21 13:50:35"
                ]
            ]
        );

        Dcat\Admin\Models\Permission::truncate();
        Dcat\Admin\Models\Permission::insert(
            [
                [
                    "id" => 1,
                    "name" => "Auth management",
                    "slug" => "auth-management",
                    "http_method" => "",
                    "http_path" => "",
                    "order" => 1,
                    "parent_id" => 0,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 2,
                    "name" => "Users",
                    "slug" => "users",
                    "http_method" => "",
                    "http_path" => "/auth/users*",
                    "order" => 2,
                    "parent_id" => 1,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 3,
                    "name" => "Roles",
                    "slug" => "roles",
                    "http_method" => "",
                    "http_path" => "/auth/roles*",
                    "order" => 3,
                    "parent_id" => 1,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 4,
                    "name" => "Permissions",
                    "slug" => "permissions",
                    "http_method" => "",
                    "http_path" => "/auth/permissions*",
                    "order" => 4,
                    "parent_id" => 1,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 5,
                    "name" => "Menu",
                    "slug" => "menu",
                    "http_method" => "",
                    "http_path" => "/auth/menu*",
                    "order" => 5,
                    "parent_id" => 1,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 6,
                    "name" => "Operation log",
                    "slug" => "operation-log",
                    "http_method" => "",
                    "http_path" => "/auth/logs*",
                    "order" => 6,
                    "parent_id" => 1,
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => NULL
                ],
                [
                    "id" => 7,
                    "name" => "用户管理",
                    "slug" => "用户",
                    "http_method" => "",
                    "http_path" => "/users*",
                    "order" => 7,
                    "parent_id" => 0,
                    "created_at" => "2020-09-28 09:49:18",
                    "updated_at" => "2020-09-28 09:49:18"
                ],
                [
                    "id" => 8,
                    "name" => "商品管理",
                    "slug" => "商品管理",
                    "http_method" => "",
                    "http_path" => "/products*",
                    "order" => 8,
                    "parent_id" => 0,
                    "created_at" => "2020-10-16 14:03:15",
                    "updated_at" => "2020-10-16 14:03:15"
                ]
            ]
        );

        Dcat\Admin\Models\Role::truncate();
        Dcat\Admin\Models\Role::insert(
            [
                [
                    "id" => 1,
                    "name" => "Administrator",
                    "slug" => "administrator",
                    "created_at" => "2020-09-27 16:27:04",
                    "updated_at" => "2020-09-27 16:27:04"
                ],
                [
                    "id" => 2,
                    "name" => "运营人员",
                    "slug" => "operator",
                    "created_at" => "2020-09-28 09:49:59",
                    "updated_at" => "2020-09-28 09:49:59"
                ]
            ]
        );

        // pivot tables
        DB::table('admin_role_menu')->truncate();
        DB::table('admin_role_menu')->insert(
            [

            ]
        );

        DB::table('admin_role_permissions')->truncate();
        DB::table('admin_role_permissions')->insert(
            [
                [
                    "role_id" => 1,
                    "permission_id" => 2,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 3,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 4,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 5,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 6,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 7,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 1,
                    "permission_id" => 8,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ],
                [
                    "role_id" => 2,
                    "permission_id" => 7,
                    "created_at" => NULL,
                    "updated_at" => NULL
                ]
            ]
        );

        // finish
    }
}
