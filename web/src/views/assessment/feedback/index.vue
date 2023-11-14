<template>
  <div class="feedback-wrapper">
    <a-form
      :form="form"
      @submit="handleSubmit"
    >
      <Describe :user="user" />
      <Planning :stan-dards="resultData" />
      <div class="feedback-submit-btn">
        <a-button
          type="primary"
          html-type="submit"
        >
          提交
        </a-button>
      </div>
    </a-form>
  </div>
</template>

<script>
import Api from '@/api';
import Describe from './components/FeedBackDescribe';
import Planning from './components/FeedBackPlanning';

export default {
  components:{
    Describe,
    Planning,
  },
  props:{},
  data(){
    return {
      form: this.$form.createForm(this, { name: 'feedBack' }),
      resultData: [],
      user: {},
      assessmentId: '',
      uuid: '',
    }
  },
  created(){
    this.fetchData();
  },
  mounted(){},
  computed:{},
  methods:{
    loadFeedBack() {
      this.assessmentId = this.$route.query.assessmentId;
      this.uuid = this.$route.query.uuid;

      Api.getFillableAssessmentsFeedback({
        query: {
          assessmentId: this.assessmentId,
          uuid: this.uuid,
        }
      }).then(res=> {
        this.user = res.user;
        this.resultData = res.sections;
      });
    },
    handleSubmit(e) {
      e.preventDefault();

      this.form.validateFields((err, fieldsValue) => {
        if (!err) {
          const fieldsValueKeys = Object.keys(fieldsValue);        
          const data = {sections: this.formatSubmitData(fieldsValueKeys, fieldsValue)};
     
          Api.setFillableAssessmentsFeedback({
            data,
            query: {
              assessmentId: this.assessmentId,
              uuid: this.uuid,
            }
          }).then(res=> {
            console.log(res);
            this.$message.info('填写成功');
          })

        }
      });
    },
    formatSubmitData(data, fieldsValue) {

      if(!data) {
        return;
      }

      let temp = {};

      return data.reduce(function(item, next) {
        const result = fieldsValue[next] ? fieldsValue[next]  : '';

        next = JSON.parse(next);

        if (temp[next.sectionId]) {

          item.map(list=> {
            if(list.sectionId === next.sectionId) {
              list.questions.push({
                id: next.questionId,
                result,
              });
            }
          })

        } else {
          
          temp[next.sectionId] = true;
          next.questions = [];
          next.questions.push({
            id: next.questionId,
            result,
          })
        
          item.push(next);
        }

        return item;

      }, []);

    },
    fetchData() {
      this.loadFeedBack();
    }
  },
  watch:{},
}
</script>
<style scoped>
.feedback-wrapper {
  padding-top: 20px;
}
.feedback-submit-btn {
  padding-bottom: 50px;
  text-align: center;
}
</style>