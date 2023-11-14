# 敏捷看板API
## 基础框架
[Webman](https://github.com/walkor/webman), High performance HTTP Service Framework for PHP based on [Workerman](https://github.com/walkor/workerman).

## 依赖
* php version >= 7.2
* ext-json
* ext-ctype
* ext-gmp
* ext-bcmath
* ext-posix
* ext-pcntl
* ext-event

## 系统部署
```shell
composer install 
ln -s vendor/bin ./bin
```

## 配置
```shell
copy .env.example .env
```
修改相关配置(redis, mysql)为本地配置
邮箱配置在不需要发送邮件时可以不配置
APP_DEBUG: debug 模式，开发环境和测试环境开启，prod一定要关闭
KANBAN_URL: 配置看板的前端访问地址，API开发时可不配置
KANBAN_INVITE_URL: 邀请加入看板回调地址, API开发时可不配置
KANBAN_FORGET_PASS_URL: 忘记密码回调地址, API开发时可不配置

## migration
入口文件在 ./bin/phpmig 下，参考 https://github.com/davedevelopment/phpmig

## 单元测试
```shell
./bin/phpunit -c app 
```

## 启动命令
```shell
php start.php start -d # 线上环境
php start.php start # 开发环境，支持热加载
php start.php stop  # 停止服务
php start.php restart # 重启服务
php start.php reload # reload 服务，线上环境避免使用 restart
```

## 命令行使用方式
运行命令
```shell
php console
```

## 上线前配置
* KANBAN_INVITE_URL 邀请加入看板回调域名

