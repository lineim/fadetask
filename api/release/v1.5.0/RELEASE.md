#### Change Log
* 看板增加uuid字段
* OSS对象存储上传

#### Migration
执行增加看板uuid的migration
```shell
./bin/phpmig up 20230329192957
./bin/phpmig up 20230331232333
```

#### 新增配置
阿里云OSS配置

#### 执行脚本
```shell
php console kanban:generate_kanban_uuid
php console task:generate_attachment_uuid
```