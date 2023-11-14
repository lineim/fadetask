const routes = [
  {
    path: '*',
    redirect: '/404',
  },
  {
    path: '/',
    name: 'Home',
    redirect: {name: 'Dashboard'},
    meta: {
      title: ''
    },
  },
  {
    path: '/404',
    name: 'NotFound',
    component: () => import('@/components/error-page/404.vue')
  },  
  {
    path: '/403',
    name: 'NotPermissions',
    component: () => import('@/components/error-page/403.vue')
  },
  {
    path: '/login',
    name: 'Login',
    meta: {
      title: '登录'
    },
    component: () => import('@/views/Login.vue')
  },
  {
    path: '/reg',
    name: 'Reg',
    meta: {
      title: '注册新用户'
    },
    // component: resolve => require(['@/views/Reg.vue'], resolve)
    component: () => import('@/views/Reg.vue')
  },
  {
    path: '/forget/pass',
    name: 'ForgetPass',
    meta: {
      title: '忘记密码'
    },
    // component: resolve => require(['@/views/ForgetPass.vue'], resolve)
    component: () => import('@/views/ForgetPass.vue')
  },
  {
    path: '/rest/pass',
    name: 'RestPass',
    meta: {
      title: '重置密码'
    },
    // component: resolve => require(['@/views/RestPass.vue'], resolve)
    component: () => import('@/views/RestPass.vue')
  },
  {
    path: '/invite/join',
    name: 'InviteJoin',
    meta: {
      title: '加入看板'
    },
    // component: resolve => require(['@/views/InviteJoin.vue'], resolve)
    component: () => import('@/views/InviteJoin.vue')
  },
  {
    path: '/project/invite/join',
    name: 'ProjectInviteJoin',
    meta: {
      title: '加入项目'
    },
    // component: resolve => require(['@/views/InviteJoin.vue'], resolve)
    component: () => import('@/views/project/InviteJoin.vue')
  },
  {
    path: '/privacypolicy',
    name: 'PrivacyPolicy',
    meta: {
      title: '隐私协议'
    },
    component: () => import('@/views/PrivacyPolicy.vue')
  },
]

export default routes;