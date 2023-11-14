<template>
  <div>
    <p>评估详情</p>
    
    <div class="assessment-deatil-desc">
      <p>针对 <b>{{ singleRecord.userTrueName }}</b> 的 <b>{{ singleRecord.assessmentName }}</b> 评估</p>
      <p>发起人：{{ singleRecord.creator }}</p>
      <p>发起时间：{{ singleRecord.createdTime|datetime }}</p>
    </div>

    <a-table
      :columns="columns"
      :data-source="tableData"
      row-key="id"
    >
      <span
        slot="isFeedback"
        slot-scope="text"
      >
        <span href="javascript:;">{{ text == 0 ? '未反馈': '已反馈' }}</span>
      </span>  

      <span
        slot="relationship"
        slot-scope="text"
      >
        <span href="javascript:;">{{ relationshipType(text) }}</span>
      </span>  

      <span
        slot="action"
        slot-scope="text, record, index"
      >

        <template v-if="singleRecord.status == 'FINISHED'">
          <a
            href="javascript:;"
            @click="handleLook(record, index)"
          >查看</a>
        </template>

        <template v-else>
          <template v-if="text.isFeedback == 1">
            <a
              href="javascript:;"
              @click="handleLook(record, index)"
            >查看</a>
            <a-divider type="vertical" />
            <a
              href="javascript:;"
              @click="handleAgain(record, index)"
            >重新评估</a>
            <a-divider type="vertical" />
          </template>

          <a
            href="javascript:;"
            @click="handledelete(record, index)"
          >删除</a>
        </template>
      </span>
    </a-table>

    <div class="assessment-btn-group">
      <a-row type="flex">
        <a-col :span="24"> 
          <a-button @click="handleCancle">
            返回
          </a-button>
        </a-col>
      </a-row>
    </div>
  </div>
</template>
<script>
import Api from '@/api';
import { getRequestName } from '@/utils';

const columns = [{
  title: '评估者',
  dataIndex: 'truename',
  key: 'truename',
}, {
  title: '关系',
  dataIndex: 'relationship',
  key: 'relationship',
  scopedSlots: { customRender: 'relationship' },
},{
  title: '状态',
  dataIndex: 'isFeedback',
  key: 'isFeedback',
  scopedSlots: { customRender: 'isFeedback' },
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
        total: 0
      },
      singleRecord: {},
    }
  },
  created(){
    this.fetchData();
  },
  mounted(){},
  computed:{
    isMyRoute() {
      return this.$route.meta && this.$route.meta.isMyRoute;
    },
  },
  methods:{
    handleLook(record) {
      const name = getRequestName('AssessmentDetailItem', this.isMyRoute);

      this.$router.push({
        name,
        params: { id: this.singleRecord.id, userId: record.userId },
      })
    },
    handleAgain(record) {
      const { userId } = record;

      this.$confirm({
        title: '是否重新评估?',
        onOk: () => {
          return this.handleAgainItem(userId);
        },
        onCancel() {
          console.log('Cancel');
        },
        cancelText: '取消',
        okText: '确认',
      });
    },
    handleAgainItem(userId) {
      const requestName = getRequestName('againAssessmentFeedback', this.isMyRoute);

      return Api[requestName]({
        query: {
          assessmentId: this.$route.params.assessmentId,
          userId,
        }
      }).then(res=> {
        console.log(res);
        this.loadAssessmentUsers();
      })
    },
    handledelete(record) {
      const { userId } = record;

      this.$confirm({
        title: '是否删除?',
        onOk: () => {
          return this.handledeleteItem(userId);
        },
        onCancel() {
          console.log('Cancel');
        },
        cancelText: '取消',
        okText: '确认',
      });
    },
    handledeleteItem(userId) {
      const requestName = getRequestName('deleteAssessmentFeedback', this.isMyRoute);

      return Api[requestName]({
        query: {
          assessmentId: this.$route.params.assessmentId,
          userId,
        }
      }).then(res=> {
        console.log(res);
        this.fetchData();
      })
    },
    handleCancle() {
      this.$router.go(-1);
    },
    relationshipType(type) {
      const map = {
        ['UP']: '上司',
        ['SAME']: '协同者',
        ['DOWN']: '下属',
      }

      return map[type];
    },
    loadAssessmentUsers() {
      const requestName = getRequestName('getAssessmentsUsers', this.isMyRoute);

      Api[requestName]({
        query: {
          assessmentId: this.$route.params.assessmentId
        }
      }).then(res=> {
        this.tableData = res;
      })
    },
    singleQueryAssessment() {
      const requestName = getRequestName('singleQueryAssessment', this.isMyRoute);
      
      Api[requestName]({
        query: {  
          assessmentId: this.$route.params.assessmentId,
        }
      }).then(res => {
        this.singleRecord = res;
      })
    },
    fetchData() {
      this.loadAssessmentUsers();
      this.singleQueryAssessment();
    }
  },
  watch:{},
}
</script>

<style scoped>
  .assessment-deatil-desc {
    padding-left: 30px;
  }

  .assessment-btn-group {
    text-align: center;
  }

</style>