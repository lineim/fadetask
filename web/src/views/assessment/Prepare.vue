<template>
  <div>
    <SearchForm @handlesearch="handleFormSearch">
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
    >
      <template #superior_feedbackUsers="superior_feedbackUsers">
        <div>{{ superior_feedbackUsers.text.length ? superior_feedbackUsers.text[0]:0 }}{{ superior_feedbackUsers.text.length > 1 ? '等' : '' }}</div>
        <a href="javascript:;">
          <a-tooltip>
            <template slot="title">
              {{ superior_feedbackUsers.text.slice(1, superior_feedbackUsers.text.length).join(',') }}
            </template>
            {{ superior_feedbackUsers.text.length > 1 ? superior_feedbackUsers.text.length + '人' : '' }} 
          </a-tooltip>
        </a>
      </template>
    </AssessmentTable>
  </div>
</template>
<script>
import Api from '@/api';
import AssessmentTable from "./components/Table";
import SearchForm from "./components/SearchForm";

const columns = [{
  title: '评估名称',
  dataIndex: 'assessmentName',
  key: 'assessmentName',
}, {
  title: "问卷名称",
  dataIndex: "questionnaire",
  key: "questionnaire",
  scopedSlots: { customRender: "questionnaireInfo" }
}, {
  title: '被评估人',
  dataIndex: 'userTrueName',
  key: 'userTrueName',
},{
  title: '上级',
  dataIndex: 'relationshipUP',
  key: 'superior',
  scopedSlots: { customRender: 'superior_feedbackUsers' },
}, {
  title: '同级',
  dataIndex: 'relationshipSAME',
  key: 'same_level',
  scopedSlots: { customRender: 'superior_feedbackUsers' },
},{
  title: '下级',
  dataIndex: 'relationshipDOWN',
  key: 'subordinates',
  scopedSlots: { customRender: 'superior_feedbackUsers' },
},{
  title: '创建人',
  dataIndex: 'creator',
  key: 'creator',
}, {
  title: '更新时间',
  dataIndex: 'createdTime',
  key: 'createdTime',
  scopedSlots: { customRender: 'createdTime' },
},{
  title: '操作',
  key: 'action',
  scopedSlots: { customRender: 'action' },
}];

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
    }
  },
  components: {
    AssessmentTable,
    SearchForm,
  },
  created(){
    this.fetchData();
  },
  mounted(){},
  computed:{},
  methods:{
    handleFormSearch(search, current = 1) {
      const data = {...search, statusList: ['PRE_READY'] };

      Api.queryAssessments({
        params: data,
      }).then(res => {
        this.formatResData(res.data);
        this.tableData = res.data;
        this.searchForm = data;
        this.pagination.total = res.paging.total;
        this.pagination.current = current;
      });
    },
    handleCreateAssessment() {
      this.$router.push({ name: "AssessmentCreate" });
    },
    loadAssessmentList(offset = 0, limit = 10, current = 1) {
      const searchForm = this.searchForm;

      Api.queryAssessments({
        params: {  
          statusList: ['PRE_READY'],
          displayFeedbackUsers: 1,
          offset,
          limit,
          ...searchForm,
        }
      }).then(res=> {
        this.formatResData(res.data);
        this.tableData = res.data;
        this.pagination.total = res.paging.total;
        this.pagination.current = current;
      })
    },
    handleTableChange(pagination) {
      const limit = 10;
      const current = pagination.current;
      const offset = (current - 1) * limit;

      this.loadAssessmentList(offset, limit, current);
    },
    formatResData(data) {
      if(!data) {
        return;
      }

      data.map((item)=> {
        item.relationshipUP = [];
        item.relationshipSAME = [];
        item.relationshipDOWN = [];

        if(item.feedbackUsers && item.feedbackUsers.length) {
          item.feedbackUsers.map(list=> {
            item[`relationship${list.relationship}`].push(list.truename);
          })
        }
      });

    },
    fetchData() {
      this.loadAssessmentList();
    }
  },
  watch:{},
}
</script>

<style scoped>
  .create-assessment-pre {
    margin-bottom: 20px;
    text-align: right;
  }
</style>