<template>
  <div class="my-assessment">
    <SearchForm
      :has_status="true"
      @handlesearch="handleFormSearch"
    >
      <span slot="btn-group">
        <a-col :span="2">
          <a-row type="flex">
            <a-button
              type="primary"
              @click="handleCreateAssessment"
            >+创建评估</a-button>
          </a-row>
        </a-col>
      </span>
    </SearchForm>

    <AssessmentTable
      :columns="columns"
      :pagination="pagination"
      :table-data="tableData"
      @handleTableChange="handleTableChange"
      @handlepreviewreport="handlepreviewreport"
    />

    <a-modal
      title="查看进度"
      :visible="remindVisible"
      :confirm-loading="remindConfirmLoading"
      @ok="handleremindModleOk"
      @cancel="handleremindModleCancel"
    >
      <div v-if="noticeFeedback.users.length">
        <p
          v-for="(item, index) in noticeFeedback.users"
          :key="item.userId"
        >
          <a-row
            type="flex"
            align="middle"
          >
            <a-col :span="7">
              {{ item.truename }}
            </a-col>
            <a-col :span="4">
              <a-icon
                :type="item.isFeedback == 1 ? 'check-circle' : 'notification'"
                :style="{ fontSize: '30px' }"
                @click="handleSingleRemind(item, noticeFeedback.assessmentId, index)"
              />
            </a-col>
          </a-row>
        </p>
      </div>
      <div v-else>
        暂无通知人员
      </div>
    </a-modal>
  </div>
</template>

<script>
import Api from "@/api";
import AssessmentTable from "@/views/assessment/components/Table";
import SearchForm from "@/views/assessment/components/SearchForm";

const columns = [
  {
    title: "评估名称",
    dataIndex: "assessmentName",
    key: "assessmentName"
  },
  {
    title: "问卷名称",
    dataIndex: "questionnaire",
    key: "questionnaire",
    scopedSlots: { customRender: "questionnaireInfo" }
  },
  {
    title: "被评估人",
    dataIndex: "userTrueName",
    key: "userTrueName"
  },
  {
    title: "状态",
    dataIndex: "status",
    key: "status",
    scopedSlots: { customRender: "status" }
  },
  {
    title: "创建人",
    dataIndex: "creator",
    key: "creator"
  },
  {
    title: "更新时间",
    dataIndex: "createdTime",
    key: "createdTime",
    scopedSlots: { customRender: "createdTime" }
  },
  {
    title: "操作",
    key: "action",
    scopedSlots: { customRender: "action" }
  }
];

export default {
  name: "MyAssessment",
  data() {
    return {
      columns,
      tableData: [],
      pagination: {
        total: 0,
        defaultCurrent: 1,
        current: 1,
        pageSize: 10,
      },
      remindVisible: false,
      remindConfirmLoading: false,
      noticeFeedback: {
        users: [],
        assessmentId: ""
      },
      searchForm: {},
    };
  },
  components: {
    AssessmentTable,
    SearchForm,
  },
  created() {
    this.fetchData();
  },
  methods: {
    handleFormSearch(search, current = 1) {
      const data = {...search, statusList: ['ASSING'] };

      Api.myQueryAssessments({
        params: data,
      }).then(res => {
        this.tableData = res.data;
        this.searchForm = search;
        this.pagination.total = res.paging.total;
        this.pagination.current = current;
      });
    },
    handlepreviewreport(record) {
      Api.myGetAssessmentsUsers({
        query: {
          assessmentId: record.id
        }
      }).then(res => {
        this.noticeFeedback.assessmentId = record.id;
        this.noticeFeedback.users = res;
        this.remindVisible = true;
      });     
    },
    handleSingleRemind(item, assessmentId, index) {
      if (item.isFeedback == 1) {
        return;
      }

      Api.mySingleAssessments({
        query: {
          assessmentId,
          userId: item.userId
        }
      }).then(() => {
        this.noticeFeedback.users[index].isFeedback = 1;
        this.$message.info("提醒成功");
      }).catch(() => {
        this.$message.error('提醒失败！');
      });
    },
    handleremindModleOk() {
      this.remindVisible = false;
      this.remindConfirmLoading = false;
    },
    handleremindModleCancel() {
      this.remindVisible = false;
    },
    handleCreateAssessment() {
      this.$router.push({ name: "myAssessmentCreate" });
    },
    loadAssessmentList(offset = 0, limit = 10, current = 1) {
      const searchForm = this.searchForm;

      Api.myQueryAssessments({
        params: {
          statusList: ['ASSING'],
          displayFeedbackUsers: 1,
          limit,
          offset,
          ...searchForm,
        }
      }).then(res => {
        this.tableData = res.data;
        this.pagination.total = res.paging.total;
        this.pagination.current = current;
      });
    },
    handleTableChange(pagination) {
      const limit = 10;
      const current = pagination.current;
      const offset = (current - 1) * limit;

      this.loadAssessmentList(offset, limit, current);
    },
    fetchData() {
      this.loadAssessmentList();
    },
  }
};
</script>

<style scoped>
.create-assessment-btn {
  margin-bottom: 20px;
  text-align: right;
}
</style>