<template>
  <div>
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
                :to="{ name: 'KanbanDetail', params: { id: kanban.uuid }}"
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
</template>
<script>
import api from '@/api';
import store from '@/store';
import * as types from '@/store/mutation-types';
import { Bar } from 'vue-chartjs/legacy';
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

export default {
    components: {
        Bar,
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
        this.loadOverview();
    },

    beforeDestroy() {
    },

    watch: {
        '$route': {
          handler(to, from) {
            const toDepth = to.path.split('/').length
            const fromDepth = from.path.split('/').length
            this.transitionName = toDepth < fromDepth ? 'slide-right' : 'slide-left'
            const uuid = this.$route.params.uuid;
            this.projectUuid = uuid;
            this.loadOverview();
          },
          deep: true
        }
    },

    methods: {
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
        }
    }
}
</script>