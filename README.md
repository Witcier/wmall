# Wmall 商城

* 基于 Laravel6.0 开发的单商户商城系统，后台使用的是 Dcat-admin  

## 功能
* 用户管理
* 商品管理
* 众筹商品下单
* 订单管理
* 优惠券管理
* 使用优惠券下单
* 商品的分期付款
* 使用全局搜索 Elasticesearch 
* 商品的秒杀活动
* .......未完待续  

## Usage
* `git clone https://github.com/Witcier/wmall.git`  
&nbsp;&nbsp;进入项目文件夹，下载 Composer 依赖 
* `composer install`    
&nbsp;&nbsp;下载 Node.js 依赖 
* `SASS_BINARY_SITE=http://npm.taobao.org/mirrors/node-sass yarn` 
&nbsp;&nbsp;创建 .env 文件 
* `cp .env.example .env`   
* `php artisan key:generate` 
&nbsp;&nbsp;修改.env配置，配置mysql数据库 
&nbsp;&nbsp;执行数据库迁移   
* `php artisan migrate` 
&nbsp;&nbsp;执行 Elasticsearch 迁移命令，在执行这个命令之前确保你已经安装了 Elasticsearch 和 IK 分析器 
* `php artisan es:migrate` 
&nbsp;&nbsp;生成假数据 
* `php artisan db:seed` 
* `php artisan db:seed --class=DDRProductsSeeder` 
&nbsp;&nbsp;导入管理后台数据 
* `php artisan db:seed --class=AdminTablesSeeder` 
&nbsp;&nbsp;创建管理后台管理员用户，确保你的 debug 是开启，只有开启才能执行 
* `php artisan admin:create-user` 
&nbsp;&nbsp;构建前端代码 
* `yarn production` 
&nbsp;&nbsp;将商品数据同步到 Elasticsearch 
* `php artisan es:sync-products` 
&nbsp;&nbsp;访问'你的域名'即为主页面   
&nbsp;&nbsp;'你的域名/admin'为后台入口 
&nbsp;&nbsp;Elasticsearch 的开启与关闭 
* sudo systemctl restart elasticsearch.service 
* sudo systemctl disable elasticsearch.service 
