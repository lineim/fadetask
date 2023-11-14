import Vue from "vue";
import * as types from "./mutation-types";

export default {
  [types.SETTING](state, payload) {
    state.setting = payload;
  },
  [types.USER_LOGIN](state, payload) {
    state.token = payload.token;
    state.user = payload.user;
    if (payload.token) {
      localStorage.setItem("token", payload.token);
      localStorage.setItem("user", JSON.stringify(payload.user));
    }
    // console.log(payload);
  },

  [types.CUR_USER](state, payload) {
    state.user = payload;
    localStorage.setItem("user", JSON.stringify(payload));
  },

  [types.UPDATE_LOADING_STATUS](state, loading) {
    state.loading = loading;
  },
  [types.SET_MENU](state, menu) {
    state.menu = menu;
  },
  
  [types.USER_LOGOUT](state, payload) {
    state.token = payload.token;
    state.user = payload.user;
    localStorage.removeItem("token", payload.token);
    localStorage.removeItem("user", JSON.stringify(payload.user));
  },

  [types.CUR_BOARD_ID](state, boardId) {
    state.boardId = boardId;
  },

  [types.CUR_BOARD](state, board) {
    state.board = board;
  },

  [types.CUR_PROJECT](state, project) {
    // const keys = Object.keys(state);
    // const defaultProject = keys.includes(types.CUR_PROJECT) ? state.project : {};
    // let p = Object.assign(defaultProject, project); // 解决watch store.state.project 不生效问题
    state.project = Vue.observable(project);
  },

  [types.CUR_BOARD_LABELS](state, labels) {
    state.board_labels = labels;
  },

  [types.CUR_BOARD_LABELS_COLORS](state, colors) {
    state.board_colors = colors;
  },

  [types.CUR_BOARD_LIST](state, list) {
    state.board_list = list;
  }
}