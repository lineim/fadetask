<template>
  <div class="question-form-wrapper">
    <a-form
      :form="form"
      @submit="submitHandle"
    >
      <a-form-item label="名称">
        <a-input
          v-decorator="['name', {
            rules: [{ required: true, message: '请输入问卷名称' }]
          }]"
          placeholder="请输入问卷名称"
        />
      </a-form-item>

      <div
        v-for="(module, index) in modules"
        :key="index"
        style="border: 1px solid #e8e8e8; padding:15px; margin-bottom: 20px;"
      >
        <div style="text-align: right;">
          <a-popconfirm
            v-if="showDeleteModule"
            title="确认删除模块?"
            @confirm="deleteModule(index)"
            ok-text="是"
            cancel-text="否"
          >
            <a-button
              type="danger"
              size="small"
              shape="circle"
              icon="close"
            />
          </a-popconfirm>
        </div>
        <a-form-item label="模块名称">
          <a-input
            v-decorator="[`module_name_${index}`, {
              rules: [{ required: true, message: '请输入模块名称' }]
            }]"
            placeholder="请输入模块名称"
          />
        </a-form-item>
        <a-divider dashed />
        <a-row
          v-for="(question, key) in module.questions"
          :key="key"
          :gutter="16"
        >
          <a-col :span="11">
            <a-form-item
              :label="questionLable(key)"
              :label-col="{ span: 5 }"
              :wrapper-col="{ span: 19 }"
            >
              <a-input
                v-decorator="[`question_${index}_${key}`, {
                  rules: [{ required: true, message: '请输入问题' }]
                }]"
                placeholder=""
              />
            </a-form-item>
          </a-col>
          <a-col :span="10">
            <a-form-item
              label="备注"
              :label-col="{ span: 4 }"
              :wrapper-col="{ span: 20 }"
            >
              <a-input
                v-decorator="[`note_${index}_${key}`, {
                  rules: [{ message: '问题说明' }]
                }]"
              />
            </a-form-item>
          </a-col>
          <a-col :span="3">
            <a-popconfirm
              title="确认删除问题?"
              @confirm="deleteQuestion(index, key)"
              ok-text="是"
              cancel-text="否"
            >
              <a-button
                type="danger"
                size="small"
                shape="circle"
                icon="minus"
                style="margin-right: 10px;"
              />
            </a-popconfirm>
            <a-tooltip placement="topLeft">
              <template slot="title">
                <span>新增问题</span>
              </template>
              <a-button
                type="primary"
                size="small"
                shape="circle"
                icon="plus"
                @click="addQuestion(index)"
              />
            </a-tooltip>
          </a-col>
        </a-row>
      </div>
      <div
        :style="{
          position: 'absolute',
          left: 0,
          bottom: 0,
          width: '100%',
          borderTop: '1px solid #e9e9e9',
          padding: '10px 16px',
          background: '#fff',
          textAlign: 'right',
          zIndex: 10,
        }"
      >
        <a-form-item style="display: inline-block;">
          <a-button
            :style="{marginRight: '8px'}"
            @click="cacneled()"
          >
            取消
          </a-button>
          <a-button
            type="primary"
            html-type="submit"
          >
            提交
          </a-button>
        </a-form-item>
        <!-- <a-button @click="submitDrawer" type="primary">Submit</a-button> -->
      </div>
    </a-form>
    <a-button
      type="primary"
      icon="plus"
      block
      @click="addModule()"
      style="margin-bottom: 53px;"
    >
      新增模块
    </a-button>
  </div>
</template>
<script>
import api from '@/api'

let initModules =  [{
  questions: [{}]
}];

export default {
  props: [
    'questionnaireId', // 编辑或复制时传入
    'action', // add, edite or copy
  ], 
  data() {
    return {
      form: this.$form.createForm(this),
      questionnaire: {},
      modules: initModules,
    }
  },
  computed: {
     showDeleteModule() {
        return this.modules.length > 1;
      },
  },
  watch: {
    action: {
      immediate: true,
      handler (newAction) {
        this.form.resetFields();
        this.resetModule();
        if (newAction == 'add') {
          return ;
        }
        this.loadQuestionnaire();
      }
    },
    questionnaireId: {
      immediate: true,
      handler () {
        this.form.resetFields();
        this.resetModule();
        this.loadQuestionnaire();
      }
    }
  },
  methods: {
    questionLable(index) {
      return "问题" + (index + 1);
    },

    resetModule() {
      this.modules = [{
        questions: [{}]
      }];
    },

    loadQuestionnaire() {
      if (!this.questionnaireId) {
        return;
      }
      api.getQuestionnaire({query: {id: this.questionnaireId}}).then(res => {
        this.questionnaire = res;
        this.setQuestionnaireToForm();
      });
    },

    setQuestionnaireToForm() {
      this.resetModule();
      const sections = this.questionnaire.sections;
      let formField = {name: this.questionnaire.name};
      let tmpModules = [];

      for (let i = 0; i < sections.length; i ++) {
        let section = sections[i];
        tmpModules[i] = {questions: []};

        const moduleFieldName = "module_name_" + i;
        formField[moduleFieldName] = section.sectionName;
        
        let questions = section.questions;
        for (let k = 0; k < questions.length; k ++) {
          const question = questions[k];
          tmpModules[i].questions.push({});
          const questionFieldName = "question_" + i + "_" + k;
          const noteFieldName     = "note_" + i + "_" + k;
          formField[questionFieldName] = question.question;
          formField[noteFieldName]     = question.comment;
        }
      }
      this.modules = tmpModules;
      let self = this;
      setTimeout(function() {
        self.form.setFieldsValue(formField);
      }, 100);
    },

    submitHandle(e) {
      e.preventDefault();

      this.form.validateFields((err, values) => {
        if (err) {
          this.$message.error('请完善表单信息');
          return;
        }

        this.$confirm({
          title: "是否发布?",
          onOk: () => {
            if (this.action != 'edit') {
              return this.addQuestionnaire(values);
            }

            return this.updateQuestionnaire(values);
          },
          cancelText: "取消",
          okText: "确认"
        });
      });
    },
    
    addQuestionnaire(values) {
      return  api.addQuestionnaire({data: values}).then(res => {
        console.log(res);
        this.form.resetFields();
        this.$emit('submited');
      });
    },
    
    updateQuestionnaire(values) {
      return  api.updateQuestionnaire({query: {id: this.questionnaireId}, data: values}).then(res => {
          console.log(res);
          this.form.resetFields();
          this.$emit('submited');
        });
    },

    cacneled() {
      this.form.resetFields();
      this.$emit('canceled'); // 付组件通过v-on:canceled 监听当前组件canceled事件
    },

    addModule() {
      this.modules.push(
        {
          questions: [
            {}
          ]
        }
      );
    },

    deleteModule(index) {
      this.modules.splice(index, 1);
    },

    addQuestion(moduleIndex) {
      this.modules[moduleIndex].questions.push({});
    },

    deleteQuestion(moduleIndex, questionIndex) {
      console.log(this.form.getFieldsValue());
      if (this.modules[moduleIndex].questions.length <= 1) {
        this.$message.error('最后一个问题不能删除！');
        return;
      }
      this.modules[moduleIndex].questions.splice(questionIndex, 1);
    }
  }
}
</script>


<style scoped>
.question-form-wrapper {
  padding-bottom: 40px;
}
</style>