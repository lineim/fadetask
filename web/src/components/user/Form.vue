<template>
  <div>
    <a-drawer
      :title="title"
      :width="720"
      :visible="visible"
      :body-style="{ paddingBottom: '80px' }"
      @close="onClose"
    >
      <a-form
        :form="form"
        layout="vertical"
        hide-required-mark
      >
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="姓名">
              <a-input
                v-decorator="[
                  'name',
                  {
                    rules: [
                      { required: true, message: '请出入用户姓名！'},
                      { max: 12, message: '姓名不能超过12个字符！'},
                    ],
                  },
                ]"
                placeholder="请出入用户姓名"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="邮箱">
              <a-input
                v-decorator="[
                  'email',
                  {
                    rules: [
                      {
                        validator: validateEmail
                      }
                    ],
                  },
                ]"
                style="width: 100%"
                placeholder="请输入邮箱"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="手机号">
              <a-input
                v-decorator="[
                  'mobile',
                  {
                    rules: [
                      {
                        validator: validatePhone
                      }
                    ],
                  },
                ]"
                style="width: 100%"
                placeholder="请输入手机号"
              />
            </a-form-item>
          </a-col>
          <a-col
            v-if="action == 'add' "
            :span="12"
          >
            <a-form-item label="密码">
              <a-input
                v-decorator="[
                  'password',
                  {
                    rules: [
                      { required: true, message: '请输入密码！' },
                      { min: 6, message: '密码必须大于等于6个字符！' }
                    ],
                  },
                ]"
                :rows="4"
                placeholder="职位"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="入职时间">
              <a-date-picker
                format="YYYY-MM-DD"
                v-decorator="[
                  'hireDate',
                  {
                    rules: [],
                  },
                ]"
                style="width: 100%"
                :get-popup-container="trigger => trigger.parentNode"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="职位">
              <a-input
                v-decorator="[
                  'title',
                  {
                    rules: [{ max: 20, message: '职位长度不能超过20个字符！' }],
                  },
                ]"
                :rows="4"
                placeholder="职位"
              />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
      <div
        :style="{
          position: 'absolute',
          right: 0,
          bottom: 0,
          width: '100%',
          borderTop: '1px solid #e9e9e9',
          padding: '10px 16px',
          background: '#fff',
          textAlign: 'right',
          zIndex: 1,
        }"
      >
        <a-button
          :style="{ marginRight: '8px' }"
          @click="onClose"
        >
          取消
        </a-button>
        <a-button
          type="primary"
          @click="submit"
        >
          提交
        </a-button>
      </div>
    </a-drawer>
  </div>
</template>
<script>
import api from "@/api";
import {isEmail, isMobile} from "@/utils";

export default {
  props: {
    uuid: {
      type: String,
      default: ""
    },
    // eslint-disable-next-line vue/require-default-prop
    action: {
      type: String,
      validator: function (value) {
        // 这个值必须匹配下列字符串中的一个
        return ['add', 'edit'].indexOf(value) !== -1
      },
      default: 'add'
    },
    title: {
      type: String,
      required: true
    },
    visible: {
      type: Boolean,
      required: true,
      default: false
    }
  },
  data() {
    return {
      form: this.$form.createForm(this),
      user: {}
    };
  },
  watch: {
    uuid: function() {
      if (this.uuid && this.action == 'edit') {
        this.loadUser();
      }
    }
  },
  mounted() {
    if (this.action == 'edit') {
      this.loadUser();
    }
  },
  methods: {
    loadUser() {
      api.AdminUser({query: {uuid: this.uuid}}).then( user => {
        this.user = user;
        this.form.setFieldsValue(this.user);
      });
    },

    validateEmail(rule, value, callback) {
      const mobile = this.form.getFieldValue('mobile');
      if (!mobile && !value) {
        callback('请输入手机号或者邮箱！');
        return;
      }

      if (value && !isEmail(value)) {
        callback('邮箱格式错误！');
        return;
      }

      if (value) {
        if (!isEmail(value)) {
          callback('邮箱格式错误！');
          return;
        }
        api.AdminUserCheckEmail({data: {email: value, uuid: this.uuid}})
          .then(ok => {
            if (!ok) {
              callback('邮箱号已被占用！');
            } else {
              callback()
            }
          })
      }
    },

    validatePhone(rule, value, callback) {
      const email = this.form.getFieldValue('email');
      if (!email && !value) {
        callback('请输入手机号或者邮箱！');
        return;
      }

      if (value) {
        if (!isMobile(value)) {
          callback('手机号格式错误！');
          return;
        }
        api.AdminUserCheckMobile({data: {mobile: value, uuid: this.uuid}})
          .then(ok => {
            if (!ok) {
              callback('手机号已被占用！');
            } else {
              callback()
            }
          })
      }
    },

    submit() {
      this.form.validateFields(err => {
        if (!err) {
          let formData = this.form.getFieldsValue();
          const user = {
            'name': formData.name,
            'email': formData.email,
            'mobile': formData.mobile,
            'password': formData.password,
            'hiredDate': formData.hireDate.format('YYYY-MM-DD'),
            'title': formData.title,
          };
          api.AdminNewUser({data: user})
            .then((resp) => {
              if (resp) {
                this.$message.success('添加用户成功！');
                this.onSubmitSuccess();
              } else {
                this.$message.error('添加用户失败！');
              }
            });
        }
      });
    },

    onSubmitSuccess() {
      this.form.resetFields();
      this.$emit('submited');
    },

    onClose() {
      this.form.resetFields();
      this.$emit('close');
    },
  },
};
</script>
