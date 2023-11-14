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
        :form="form"
        layout="horizontal"
        @submit="handleSubmit"
      >
        <a-form-item
          v-bind="formItemLayout"
          :label="$t('reg.email_label')"
        >
          <a-input
            @change="emailChanged"
            v-decorator="[
              'email',
              {
                rules: [
                  {
                    type: 'email',
                    message: $t('reg.email_err_tips'),
                  },
                  {
                    required: true,
                    message: $t('reg.email_require_tips'),
                  },
                  {
                    validator: emailAvailable,
                  }
                ],
              },
            ]"
          />
        </a-form-item>

        <a-form-item
          v-bind="formItemLayout"
          :label="$t('reg.code_label')"
        >
          <a-input
            v-decorator="[
              'verify-code',
              {
                rules: [
                  {
                    required: true,
                    message: $t('reg.code_require_tips'),
                  },
                  {
                    len: 6,
                    message: $t('reg.code_err_tips'),
                  }
                ],
              },
            ]"
            :style="{'width': '50%', 'margin-right': '10px'}"
          />
          <a-button
            @click="sendRegVerifyCode"
            :style="{width: '45%'}"
            :disabled="isVerifyCodeDisable || hasSended"
          >
            {{ verifyCodeTxt }}
          </a-button>
        </a-form-item>

        <a-form-item
          v-bind="formItemLayout"
          :label="$t('reg.password_label')"
          has-feedback
        >
          <a-input
            v-decorator="[
              'password',
              {
                rules: [
                  {
                    required: true,
                    message: $t('reg.password_require_tips'),
                  },
                  {
                    min: 8,
                    message: $t('reg.password_err_tips'),
                  },
                  {
                    validator: validateToNextPassword,
                  },
                ],
              },
            ]"
            type="password"
          />
        </a-form-item>
        <a-form-item
          v-bind="formItemLayout"
          :label="$t('reg.confirm_password_label')"
          has-feedback
        >
          <a-input
            v-decorator="[
              'confirm',
              {
                rules: [
                  {
                    required: true,
                    message: $t('reg.confirm_password_require_tips'),
                  },
                  {
                    validator: compareToFirstPassword,
                  },
                ],
              },
            ]"
            type="password"
            @blur="handleConfirmBlur"
          />
        </a-form-item>
        <a-form-item v-bind="formItemLayout">
          <span slot="label">
            {{ $t('reg.name_label') }}&nbsp;
            <!-- <a-tooltip title="What do you want others to call you?">
              <a-icon type="question-circle-o" />
            </a-tooltip> -->
          </span>
          <a-input
            v-decorator="[
              'name',
              {
                rules: [
                  { required: true, message: $t('reg.name_require_tips'), whitespace: true },
                  {
                    max: 8,
                    message: $t('reg.name_err_tips'),
                  },
                ],
              },
            ]"
          />
        </a-form-item>
        <a-form-item v-bind="tailFormItemLayout">
          <a-button
            block
            type="primary"
            html-type="submit"
            :disabled="btnDisable"
          >
            {{ $t('reg.btn_reg') }}
          </a-button>
          <a-button
            type="link"
            @click="gotoLogin()"
            block
          >
            {{ $t('reg.btn_login') }}
          </a-button>
        </a-form-item>
        <div
          class="tac text-secondary"
        >
          {{ $t('reg.privacy_policy_prefix') }}
          <router-link
            tag="a"
            target="_blank"
            :to="{name:'PrivacyPolicy'}"
          >
            《{{ $t('reg.privacy_policy') }}》
          </router-link>
        </div>
      </a-form>
    </div>
    <div class="ipc text-center reg-icp">
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
import api from '@/api'
import i18n from '../i18n';

export default {
    data() {
      return {
        formItemLayout: {
          labelCol: { span: 6 },
          wrapperCol: { span: 16 },
        },
        btnDisable: false,
        tailFormItemLayout: {
          // wrapperCol: {
          //   xs: {
          //       span: 24,
          //       offset: 0,
          //   },
          //   sm: {
          //       span: 16,
          //       offset: 8,
          //   },
          // },
        },
        verifyCodeTxt: i18n.t('reg.get_code_label'),
        nextVerifyCodeSeconds: 60,
        isVerifyCodeDisable: true,
        timer: '',
        hasSended: false
      }
    },
    beforeCreate() {
        this.form = this.$form.createForm(this, { name: 'register' });
    },
    
    methods: {
      ...mapActions([
        'userLogin'
      ]),

      emailChanged() {
        this.isVerifyCodeDisable = true;
      },

      handleSubmit(e) {
        e.preventDefault();
        this.btnDisable = true;
        this.form.validateFieldsAndScroll((err, values) => {
          if (err) {
            this.btnDisable = false;
            return;
          }
          api.reg({data: values}).then(resp => {
            if (resp) {
              this.$message.success(i18n.t('reg.success_tips'));
              this.gotoLogin();
            }
            this.btnDisable = false;
          }).catch(() => {
            this.btnDisable = false;
          })
        });
      },
      handleConfirmBlur(e) {
        const value = e.target.value;
        this.confirmDirty = this.confirmDirty || !!value;
      },

      emailAvailable(rule, value, callback) {
        api.emailAvailable({data: {'email': value}}).then(res => {
          if (res.code == 0) {
            callback();
            this.isVerifyCodeDisable = false;
          } else {
            callback(i18n.t(res.msg));
          }
        });
      },

      compareToFirstPassword(rule, value, callback) {
        const form = this.form;
        if (value && value !== form.getFieldValue('password')) {
            callback(i18n.t('reg.confirm_password_not_equal_tips'));
        } else {
            callback();
        }
      },

      validateToNextPassword(rule, value, callback) {
        const form = this.form;
        if (value && this.confirmDirty) {
            form.validateFields(['confirm'], { force: true });
        }
        callback();
      },

      sendRegVerifyCode() {
        let email = this.form.getFieldValue('email');
        api.sendRegVerifyCode({data: {'email': email}}).then(() => {
          this.$message.success(i18n.t('reg.code_sended_tips'));
          this.hasSended = true;
          this.nextVerifyCodeSeconds = 59;
          this.timer = setInterval(this.nextSendTick, 1000);
        });
      },

      nextSendTick() {
        if (this.nextVerifyCodeSeconds <=0) {
          this.hasSended = false;
          this.verifyCodeTxt = i18n.t('reg.get_code_label');
          clearInterval(this.timer);
          return;
        }
        this.verifyCodeTxt = i18n.t('reg.code_resend_tips', {second: this.nextVerifyCodeSeconds});
        this.nextVerifyCodeSeconds --;
      },

      handleWebsiteChange(value) {
        let autoCompleteResult;
        if (!value) {
            autoCompleteResult = [];
        } else {
            autoCompleteResult = ['.com', '.org', '.net'].map(domain => `${value}${domain}`);
        }
        this.autoCompleteResult = autoCompleteResult;
      },

      gotoLogin() {
        this.$router.push({name: 'Login', query: {from: "reg"}});
      },

      beforeDestroy() {
        if (this.timer) {
          clearInterval(this.timer);
        }
      }
    },
}
</script>

<style>

</style>
