<template>
  <div class="login-page">
    <a-result
      v-if="show_result"
      :status="status"
      :title="result_msg"
      :sub-title="result_msg_sub"
    >
      <template #extra>
        <a-button
          key="console"
          type="primary"
          @click="gotoLogin"
        >
          去登录
        </a-button>
      </template>
    </a-result>

    <div
      v-if="!show_result"
      class="login-content"
    >
      <div class="login-logo-wrap text-center">
        <img
          src="/logo_without_txt.png"
          height="60px"
        >
        <span style="color: #292D34; margin-left: 10px;">重置密码</span>
      </div>
      <a-form
        id="components-form-demo-normal-login"
        :form="form"
        class="login-form"
        @submit="handleSubmit"
      >
        <a-form-item>
          <a-input
            v-decorator="[
              'new_pass',
              { rules: 
                [
                  { 
                    required: true, 
                    message: '请输入新密码!' 
                  },
                  {
                    min: 8,
                    message: '密码长度不低于8位!',
                  },
                ]
              },
            ]"
            type="password"
            placeholder="新密码"
          >
            <a-icon
              slot="prefix"
              type="lock"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>

        <a-form-item>
          <a-input
            v-decorator="[
              'new_pass_replay',
              { rules: 
                [
                  {
                    validator: compareToFirstPassword,
                  },
                ]
              },
            ]"
            type="password"
            placeholder="重复密码"
          >
            <a-icon
              slot="prefix"
              type="lock"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>
        
        <a-form-item>
          <a-button
            type="primary"
            class="login-form-button"
            html-type="submit"
            :disabled="btn_disabled"
          >
            重置
          </a-button>
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
import api from '@/api';

const SUCCESS_MSG = '密码重置成功！';

export default {
  data() {
    return {
      token: '',
      status: 'success',
      show_result: false,
      result_msg: SUCCESS_MSG,
      result_msg_sub: '',
      btn_disabled: true
    };
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'restpass' });
  },

  created() {
    this.init();
  },

  mounted() {
  },

  watch: {
    '$route.query': function() {
      this.init();
    }
  },

  methods: {
      init() {
          let query = Object.assign({}, this.$route.query);
          if (!('token' in query)) {
            this.show_result = true;
            this.status = 'error';
            this.result_msg = "缺少token参数！";
            return;
          }
          this.token = query.token;
      },


    gotoLogin: function() {
      this.$router.push({name: 'Login', query: {from: 'reg'}});
    },

    compareToFirstPassword(rule, value, callback) {
      const form = this.form;
      if (value && value !== form.getFieldValue('new_pass')) {
        this.btn_disabled = true;
        callback('两次输入密码不一致!');
      } else {
        this.btn_disabled = false;
        callback();
      }
    },

    handleSubmit(e) {
      e.preventDefault();
      this.form.validateFieldsAndScroll((err, formData) => {
        if (err) {
          return;
        }
        this.btn_disabled = true;
        formData.token = this.token;
        api.restPass({data: formData}).then(resp => {
          this.show_result = true;
          if (resp.success) {
            this.result_msg = SUCCESS_MSG;
          } else {
            this.result_msg = resp.msg;
          }
        });
      });
    }
  }
};
</script>
<style>

</style>

