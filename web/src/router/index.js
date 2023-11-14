import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store';
import * as types from '@/store/mutation-types';
import commonRoutes from './modules/common';
import userRoutes from './modules/user_routes';
import HomeRoute from './modules/home';
import ProjectRoute from './modules/project';
import KanbanRoute from './modules/kanban';

import Layout from '@/Layout.vue';

const HomeRouteMap = [
  {
    path: '/',
    component: Layout,
    children: [... HomeRoute, ... ProjectRoute, ... KanbanRoute, ... userRoutes]
  }
]


Vue.use(VueRouter)

const whiteRoutes = [
  'Login',
  'Reg',
  'ForgetPass',
  'RestPass',
  'PrivacyPolicy'
];


const router = new VueRouter({
  mode: 'hash',
  routes: [...commonRoutes, ...HomeRouteMap]
})

const defaultTitle = 'FadeTask | 任务管理 | 项目管理 | 敏捷看板';

router.beforeEach((to, from, next) => {
  let path = to.path;
  let menu = '/kanban/board';
  if (path !== '/') {
    menu = path;
  }
  if (to.meta.title) { //判断是否有标题
    document.title = to.meta.title + ' | ' + defaultTitle;
  } else if (document.title == "") {
    document.title = defaultTitle;
  }
  store.commit(types.SET_MENU, menu); // 设置菜单

  // 页面刷新，store数据会被清掉，需对token、user重新赋值
  if (localStorage.getItem('token')) {
    store.commit(types.USER_LOGIN, {
      token: localStorage.getItem('token'),
      user: JSON.parse(localStorage.getItem('user'))
    });
  }
  
  if (store.state.token) {
    if (to.name === 'Login' || 
        to.name == 'ForgetPass' || 
        to.name == 'Reg' ||
        to.name == 'RestPass'
    ) {
      next(to.query.redirect || '/kanban/board');
      return;
    }
  }

  // 未登录用户, 如果路由不在白名单中，跳转到首页
  if (whiteRoutes.indexOf(to.name) === -1 && !store.state.token) {
    next('/login');
    return;
  }
  next();
});

router.afterEach((to) => {
  if ("KanbanDetail" == to.name || "KanbanDashboard" == to.name) { // 看板详情页面提交当前看板id，用于导航展示看板其他信息
    store.commit(types.CUR_BOARD_ID, to.params.id);
  } else {
    store.commit(types.CUR_BOARD_ID, "");
  }
})

export default router
