<template>
  <a-form
    :form="form"
    @submit="submitSetting"
  >
    <h3 :style="{ marginBottom: '16px' }">
      钉钉同步配置
    </h3>
    <a-form-item label="CorpID">
      <a-input
        type="password"
        v-decorator="['dingtalkSyncKey', {
          rules: [{ required: true, message: '请输入CorpID' }]
        }]"
        placeholder="请输入CorpID"
      />
    </a-form-item>

    <a-form-item label="CorpSecret">
      <a-input
        type="password"
        v-decorator="['dingtalkSyncSecret', {
          rules: [{ required: true, message: '请输入CorpSecret' }]
        }]"
        placeholder="请输入CorpSecret"
      />
    </a-form-item>

    <h3 :style="{ marginBottom: '16px' }">
      钉钉扫一扫登录配置
    </h3>
    <a-form-item label="AppId">
      <a-input
        type="password"
        v-decorator="['dingtalkScanLoginKey', {
          rules: [{ required: true, message: '请输入AppId' }]
        }]"
        placeholder="请输入AppId"
      />
    </a-form-item>

    <a-form-item label="AppSecret">
      <a-input
        type="password"
        v-decorator="['dingtalkScanLoginSecret', {
          rules: [{ required: true, message: '请输入AppSecret' }]
        }]"
        placeholder="请输入AppSecret"
      />
    </a-form-item>

    <h3 :style="{ marginBottom: '16px' }">
      邮件发送配置
    </h3>
    <a-form-item label="发送服务器">
      <a-input
        v-decorator="['emailHost', {
          rules: [{ required: true, message: '请输入发送服务器' }]
        }]"
        placeholder="请输入发送服务器， 如smtp.exmail.qq.com"
      />
    </a-form-item>

    <a-form-item label="端口号">
      <a-input
        v-decorator="['emailPort', {
          rules: [{ required: true, message: '请输入端口号' }]
        }]"
        placeholder="请输入端口号"
      />
    </a-form-item>

    <a-form-item label="邮箱别名">
      <a-input
        v-decorator="['emailFromName', {
          rules: [{ required: true, message: '请输入邮箱别名' }]
        }]"
        placeholder="邮箱别名"
      />
    </a-form-item>

    <a-form-item label="邮箱账号">
      <a-input
        type="password"
        v-decorator="['emailUsername', {
          rules: [{ required: true, message: '请输入邮箱账号' }]
        }]"
        placeholder="邮箱账号，如 edusoho@edusoho.com"
      />
    </a-form-item>

    <a-form-item label="邮箱密码">
      <a-input
        type="password"
        v-decorator="['emailPassword', {
          rules: [{ required: true, message: '请输入邮箱密码' }]
        }]"
        placeholder="邮箱密码"
      />
    </a-form-item>

    <div>
      <a-form-item style="display: inline-block;">
        <a-button
          type="primary"
          html-type="submit"
        >
          提交
        </a-button>
      </a-form-item>
    </div>
    {{ getDingdingCode() }}
  </a-form>
</template>

<script>
import api from '@/api'

export default {
    data() {
        return {
            setting: [],
            form: this.$form.createForm(this),
            dingdingCode: {},
        }
    },
    mounted() {
      this.loadSetting();
    },
    methods: {
      getDingdingCode() {
        return localStorage.getItem('dingdingCode')
      },
      loadSetting() {
        api.setting({query: {type: 'system'}}).then(res => {
          this.setting = res;
          this.form.setFieldsValue(this.setting);
        });
      },
      submitSetting(e) {
        e.preventDefault();
        let self = this;
        this.form.validateFields((err, values) => {
          if (err) {
            this.$message.error('请完善表单信息');
            return;
          }
          api.updateSetting({query: {type: 'system'}, data: values}).then(res => {
            console.log(res);
            self.loadSetting();
          });
        });
      },
    }
}
</script>