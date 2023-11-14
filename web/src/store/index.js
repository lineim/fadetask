import Vue from "vue"
import Vuex from "vuex"
import * as actions from "./actions"
import mutations from "./mutation"
import personnelSelector from "./modules/personnel-selector";

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    loading: false,
    state: "",
    setting: {},
    user: {},
    token: "",
    menu: "/my/assessment",
    boardId: 0, // 当前的board id
    board: {}, // 当前的board 
    board_labels: [],
    board_list: [], // 当前board的列
  },
  mutations,
  actions,
  modules: {
    personnelSelector,
  }
})
