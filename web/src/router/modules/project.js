const projectRouter = [
  {
    path: '/project/:uuid',
    name: 'ProjectDetail',
    meta: {
      title: "项目详情"
    },
    component: resolve => require(['@/views/project/Layout.vue'], resolve),
    children: [
      {
        path: '/project/:uuid/overview',
        name: 'ProjectOverview',
        meta: {
          title: "项目概览"
        },
        component: resolve => require(['@/components/project/Overview.vue'], resolve),
      },
      {
        path: '/project/:uuid/kanban',
        name: 'ProjectKanban',
        meta: {
          title: "项目看板"
        },
        component: resolve => require(['@/components/project/KanbanList.vue'], resolve),
      },
      {
        path: '/project/:uuid/member',
        name: 'ProjectMember',
        meta: {
          title: "项目成员"
        },
        component: resolve => require(['@/components/project/MemberList.vue'], resolve),
      }
    ]
  }
];

export default projectRouter;