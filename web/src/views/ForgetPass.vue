<template>
  <div class="login-page">
    <a-result
      v-if="send_success"
      status="success"
      :title="success_msg"
      :sub-title="success_msg_sub"
    >
      <template #extra>
        <a-button
          key="console"
          type="primary"
          @click="reSend"
        >
          重新发送
        </a-button>
      </template>
    </a-result>

    <div
      v-if="!send_success"
      class="login-content"
    >
      <div class="login-logo-wrap text-center">
        <img
          src="/logo_without_txt.png"
          height="60px"
        >
        <span style="color: #292D34; margin-left: 10px;">找回密码</span>
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
              'email',
              { rules: 
                [
                  { 
                    required: true, 
                    message: '请输入邮箱地址!' 
                  },
                  {
                    type: 'email',
                    message: '邮箱格式错误!',
                  }
                ]
              },
            ]"
            placeholder="Email"
          >
            <a-icon
              slot="prefix"
              type="mail"
              style="color: rgba(0,0,0,.25)"
            />
          </a-input>
        </a-form-item>
        
        <a-form-item>
          <a-button
            type="primary"
            class="login-form-button"
            html-type="submit"
          >
            发送重置密码邮件
          </a-button>
          <router-link :to="{ name: 'Login'}">
            去登录
          </router-link>
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

export default {
  data() {
    return {
      send_success: false,
      success_msg: '',
      success_msg_sub: '如果没有收到邮件，请重新发送，或者联系我们。',
    };
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'restpass' });
  },

  created() {
  },

  mounted() {
  },

  watch: {
  },

  methods: {
    reSend: function() {
      this.send_success = false;
    },

    handleSubmit(e) {
      e.preventDefault();
      this.form.validateFields((err, values) => {
        if (err) {
          return;
        }
        api.resetPassEmail({data: values}).then(resp => {
          if (resp.code == 0) {
            this.send_success = true;
            this.success_msg = resp.msg;
          } else {
            this.$message.error(resp.msg);
          }
        });
      });
    }
  }
};
</script>
<style>

</style>

