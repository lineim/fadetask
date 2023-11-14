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
      label="开启企业微信登录"
      prop="enabled"
    >
      <a-switch v-model="form.enabled" />
    </a-form-model-item>

    <!-- 企业ID 用于同步数据用 -->
    <a-form-model-item
      v-if="form.enabled"
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
    
    <!-- 登录应用的 AgentId -->
    <a-form-model-item
      v-if="form.enabled"
      ref="agentId"
      label="登录应用 AgentId"
      prop="agentId"
    >
      <a-input
        v-model="form.agentId"
        @blur="
          () => {
            $refs.agentId.onFieldBlur();
          }
        "
      />
    </a-form-model-item>

    <!-- 登录应用的 Secret -->
    <a-form-model-item
      v-if="form.enabled"
      ref="secret"
      label="登录应用 Secret"
      prop="secret"
    >
      <a-input
        v-model="form.secret"
        @blur="
          () => {
            $refs.secret.onFieldBlur();
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
    agentId: [
        { required: true, message: '请填写应用 AgentId', trigger: 'blur' },
    ],
    secret: [
        { required: true, message: '请填写扫码登录应用 secret', trigger: 'blur' },
    ],
};

export default {
  data() {
    return {
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
      other: '',
      form: {
        enabled: 0,
        corpId: '',
        agentId: '',
        secret: '',
      },
      rules: {},
    };
  },
  watch: {
      form: {
          handler() {
            if (this.form.enabled) {
                this.rules = validateRule;
            } else {
                this.rules = {};
            }
          },
          deep: true
      }
  },
  mounted: function() {
      api.settingAdmin({query: {type: 'wework'}}).then((res) => {
          this.form.enabled = !!res.enabled;
          this.form.corpId = res.corpId;
          this.form.agentId = res.agentId;
          this.form.secret = res.secret;
      });
  },

  methods: {
    onSubmit() {
      this.$refs.ruleForm.validate(valid => {
        if (valid) {
          api.updateSetting({query: {type: 'wework'}, data: this.form}).then(() => {
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
