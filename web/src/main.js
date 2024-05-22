import Vue from "vue"
import Vuex from "vuex"
import filters from "@/filters";
import App from "./App.vue"
import router from "./router"
import store from "./store"
import Antd from "ant-design-vue"
import "ant-design-vue/dist/antd.css";
import less from 'less'
import bus from "@/utils/bus";
import i18n from './i18n/';
import { firstWord } from "@/utils";
import '@/less/main.less';
// import * as Sentry from "@sentry/vue";
// import { BrowserTracing } from "@sentry/tracing";

Vue.config.productionTip = false
Vue.prototype.firstWord = firstWord

// https://thewebdev.info/2021/05/17/how-to-detect-clicks-outside-an-element-with-vue-js/
//  <div v-click-outside="onClickOutside" /> 点击元素外的事件
Vue.directive("click-outside", {
  bind(el, binding, vnode) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        vnode.context[binding.expression](event);
      }
    };
    document.body.addEventListener("click", el.clickOutsideEvent);
  },
  unbind(el) {
    document.body.removeEventListener("click", el.clickOutsideEvent);
  },
});

// 注册一个全局自定义指令 `v-focus`
Vue.directive('focus', {
  inserted: function (el) {
    el.focus()
  }
})

Vue.use(Vuex);
Vue.use(Antd);
Vue.use(filters);
Vue.use(bus);
Vue.use(less);

if (process.env.NODE_ENV === "production") {
  // Sentry.init({
  //   Vue,
  //   dsn: "https://d3dde49e6eb34282871bad8a2ec3c42c@o4504286262722560.ingest.sentry.io/4504286268096512",
  //   integrations: [
  //     new BrowserTracing({
  //       routingInstrumentation: Sentry.vueRouterInstrumentation(router),
  //       tracePropagationTargets: ["kb.lineim.com", /^\//],
  //     }),
  //   ],
  //   // Set tracesSampleRate to 1.0 to capture 100%
  //   // of transactions for performance monitoring.
  //   // We recommend adjusting this value in production
  //   tracesSampleRate: 1.0,
  //   logErrors: true
  // });
}

new Vue({
  router,
  store,
  i18n,
  render: h => h(App)
}).$mount("#app")
