<template>
  <div class="wrapper">
    <p>填写评估人</p>
    <a-form
      :form="form"
      @submit="handleSubmit"
    >
      <a-form-item 
        :label-col="{ span: 3 }"
        :wrapper-col="{ span: 15 }"
        label="上级评估者"
      >
        <a-select
          v-decorator="[
            'upUserIds',
            { 
              rules: [{ required: false, message: '请选择上级评估者' }],
            },
          ]"
          show-search
          :allow-clear="true"
          placeholder="支持姓名，姓名拼音，左起模糊匹配"
          option-filter-prop="children"
          mode="multiple"
          style="width: 50%"
          @change="handleRelationUserChange($event, 'up')"
          @search="handleRelationUserSearch($event, 'up')"
          :filter-option="false"
        >
          <a-select-option
            v-for="user in upUsersData.data" 
            :key="user.userId"
            :value="user.userId"
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

      <a-form-item 
        :label-col="{ span: 3 }"
        :wrapper-col="{ span: 15 }"
        label="协作评估者"
      >
        <a-select
          v-decorator="[
            'sameUserIds',
            { 
              rules: [{ required: false, message: '请选择协作评估者' }],
            },
          ]"
          show-search
          :allow-clear="true"
          placeholder="支持姓名，姓名拼音，左起模糊匹配"
          option-filter-prop="children"
          mode="multiple"
          style="width: 50%"
          @change="handleRelationUserChange($event, 'same')"
          @search="handleRelationUserSearch($event, 'same')"
          :filter-option="false"
        >
          <a-select-option
            v-for="user in sameUsersData.data" 
            :key="user.userId"
            :value="user.userId"
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

      <a-form-item 
        :label-col="{ span: 3 }"
        :wrapper-col="{ span: 15 }"
        label="下级评估者"
      >
        <a-select
          v-decorator="[
            'downUserIds',
            { 
              rules: [{ required: false, message: '请选择下级评估者' }],
            },
          ]"
          show-search
          :allow-clear="true"
          placeholder="支持姓名，姓名拼音，左起模糊匹配"
          option-filter-prop="children"
          mode="multiple"
          style="width: 50%"
          @change="handleRelationUserChange($event, 'down')"
          @search="handleRelationUserSearch($event, 'down')"
          :filter-option="false"
        >
          <a-select-option
            v-for="user in downUsersData.data" 
            :key="user.userId"
            :value="user.userId"
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
    </a-form>  
    
    <div class="assessment-btn-group">
      <a-row type="flex">
        <a-col :span="2"> 
          <a-button @click="handleCancle">
            取消
          </a-button>
        </a-col>

        <a-col :span="2"> 
          <a-button @click="handleSave">
            保存
          </a-button>
        </a-col>

        <a-col :span="2"> 
          <a-button
            type="primary"
            @click="handleSubmit"
          >
            发布
          </a-button>
        </a-col>
      </a-row>
    </div>
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
    this.handleRelationUserSearch = debounce(this.handleRelationUserSearch, debounceTimer);

    return {
      form: this.$form.createForm(this, { name: 'editorAssessment' }),
      submitInfo: {
        isPublished: 0,
        feedbackUsers: [],
      },
      upUsersData: {
        users: [],
        data: [],
        initialValue: [],
      },
      sameUsersData: {
        users: [],
        data: [],
        initialValue: [],
      },
      downUsersData: {
        users: [],
        data: [],
        initialValue: [],
      },
      minNumInfo: {
        num: 1,
      }
    };
  },
  created() {
    this.fetchData();
  },
  mounted() {},
  computed: {},
  methods: {
    handleRelationUserSearch(keyword, type) {
      this.getUsersData({
        keyword,
      }).then(res=> {
        this[`${type}UsersData`].data = res;
      });
    },
    handleRelationUserChange(value, type) {
      this.setRelationshipUserData(value, type);
    },
    setRelationshipUserData(arr, type) {
      this[`${type}UsersData`].users.length = 0;

      if(arr && arr.length) {
        for(let i = 0; i<arr.length; i++) {
          this[`${type}UsersData`].users.push({
            userId: arr[i].key ? arr[i].key : arr[i] ,
            relationship: type.toLocaleUpperCase(),
          })
        }
      }
    },
    handleSubmit(e) {
      e.preventDefault();
      
      this.formValidateFields(1);
    },
    handleCancle() {
      this.$router.go(-1);
    },
    handleSave() {
      this.formValidateFields();
    },
    formValidateFields(isPublished = 0) {
      this.form.validateFields((err) => {
        if (!err) {
          const feedbackUsers = [...this.upUsersData.users, ...this.sameUsersData.users, ...this.downUsersData.users];
          this.submitInfo.feedbackUsers = JSON.parse(JSON.stringify(feedbackUsers));
          this.submitInfo.isPublished = isPublished;
          const data = JSON.parse(JSON.stringify(this.submitInfo));
          const { assessmentId } = this.$route.params;

          if(data.feedbackUsers.length < this.minNumInfo.num) {

            this.$message.info('至少选择一个评估者');
            return;
          }

          if(isPublished) {
            this.$confirm({
              title: "是否发布?",
              onOk: () => {
                return this.setAssessmentUsers(data, assessmentId);
              },
              cancelText: "取消",
              okText: "确认"
            });

            return;
          }

          this.setAssessmentUsers(data, assessmentId);
        }
      });
    },
    setAssessmentUsers(data, assessmentId) {
      const promise = Api.mySetAssessmentUsers({
        data,
        query: {
          assessmentId,
        }
      }).then(res=> {
        if (res) {
          this.$router.push({name: 'myAssessmentPrepare'})
        } else {
          this.$message.warning('操作失败！');
        }
      }).catch(() => {
        this.$message.error('操作失败！');
      });

      return promise;
    },
    loadAssessmentUsers() {
      Api.myGetAssessmentsUsers({
        query: {
          assessmentId: this.$route.params.assessmentId,
        }
      }).then(res=> {
        this.relationshipType(res);
      })
    },
    relationshipType(data) {
      if(!data) {
        return;
      }

      data.map(item=> {
        const type = item.relationship.toLocaleLowerCase();

        this[`${type}UsersData`].data.push(item);
        this[`${type}UsersData`].initialValue.push(item.userId);

        this[`${type}UsersData`].users.push({
          userId: item.userId,
          relationship: item.relationship,
        });
      })

      this.form.setFieldsValue({
        upUserIds: this.upUsersData.initialValue,
        sameUserIds: this.sameUsersData.initialValue,
        downUserIds: this.downUsersData.initialValue,
      });
    },
    fetchData() {
      this.loadAssessmentUsers();
    }
  },
  watch: {}
};
</script>
<style scoped>
.editor-assessment-item {
  margin-bottom: 20px;
  margin-left: 20px;
}

.editor-assessment-lable {
  display: inline-block;
  width: 100px;
  padding-right: 10px;
}

.editor-assessment-lable span {
  color: brown;
}

.checkbox-container {
  margin-left: 95px;
  margin-bottom: 100px;
}

.assessment-btn-group {
  margin-top: 100px;
  margin-left: 100px;
}

.user-avatar {
  width: 25px!important;
  height: 25px!important;
  border-radius: 50%;
}
</style>