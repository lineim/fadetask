import Vue from 'vue';
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);
import zh from './zh';
import en from './en';

const i18n = new VueI18n({
    // 设置默认语言
    locale: 'zh',
    messages: {
        zh,
        en,
    }
})
export default i18n;