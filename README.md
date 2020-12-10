# Wmall 商城

* 基于 Laravel6.0 开发的单商户商城系统，后台使用的是 Dcat-admin  

## 功能
* 用户管理
* 商品管理
* 众筹商品下单
* 订单管理
* 优惠券管理
* 使用优惠券下单
* .......未完待续  

## Usage
* `git clone https://github.com/Witcier/wmall.git`  
&nbsp;&nbsp;进入项目文件夹:  
* `cp .env.example .env`  
&nbsp;&nbsp;修改.env配置，配置mysql数据库。  
* `composer install`  
* `yarn install`
* 运行 `npm run watch-poll` 编译app.scss文件
* `php artisan migrate:refresh --seed`生成数据库文件    
* `php artisan key:gen`
* 在 linux 服务器下需要获取权限
* `chmod -R 777 storage/`
* `php artisan storage:link`
* 消息队列开启
* `php artisan queue:work`
* 访问'你的域名'即为主页面  
* '你的域名/admin'为后台入口。
* 主管理员账号和密码 admin admin
* ngrok http -host-header=shop.test -region us 80
* git config core.autocrlf false -g
* sudo systemctl restart elasticsearch.service
* sudo systemctl disable elasticsearch.service




