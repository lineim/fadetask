<template>
  <div>
    <p class="search-form">
      <a-row
        type="flex"
        align="middle"
      >
        <a-col :span="8">
          <a-row
            type="flex"
            align="middle"
          >
            <a-col :span="5">
              <span class="search-form-label">部门：</span>
            </a-col>
            <a-col :span="18">
              <div class="org-select">
                <a-tree-select
                  style="width: 100%; height: 30px;"
                  :tree-data="orgTreeData"
                  :value="value"
                  @change="handleTreeChange"
                  :show-checked-strategy="SHOW_ALL"
                  tree-checkable
                  :max-tag-count="maxTagCount"
                  :max-tag-placeholder="maxTagPlaceholder"
                  :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
                  search-placeholder="Please select"
                />
              </div>
            </a-col>
          </a-row>
        </a-col>

        <a-col :span="8">
          <a-row
            type="flex"
            align="middle"
          >
            <a-col :span="5">
              <span class="search-form-label">评估名称：</span>
            </a-col>
            <a-col :span="18">
              <a-input
                placeholder="输入评估名称"
                style
                v-model="search.assessmentName"
              />
            </a-col>
          </a-row>
        </a-col>
      </a-row>

      <a-row
        type="flex"
        align="middle"
      >
        <a-col :span="8">
          <a-row
            type="flex"
            align="middle"
          >
            <a-col :span="5">
              <span class="search-form-label">被评估人：</span>
            </a-col>
            <a-col :span="18">
              <a-select
                show-search
                allow-clear
                placeholder="请选择被评估人"
                option-filter-prop="children"
                style="width: 100%"
                @change="value => handleChange(value, 'userIds')"
                @search="searchUser"
                @select="onSelect"
                :filter-option="false"
              >
                <a-select-option
                  v-for="(item, idx) in users"
                  :key="idx"
                  :value="item.id"
                >
                  <img
                    v-if="item.avatarUrl"
                    class="user-avatar"
                    :src="item.avatarUrl"
                    alt="用户图像"
                  >
                  <img
                    v-else
                    class="user-avatar"
                    src="@/assets/default-avatar.png"
                    alt="用户图像"
                  >
                  {{ item.truename }}{{ item.email }}
                </a-select-option>
              </a-select>
            </a-col>
          </a-row>
        </a-col> 
        <a-col :span="8">
          <a-row
            type="flex"
            align="middle"
          >
            <a-col :span="5">
              <span class="search-form-label">创建人：</span>
            </a-col>
            <a-col :span="18">
              <a-select
                show-search
                allow-clear
                placeholder="请选择创建人"
                option-filter-prop="children"
                style="width: 100%"
                @change="value => handleChange(value, 'createdUserIds')"
                @search="searchUser"
                @select="onSelect"
                :filter-option="false"
              >
                <a-select-option
                  v-for="(item, idx) in users"
                  :key="idx"
                  :value="item.id"
                >
                  <img
                    v-if="item.avatarUrl"
                    class="user-avatar"
                    :src="item.avatarUrl"
                    alt="用户图像"
                  >
                  <img
                    v-else
                    class="user-avatar"
                    src="@/assets/default-avatar.png"
                    alt="用户图像"
                  >
                  {{ item.truename }}{{ item.email }}
                </a-select-option>
              </a-select>
            </a-col>
          </a-row>
        </a-col>
        <a-col :span="2">
          <a-row type="flex">
            <a-button
              @click="handlesearch"
              type="primary"
            >
              搜索
            </a-button>
          </a-row>
        </a-col>
        <slot name="btn-group" />
      </a-row>
    </p>
  </div>
</template>
<script>
import debounce from "lodash/debounce";
import { TreeSelect } from "ant-design-vue";
import Api from "@/api";
import mixins from "@/mixins/personnel-selector";

const debounceTimer = 500;
const SHOW_ALL = TreeSelect.SHOW_ALL;


export default {
  data() {
    this.searchUser = debounce(this.searchUser, debounceTimer);
    return {
      value: [],
      SHOW_ALL,
      maxTagCount: 3,
      users: [],
      search: {
        userIds: [], // 被反馈人
        createdUserIds: [], // 发起人
        orgIds: [], // 部门
        assessmentName: "", // 评估名称
        statusList: [], // 状态
        displayFeedbackUsers: 1,
        offset: 0,
        limit: 10,
      },
      username: "",
    };
  },
  created() {
    this.fetchData();
  },
  props: {
    has_status: {
      type: Boolean,
      default: function() {
        return false;
      },
    },
  },
  mixins: [mixins],
  mounted() {},
  computed: {},
  methods: {
    handlesearch() {
      const search = JSON.parse(JSON.stringify(this.search));
      
      this.$emit('handlesearch', search);
    },
    changeStatus(value) {
      this.search.statusList = [];
      if(value) {
        this.search.statusList.push(value);

        return;
      }

      Reflect.deleteProperty(this.search, 'statusList');
    },
    handleChange(id, type) {
      this.search[type] = [];
      if(id && this.search[type].indexOf(id) === -1) {
        this.search[type].push(id);
      
        return;
      }   

      Reflect.deleteProperty(this.search, type);
    },
    handleTreeChange(value) {
      this.value = value;
      this.search.orgIds = [...this.value];
    },
    maxTagPlaceholder() {
      return "...";
    },
    searchUser(value) {
      value = value === "" ? -1 : value;
      this.username = value;
      if (this.username === value) {
        Api.getUsers({
          params: {
            keyword: this.username
          }
        }).then(res => {
          this.users = res;
        });
      }
    },
    fetchData() {
      this.getTreeData();
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

.user-avatar {
  width: 25px!important;
  height: 25px!important;
  border-radius: 50%;
}

</style>