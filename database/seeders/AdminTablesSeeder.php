<?php

namespace Database\Seeders;

use Dcat\Admin\Models;
use Illuminate\Database\Seeder;
use DB;

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
        Models\Menu::truncate();
        Models\Menu::insert(
            [
                [
                    "id" => 1,
                    "parent_id" => 0,
                    "order" => 1,
                    "title" => "Index",
                    "icon" => "feather icon-bar-chart-2",
                    "uri" => "/",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-07-28 09:33:28"
                ],
                [
                    "id" => 2,
                    "parent_id" => 0,
                    "order" => 8,
                    "title" => "Admin",
                    "icon" => "feather icon-settings",
                    "uri" => "",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 3,
                    "parent_id" => 2,
                    "order" => 9,
                    "title" => "Users",
                    "icon" => "",
                    "uri" => "auth/users",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 4,
                    "parent_id" => 2,
                    "order" => 10,
                    "title" => "Roles",
                    "icon" => "",
                    "uri" => "auth/roles",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 5,
                    "parent_id" => 2,
                    "order" => 11,
                    "title" => "Permission",
                    "icon" => "",
                    "uri" => "auth/permissions",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 6,
                    "parent_id" => 2,
                    "order" => 12,
                    "title" => "Menu",
                    "icon" => "",
                    "uri" => "auth/menu",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 7,
                    "parent_id" => 2,
                    "order" => 13,
                    "title" => "Extensions",
                    "icon" => "",
                    "uri" => "auth/extensions",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 8,
                    "parent_id" => 0,
                    "order" => 2,
                    "title" => "用户管理",
                    "icon" => NULL,
                    "uri" => "users",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 12:07:58",
                    "updated_at" => "2021-07-28 09:33:28"
                ],
                [
                    "id" => 9,
                    "parent_id" => 0,
                    "order" => 3,
                    "title" => "商品管理",
                    "icon" => NULL,
                    "uri" => NULL,
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 14:02:41",
                    "updated_at" => "2021-07-28 09:33:28"
                ],
                [
                    "id" => 10,
                    "parent_id" => 9,
                    "order" => 4,
                    "title" => "普通商品",
                    "icon" => NULL,
                    "uri" => "products",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-14 14:03:18",
                    "updated_at" => "2021-07-28 09:33:28"
                ],
                [
                    "id" => 11,
                    "parent_id" => 0,
                    "order" => 7,
                    "title" => "Dcat Plus",
                    "icon" => "feather icon-settings",
                    "uri" => "dcat-plus/site",
                    "extension" => "celaraze.dcat-extension-plus",
                    "show" => 1,
                    "created_at" => "2021-07-21 14:33:00",
                    "updated_at" => "2021-08-03 14:15:51"
                ],
                [
                    "id" => 12,
                    "parent_id" => 0,
                    "order" => 5,
                    "title" => "订单管理",
                    "icon" => NULL,
                    "uri" => "orders",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-07-28 09:33:03",
                    "updated_at" => "2021-07-28 09:33:28"
                ],
                [
                    "id" => 13,
                    "parent_id" => 0,
                    "order" => 6,
                    "title" => "优惠卷管理",
                    "icon" => NULL,
                    "uri" => "coupons/codes",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2021-08-03 14:15:36",
                    "updated_at" => "2021-08-03 14:15:51"
                ]
            ]
        );

        Models\Permission::truncate();
        Models\Permission::insert(
            [
                [
                    "id" => 1,
                    "name" => "Auth management",
                    "slug" => "auth-management",
                    "http_method" => "",
                    "http_path" => "",
                    "order" => 1,
                    "parent_id" => 0,
                    "created_at" => "2021-07-14 12:00:07",
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
                    "created_at" => "2021-07-14 12:00:07",
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
                    "created_at" => "2021-07-14 12:00:07",
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
                    "created_at" => "2021-07-14 12:00:07",
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
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => NULL
                ],
                [
                    "id" => 6,
                    "name" => "Extension",
                    "slug" => "extension",
                    "http_method" => "",
                    "http_path" => "/auth/extensions*",
                    "order" => 6,
                    "parent_id" => 1,
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => NULL
                ]
            ]
        );

        Models\Role::truncate();
        Models\Role::insert(
            [
                [
                    "id" => 1,
                    "name" => "Administrator",
                    "slug" => "administrator",
                    "created_at" => "2021-07-14 12:00:07",
                    "updated_at" => "2021-07-14 12:00:10"
                ]
            ]
        );

        Models\Setting::truncate();
		Models\Setting::insert(
			[
                [
                    "slug" => "footer_remove",
                    "value" => "1",
                    "created_at" => "2021-07-21 14:38:29",
                    "updated_at" => "2021-07-21 14:38:29"
                ],
                [
                    "slug" => "grid_row_actions_right",
                    "value" => "0",
                    "created_at" => "2021-07-21 14:38:31",
                    "updated_at" => "2021-07-21 14:38:31"
                ],
                [
                    "slug" => "sidebar_style",
                    "value" => "",
                    "created_at" => "2021-07-21 14:38:30",
                    "updated_at" => "2021-07-21 14:38:30"
                ],
                [
                    "slug" => "site_debug",
                    "value" => "0",
                    "created_at" => "2021-07-21 14:39:07",
                    "updated_at" => "2021-07-21 14:39:07"
                ],
                [
                    "slug" => "site_lang",
                    "value" => "zh_CN",
                    "created_at" => "2021-07-21 14:39:07",
                    "updated_at" => "2021-07-21 14:39:07"
                ],
                [
                    "slug" => "site_logo",
                    "value" => "",
                    "created_at" => "2021-07-21 14:39:06",
                    "updated_at" => "2021-07-21 14:39:06"
                ],
                [
                    "slug" => "site_logo_mini",
                    "value" => "",
                    "created_at" => "2021-07-21 14:39:06",
                    "updated_at" => "2021-07-21 14:39:06"
                ],
                [
                    "slug" => "site_logo_text",
                    "value" => "Shop",
                    "created_at" => "2021-07-21 14:39:06",
                    "updated_at" => "2021-07-21 14:39:06"
                ],
                [
                    "slug" => "site_title",
                    "value" => "Shop",
                    "created_at" => "2021-07-21 14:39:06",
                    "updated_at" => "2021-07-21 14:39:06"
                ],
                [
                    "slug" => "site_url",
                    "value" => "http://127.0.0.1:8000",
                    "created_at" => "2021-07-21 14:39:06",
                    "updated_at" => "2021-07-21 14:39:06"
                ],
                [
                    "slug" => "theme_color",
                    "value" => "default",
                    "created_at" => "2021-07-21 14:38:29",
                    "updated_at" => "2021-07-21 14:38:29"
                ]
            ]
		);

		Models\Extension::truncate();
		Models\Extension::insert(
			[
                [
                    "id" => 1,
                    "name" => "celaraze.dcat-extension-plus",
                    "version" => "1.1.1",
                    "is_enabled" => 1,
                    "options" => NULL,
                    "created_at" => "2021-07-21 14:33:00",
                    "updated_at" => "2021-07-21 14:33:27"
                ],
                [
                    "id" => 2,
                    "name" => "lake.login-captcha",
                    "version" => "1.0.8",
                    "is_enabled" => 1,
                    "options" => NULL,
                    "created_at" => "2021-07-21 14:33:08",
                    "updated_at" => "2021-07-21 14:33:30"
                ]
            ]
		);

		Models\ExtensionHistory::truncate();
		Models\ExtensionHistory::insert(
			[
                [
                    "id" => 1,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.0",
                    "detail" => "原始版本发布",
                    "created_at" => "2021-07-21 14:33:00",
                    "updated_at" => "2021-07-21 14:33:00"
                ],
                [
                    "id" => 2,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.1",
                    "detail" => "增加调试模式开关 & 侧栏子菜单缩进增加",
                    "created_at" => "2021-07-21 14:33:01",
                    "updated_at" => "2021-07-21 14:33:01"
                ],
                [
                    "id" => 3,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.2",
                    "detail" => "扩展表单字段 selectCreate 为 select 字段的升级版，支持快速创建。",
                    "created_at" => "2021-07-21 14:33:01",
                    "updated_at" => "2021-07-21 14:33:01"
                ],
                [
                    "id" => 4,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.3",
                    "detail" => "增加扩展图标和别名。",
                    "created_at" => "2021-07-21 14:33:02",
                    "updated_at" => "2021-07-21 14:33:02"
                ],
                [
                    "id" => 5,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.4",
                    "detail" => "增加表单提交预处理过滤，防止XSS攻击。",
                    "created_at" => "2021-07-21 14:33:03",
                    "updated_at" => "2021-07-21 14:33:03"
                ],
                [
                    "id" => 6,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.5",
                    "detail" => "优化表单提交预处理过滤，不再依赖第三方包。",
                    "created_at" => "2021-07-21 14:33:03",
                    "updated_at" => "2021-07-21 14:33:03"
                ],
                [
                    "id" => 7,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.6",
                    "detail" => "selectCreate组件的颜色改为主题色。",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 8,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.6",
                    "detail" => "UI增加表格行操作按钮紧贴最右侧。",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 9,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.7",
                    "detail" => "支持DcatAdmin 2.0.18beta。",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 10,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.7",
                    "detail" => "暂时移除侧栏菜单子菜单缩进（不兼容）。",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 11,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.7",
                    "detail" => "增加水平菜单选项。",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 12,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.7",
                    "detail" => "原先的头部块状显示改为边距优化",
                    "created_at" => "2021-07-21 14:33:04",
                    "updated_at" => "2021-07-21 14:33:04"
                ],
                [
                    "id" => 13,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.8",
                    "detail" => "提供了自定义颜色的支持入口",
                    "created_at" => "2021-07-21 14:33:05",
                    "updated_at" => "2021-07-21 14:33:05"
                ],
                [
                    "id" => 14,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.9",
                    "detail" => "移除HTML、JS过滤",
                    "created_at" => "2021-07-21 14:33:05",
                    "updated_at" => "2021-07-21 14:33:05"
                ],
                [
                    "id" => 15,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.0.9",
                    "detail" => "移除部分UI优化",
                    "created_at" => "2021-07-21 14:33:05",
                    "updated_at" => "2021-07-21 14:33:05"
                ],
                [
                    "id" => 16,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.1.0",
                    "detail" => "修复debug配置无效的问题",
                    "created_at" => "2021-07-21 14:33:05",
                    "updated_at" => "2021-07-21 14:33:05"
                ],
                [
                    "id" => 17,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.1.0",
                    "detail" => "自动注入扩展字段",
                    "created_at" => "2021-07-21 14:33:05",
                    "updated_at" => "2021-07-21 14:33:05"
                ],
                [
                    "id" => 18,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.1.0",
                    "detail" => "移除了一些无用的配置",
                    "created_at" => "2021-07-21 14:33:06",
                    "updated_at" => "2021-07-21 14:33:06"
                ],
                [
                    "id" => 19,
                    "name" => "celaraze.dcat-extension-plus",
                    "type" => 1,
                    "version" => "1.1.1",
                    "detail" => "增加详情页视频扩展字段",
                    "created_at" => "2021-07-21 14:33:06",
                    "updated_at" => "2021-07-21 14:33:06"
                ],
                [
                    "id" => 20,
                    "name" => "lake.login-captcha",
                    "type" => 1,
                    "version" => "1.0.6",
                    "detail" => "优化验证码页面显示。优化dcat新版验证码不显示问题。",
                    "created_at" => "2021-07-21 14:33:08",
                    "updated_at" => "2021-07-21 14:33:08"
                ],
                [
                    "id" => 21,
                    "name" => "lake.login-captcha",
                    "type" => 1,
                    "version" => "1.0.7",
                    "detail" => "添加数学公式验证码。",
                    "created_at" => "2021-07-21 14:33:09",
                    "updated_at" => "2021-07-21 14:33:09"
                ],
                [
                    "id" => 22,
                    "name" => "lake.login-captcha",
                    "type" => 1,
                    "version" => "1.0.8",
                    "detail" => "修复验证码类型翻译丢失问题。",
                    "created_at" => "2021-07-21 14:33:09",
                    "updated_at" => "2021-07-21 14:33:09"
                ]
            ]
		);

        // pivot tables
        DB::table('admin_permission_menu')->truncate();
		DB::table('admin_permission_menu')->insert(
			[

            ]
		);

        DB::table('admin_role_menu')->truncate();
        DB::table('admin_role_menu')->insert(
            [

            ]
        );

        DB::table('admin_role_permissions')->truncate();
        DB::table('admin_role_permissions')->insert(
            [

            ]
        );

        // finish
    }
}
