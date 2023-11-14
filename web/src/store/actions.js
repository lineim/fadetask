import * as types from "@/store/mutation-types";
import Api from "@/api";
// import { type } from 'os';

export const setting = ({ commit }) => {
  Api.setting({}).then(res => {
      commit(types.SETTING, res);
  });
};

export const userLogin = ({ commit }, { data }) => {
  return Api.auth({data: data}).then(res => {
    commit(types.USER_LOGIN, res);
    return res;
  });
};

export const userLoginBySms = ({commit}, {data}) => {
  return Api.loginBySmsCode({data: data}).then(res => {
    commit(types.USER_LOGIN, res);
    return res;
  });
};

export const changeCurrentUser = ({commit}, {data}) => {
  return new Promise((resolve, reject) => {
    Api.meUpdate({data: data}).then(user => {
      commit(types.CUR_USER, user);
      resolve(data);
    }).catch(e => {
      reject(e);
    });
  });
  
};

export const loadProject = ({commit} , {data}) => {
  return new Promise((resolve, reject) => {
    Api.getProject(data).then(project => {
      commit(types.CUR_PROJECT, project);
      resolve(project);
    }).catch( e => {
      reject(e);
    });
  });
}
