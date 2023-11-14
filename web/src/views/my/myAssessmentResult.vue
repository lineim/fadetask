<template>
  <div>       
    <p>评估结果</p>

    <SearchForm @handlesearch="handleFormSearch" />

    <AssessmentTable
      :columns="columns"
      :pagination="pagination"
      :table-data="tableData"
    />
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
    title: "请求数",
    dataIndex: "totalFeedbackNum",
    key: "totalFeedbackNum"
  },
  {
    title: "反馈数",
    dataIndex: "finishedFeedbackNum",
    key: "finishedFeedbackNum"
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
      searchForm: {},
    };
  },
  created() {
    this.fetchData();
  },
  components: {
    AssessmentTable,
    SearchForm,
  },
  mounted() {},
  computed: {},
  methods: {
    handleFormSearch(search, current = 1) {
      const data = {...search, statusList: ['FINISHED'] };

      Api.myQueryAssessments({
        params: data,
      }).then(res => {
        this.tableData = res.data;
        this.searchForm = data;
        this.pagination.total = res.paging.total;
        this.pagination.current = current;
      });
    },
    loadAssessmentList(offset = 0, limit = 10, current = 1) {
      const searchForm = this.searchForm;

      Api.myQueryAssessments({
        params: {
          statusList: ["FINISHED"],
          displayFeedbackUsers: 1,
          offset,
          limit,
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
    }
  },
  watch: {}
};
</script>

<style scoped>
.create-assessment-btn {
  margin-bottom: 20px;
  text-align: right;
}

.search-form > div {
  margin-bottom: 20px;
}

.org-select {
  height: 32px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  overflow: hidden;
}

.org-select >>> .ant-select-selection--multiple {
  border: none;
}

</style>