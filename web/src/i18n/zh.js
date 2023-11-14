const zh = {
    language: {
        zh: '中文',
        en: 'English',
    },
    nav: {
        homepage: '首页',
        project: '项目',
        kanban: '看板'
    },
    today: '今天',
    login: "登录",
    logout: "退出登录",
    submit: "提交",
    save: "保存",
    delete: "删除",
    cancel: "取消",
    open: "打开",
    member: "成员",
    label: "标签",
    quit: "退出",
    action: "操作",
    access_denied: "权限不足！",
    last_view_and_go: "最近访问/前往",
    convert_to_card: "转换为卡片",
    assign: "处理人",
    due_date: '截止日期',
    load_more: "加载更多",
    no_more: "没有更多了",
    expired: '已过期',
    no_data_now: '暂无数据',
    yes: '是',
    no: '否',
    system_error: '系统错误',
    add: '新增',
    edit: '编辑',
    statistics: '统计',
    dashboard: "仪表盘",
    copy_link: '复制链接',
    system: {
        request_too_fast: '请求太快，请稍后重试！',
    },
    menu: {
        personal_setting: "个人设置",
        system_setting: "系统设置",
    },
    home: {
        menu_home: '首页',
        menu_kanban: '看板',
        menu_project: '项目',
        menu_my: '我的',
        menu_delete: '回收站',
        menu_closed_kanban: '已关闭看板',
        menu_closed_project: '已关闭项目',
        kanban_recent_vist: '最近访问的看板',
        project_recent_vist: '最近访问的项目',
        menu_favorited_kanban: '收藏的看板',
        my_todo: '我的待办',
        title_my_kanban: '我的看板',
        title_my_project: '我的项目',
        title_closed_kanban: '已关闭的看板',
        title_closed_project: '已关闭的项目',
    },
    reg: {
        email_label: '邮箱',
        email_require_tips: '请输入邮箱地址！',
        email_err_tips: '邮箱格式错误！',
        code_label: '验证码',
        code_require_tips: '请输入验证码！',
        code_err_tips: '验证码长度为6位！',
        code_sended_tips: '注册验证码已发送到您的邮箱，请注意查收！',
        code_resend_tips: '{second}s后重新发送',
        password_label: '密码',
        password_require_tips: '请输入密码！',
        password_err_tips: '密码长度不低于8位！',
        confirm_password_label: '确认密码',
        confirm_password_require_tips: '再输入一次密码！',
        confirm_password_not_equal_tips: '两次输入密码不一致！',
        name_label: '用户名',
        name_require_tips: '请输入用户名！',
        name_err_tips: '名字长度不超过8个字符！',
        btn_reg: '注册',
        btn_login: '去登录',
        privacy_policy_prefix: '注册即代表同意',
        privacy_policy: '隐私政策',
        get_code_label: '获取验证码',
        success_tips: '注册成功！',

    },
    board_nav: {
        view: {
            kanban: "看板",
            dashboard: "仪表盘"
        },
        kanban: {
            create_from: '创建看板',
            edit: '编辑看板',
            wip: 'WIP 设置',
            to_project: '加入项目',
            to_project_success: '成功加入项目！',
        },
        label: {
            label: '标签管理',
        },
        archive: {
            project: '归档项目',
            cards: '已归档的卡片',
            list: '已归档的列',
            restore: '还原',
        }
    },
    user: {
        invalid: "用户已被封禁",
        name: {
            invalid: "用户名错误！"
        }
    },
    setting: {
        wip: {
            global: '全局设置',
            list: '按列设置'
        },
    },
    auth: {
        sms_send_too_many: '获取验证码次数过多，请稍后再试！',
        sms_send_too_quick: '获取验证码过于频繁，请稍后再试！',
        sms_code_error: '验证码错误！',
        mobile_error: '手机号错误',
    },
    kanban: {
        create: {
            label: '新看板',
            kanban_template_title: '以看板【{name}】为模版创建新看板',
            success_msg: '新建成功！',
            create_limited: '创建看板数量已达上限！',
        },
        name_label: '名称',
        name_placeholder: '请输入看板名称',
        name_requeired_msg: '请输入看板名称',
        name_too_long_msg: '看板名称长度不能超过32个字符',
        desc_label: '描述',
        desc_placeholder: '请输入看板描述',
        desc_too_long_msg: '看板描述不超过128个字符',
        kanban_template_label: '模版看板',
        kanban_template_help: '以当前看板为模版创建看板，将列、看板成员、标签及自定义字段等复制到新看板中，不复制卡片！',
        kanban_template_help_short: '以当前看板为模版创建新看板',
        kanban_project_help: '在项目中创建看板时，会将项目成员自动加入到看板中！',
        close: '关闭',
        closed: '看板已关闭',
        close_tips: '可在首页、已关闭的看板中重新打开。确认关闭？',
        closed_err_msg: '看板已关闭！',
        unclose: '取消关闭',
        unclose_tips: '确认取消关闭？',
        unclose_success: '取消关闭成功！',
        not_found: '看板不存在',
        favorite: '收藏',
        unfavorite: '取消收藏',
        favorite_success_msg: '收藏成功！',
        unfavorite_success_msg: '已取消收藏！',
        unfavirote_tips: '确认取消收藏？',
        member: {
            msg_not_found: "看板成员不存在！",
            action_set_admin: "设为管理员",
            action_set_admin_success_msg: "已将成员设置为管理员，重新登录后生效！",
            action_set_member: "设为普通成员",
            action_set_member_success_msg: "已将成员设置为普通成员，重新登录后生效！",
            action_remove: "从看板移除",
            action_remove_success_msg: "成员已移除！",
        },
        label: {
            label: '标签',
            new_label: '新标签',
            delete_tips: '删除标签后，会将此标签从所有卡片中移除，是否继续？',
            form: {
                name_label: '名称',
                color_label: '颜色'
            }
        },
        menu: {
            filter: "筛选",
            filter_clean_tips: "清除筛选",
            sort: "排序",
            member: "成员",
            more: "更多"
        },
        filter: {
            label: '筛选',
            title: '卡片筛选',
            keyword: '卡片关键字',
            priority: '优先级',
            tags: '标签',
            no_tags: '未设置标签',
            member: '成员',
            no_member: '未指派成员',
            finish: {
                label: '完成',
                all: '全部',
                finished: '已完成',
                unfinished: '未完成',
            },
            due: {
                label: '过期',
                all: '全部',
                over_due: '已过期',
                today_due: '今天过期',
                this_week_due: '本周过期',
                next_week_due: '下周过期',
                no_due: '未设置过期时间'
            },
            clear: '清除筛选',
        }
    },
    project: {
        label: '项目',
        new: "新项目",
        edit: '编辑',
        close: '关闭',
        closed: '项目已关闭',
        opened: '项目已打开',
        not_found: '项目不存在',
        select_tips: '--选择项目--',
        need_select_tips: '请选择项目！',
        name_too_long_tips: '项目名称不得大于{max}个字符！',
        name_requeired_msg: '请填写项目名称！',
        description_too_long_tips: '项目描述不得大于{max}个字符！',
        create: {
            name: '名称',
            description: '描述',
            description_placeholder: '项目描述',
            success_msg: '项目创建成功！',
        },
        stats: {
            total_task: '卡片总数',
            total_overdue_task_today: '今日到期',
            total_overdue_task: '总逾期数',
            total_finished_task: '完成数',
            total_kanban: '看板数',
            total_member: '成员数'
        },
        dashboard: {
            kanban_progress: '看板进度',
        },
        detail: {
            kanban_count: '看板数量',
            member_count: '成员数量',
            creator: '创建者',
            create_time: '创建时间',
            description: '描述',
            dashboard: '项目概览',
            kanban: '项目看板',
            kanban_list: '返回看板列表',
            member: '项目成员',
            tab_kanban: '看板({count})',
            tab_member: '成员({count})',
        },
        list: {
            name: "名称",
            kanban_count: '看板数量',
            member_count: '成员数量',
            creator: '创建者',
            create_time: '创建时间',
            handle: '操作',
            detail: '详情',
            open: '打开'
        },
        member: {
            name: '用户名',
            role: '角色',
            join_time: '加入时间',
            handle: '操作',
            remove: '移除',
            invert: '邀请成员',
            invert_email_placeholder: '请输入邮箱地址，多个邮箱地址用英文逗号隔开。',
            invert_email_tips: '通过链接邀请成员到项目',
            invert_link_copied: '加入链接已复制',
            not_found: '成员不存在',
            remove_tips: '确认移除该成员？',
            removed_tips: '项目成员已被移除',
            role_not_allowed: '不允许设置该角色',
            role_changed: '角色已切换',
            role_label: {
                owner: '创建者',
                admin: '管理员',
                user: '普通成员',
            }
        },
        kanban: {
            name: '看板名称',
            total_task_count: '卡片总数',
            overdue_task_count: '已过期卡片数',
            finished_task_count: '已完成卡片数',
            remove: '移除',
            remove_tips: '确认将看板移除项目？',
            removed_tips: '看板已移除项目',
            view_detail: '详情',
            handle: '操作'
        }
    },
    list: {
        create: {
            label: "新列",
            placeholer: "请输入新列名称"
        },
        edit: {
            label: "编辑",
        },
        archive: {
            label: "归档",
            confirm_txt: "确认归当前列？",
            confirm_yes: "是",
            confirm_no: "否"
        },
        complete: {
            label_complete: "标记为完成列",
            label_uncomplete: "取消完成列",
            tips: '完成列，卡片拖动到此列会自动标记完成。',
        },
        not_found: '列不存在！'
    },
    task: {
        not_found: "卡片不存在！",
        detail: "详情",
        create_info: '创建信息',
        create: {
            create_btn_label: "新卡片",
            wip_limited_label: "WIP超限",
            wip_limited_msg: "列卡片数已经达到wip限制，不能创建新卡片！",
        },
        member: {
            label: '成员',
            placeholder: '添加成员'
        },
        tags: {
            label: '标签'
        },
        change: {
            wip_limited_label: 'WIP超限',
        },
        drag: {
            sort_can_drag_tips: '排序展示卡片，不能在当前列中拖拽卡片！',
            wip_limit_can_drag_tips: '列【{listName}】的WIP限制为{wipLimit}，不能拖拽到该列！'
        },
        priority: {
            emergency: '紧急',
            hight: '高',
            normal: '普通',
            low: '低'
        },
        archive: {
            label: "归档",
            confirm_txt: "确认归档卡片？",
            confirm_yes: "是",
            confirm_no: "否"
        },
        copy: {
            label: '复制卡片',
            title: '复制卡片',
            kanban: '看板',
            list: '列'
        },
        move: {
            title: "移动卡片",
            label: "移动",
            kanban: '看板',
            list: '列'
        },
        kanban_require_tips: '请选择目标看板',
        list_require_tips: '请选择列',
        title_placeholder: "卡片标题",
        unarchive: {
            error: {
                list_not_found: "列不存在！",
                wip_limit: "列任卡片数已经达到wip限制，不能还原！如果要还原，请先从列中移除一个以上卡片！"
            }
        },
        attachment: {
            label: '附件',
            preview: '预览',
            download: '下载',
            del: '删除',
            no_preview: '无法预览该文件'
        },
        done: '已完成',
        due_date: '截止时间',
        due_soon: '即将到期',
        due_overfall: '已到期',
        due_next: '下一步工作',
        not_scheduled: '未规划',
        mark_as_done: '标记完成',
        wip: {
            too_large: "Wip过大，最大值为99！",
            limited: "列表卡片数量超过WIP限制!",
        },
        share: {
            label: '分享',
            link: '分享链接'
        }
    },
    notification: {
        name: '通知',
        empty: '暂无通知',
        join_task: '{who}将你加入到了卡片{card}',
        task_due_notify: '你的卡片{card}将在{date}到期，请尽快处理',
        join_kanban: "{who}邀请你加入看板{kanban}"
    },
    custom_fields: {
        label: '自定义字段',
        new: '新字段',
        name_exist: '字段名称已存在！',
        empty: '没有字段',
        form: {
            name: '名称',
            name_placeholder: '字段名称',
            type: '类型',
            options: '选项',
            new_option: '新选项',
            show_front: '在看板页面展示',
            add_success_msg: '字段新增成功',
        },
        del_field_tips: "删除的字段将会从所有卡片中移除，是否继续？",
        error: {
            name_error: '字段名称错误！',
            max_count: '自定字段数量超过最大限制！',
            field_not_found: '字段不存在！',
        },
        not_support_type: '类型不支持',
        existed: '字段已存在',
        no_permission_tips: "非管理员不能管理自定义字段！"
    },
    checklist: {
        label: '检查项',
        total: '总计',
        title_required_tips: '检查项内容不能为空',
        finished: '已完成',
        new_placeholder: '新检查项',
        del_confirm_msg: '确认删除检查项？',
    },
    stats: {
        board: {
            sub_title: "卡片统计"
        },
        task_count: {
            per_list: "每列卡片数量",
            per_member: "每个成员卡片数量",
            per_label: "每个标签卡片数量",
            no_member: "未设置用户",
            no_label: "无标签",
            due_date: "过期时间卡片数量",
            done: "已完成",
            due_soon: "即将过期",
            due_later: "稍后过期",
            over_due: "已过期",
            no_due_date: "未设置过期时间"
        },
    },
    form: {
        checkbox_name: '单选框',
        date_name: '日期时间',
        dropdown_name: '下拉框',
        number_name: '数字',
        text_name: '文本',
        checkbox_checked: '已选',
        checkbox_uncheck: '未选',
        save: '保存',
    }
}
export default zh;