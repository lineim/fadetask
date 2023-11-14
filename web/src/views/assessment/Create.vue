<template>
  <div class="wrapper">
    <p>创建评估</p>
    <a-form
      :form="form"
      @submit="handleSubmit"
    >
      <a-form-item
        :label-col="{ span: 2 }"
        :wrapper-col="{ span: 15 }"
        label="名称"
      >
        <a-input 
          v-decorator="[
            'assessmentName',
            { rules: [{ required: true, message: '请输入评估名称' }, {validator: limitAssessmentName}] },
          ]"
          placeholder="输入评估名称"
          style="width: 50%"
          @change="handleNameChange"
        />
      </a-form-item>

      <a-form-item
        :label-col="{ span: 2 }"
        :wrapper-col="{ span: 15 }"
        label="问卷"
      >
        <a-select
          v-decorator="[
            'questionnaireId',
            { rules: [{ required: true, message: '请选择问卷' }] },
          ]"
          show-search
          placeholder="请选择问卷"
          option-filter-prop="children"
          style="width: 50%"
          @change="handleQuestionChange"
        >
          <a-select-option 
            v-for="item in questionnaires.data.questionnaires" 
            :key="item.id"
            :value="item.id"
          >
            {{ item.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
        
      <a-form-item 
        :label-col="{ span: 2 }"
        :wrapper-col="{ span: 15 }"
        label="被评估人"
      >
        <a-select
          v-decorator="[
            'userIds',
            { rules: [{ required: true, message: '请选被评估人' }] },
          ]"
          show-search
          :allow-clear="true"
          placeholder="支持姓名，姓名拼音，左起模糊匹配"
          option-filter-prop="children"
          mode="multiple"
          style="width: 50%"
          @change="handleUsersChange"
          @search="handleUsersSearch"
          :filter-option="false"
        >
          <a-select-option
            v-for="user in usersData" 
            :key="user.id"
            :value="user.id"
          >
            <img
              v-if="user.avatarUrl"
              class="user-avatar"
              :src="user.avatarUrl"
              alt="用户图像"
            >
            <img
              v-else
              class="user-avatar"
              src="@/assets/default-avatar.png"
              alt="用户图像"
            >
            {{ user.truename }}{{ user.email }}
          </a-select-option>
        </a-select>
      </a-form-item>  

      <div class="checkbox-container">
        <a-form-item>
          <a-row type="flex">
            <a-col class="checkbox-container-item"> 
              <a-checkbox 
                @change="onChangeAssign"
                v-decorator="[
                  'isSelfAssign',
                  { rules: [{ required: false, message: '' }] },
                ]"
              >
                指定评估
              </a-checkbox>
            </a-col>

            <a-col :span="10"> 
              <span v-if="specified">创建人才可指定评估人</span>
              <span v-else>被评估人和创建人均可指定评估人</span>
            </a-col>
          </a-row>
        </a-form-item>

        <a-form-item>
          <a-row type="flex">
            <a-col class="checkbox-container-item"> 
              <a-checkbox 
                :checked="!!(submitInfo.isSelfAssessment)"
                v-decorator="[
                  'isSelfAssessment',
                  { rules: [{ required: false, message: '' }] },
                ]"
                @change="onChangeAssessment"
              >
                自我评估
              </a-checkbox>
            </a-col>

            <a-col :span="10"> 
              <span>被评估人参与自我评估</span>
            </a-col>
          </a-row>
        </a-form-item>

        <a-form-item>
          <a-row type="flex">
            <a-col class="checkbox-container-item"> 
              <a-checkbox 
                v-decorator="[
                  'isAutoSendResult',
                  { rules: [{ required: false, message: '' }] },
                ]"
                :default-checked="false"
                @change="onChangeSpecified"
              >
                自动发送结果
              </a-checkbox>
            </a-col>

            <a-col :span="10"> 
              <span>评估完成后，第一时间发送评估结果给被评估人</span>
            </a-col>
          </a-row>
        </a-form-item>  
      </div>

      <a-form-item>
        <div class="assessment-btn-group">
          <a-row type="flex">
            <a-col :span="2"> 
              <a-button @click="handleCancle">
                取消
              </a-button>
            </a-col>
            <a-col :span="2"> 
              <a-button
                type="primary"
                html-type="submit"
              >
                发布
              </a-button>
            </a-col>
          </a-row>
        </div>
      </a-form-item>
    </a-form>  
  </div>
</template>

<script>
import debounce from 'lodash/debounce';
import Api from '@/api';
import mixins from "@/mixins/personnel-selector";
const debounceTimer = 500;

export default {
  components: {},
  props: {},
  mixins: [mixins],
  data() {
    this.handleUsersSearch = debounce(this.handleUsersSearch, debounceTimer);

    return {
      specified: false,
      formLayout: 'horizontal',
      form: this.$form.createForm(this, { name: 'createAssessment' }),
      questionnaires: {
        data: {},
      },
      submitInfo: {
        isSelfAssign: 1,
        isSelfAssessment: 1,
        isAutoSendResult: 0,
        questionnaireId: '',
        assessmentName: '',
        userIds: [],
      },
    };
  },
  created() {
    this.fetchData();
  },
  computed: {

  },
  methods: {
    limitAssessmentName(rule, value, callback) {
      const len = 20;

      if(value && value.length > len) {
        callback(`名称不能超过${len}个字符`);
        return;
      }
      
      callback();
    },
    handleNameChange(e) {
      this.submitInfo.assessmentName = e.target.value;
    },
    handleQuestionChange(value) {
      this.submitInfo.questionnaireId = value;
    },
    handleUsersSearch(keyword) {
      this.getUsersData({
        keyword,
      });
    },   
    handleUsersChange(value) {
      this.submitInfo.userIds = value;
    }, 
    onChangeAssign(e) {
      this.submitInfo.isSelfAssign = e.target.checked ? 0 : 1;
      this.specified = Number(e.target.checked);
    },
    onChangeAssessment(e) {
      this.submitInfo.isSelfAssessment = Number(e.target.checked);
    },
    onChangeSpecified(e) {
      this.submitInfo.isAutoSendResult = Number(e.target.checked);
    },
    handleSubmit(e) {  
      e.preventDefault();

      this.form.validateFields((err) => {
        if (!err) {

          const data = JSON.parse(JSON.stringify(this.submitInfo));

          this.$confirm({
            title: "是否发布?",
            onOk: () => {
              return this.createAssessments(data);
            },
            cancelText: "取消",
            okText: "确认"
          });
        }
      });
 
    },
    createAssessments(data) {
      return Api.createAssessments({data}).then(res=> {
        console.log(res);
        this.$router.push({name: 'AssessmentPrepare'})
      })
    },
    handleCancle() {
      this.$router.go(-1);
    },
    loadQuestionnaires() {
      Api.getQuestionnaires().then(res => {
        this.questionnaires.data = res;
      });
    },
    fetchData() {
      this.getUsersData();
      this.loadQuestionnaires();
    }
  },
  watch: {}
};
</script>
<style scoped>
.checkbox-container {
  margin-left: 95px;
  margin-bottom: 100px;
}

.assessment-btn-group {
  margin-left: 100px;
}

.checkbox-container-item {
  min-width: 120px;
}

.user-avatar {
  width: 25px!important;
  height: 25px!important;
  border-radius: 50%;
}
</style>