<template>
  <div
    class="phl"
    style="padding-bottom: 60px;"
  >
    <a-page-header
      style="padding-left: 0px; padding-right: 0px;"
      :title="$t('dashboard')"
      :sub-title="$t('stats.board.sub_title')"
    />
    <div
      class="d-flex flex-jc-sp flex-wap-flex"
    >
      <div class="chart-item bg-white">
        <LineChartGenerator
          :chart-data="kanbanCfd"
          :chart-options="kanbanCfdchartOptions"
          :chart-id="chartId"
          :dataset-id-key="'kanban-CFD'"
        />
      </div>
      <div class="chart-item bg-white">
        <Bar
          :chart-data="cardCountPerList"
          :chart-options="chartOptions"
          :chart-id="chartId"
          :dataset-id-key="datasetIdKey"
        />
      </div>
      <div class="chart-item bg-white">
        <Bar
          :chart-data="cardCountPerMember"
          :chart-options="chartOptions"
          :chart-id="chartId"
          :dataset-id-key="datasetIdKey"
        />
      </div>
      <div class="chart-item bg-white">
        <Bar
          :chart-data="cardCountPerLabel"
          :chart-options="chartOptions"
          :chart-id="chartId"
          :dataset-id-key="datasetIdKey"
        />
      </div>
      <div class="chart-item bg-white">
        <Bar
          :chart-data="cardCountDueDate"
          :chart-options="chartOptions"
          :chart-id="chartId"
          :dataset-id-key="datasetIdKey"
        />
      </div>
    </div>
  </div>
</template>
<script>
import api from "@/api";
import store from '@/store';
import i18n from '@/i18n';
import { mainColors } from "../../utils/colors";
import * as types from '@/store/mutation-types';
import { Bar, Line as LineChartGenerator} from 'vue-chartjs/legacy';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Filler
} from 'chart.js';
import { isInt, hexToRgb } from '../../utils';

ChartJS.register(
  Title, 
  Tooltip, 
  Legend, 
  BarElement, 
  LineElement, 
  PointElement, 
  CategoryScale, 
  LinearScale,
  Filler
);

const maxBarThickness = 40;

export default {
  components: {
    Bar,
    LineChartGenerator
  },
  data() {
    return {
      id: 0,
      kanbanCfd: {
        labels: [
        ],
        datasets: [
        ],
      },
      kanbanCfdchartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { // hover时展示所有列的数据
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            position: 'top',
          },
          title: {
            display: true,
            text: '卡片累积图'
          }
        },
        scales: {
          y: {
            stacked: true,
            ticks: {
              beginAtZero: true,
              callback: function(value) {if (value % 3 === 0) {return value;}}
            },
            title: {
              display: true,
              text: '卡片数'
            }
          }
        }
      },
      cardCountPerList: {
        labels: [
        ],
        datasets: [
          {
            label: '',
            backgroundColor: '#f87979',
            data: [],
            maxBarThickness: maxBarThickness
          }
        ]
      },
      cardCountPerMember: {
        labels: [
        ],
        datasets: [
          {
            label: '',
            backgroundColor: '#f87979',
            data: [],
            maxBarThickness: maxBarThickness
          }
        ]
      },
      cardCountPerLabel: {
        labels: [
        ],
        datasets: [
          {
            label: '',
            backgroundColor: [],
            data: [],
            maxBarThickness: maxBarThickness
          }
        ]
      },
      cardCountDueDate: {
        labels: [
        ],
        datasets: [
          {
            label: '',
            backgroundColor: '#f87979',
            data: [],
            maxBarThickness: maxBarThickness
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        scales: {
          y: {
            ticks: {
              beginAtZero: true,
              callback: function(value) {if (isInt(value)) {return value;}}
            },
            title: {
              display: true,
              text: '卡片数'
            }
          }
        }
      },
      chartId: "bar-chart",
      datasetIdKey: "label"
    };
  },

  watch: {
    '$route.path':function() {
      this.id = parseInt(this.$route.params.id);
      this.loadData(this.id);
    },

  },

  created() {
    store.commit(types.CUR_BOARD, {}); // 每次进入，重置当前board
    var id = this.$route.params.id;
    this.id = id;
    this.loadData(id);
  },

  methods: {
    loadData: function(id) {
      api.kanbanDashboard({query: {kanbanId: id}}).then(resp => {
        store.commit(types.CUR_BOARD, resp.kanban);
        if (typeof(resp.kanban.project.uuid) != 'undefined') {
          store.commit(types.CUR_PROJECT, resp.kanban.project);
        }
        let lists = resp.stats_task_count_per_list.labels;
        for (const index in lists) {
          this.cardCountPerList.labels.push(i18n.t(lists[index]));
        }
        this.cardCountPerList.datasets[0].data = resp.stats_task_count_per_list.data;
        this.cardCountPerList.datasets[0].label = i18n.t(resp.stats_task_count_per_list.label_name);
        
        let members = resp.stats_task_count_per_member.labels;
        for (const index in members) {
          this.cardCountPerMember.labels.push(i18n.t(members[index]));
        }
        this.cardCountPerMember.datasets[0].data = resp.stats_task_count_per_member.data;
        this.cardCountPerMember.datasets[0].label = i18n.t(resp.stats_task_count_per_member.label_name);

        let labels = resp.stats_task_count_per_label.labels;
        for (const index in labels) {
          this.cardCountPerLabel.labels.push(i18n.t(labels[index]));
        }
        this.cardCountPerLabel.datasets[0].data = resp.stats_task_count_per_label.data;
        this.cardCountPerLabel.datasets[0].label = i18n.t(resp.stats_task_count_per_label.label_name);
        this.cardCountPerLabel.datasets[0].backgroundColor = resp.stats_task_count_per_label.label_colors;

        let duedates = resp.stats_task_count_due_date.labels;
        for (const index in duedates) {
          this.cardCountDueDate.labels.push(i18n.t(duedates[index]));
        }
        this.cardCountDueDate.datasets[0].data = resp.stats_task_count_due_date.data;
        this.cardCountDueDate.datasets[0].label = i18n.t(resp.stats_task_count_due_date.label_name);

        // cfd
        this.kanbanCfd.labels = resp.stats_kanban_cfd_data.labels;
        let datasets = [];
        let opacity = 1;
        for (const index in resp.stats_kanban_cfd_data.datasets) {
          // color不够用时，采用减轻透明度，重复利用main color；只处理一次是因为列表太多，看板本身就难跟踪, cfd图就失去了意义。
          // 这里能处理18个列的颜色
          if (index > mainColors.length - 1) {
            opacity = 0.8;
          }
          let dataset = resp.stats_kanban_cfd_data.datasets[index];
          dataset.fill = true;
          const rgbColor = hexToRgb(mainColors[index%9], opacity);
          dataset.backgroundColor = rgbColor;
          dataset.borderColor = rgbColor;
          datasets.push(dataset);
        }
        this.kanbanCfd.datasets = datasets;

      }).catch(e => {
        console.error(e);
      });
    },

    goBack: function() {
      this.$router.push({ name: 'KanbanDetail', params: { id: this.id }});
    }
  }
    
}
</script>

<style scoped>
.chart-item {
  /* width: calc(50%  -  30px); */
  flex: 0 1 48%;
  padding: 15px 20px;
  margin-bottom: 36px;
  border-radius: 5px;
  box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
}
</style>