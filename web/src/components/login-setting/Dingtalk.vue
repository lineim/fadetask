<template>
  <a-form-model
    ref="ruleForm"
    :model="form"
    :rules="rules"
    :label-col="labelCol"
    :wrapper-col="wrapperCol"
    style="padding-top: 40px;"
  >
    <!-- 开关 -->
    <a-form-model-item
      label="开启钉钉登录"
      prop="dingtalk"
    >
      <a-switch v-model="form.dingtalk" />
    </a-form-model-item>

    <!-- 企业ID 用于同步数据用 -->
    <a-form-model-item
      v-if="form.dingtalk"
      ref="corpId"
      label="企业Id"
      prop="corpId"
    >
      <a-input
        v-model="form.corpId"
        @blur="
          () => {
            $refs.corpId.onFieldBlur();
          }
        "
      />
    </a-form-model-item>
    
    <!-- 登录应用的AppId -->
    <a-form-model-item
      v-if="form.dingtalk"
      ref="loginAppId"
      label="登录应用AppId"
      prop="loginAppId"
    >
      <a-input
        v-model="form.loginAppId"
        @blur="
          () => {
            $refs.loginAppId.onFieldBlur();
          }
        "
      />
    </a-form-model-item>

    <!-- 登录应用的AppSecret -->
    <a-form-model-item
      v-if="form.dingtalk"
      ref="loginAppSecret"
      label="登录应用AppSecret"
      prop="loginAppSecret"
    >
      <a-input
        v-model="form.loginAppSecret"
        @blur="
          () => {
            $refs.loginAppSecret.onFieldBlur();
          }
        "
      />
    </a-form-model-item>

    <!-- 企业内部应用的AppKey -->
    <a-form-model-item
      v-if="form.dingtalk"
      ref="syncAppKey"
      label="企业内部应用AppKey"
      prop="syncAppKey"
    >
      <a-input
        v-model="form.syncAppKey"
        @blur="
          () => {
            $refs.syncAppKey.onFieldBlur();
          }
        "
      />
    </a-form-model-item>

    <!-- 企业内部应用的AppSecret -->
    <a-form-model-item
      v-if="form.dingtalk"
      ref="syncAppSecret"
      label="企业内部应用AppSecret"
      prop="syncAppSecret"
    >
      <a-input
        v-model="form.syncAppSecret"
        @blur="
          () => {
            $refs.syncAppSecret.onFieldBlur();
          }
        "
      />
    </a-form-model-item>

    <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
      <a-button
        type="primary"
        @click="onSubmit"
      >
        保存
      </a-button>
    </a-form-model-item>
  </a-form-model>
</template>

<script>
import api from '@/api';
const validateRule = {
    corpId: [
        { required: true, message: '请填写企业Id', trigger: 'blur' },
    ],
    loginAppId: [
        { required: true, message: '请填写扫码登录应用AppId', trigger: 'blur' },
    ],
    loginAppSecret: [
        { required: true, message: '请填写扫码登录应用AppSecret', trigger: 'blur' },
    ],
    syncAppKey: [
        { required: true, message: '请填写企业内部应用AppKey', trigger: 'blur' },
    ],
    syncAppSecret: [
        { required: true, message: '请填写企业内部应用AppSecret', trigger: 'blur' },
    ],
};

export default {
  data() {
    return {
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
      other: '',
      form: {
        corpId: '',
        loginAppId: '',
        loginAppSecret: '',
        syncAppKey: '',
        syncAppSecret: '',
        dingtalk: false
      },
      rules: {},
    };
  },
  watch: {
      form: {
          handler() {
            if (this.form.dingtalk) {
                this.rules = validateRule;
            } else {
                this.rules = {};
            }
          },
          deep: true
      }
  },
  mounted: function() {
      api.settingAdmin({query: {type: 'dingtalk'}}).then((res) => {
          this.form.dingtalk = !!res.enabled;
          this.form.corpId = res.corpId;
          this.form.loginAppId = res.loginAppId;
          this.form.loginAppSecret = res.loginAppSecret;
          this.form.syncAppKey = res.syncAppKey;
          this.form.syncAppSecret = res.syncAppSecret;
      });
  },

  methods: {
    onSubmit() {
      this.$refs.ruleForm.validate(valid => {
        if (valid) {
          api.updateSetting({query: {type: 'dingtalk'}, data: this.form}).then(() => {
              this.$message.success("修改成功！");
          });
        } else {
          return false;
        }
      });
    },
    resetForm() {
      this.$refs.ruleForm.resetFields();
    },
  },
};
</script>
