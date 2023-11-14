<template>
  <div class="login-page">
    <div class="login-content">
      <div class="login-logo-wrap text-center">
        <img
          src="/logo.png"
          height="100"
        >
      </div>
      <div
        id="components-form-demo-normal-login"
        class="login-form tac"
      >
        {{ txt }}
      </div>
    </div>
  </div>
</template>
  
  <script>
  import { mapActions } from 'vuex';
  import api from '@/api';
  
  export default {
    data() {
      return {
        txt: '正在加入项目...',
      };
    },
  
    created() {
      this.watchRoute();
    },
  
    watch: {
      // 如果路由有变化，会再次执行该方法
      '$route': 'watchRoute'
    },
  
    methods: {
      ...mapActions([]),
  
      watchRoute() {      
        let token = this.$route.query.token;
        let projectUuid = this.$route.query.project_uuid;
        if (token && projectUuid) {
          let post = {
            'project_uuid': projectUuid,
            'token': token
          };
          api.projectInvert({query: {uuid: projectUuid}, data: post}).then(resp => {
            if (resp.success) {
                this.$message.success('加入成功');
                this.$router.push({
                  name: 'ProjectOverview',
                  params: { uuid: projectUuid }
                });
            } else {
                this.$message.error('加入失败，无效的链接！');
                this.txt = '加入失败，无效的链接！';
            }
          });
        } else {
            this.txt = '参数错误！';
        }
      },
    },
  };
  </script>
  <style scope>
  #components-form-demo-normal-login .login-form {
    max-width: 300px;
  }
  #components-form-demo-normal-login .login-form-forgot {
    float: right;
  }
  #components-form-demo-normal-login .login-form-button {
    width: 100%;
  }
  
  .invite-content {
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    margin: 0 auto;
    min-width: 400px;
    background: #fff;
    padding: 20px 15px;
    border-radius: 10px;
  }
  
  .invite-content .login-logo-wrap {
    height: 100px;
    line-height: 100px;
    vertical-align: middle;
    font-size: 48px;
    color: #1890ff;
  }
  
  .invite-content .other-login {
    text-align: center;
    font-size: 24px;
  }
  
  .invite-content .other-login span {
    padding-left: 10px;
    padding-right: 10px;
  }
  </style>
  
  