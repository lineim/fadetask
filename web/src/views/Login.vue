<template>
  <div class="login-page">
    <div class="login-content">
      <div class="login-logo-wrap text-center">
        <img
          src="/logo.png"
          height="100"
        >
      </div>
      <a-form
        id="components-form-demo-normal-login"
        :form="form"
        class="login-form"
        @submit="handleSubmit"
      >
        <a-form-item
          v-if="loginBySms"
        >
          <a-input
            placeholder="请输入手机号"
            v-decorator="[
              'mobile',
              {
                rules: [
                  {
                    validator: mobileAvailable,
                  }
                ],
              },
            ]"
          >
            <a-icon
              slot="prefix"
              type="mobile"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>
        <a-form-item
          v-if="loginBySms"
        >
          <a-input
            placeholder="验证码"
            ref="code"
            v-decorator="[
              'code',
              {
                rules: [
                  {
                    required: true,
                    message: '请输入验证码!',
                  },
                  {
                    len: 6,
                    message: '验证码长度为6位!',
                  },
                ],
              },
            ]"
          >
            <a-icon
              slot="prefix"
              type="code"
              style="color: rgba(0,0,0,.25)"
            />
            <a
              href="javascript:;"
              @click="sendVerifyCode"
              :style="(isVerifyCodeDisable || hasSended) ? {color: 'rgb(217, 217, 217)', cursor: 'default'} : {}"
              slot="addonAfter"
            >{{ verifyCodeTxt }}</a>
          </a-input>
        </a-form-item>
        <a-form-item
          v-if="!loginBySms"
        >
          <a-input
            v-decorator="[
              'email',
              { rules: [{ required: true, message: '请输入邮箱！' }] },
            ]"
            placeholder="请输入邮箱"
          >
            <a-icon
              slot="prefix"
              type="mail"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>
        <a-form-item
          v-if="!loginBySms"
        >
          <a-input
            v-decorator="[
              'password',
              { rules: [{ required: true, message: '请输入密码！' }] },
            ]"
            type="password"
            placeholder="请输入密码"
          >
            <a-icon
              slot="prefix"
              type="lock"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>
        <a-form-item style="margin-bottom: 0px;">
          <router-link
            v-if="!loginBySms"
            :to="{name: 'ForgetPass'}"
            class="login-form-forgot"
          >
            忘记密码
          </router-link>

          <a-button
            type="primary"
            html-type="submit"
            :disabled="loading"
            class="login-form-button"
          >
            {{ loading ? '登录中' : '登录' }}
          </a-button>
          <router-link
            class="pull-right"
            v-if="!loginBySms"
            :to="{ name: 'Reg'}"
          >
            注册新用户<a-icon type="right" />
          </router-link>

          <a
            href="javascript:;"
            v-if="!loginBySms"
            @click="changeToSmsLogin()"
          >
            短信验证码登录
          </a>

          <a
            href="javascript:;"
            v-if="loginBySms"
            @click="changeToEmailLogin()"
          >
            邮箱登录
          </a>
          <a-alert
            v-if="loginBySms"
            message="手机号不存在，注册新账号！"
            type="info"
            banner
            show-icon
          />
          <div
            v-if="loginBySms"
            class="pvm tac text-secondary"
          >
            未注册手机将自动注册，注册即代表同意
            <router-link
              tag="a"
              target="_blank"
              :to="{name:'PrivacyPolicy'}"
            >
              《隐私政策》
            </router-link>
          </div>
          <a-divider v-if="settings.length > 0" />
          <div
            class="other-login"
            v-if="settings.length > 0"
          >
            <span
              v-for="(setting, type) in settings"
              :key="type"
            >
              <a
                v-if="setting.enabled"
                href="javascript:;"
                @click="otherLogin(type)"
              ><a-icon :type="getOtherLoginIcon(type)" /></a>
            </span>
          </div>
        </a-form-item>
      </a-form>
    </div>
    <div class="ipc text-center login-icp">
      <div class="mbs">
        ©2021-2023 www.fadetask.com All Rights Reserved.
      </div>
      <div>
        <a
          href="https://beian.miit.gov.cn/"
          class="text-main"
          target="_blank"
        >蜀ICP备2023009277号</a>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import api from '@/api';
// import * as dd from 'dingtalk-jsapi'; 

export default {
  data() {
    return {
      settings: [],
      loginBySms: true,
      verifyCodeTxt: '获取',
      timer: null,
      nextVerifyCodeSeconds: 60,
      isVerifyCodeDisable: true,
      hasSended: false,
      loading: false
    };
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'login' });
    api.setting({params: {type: 'all'}}).then((setting) => {
      this.settings = setting;
    });
  },

  created() {
    this.watchRoute();
  },

  watch: {
    // 如果路由有变化，会再次执行该方法
    '$route': 'watchRoute'
  },

  methods: {
    ...mapActions([
      'userLogin', 
      'userLoginBySms'
    ]),
    
    dingtalkLogin() {
      const setting = this.settings['dingtalk'];
      const appId = setting.loginAppId;
      const redirectUri = window.location.origin + window.location.pathname;
      const url = 'https://oapi.dingtalk.com/connect/qrconnect?appid='+appId+'&response_type=code&scope=snsapi_login&state=dingtalk&redirect_uri='+redirectUri;
      window.location.replace(url);
      return;
    },

    weworkLogin() {
      const setting = this.settings['wework'];
      const corpId = setting.corpId;
      const agentId = setting.agentId;
      const redirectUri = window.location.origin + window.location.pathname;

      const url = 'https://open.work.weixin.qq.com/wwopen/sso/qrConnect?appid='+corpId+'&agentid='+agentId+'&state=wework&redirect_uri='+encodeURIComponent(redirectUri);
      window.location.replace(url);
    },

    otherLogin(type) {
      switch(type) {
        case 'dingtalk':
          this.dingtalkLogin();
          break;
        case 'wework':
          this.weworkLogin();
          break;
        default:
          this.$message.error('错误的登录类型' + type);
      }
    },

    getOtherLoginIcon(type) {
      switch(type) {
        case 'dingtalk':
          return 'dingding';
        case 'wework':
          return 'wechat';
        default:
          return '';
      }
    },

    watchRoute() {
      let code = this.$route.query.code;
      let state = this.$route.query.state;
      let from = this.$route.query.from;
      if (from == "reg") {
        this.loginBySms = false;
      }
      if (code && state) {
        let post = {
          'third_part': 1,
          'type': state,
          'code': code
        };
        this.userLogin({data: post}).then(resp => {
          if (resp.token) {
            this.$message.success('登录成功!');
            this.gotoNextPage();
          }
        });
      }
    },

    mobileAvailable(rule, value, callback) {
      if (/^1[3-9]\d{9}$/.test(value)) {
        callback();
        this.isVerifyCodeDisable = false;
      } else {
        callback('手机号格式错误！');
        this.isVerifyCodeDisable = true;
      }
    },

    sendVerifyCode() {
      let mobile = this.form.getFieldValue('mobile');
      this.hasSended = true;
      api.sendLoginSmsCode({data: {'mobile': mobile}}).then(() => {
        this.$message.success('短信已发送，请注意查收！');
        this.nextVerifyCodeSeconds = 59;
        this.timer = setInterval(this.nextSendTick, 1000);
        this.$refs.code.focus();
      }).catch((e) => {
        console.log(e);
        this.hasSended = false;
      });
    },

    nextSendTick() {
      if (this.nextVerifyCodeSeconds <=0) {
        this.hasSended = false;
        this.verifyCodeTxt = '获取';
        clearInterval(this.timer);
        return;
      }
      this.verifyCodeTxt = this.nextVerifyCodeSeconds + 'S';
      this.nextVerifyCodeSeconds --;
    },

    changeToEmailLogin() {
      this.loginBySms = false;
    },

    changeToSmsLogin() {
      this.loginBySms = true;
    },

    handleSubmit(e) {
      e.preventDefault();
      this.form.validateFields((err, values) => {
        if (err) {
          return;
        }
        this.loading = true;

        if (this.loginBySms) {
          this.userLoginBySms({data: values}).then(resp => {
            if (resp.token) {
              this.$message.success('登录成功!');
              this.gotoNextPage();
            }
          }).catch(() => {
            this.loading = false;
          });
          return;
        }

        this.userLogin({data: values}).then(resp => {
          if (resp.token) {
            this.$message.success('登录成功!');
            this.gotoNextPage();
          }
        }).catch(() => {
          this.loading = false;
        });
      });
    },

    gotoNextPage() {
      const targetRoute = JSON.parse(localStorage.getItem('before_login_route'));
      localStorage.removeItem('before_login_route');
      if (targetRoute) {
        this.$router.push({name: targetRoute.name, params: targetRoute.params, query: targetRoute.query});
      } else {
        this.$router.push({name: 'Dashboard'});
      }
    }

  },
};
</script>
<style>
</style>

