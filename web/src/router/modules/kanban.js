const kanbanRoute = [
  {
    path: '/kanban/:id',
    name: 'KanbanDetail',
    meta: {
      title: ""
    },
    component: resolve => require(['@/views/kanban/Detail.vue'], resolve)
    // component: () => import(/* webpackChunkName: "KanbanDetail" */ '@/views/kanban/Detail.vue')
  },
  {
    path: '/kanban/:id/dashboard',
    name: 'KanbanDashboard',
    meta: {
      title: ""
    },
    component: resolve => require(['@/views/kanban/Dashboard.vue'], resolve)
  }
];
  
export default kanbanRoute;