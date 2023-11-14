import { message } from 'ant-design-vue';
import axios from "axios";
import store from "../store";
import router from "@/router";
import i18n from '@/i18n';
import * as types from "../store/mutation-types";

// 状态码
const statusCode = {
  EXPIRED_CREDENTIAL: 4,
  TOKEN_NOT_EXIST: 4040117
};

axios.interceptors.request.use(config => {
  if (config.interceptor === "end") {
    return config;
  }
  
  config.headers.SessionIgnore = 1;
  config.headers.accept = "application/json";

  if (store.state.token) {
    config.headers["X-Auth-Token"] = store.state.token;
  }

  // 自定义配置显示 loading 动画
  if (config.disableLoading) {
    return config;
  }

  store.commit("UPDATE_LOADING_STATUS", true);

  return config;
}, error => Promise.reject(error));

axios.interceptors.response.use(res => {
  if (res.data.hash) {
    return res;
  }
  // 自定义配置显示 loading 动画
  if (!res.config.disableLoading) {
    store.commit("UPDATE_LOADING_STATUS", false);
  }
  
  if (res.headers["content-disposition"] 
      && res.headers["content-disposition"].indexOf("attachment") != -1) {
    return res.data;
  }
  if (res.data.code != 0) {
    res.data.data = false;
    const i18nmsg = i18n.t(res.data.msg);
    message.error(i18nmsg);
    return Promise.reject(i18nmsg);
  }
  return res.data.data;
}, error => {
  store.commit("UPDATE_LOADING_STATUS", false);

  let code = "";
  let beforeLoginRoute = {
    name: router.currentRoute.name,
    query: router.currentRoute.query,
    params: router.currentRoute.params,
  };
  switch (error.response.status) {
    case 401:
      store.commit(types.USER_LOGIN, { // 清空token和用户信息
        token: "",
        user: {}
      });
      localStorage.removeItem("user");
      localStorage.removeItem("token");
      if (router.currentRoute.name == 'Login') {
        return;
      }
      localStorage.setItem('before_login_route', JSON.stringify(beforeLoginRoute));
      router.push({ // 待解决：replace 会导致返回按钮的功能有问题
        name: "Login"
      });
    break;
    case 404:
      code = error.response.data.error.code;
      if (code === statusCode.EXPIRED_CREDENTIAL || code === statusCode.TOKEN_NOT_EXIST) {
        store.commit(types.USER_LOGIN, { // 清空token和用户信息
          token: "",
          user: {}
        });
        localStorage.removeItem("user");
        localStorage.removeItem("token");
        if (router.currentRoute.name == 'Login') {
          return;
        }
        
        localStorage.setItem('before_login_route', JSON.stringify(beforeLoginRoute));
        router.push({ // 待解决：replace 会导致返回按钮的功能有问题
          name: "Login"
        });
      }
      break;
    default:
      break;
  }

  return Promise.reject(error.response.data.error);
});
