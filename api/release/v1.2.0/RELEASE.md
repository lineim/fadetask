#### Change Log
* 可搜索未设置成员的卡片
* 可搜索未设置标签的卡片

#### Migration
```sql
alter table `kanban_task` add column `member_count` int unsigned NOT NULL DEFAULT '0' COMMENT '卡片成员数量' after `priority`; 
alter table `kanban_task` add column `label_count` int unsigned NOT NULL DEFAULT '0' COMMENT '卡片标签数量' after `member_count`;
```

#### 新增配置
无

#### 执行脚本
./bin/console task:re_cal_member_count -v
./bin/console task:re_cal_label_count -v