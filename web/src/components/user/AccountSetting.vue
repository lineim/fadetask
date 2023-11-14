<template>
  <div>
    <a-modal
      title="个人设置"
      v-model="showModal"
      :footer="null"
      :destroy-on-close="true"
      :force-render="false"
      @afterClose="close"
    >
      <div>
        <a-tabs
          default-active-key="1"
          @change="tabChange"
        >
          <a-tab-pane
            key="1"
            tab="姓名"
          >
            <a-form-model
              layout="inline"
              ref="unameForm"
              :model="unameFrom"
              :rules="unameFromRules"
              class="mbl"
            >
              <a-form-model-item
                label="姓名"
                ref="name"
                prop="name"
                required
              >
                <a-input
                  v-model="unameFrom.name"
                  placeholder="请输入您的姓名"
                />
              </a-form-model-item>

              <a-form-model-item>
                <a-button
                  type="primary"
                  :disabled="unameFrom.name.length <= 0"
                  :loading="unameSubmiting"
                  @click="submitUname()"
                >
                  提交
                </a-button>
              </a-form-model-item>
            </a-form-model>
          </a-tab-pane>
          <a-tab-pane
            v-if="me.reg_type == 'email'"
            key="2"
            tab="修改密码"
            force-render
          >
            <a-form-model
              layout="horizontal"
              ref="updatePassForm"
              :model="updatePassForm"
              :rules="updatePassFormRules"
              v-bind="updatePassFormLayout"
              class="mbl"
            >
              <a-form-model-item
                label="当前密码"
                ref="old_pass"
                prop="old_pass"
                required
              >
                <a-input-password
                  v-model="updatePassForm.old_pass"
                  placeholder="请输入当前密码"
                />
              </a-form-model-item>

              <a-form-model-item
                label="新密码"
                ref="new_pass"
                prop="new_pass"
                required
              >
                <a-input-password
                  v-model="updatePassForm.new_pass"
                  placeholder="请输入新密码"
                />
              </a-form-model-item>

              <a-form-model-item
                label="确认密码"
                ref="confirm_pass"
                prop="confirm_pass"
                required
              >
                <a-input-password
                  v-model="updatePassForm.confirm_pass"
                  placeholder="请再次输入新密码"
                />
              </a-form-model-item>

              <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                <a-button
                  type="primary"
                  :disabled="updatePassForm.old_pass.length <= 0 || updatePassForm.new_pass.length <= 0 || updatePassForm.confirm_pass.length <= 0"
                  :loading="unameSubmiting"
                  @click="submitUpdatePass()"
                >
                  提交
                </a-button>
              </a-form-model-item>
            </a-form-model>
          </a-tab-pane>
        </a-tabs>
      </div>
    </a-modal>
  </div>
</template>

<script>

import api from '@/api';
import { mapActions } from 'vuex';
import store from "@/store";
import * as types from '@/store/mutation-types';

const DEFAULT_UPDATE_PASS_FORM = {
    'old_pass': '',
    'new_pass': '',
    'confirm_pass': ''
};

export default {
  props: {
      'visible': {
        type: Boolean,
        default: false
      }
  },
  data() {
    let validateConfirmPass = (rule, value, callback) => {
        if (!value) {
            callback(new Error('请再一次输入密码'));
        } else if (value !== this.updatePassForm.new_pass) {
            callback(new Error("两次输入密码不匹配"));
        } else {
            callback();
        }
    }

    return {
      show: true,
      me: {},
      unameFrom: {
        'name': '',
      },
      unameFromRules: {
        'name': [
            {required: true, message: '请输入您的姓名',  trigger: 'change', whitespace: true },
            {max: 8, message: '长度不能大于8个字符', trigger: 'change', whitespace: true }
        ]
      },
      unameSubmiting: false,

      updatePassForm: Object.assign({}, DEFAULT_UPDATE_PASS_FORM),
      updatePassFormRules: {
        'old_pass': [
            {required: true, message: '请输入当前密码', trigger: 'change', whitespace: true },
        ],
        'new_pass': [
            {required: true, message: '请输入新密码', trigger: 'change', whitespace: true },
            {min: 8, message: '密码长度不低于8位!', trigger: 'change', whitespace: true },
        ],
        'confirm_pass': [
            {validator: validateConfirmPass, trigger: 'change', whitespace: true },
        ],
      },
      updatePassFormLayout: {
        labelCol: { span: 4 },
        wrapperCol: { span: 14 },
      },
      updatePassFormSubmiting: false
    };
  },

   created() {
      this.loadLoginUser();
   },

   computed: {
      showModal: {
          get() {
              return this.visible;
          },
          set(val) {
              if (!val) {
                  this.close();
              }
          }
      }
   },

   methods: {
        ...mapActions([
           'changeCurrentUser'
        ]),

       loadLoginUser: function() {
           api.me().then(me => {
              this.me = me;
              this.unameFrom.name = me.name;
           });
       },

        submitUname: function() {
            this.$refs.unameForm.validate(valid => {
                if (!valid) {
                    return false;
                }
                this.unameSubmiting = true;
                this.changeCurrentUser({data: this.unameFrom}).then((r) => {
                    this.unameSubmiting = false;
                    if (!r) {
                      return;
                    }
                    this.$message.success('修改成功！');
                }).catch(e => {
                  this.unameSubmiting = false;
                  console.log(e);
                });
            });
        },

        submitUpdatePass: function() {
            this.$refs.updatePassForm.validate(valid => {
                if (!valid) {
                    return false;
                }
                this.updatePassFormSubmiting = true;
                api.meUpdatePass({data: this.updatePassForm}).then((res) => {
                    if (!res) {
                        this.$message.console.error('修改密码失败，请联系管理员！');
                        return;
                    }
                    this.$message.success('密码修改成功，请重新登录！');
                    this.updatePassFormSubmiting = false;
                    this._resetUpdatePassForm();
                    const data = { user: {}, token: ''};
                    store.commit(types.USER_LOGOUT, data);
                    this.$router.push({path: '/login'})
                });
            });
        },

        _resetUpdatePassForm: function() {
            this.updatePassForm = Object.assign({}, DEFAULT_UPDATE_PASS_FORM);
        },
    
        tabChange: function(key) {
            console.log(key);
        },

        close: function() {
            console.log('closed');
            this._resetUpdatePassForm();
            this.$emit('close');
        }
   }
  

}

</script>