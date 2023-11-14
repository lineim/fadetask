const homeRoute = [{
    path: '',
    // name: 'Home',
    component: () => import(/* webpackChunkName: "KanbanBoard" */ '@/components/home/Layout.vue'),
    meta: {
      title: "个人中心"
    },
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import(/* webpackChunkName: "KanbanBoard" */ '@/components/home/Index.vue'),
        meta: {
          title: "个人中心"
        },
      },
      {
        path: '/project/list',
        name: 'ProjectList',
        component: resolve => require(['@/views/project/List.vue'], resolve),
        meta: {
          title: "项目"
        },
        // component: () => import(/* webpackChunkName: "questionnaire" */ '@/views/project/List.vue')
      },
      {
        path: '/dashboard/kanban/all',
        name: 'KanbanAll',
        // component: resolve => require(['@/views/setting/Login.vue'], resolve),
        component: () => import(/* webpackChunkName: "KanbanAll" */ '@/components/home/AllKanban.vue'),
        meta: {
          title: "所有看板"
        }
      },
      {
        path: '/dashboard/kanban/favorited',
        name: 'KanbanFavorited',
        // component: resolve => require(['@/views/setting/Index.vue'], resolve),
        component: () => import(/* webpackChunkName: "KanbanClosed" */ '@/components/home/Favorite.vue'),
        meta: {
          title: "收藏的看板"
        }
      },
      {
        path: '/project/list/closed',
        name: 'ClosedProject',
        meta: {
          title: "已关闭的项目"
        },
        component: resolve => require(['@/components/home/ClosedProject.vue'], resolve)
      },
      {
        path: '/dashboard/kanban/closed',
        name: 'KanbanClosed',
        // component: resolve => require(['@/views/setting/Index.vue'], resolve),
        component: () => import(/* webpackChunkName: "KanbanClosed" */ '@/components/home/ClosedKanban.vue'),
        meta: {
          title: "已关闭看板"
        }
      }
    ],
  }]
  
  export default homeRoute;