<?php
namespace app\module\Workspace;

class TaskTypes
{
    public static $types = [
        [
            'name' => '任务',
            'code' => 'task',
            'use_case' => '通用类型',
            'desc' => '',
            'icon' => 'radio_button_checked',
            'color' => 'deep-purple-6'
        ],
        [
            'name' => '需求',
            'code' => 'demand',
            'use_case' => '软件开发, 产品管理',
            'desc' => '软件开发行业的产品需求，或者其他行业的需求',
            'icon' => 'assignment',
            'icon' => 'work_outline',
            'color' => 'blue-6'
        ],
        [
            'name' => '里程碑',
            'code' => 'milestone',
            'use_case' => '项目管理, 产品管理',
            'desc' => '项目或者产品的重要节点',
            'icon' => 'adjust',
            'color' => 'indigo-6'
        ],
        [
            'name' => 'Issue',
            'code' => 'issue',
            'use_case' => '客服，IT',
            'desc' => '问题、议题或者话题',
            'icon' => 'bug_report',
            'color' => 'red-6'
        ], 
        [
            'name' => 'Bug',
            'code' => 'Bug',
            'use_case' => '软件开发，IT',
            'desc' => '软件缺陷',
            'icon' => 'bug_report',
            'color' => 'red-8'
        ], 
        [
            'name' => '目标',
            'code' => 'target',
            'use_case' => '项目管理，市场，运营',
            'desc' => '',
            'icon' => 'flag_circle',
            'color' => 'teal-6'
        ], 
        [
            'name' => '关键指标',
            'code' => 'objective',
            'use_case' => '项目管理，市场，运营，战略管理',
            'desc' => '任务的关键指标',
            'icon' => 'star',
            'color' => 'cyan-6'
        ], 
        [
            'name' => '关键结果',
            'code' => 'key_result',
            'use_case' => '项目管理，市场，运营，战略管理',
            'desc' => '任务的关键结果',
            'icon' => 'key',
            'color' => 'green-6'
        ], 
        [
            'name' => 'Leads',
            'code' => 'leads',
            'use_case' => '销售，市场',
            'desc' => '线索、商机、潜在客户',
            'icon' => 'insights',
            'color' => 'orange-6'
        ],
        [
            'name' => '账号',
            'code' => 'account',
            'use_case' => '销售，财务，IT',
            'desc' => '客户账号信息',
            'icon' => 'account_balance',
            'color' => 'pink-6'
        ],
        [
            'name' => '资源',
            'code' => 'source',
            'use_case' => 'HR，IT，财务',
            'desc' => '资产、资源、素材',
            'icon' => 'source',
            'color' => 'purple-6'
        ],
        [
            'name' => '物料',
            'code' => 'asset',
            'use_case' => '设计，市场，运营',
            'desc' => '物料、素材、资源',
            'icon' => 'folder',
            'color' => 'light-blue-6',
        ]
    ];
}