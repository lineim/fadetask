<template>
  <div>
    <a-page-header
      title="用户登录日志"
    />
    <a-table
      :columns="columns"
      :row-key="log => log.id"
      :data-source="data"
      :pagination="pagination"
      :loading="loading"
      @change="handleTableChange"
    >
      <template
        slot="login-status"
        slot-scope="status"
      >
        {{ loginStatsLabels[status] }}
      </template>
      <template
        slot="error-type"
        slot-scope="type"
      >
        {{ errorTypeLabels[type] }}
      </template>
    </a-table>
  </div>  
</template>
  <script>
  import api from '@/api';
  
  const columns = [
    {
      title: '用户',
      dataIndex: 'user_name',
    },
    {
      title: 'Email/Mobile',
      dataIndex: 'email',
    },
    {
      title: '登录成功',
      dataIndex: 'is_success',
      scopedSlots: { customRender: 'login-status' },
    },
    {
      title: '参数',
      dataIndex: 'params',
      width: 250
    },
    {
        title: "登录ip",
        dataIndex: "login_ip",
    },
    {
        title: "错误类型",
        dataIndex: "error_type",
        scopedSlots: { customRender: 'error-type' },
    },
    {
        title: "登录时间",
        dataIndex: "created_time",
    },
  ];
  
  export default {
    data() {
      return {
        data: [],
        pagination: {},
        loading: false,
        columns,
        loginStatsLabels: {
            1: "登录成功",
            0: "登录失败"
        },
        errorTypeLabels: {
            USER_NOT_FOUND: "用户不存在",
            PASSWORD_ERROR: "密码错误",
            USER_INVALID: "用户封禁",
            MOBILE_LOGIN_PARAMS_ERROR: "参数错误",
            MOBILE_LOGIN_SMS_CODE_ERROR: "验证码错误",
            REQUEST_TOO_MANY: '登录频率太快',
            MOBILE_LOGIN_REQUEST_TOO_MANY: '登录频率太快'
        }
      };
    },
    mounted() {
      this.loadLogs();
    },
    methods: {
      handleTableChange(params) {
        console.log(params);
        this.loadLogs(params);
      },
      loadLogs(params = {}) {
        this.loading = true;
        api.AdminLoginLogs({params: params}).then(data => {
          const pagination = { ...this.pagination };
          // Read total count from server
          pagination.total = data.total;
          this.loading = false;
          this.data = data.logs;
          this.pagination = pagination;
        }).catch(e => {
            console.log(e);
        })
      },
    },
  };
  </script>
  