<template>
  <a-layout class="project">
    <a-layout-sider
      class="project-layout-sider"
      collapsible
      :trigger="null"
      v-model="collapsed"
      :style="{
        display: 'flex', 
        height: '100%',
        minHeight: '100%',
        flex: '0 0 200px',
        flexDirection: 'row-reverse',
        zIndex: 5,
        transform: 'translate3d(0%, 0, 0)',
        transition: 'transform 100ms ease-in',
        backgroundColor: '#fff'
      }"
    >
      <ProjectMenu
        :project="project"
        :collapsed="collapsed"
      />
      <span
        class="collapsed-trigger"
        :style="{right: collapsed ? '-12px' : '-12px'}"
      >
        <a-button 
          shape="circle" 
          size="small" 
          :icon="collapsed ? 'double-right' : 'double-left'" 
          @click.stop="() => (collapsed = !collapsed)" 
        />
      </span>
    </a-layout-sider>
    <a-layout-content>
      <div class="project">
        <a-page-header>
          <span
            class="ant-page-header-heading-title"
            slot="title"
          >
            <span v-if="!showEditTitleForm">
              {{ project.name }}
              <a
                class="edit-icon"
                v-if="project.is_manager"
              ><a-icon
                type="edit"
                @click.stop="() => { editTitle = project.name; showEditTitleForm = true; }"
              /></a>
            </span>
            <span 
              class="d-inline-block" 
              style="width: 460px;" 
              v-else
            >
              <a-input
                v-model="editTitle"
                class="mrl"
                style="width: 260px"
              />
              <a-button
                class="mrm"
                @click.stop="() => {showEditTitleForm = false;}"
              >{{ $t('cancel') }}</a-button>
              <a-button 
                type="primary" 
                :loading="titleSubmiting"
                @click.stop="submitTitle"
                :disabled="editTitle.length < 1"
              >
                {{ $t('submit') }}
              </a-button>
            </span>
          </span>
  
          <template
            v-if="project.is_manager"
            slot="extra"
          >
            <a-button
              type="danger"
              @click.stop="showCloseModal"
            >
              {{ $t('project.close') }}
            </a-button>
          </template>
          <div class="content">
            <div class="main project-detail-content">
              <a-descriptions
                size="small"
                :column="1"
              >
                <a-descriptions-item :label="$t('project.detail.description')">
                  <span v-if="!showEditDescForm">
                    {{ project.description|defalut('无') }}
                    <a
                      class="edit-icon"
                      v-if="project.is_manager"
                    ><a-icon
                      type="edit"
                      @click.stop="() => { editDesc = project.description; showEditDescForm = true; }"
                    /></a>
                  </span>
                  <span v-else>
                    <a-input
                      :placeholder="$t('project.create.description_placeholder')"
                      class="mrl"
                      v-model="editDesc"
                      style="width: 460px;"
                    />
                    <a-button
                      class="mrm"
                      @click.stop="() => {showEditDescForm = false;}"
                    >{{ $t('cancel') }}</a-button>
                    <a-button 
                      type="primary" 
                      :loading="descSubmiting"
                      @click.stop="submitDesc"
                      :disabled="editDesc.length < 1"
                    >
                      {{ $t('submit') }}
                    </a-button>
                  </span>
                </a-descriptions-item>
              </a-descriptions>
              <a-descriptions
                size="small"
                :column="2"
              >
                <a-descriptions-item :label="$t('project.detail.creator')">
                  <a>{{ project.creator.name }}</a>
                </a-descriptions-item>
                <a-descriptions-item :label="$t('project.detail.create_time')">
                  <a v-if="project.created_date">{{ project.created_date|friendlyTime(0) }}</a>
                </a-descriptions-item>
              </a-descriptions>
            </div>
          </div>
          <a-divider />
          <router-view />
        </a-page-header>
        <CloseModal
          v-if="closeModalVisiable"
          :visiable="closeModalVisiable"
          :uuid="projectUuid"
          @close="closeModalClosed"
        />
      </div>
    </a-layout-content>
  </a-layout>
</template>
    
<script>
import api from '@/api';
import store from '@/store';
import { mapActions } from 'vuex';
import * as types from '@/store/mutation-types';
import CloseModal from '@/components/project/CloseModal.vue';
import ProjectMenu from '@/components/project/ProjectMenu.vue';
import { TITLE_MAX_LEN, DESC_MAX_LEN } from '../../components/project';
import i18n from '../../i18n';
const tabKeys = ['dashboard', 'kanban', 'member'];

export default {
  components: {
    CloseModal,
    ProjectMenu
  },
  data() {
    return {
      projectUuid: '',
      collapsed: false,
      project: {name: '', creator: {name: ''}},
      flows: [],
      flowTasks: {},
      currentFlowId: 0,
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
      sprintForm: this.$form.createForm(this, { name: 'coordinated' }),
      rangeConfig: {
        rules: [{ type: 'array', required: true, message: 'Please select time!' }],
      },
      taskFormVisible: false,
      closeModalVisiable: false,
      tabKey: 'dashboard',
      showEditTitleForm: false,
      editTitle: '',
      showEditDescForm: false,
      editDesc: '',
      descSubmiting: false,
      titleSubmiting: false,
    }
  },

  mounted() {
      store.commit(types.CUR_PROJECT, {}); // 每次进入，重置当前project
      const uuid = this.$route.params.uuid;
      this.projectUuid = uuid;
      this.initTabkey();
      this.getProject();
  },

  beforeDestroy() {
  },

  watch: {
      '$route': {
        handler(to, from) {
          const toDepth = to.path.split('/').length
          const fromDepth = from.path.split('/').length
          this.transitionName = toDepth < fromDepth ? 'slide-right' : 'slide-left'
          this.initTabkey();
          const uuid = this.$route.params.uuid;
          this.projectUuid = uuid;
          this.initTabkey();
          this.getProject();
        },
        deep: true
      },
      editTitle: {
        handler: function() {
          if (this.editTitle.length < 1) {
            this.$message.info(i18n.t('project.name_requeired_msg'));
          }
          if (this.editTitle.length > TITLE_MAX_LEN) {
            this.editTitle = this.editTitle.substring(0, TITLE_MAX_LEN);
            this.$message.info(i18n.t('project.name_too_long_tips', {max: TITLE_MAX_LEN}));
          }
        }
      },
      editDesc: {
        handler: function() {
          if (this.editDesc.length > DESC_MAX_LEN) {
            this.editDesc = this.editDesc.substring(0, DESC_MAX_LEN);
            this.$message.info(i18n.t('project.description_too_long_tips', {max: DESC_MAX_LEN}));
          }
        }
      }
  },

  methods: {
    ...mapActions([
        'loadProject'
    ]),
      initTabkey() {
        const query = this.$route.query;
        this.tabKey = query.tab && tabKeys.includes(query.tab) ? query.tab : 'dashboard';
      },
      getProject() {
        const data = {query: {uuid: this.projectUuid}};
        this.loadProject({data: data}).then(project => {
          this.project = project;
        }).catch(() => {});
      },

      loadOverview() {
        api.projectOverview({query: {uuid: this.projectUuid}}).then(data => {
          this.overviewLoading = false;
          this.overview = data;
          this.processLoadData(data.member_load);
          this.processKanbanOverdueData(data.kanban_overdue);
          this.processPriorityData(data.priority_distribution);
        }).catch(() => {
          this.overviewLoading = false;
        });
      },
      processLoadData(memberLoad) {
        let labels = [], totalData = [], doneData = [], overdueData = [];
        for (const item of memberLoad) {
          labels.push(item.name);
          totalData.push(item.load.total);
          doneData.push(item.load.done);
          overdueData.push(item.load.overdue);
        }
        this.memberLoadChartData.labels = labels;
        this.memberLoadChartData.datasets[0].data = overdueData;
        this.memberLoadChartData.datasets[1].data = doneData;
        this.memberLoadChartData.datasets[2].data = totalData;

      },
      processKanbanOverdueData(kanbanOverdueData) {
        let labels = [], total = [], overdue = [];
        for (const item of kanbanOverdueData) {
          labels.push(item.name);
          total.push(item.total);
          overdue.push(item.overdue);
        }
        this.kanbanOverdueChartData.labels = labels;
        this.kanbanOverdueChartData.datasets[0].data = overdue;
        this.kanbanOverdueChartData.datasets[1].data = total;
      },
      processPriorityData(data) {
        let labels = [], priorityData = [];
        for (const item of data) {
          labels.push(i18n.t(item.name));
          priorityData.push(item.total);
        }
        this.priorityChartData.labels = labels;
        this.priorityChartData.datasets[0].data = priorityData;
      },
      submitTitle() {
        if (this.editTitle.length < 1) {
          this.$message.error(i18n.t('project.name_requeired_msg'));
          return;
        }
        if (this.editTitle.length > TITLE_MAX_LEN) {
          this.$message.error(i18n.t('project.name_too_long_tips', {max: TITLE_MAX_LEN}));
          return;
        }
        this.titleSubmiting = true;
        const data = {name: this.editTitle};
        api.updateProject({query: {uuid: this.projectUuid}, data: data}).then((project) => {
          this.project.name = project.name;
          this.titleSubmiting = false;
          this.showEditTitleForm = false;
        }).catch(() => {
          this.titleSubmiting = false;
        });
      },
      submitDesc() {
        if (this.editDesc.length > DESC_MAX_LEN) {
          this.$message.error(i18n.t('project.description_too_long_tips', {max: DESC_MAX_LEN}));
          return;
        }
        this.descSubmiting = true;
        const data = {desc: this.editDesc};
        api.updateProject({query: {uuid: this.projectUuid}, data: data}).then((project) => {
          this.project.description = project.description;
          this.descSubmiting = false;
          this.showEditDescForm = false;
        }).catch(() => {
          this.descSubmiting = false;
        });
      },
      tabChange(key) {
        this.tabKey = key;
      },
      showCloseModal() {
        this.closeModalVisiable = true;
      },
      closeModalClosed() {
        this.closeModalVisiable = false;
      },
      formCanceled() {
          this.taskFormVisible = false;
      },
      formSubmited(task) {
          console.log(task);
          this.taskFormVisible = false;
      }
  }
}
</script>
  
  <style scoped>
  .col {
    background-color: rgb(241, 243, 245);
    padding-right: 8px;
    padding-left: 8px;
    padding-top: 10px;
    padding-bottom: 10px;
    border-radius: 5px;
  }

  .project-layout-sider {
    height: 100%;
  }
  .col-title {
    text-align: center;
  }
  
  .chart-container {
    border-color: #e8e8e8;
    border-width: 1px;
    border-style: solid;
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
  }
  .project {
    background-color: #fff;
    height: 100%;
    position: relative;
    overflow-y: auto;
    padding-bottom: 40px;
  }
  
  .edit-icon {
    display: none;
  }
  .ant-page-header-heading:hover .edit-icon,
  .project-detail-content:hover .edit-icon 
   {
    display: inline-block;
  }
  
  tr:last-child td {
    padding-bottom: 0;
  }
  .content {
    display: flex;
  }
  .ant-statistic-content {
    font-size: 20px;
    line-height: 28px;
  }
  @media (max-width: 576px) {
    .content {
      display: block;
    }
  
    .main {
      width: 100%;
      margin-bottom: 12px;
    }
  
    .extra {
      width: 100%;
      margin-left: 0;
      text-align: left;
    }
  }
  
  </style>