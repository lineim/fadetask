# FadeTask
## What is it
FadeTask 是基于php和vue2开发的一个开源看板项目。

## Requires
* PHP Version > 7.2
* Webman
* Vue Version = 2
* 关于PHP运行环境，请参考 [workerman](https://github.com/walkor/workerman/blob/master/README.md#requires)

## Install
#### api
```shell
cd api/ && composer install
# copy config file
cp .env.example ./.env
```

#### web
```shell
cd web && yarn
```

## Run 
#### api
```shell
cd api
# migrate
./vendor/bin/phpmig migrate
# start for develop
php start.php start
# Daemon
php start.php start -d

# restart
php start.php restart -d
```

#### web
See [Web README.md](./web/README.md)
