#### Change Log
* 新增： 自定义label
* 新增： 卡片排序

#### Migration
```sql
alter table kanban add column `desc` varchar(128) NOT NULL DEFAULT '' COMMENT '看板描述' after `name`;
```

#### 新增配置
无