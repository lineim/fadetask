<template>
  <div class="project">
    <div class="project-nav">
      <a-page-header
        @back="() => $router.push({name: 'ProjectList'})"
      >
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
        <a-tabs
          default-active-key="dashboard"
          v-model="tabKey"
          @change="tabChange"
        >
          <a-tab-pane
            key="dashboard"
            :tab="$t('project.detail.dashboard')"
          >
            <div class="mbl">
              <a-row
                :gutter="16"
                class="mbm"
              >
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_task')"
                    :value="overview.main_indicators.total"
                  />
                </a-col>
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_overdue_task_today')"
                    :value="0"
                  >
                    <span
                      style="color:rgb(245, 74, 69);"
                      slot="formatter"
                    >{{ overview.main_indicators.today_overdue }}</span>
                  </a-statistic>
                </a-col>
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_overdue_task')"
                  >
                    <span
                      style="color:rgb(245, 74, 69);"
                      slot="formatter"
                    >{{ overview.main_indicators.overdue }}</span>
                  </a-statistic>
                </a-col>
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_finished_task')"
                    :value="0"
                  >
                    <span
                      style="color: rgb(46, 161, 33)"
                      slot="formatter"
                    >{{ overview.main_indicators.done }}</span>
                  </a-statistic>
                </a-col>
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_kanban')"
                    :value="project.kanban_num"
                  />
                </a-col>
                <a-col :span="4">
                  <a-statistic
                    :title="$t('project.stats.total_member')"
                    :value="project.member_num"
                  />
                </a-col>
              </a-row>
            </div>
            <div>
              <a-row
                :gutter="16"
                class="mbm"
              >
                <a-col
                  :span="12"
                  class="mbl"
                >
                  <div class="chart-container">
                    <a-table
                      :columns="progressColumns"
                      :data-source="overview.progress"
                      :pagination="false"
                      :loading="overviewLoading"
                      :row-key="item => item.id"
                    > 
                      <template
                        slot="title"
                      >
                        <div
                          class="tac"
                          style="font-size: 14px; font-weight: bold;"
                        >
                          {{ $t('project.dashboard.kanban_progress') }}
                        </div>
                      </template>
                      <span
                        slot="name"
                        slot-scope="kanban"
                      >
                        <router-link
                          :to="{ name: 'KanbanDetail', params: { id: kanban.id }}"
                        >
                          {{ kanban.name }}
                        </router-link>
                      </span>
                      <a-progress
                        :stroke-color="{
                          '0%': '#108ee9',
                          '100%': '#87d068',
                        }"
                        slot="progress"
                        slot-scope="item"
                        :percent="item.total > 0 ? parseInt(item.finished/item.total*100) : 0"
                      />
                    </a-table>
                  </div>
                </a-col>

                <a-col
                  :span="12"
                  class="mbl"
                >
                  <div class="chart-container">
                    <Bar 
                      :chart-options="kanbanOverdueChartOptions"
                      :chart-data="kanbanOverdueChartData"
                    />
                  </div>
                </a-col>
                <a-col
                  :span="24"
                  class="mbl"
                >
                  <div class="chart-container">
                    <Bar 
                      :chart-options="memberLoadChartOptions"
                      :chart-data="memberLoadChartData"
                    />
                  </div>
                </a-col>

                <!-- <a-col :span="12" class="mbl">
                  <a-card
                    size="small"
                    title="吞吐量"
                  />
                </a-col> -->

                <a-col
                  :span="12"
                  class="mbl"
                >
                  <div class="chart-container">
                    <Bar 
                      :chart-options="priorityChartOptions"
                      :chart-data="priorityChartData"
                    />
                  </div>
                </a-col>
              </a-row>
            </div>
          </a-tab-pane>
          <a-tab-pane
            key="kanban"
            :tab="$t('project.detail.tab_kanban', {count: project.kanban_num})"
          >
            <KanbanList
              v-if="tabKey=='kanban'"
              :uuid="project.uuid"
            />
          </a-tab-pane>
          <a-tab-pane
            key="member"
            :tab="$t('project.detail.tab_member', {count: project.member_num})"
          >
            <MemberList
              v-if="tabKey=='member'"
              :uuid="project.uuid"
            />
          </a-tab-pane>
        </a-tabs>
      </a-page-header>
    </div>
    <CloseModal
      v-if="closeModalVisiable"
      :visiable="closeModalVisiable"
      :uuid="projectUuid"
      @close="closeModalClosed"
    />
  </div>
</template>
  
<script>
import api from '@/api';
import store from '@/store';
import * as types from '@/store/mutation-types';
import KanbanList from '@/components/project/KanbanList.vue';
import MemberList from '../../components/project/MemberList.vue';
import CloseModal from '@/components/project/CloseModal.vue';
import { Bar } from 'vue-chartjs/legacy';
import { TITLE_MAX_LEN, DESC_MAX_LEN } from '../../components/project';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js';
import i18n from '../../i18n';
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const progressColumns = [
  {
    title: '看板',
    key: 'name',
    scopedSlots: { customRender: 'name' },
    width: '30%'
  },
  {
    title: 'Total',
    dataIndex: 'total',
    key: 'total',
    align: 'center',
    width: '10%'
  },
  {
    title: 'Done',
    dataIndex: 'finished',
    key: 'finished',
    align: 'center',
    width: '10%'
  },
  {
    title: '进度',
    key: 'progress',
    scopedSlots: { customRender: 'progress' },
    align: 'center',
  },
];

const chartTitleStyle = {size: 14, weight: 'bold'};
const chartaxBarThickness = 40;
const tabKeys = ['dashboard', 'kanban', 'member'];

export default {
    components: {
        KanbanList,
        MemberList,
        Bar,
        CloseModal,
    },
    data() {
        return {
            projectUuid: '',
            project: {creator: {name: ''}},
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
            overviewLoading: true,
            overview: {
              progress: [],
              main_indicators: {
                total: 0,
                done: 0,
                overdue: 0
              }
            },
            progressColumns,
            memberLoadChartData: {
              labels: [],
              datasets: [
                {
                  label: '已过期',
                  data: [],
                  backgroundColor: '#f54a45',
                  maxBarThickness: chartaxBarThickness
                },
                {
                  label: '已完成',
                  data: [],
                  backgroundColor: '#2ea121',
                  maxBarThickness: chartaxBarThickness
                },
                {
                  label: 'Total',
                  data: [],
                  backgroundColor: '#1890ff',
                  maxBarThickness: chartaxBarThickness
                },
              ]
            },
            memberLoadChartOptions: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                title: {
                  display: true,
                  text: '成员负载',
                  font: chartTitleStyle
                },
              },
              scales: {
                x: {
                  stacked: true,
                },
                y: {
                  suggestedMin: 0,
                  ticks: {
                    // stepSize: 3
                  }
                }
              }
            },
            kanbanOverdueChartOptions: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                title: {
                  display: true,
                  text: '看板延期',
                  font: chartTitleStyle
                },
              },
              scales: {
                x: {
                  stacked: true,
                },
                y: {
                  suggestedMin: 0,
                  ticks: {
                    // stepSize: 3
                  }
                }
              }
            },
            kanbanOverdueChartData: {
              labels: [],
              datasets: [
                {
                  label: '已过期',
                  data: [],
                  backgroundColor: '#f54a45',
                  maxBarThickness: chartaxBarThickness
                },
                {
                  label: 'Total',
                  data: [],
                  backgroundColor: '#1890ff',
                  maxBarThickness: chartaxBarThickness
                },
              ]
            },

            priorityChartOptions: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                title: {
                  display: true,
                  text: '优先级分布',
                  font: chartTitleStyle
                },
              },
              scales: {
                x: {
                },
                y: {
                  suggestedMin: 0,
                  ticks: {
                    // stepSize: 3
                  }
                }
              }
            },

            priorityChartData: {
              labels: [],
              datasets: [
                {
                  label: '卡片优先级分布',
                  data: [10],
                  backgroundColor: [
                    'rgb(235, 91, 70)',
                    'rgb(255, 158, 25)',
                    'rgb(96, 189, 78)',
                    'rgb(178, 186, 197)'
                  ],
                  maxBarThickness: chartaxBarThickness
                }
              ]
            },

            chartId: "bar-chart",
            datasetIdKey: "label"
        }
    },

    mounted() {
        store.commit(types.CUR_PROJECT, {}); // 每次进入，重置当前project
        const uuid = this.$route.params.uuid;
        this.projectUuid = uuid;
        this.initTabkey();
        this.loadProject();
        this.loadOverview();
    },

    watch: {
        '$route': {
          handler(to, from) {
            const toDepth = to.path.split('/').length
            const fromDepth = from.path.split('/').length
            this.transitionName = toDepth < fromDepth ? 'slide-right' : 'slide-left'
            this.initTabkey();
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
        initTabkey() {
          const query = this.$route.query;
          this.tabKey = query.tab && tabKeys.includes(query.tab) ? query.tab : 'dashboard';
        },
        loadProject() {
          api.getProject({query: {uuid: this.projectUuid}}).then(project => {
            this.project = project;
            store.commit(types.CUR_PROJECT, project);
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