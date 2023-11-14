import * as types from "../mutation-types";
import Api from "@/api";

const state = {
  orgTreeData: [],
  usersData: [],
};

const mutations = {
  [types.GET_ORG_TREE](state, payload) {    
    state.orgTreeData = [payload];
  },
  [types.GET_USERS_DATA](state, payload) {
    state.usersData = payload;
  },

};

const actions = {
  getTreeData({ commit }) {
    return Api.getOrgTree().then((res) => {
      commit(types.GET_ORG_TREE, res);
      return res;
    });
  },
  getUsersData({ commit }, { keyword = "", limit = 10} = {}) {
    return Api.getUsers({
      params: {
        keyword,
        limit,
      }
    }).then((res) => {
      commit(types.GET_USERS_DATA, res);
      return res;
    });
  }
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};
