import SettingLayout from '@/views/setting/Layout.vue';

const settingRoute = [{
    path: '/kanban/setting',
    name: 'Setting',
    component: SettingLayout,
    children: [
      {
        path: '/kanban/setting/user',
        name: 'SettingUser',
        component: resolve => require(['@/views/setting/User.vue'], resolve),
        // component: () => import(/* webpackChunkName: "KanbanBoard" */ '@/views/setting/User.vue'),
        meta: {
          title: "用户设置"
        }
      },
      {
        path: '/kanban/setting/login',
        name: 'SettingLogin',
        component: resolve => require(['@/views/setting/Login.vue'], resolve),
        // component: () => import(/* webpackChunkName: "KanbanBoard" */ '@/views/setting/Login.vue'),
        meta: {
          title: "登录设置"
        }
      },
      {
        path: '/kanban/login/logs',
        name: 'LoginLog',
        component: resolve => require(['@/views/setting/LoginLog.vue'], resolve),
        // component: () => import(/* webpackChunkName: "KanbanBoard" */ '@/views/setting/LoginLog.vue'),
        meta: {
          title: "登录日志"
        }
      },
      {
        path: '/kanban/setting/sys',
        name: 'SettingSys',
        component: resolve => require(['@/views/setting/Index.vue'], resolve),
        // component: () => import(/* webpackChunkName: "KanbanDetail" */ '@/views/setting/Index.vue'),
        meta: {
          title: "系统设置"
        }
      },
      {
          path: '/kanban/setting/mail',
          name: 'SettingMail',
          component: resolve => require(['@/views/setting/Mail.vue'], resolve),
          // component: () => import(/* webpackChunkName: "KanbanDetail" */ '@/views/setting/Mail.vue'),
          meta: {
            title: "邮箱设置"
          }
      },
    ],
  }]
  
  export default settingRoute;