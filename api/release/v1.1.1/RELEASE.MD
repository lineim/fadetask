#### Change Log
* 卡片截止日期精确到分
* 增加卡片截止日期通知

#### Migration
```sql
ALTER TABLE kanban_task ADD COLUMN due_notify_interval INT NOT NULL DEFAULT 0 COMMENT '到期之前多少秒触发通知' AFTER end_time;
ALTER TABLE kanban_task ADD COLUMN due_notify_time INT NOT NULL DEFAULT 0 COMMENT '到期通知触发具体时间' AFTER due_notify_interval;
ALTER TABLE kanban_task ADD COLUMN due_notified TINYINT NOT NULL DEFAULT 0 COMMENT '到期通知是否已触发' AFTER due_notify_time;
ALTER TABLE kanban_task ADD INDEX idx_due_notify_time (`due_notify_time`);
```

#### 新增配置
发件人姓名
SMTP_USERNAME=LineIm 
