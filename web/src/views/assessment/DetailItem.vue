<template>
  <div
    class="questions"
    v-if="record"
  >
    <div class="detail-item">
      <a-row
        type="flex"
        align="middle"
      >
        <a-col :span="8">
          {{ record.feedbacker.truename }}对{{ record.user.truename }}的{{ record.assessmentName }}评估
        </a-col>
        <a-col
          :span="2"
          push="14"
        > 
          <a-button @click="handleCancle">
            返回
          </a-button>
        </a-col>
      </a-row>
    </div>
    <p
      v-for="data in record.sections"
      :key="data.id"
    >
      <a-list
        size="small"
        :data-source="data.questions"
      >
        <a-list-item
          slot="renderItem"
          slot-scope="item"
          class="question-list"
        >
          <!-- <span v-if="item.comment">（{{ item.comment }}）</span> -->
          <template v-if="item.feedType === 'STANDARD'">
            {{ item.question }}
            <div
              class="detail-item-score"
              v-if="item.result"
            >
              {{ item.result }}
            </div>  
          </template>

          <template v-else-if="item.feedType === 'COMMENT'">
            <a-row
              type="flex"
              class="comment-warpper"
            >
              <a-col :span="6">
                {{ item.question }}
              </a-col>
              <a-col
                :span="18"
                class="comment-warpper-text"
              >    
                <a-textarea
                  v-if="item.result"
                  placeholder=""
                  :autosize="{ minRows: 2, maxRows: 10 }"
                  :disabled="true"
                  :default-value="item.result"
                />
                <!-- <a-card size="small">{{item.result}}</a-card> -->
              </a-col>
            </a-row>
          </template>
        </a-list-item>
        <div slot="header">
          <span class="left">{{ data.sectionName }}</span>
          <span
            class="right total-score"
            v-if="data.result"
          >{{ data.result }}</span>
          <div style="clear: both;" />
        </div>
      </a-list>
    </p>
  </div>
</template>
<script>
import Api from '@/api';
import { getRequestName } from '@/utils';

const dataSource = [
  'Racing car sprays burning fuel into crowd.',
  'Japanese princess to wed commoner.',
  'Australian walks 100km after outback crash.',
  'Man charged over missing wedding girl.',
  'Los Angeles battles huge wildfires.',
];

export default {
  data() {
    return {
      dataSource,
      record: {
        feedbacker: {},
        user: {},
        sections: []
      }
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
    handleCancle() {
      this.$router.go(-1);
    },
    fetchData() {
      const requestName = getRequestName('singleAssessmentFeedBack', this.isMyRoute);

      Api[requestName]({
        query: {
          assessmentId: this.$route.params.id,
          userId: this.$route.params.userId
        }
      }).then(res => {
        this.record = res;
        console.log(this.record.sections);
      });
    }
  },
  watch:{},
}
</script>

<style scoped>
  .detail-item {
    padding-bottom: 20px;
  }

  .detail-item-score {
    position: absolute;
    right: 20px;
  }

  .left {
    float: left;
  }
  .right {
    float: right;
  }

.questions {
  width: 70%;
}

.question-list  {
  padding-left: 30px !important;
}

.total-score {
  padding-right: 20px;
}

.comment-warpper {
  width: 100%;
}

.comment-warpper-text  >>> .ant-input-disabled {
  color: #333;
  cursor: default;
  background-color: transparent;
  resize: none;
  border: none;
}

</style>