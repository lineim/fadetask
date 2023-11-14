<template>
  <div>
    <a-page-header
      title="用户管理"
    >
      <template slot="extra">
        <a-button
          icon="plus"
          type="primary"
          @click="add"
        >
          新增用户
        </a-button>
      </template>
    </a-page-header>
    <a-table
      :columns="columns"
      :row-key="record => record.id"
      :data-source="users"
      :pagination="pagination"
      :loading="loading"
      @change="handleTableChange"
    >
      <!-- 状态 -->
      <a
        slot="status"
        slot-scope="text, record"
      >
        <a-tag
          color="green"
          v-if="record.verified"
        >
          正常
        </a-tag>
        <a-tag
          color="red"
          v-if="!record.verified"
        >
          封禁
        </a-tag>
      </a>

      <!-- 来源 -->
      <span
        slot="type"
        slot-scope="text, record"
      >
        <a-icon
          v-if="record.type == 'normal'"
          type="global"
        />
        <a-icon
          v-if="record.type == 'dingtalk'"
          type="dingding"
          :style="{color: '#108ee9'}"
        />
        <a-icon
          v-if="record.type == 'wework'"
          type="wechat"
          :style="{color: '#87d068'}"
        />
      </span>

      <span
        slot="createdDate"
        slot-scope="text, record"
      >
        {{ record.created_time*1000 | datefmt('YYYY-MM-DD HH:mm:ss') }}
      </span>

      <!-- action -->
      <span
        slot="action"
        slot-scope="text, record"
      >
        <a @click="edit(record.uuid)">编辑</a>
        <a-divider type="vertical" />
        <a @click="view(record.uuid)">查看</a>
        <a-divider
          v-if="record.type == 'normal'"
          type="vertical"
        />
        <a
          v-if="record.type == 'normal'"
          @click="showChangePassModel(record.uuid, record.name)"
        >修改密码</a>
        <a-divider type="vertical" />
        <a
          v-if="!record.verified"
          @click="updateVerify(record.uuid, 1)"
        >解封</a>
        <a
          v-if="record.verified"
          @click="updateVerify(record.uuid, 0)"
        >封禁</a>
      </span>
    </a-table>
    <UserForm 
      :visible="formVisible"
      :title="formTitle"
      :uuid="currentUuid"
      :action="action"
      @close="userFormClose"
      @submited="userFormSubmited"
    />
    <a-modal
      title="修改密码"
      :visible="changePassModel"
      :confirm-loading="false"
      ok-text="修改"
      cancel-text="取消"
      @ok="changePass"
      @cancel="cancelChangePass"
    >
      <a-form-model
        ref="changePassForm" 
        :model="changePassForm"
        :rules="changePassRules"
        :label-col="labelCol"
        :wrapper-col="wrapperCol"
        :confirm-loading="confirmLoading"
      >
        <a-form-model-item label="用户">
          <a-input
            :disabled="true"
            :value="currentUserName"
          />
        </a-form-model-item>
        <a-form-model-item
          label="新密码"
          prop="password"
        >
          <a-input v-model="changePassForm.password" />
        </a-form-model-item>
      </a-form-model>
    </a-modal>
  </div>
</template>
<script>
import api from '@/api';
import UserForm from '@/components/user/Form';

const columns = [
  {
    title: '序号',
    dataIndex: 'id',
    key: 'id',
    width: '5%',
  },
  {
    title: '姓名',
    dataIndex: 'name',
    key: 'name',
    width: '10%',
  },
  {
    title: '手机号',
    dataIndex: 'mobile',
    key: 'mobile',
    width: '10%',
  },
  {
    title: '邮箱',
    dataIndex: 'email',
    key: 'email',
    width: '15%',
  },
  {
    title: '状态',
    dataIndex: 'verified',
    key: 'verified',
    scopedSlots: { customRender: 'status' },
    width: '5%',
  },
  {
    title: '来源',
    dataIndex: 'type',
    key: 'type',
    scopedSlots: { customRender: 'type' },
    width: '5%',
  },
  {
    title: '注册时间',
    dataIndex: 'created_time',
    key: 'created_time',
    scopedSlots: { customRender: 'createdDate' },
    width: '10%',
  },
  {
    title: '操作',
    key: 'action',
    scopedSlots: { customRender: 'action' },
  },
];

let validatePass = (rule, value, callback) => {
  if (value === '') {
    callback(new Error('请输入密码'));
  } else if (value.length < 6)  {
    callback(new Error('密码必须大于等于6个字符！'));
  }
  callback();
};

export default {
  components: {
    UserForm
  },
  data() {
    return {
      users: [],
      pagination: {pageSize: 20},
      filters: {},
      sorter: {},
      loading: false,
      columns,
      formVisible: false,
      formTitle: '新增用户',
      action: 'add',
      currentUuid: "",
      currentUserName: "",
      changePassModel: false,
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
      changePassForm: {
        password: '',
      },
      changePassRules: {
        password: [{ validator: validatePass, trigger: 'change' }],
      },
      confirmLoading: false
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    add() {
      this.formVisible = true;
      this.formTitle = '新增用户';
      this.currentUuid = "";
      this.action = 'add';
    },
    edit(uuid) {
      this.formVisible = true;
      this.formTitle = '编辑用户';
      this.currentUuid = uuid;
      this.action = 'edit';
    },

    userFormSubmited() {
      this.formVisible = false;
      this.currentUuid = '';
    },

    userFormClose() {
      this.formVisible = false;
      this.currentUuid = '';
    },

    showChangePassModel(uuid, name) {
      this.currentUuid = uuid;
      this.currentUserName = name;
      this.changePassModel = true;
    },

    cancelChangePass() {
      this.currentUuid = '';
      this.changePassModel = false;
      this.currentUserName = '';
      this.resetChangePassForm();
    },

    changePass() {
      const formRef = 'changePassForm';
      this.$refs[formRef].validate(valid => {
        if (!valid) {
          return false;
        }
        this.confirmLoading = true;
        api.AdminUserUpdatePassword({query: {uuid: this.currentUuid}, data: {password: this.changePassForm.password}})
        .then(() => {
          this.confirmLoading = false;
          this.changePassModel = false;
          this.currentUuid = '';
          this.currentUserName = '';
          this.resetChangePassForm();
        });
      });
    },

    resetChangePassForm() {
      const formRef = 'changePassForm';
      this.$refs[formRef].resetFields();
      this.changePassForm.password = '';
    },

    updateVerify(uuid, verified = 1) {
      api.AdminUserUpdateVerify({query: {uuid: uuid}, data: {verified: verified}})
      .then(() => {
        this.fetch({
          pageSize: this.pagination.pageSize,
          page: this.pagination.current,
          sortField: this.sorter.field,
          sortOrder: this.sorter.order,
          ...this.filters,
        });
      });
    },

    handleTableChange(pagination, filters, sorter) {
      const pager = { ...this.pagination };
      pager.current = pagination.current;
      this.pagination = pager;
      this.filters = filters;
      this.sorter = sorter;
      this.fetch({
        pageSize: parseInt(pagination.pageSize),
        page: parseInt(pagination.current),
        sortField: sorter.field,
        sortOrder: sorter.order,
        ...filters,
      });
    },

    fetch(params) {
      this.loading = true;
      api.AdminUsers({params: params}).then(res => {
        const pagination = { ...this.pagination };
        pagination.total = parseInt(res.total);
        pagination.page = parseInt(res.page);
        pagination.pageSize = parseInt(res.pageSize);
        this.loading = false;
        this.users = res.users;
        this.pagination = pagination;
      });
    },
  },
};
</script>
