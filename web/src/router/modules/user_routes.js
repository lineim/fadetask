// 定义管理员才能看到的路由
import settingRoute from './setting';

let adminRoutes = [];
const user = JSON.parse(localStorage.getItem('user'));
if (user && 'ADMIN' == user.role) {
  adminRoutes = [...settingRoute]; // 管理员才能看到的路由
}

let userRoutes = [...adminRoutes];

export default userRoutes;