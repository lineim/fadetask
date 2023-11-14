<template>
  <div>
    <div
      class="clearfix mbs"
    >
      <a-button
        class="pull-right"
        icon="plus"
        type="primary"
        @click.stop="openCreateBoardModal"
      >
        {{ $t('kanban.create.label') }}
      </a-button>
    </div>
    <a-table
      :style="{background: '#fff'}"
      :columns="columns" 
      :data-source="kanbans"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.uuid"
      @change="tableChange"
    >
      <template
        slot="name"
        slot-scope="name, r, index"
      >
        <router-link :to="{name: 'KanbanDetail', params: {id: r.uuid}}">
          <a-avatar
            shape="square"
            size="large"
            :style="{backgroundColor: randColor(index)}"
          >
            {{ firstWord(name) }}
          </a-avatar>
          {{ name }}
        </router-link>
      </template>

      <template
        slot="total_task_count"
        slot-scope="kanban"
      >
        {{ kanban.overview.total }}
      </template>
      <template
        slot="overdue_task_count"
        slot-scope="kanban"
      >
        {{ kanban.overview.overdue }}
      </template>
      <template
        slot="done_task_count"
        slot-scope="kanban"
      >
        {{ kanban.overview.done }}
      </template>
      <template
        slot="action"
        slot-scope="kanban"
      >
        <a-button-group size="small">
          <a-button
            type="primary"
            @click.stop="goKanbanDetail(kanban.uuid)"
          >
            {{ $t('project.kanban.view_detail') }}
          </a-button>
          <a-popconfirm
            placement="left"
            :title="$t('project.kanban.remove_tips')"
            :ok-text="$t('yes')"
            :cancel-text="$t('no')"
            @confirm="rmKanban(kanban.uuid)"
          >
            <a-button
              type="danger"
            >
              {{ $t('project.kanban.remove') }}
            </a-button>
          </a-popconfirm>
          <!-- <a-button 
            @click.stop="closeKanban(kanban.id)" 
            type="danger"
          >
            关闭
          </a-button> -->
        </a-button-group>
      </template>
    </a-table>
    
    <CreateBoard
      v-if="showCreateBoardModal"
      :visible="showCreateBoardModal"
      :title="$t('board_nav.kanban.create_from')"
      :select-project="uuid"
      @close="closeCreateBoardModal"
    />
  </div>
</template>

<style>
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
</style>

<script>
import api from '@/api';
import i18n from '../../i18n';
import CreateBoard from "@/components/kanban/CreateBoard";
import { randColor } from '../../utils';

const columns = [
  {
    title: i18n.t('project.kanban.name'),
    key: 'name',
    dataIndex: 'name',
    scopedSlots: { customRender: 'name' },
  },
  {
    title: i18n.t('project.kanban.total_task_count'),
    scopedSlots: { customRender: 'total_task_count' },
    align: 'center',
    width: '15%',
  },
  {
    title: i18n.t('project.kanban.overdue_task_count'),
    scopedSlots: { customRender: 'overdue_task_count' },
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.kanban.finished_task_count'),
    scopedSlots: { customRender: 'done_task_count' },
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.kanban.handle'),
    key: 'action',
    scopedSlots: { customRender: 'action' },
    align: 'right',
    width: '15%',
  },
];

export default {
  components: {
    CreateBoard
  },
  props: {
  },
  data() {
    return {
      columns,
      project: {},
      kanbans: [],
      loading: false,
      pagination: {
        total: 0,
        pageSize: 10,
        current: 1,
      },
      flowTasks: {},
      currentFlowId: 0,
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
      sprintForm: this.$form.createForm(this, { name: 'coordinated' }),
      rangeConfig: {
        rules: [{ type: 'array', required: true, message: 'Please select time!' }],
      },
      taskFormVisible: false,
      showCreateBoardModal: false
    }
  },

  created() {
    this.init();
  },

  watch: {
    '$route': {
      handler() {
        this.init();
      },
      deep: true
    }
  },

  methods: {
    randColor,
    init() {
      this.uuid = this.$route.params.uuid;
      this.loadKanban();
    },
    loadKanban() {
      this.loading = true;
      const params = {
        page: this.pagination.current,
        pageSize: this.pagination.pageSize
      };
      api.projectKanban({query: {uuid: this.uuid}, params: params}).then(kanbans => {
        this.kanbans = kanbans;
        this.loading = false;
      });
    },
    tableChange() {
      this.loadKanban();
    },
    openCreateBoardModal() {
      this.showCreateBoardModal = true;
    },
    closeCreateBoardModal() {
      this.showCreateBoardModal = false;
    },
    goKanbanDetail(id) {
      this.$router.push({name: 'KanbanDetail', params: {id: id}});
    },
    
    rmKanban(id) {
      api.rmProjectKanban({query: {uuid: this.uuid}, data: {kanban_id: id}}).then(() => {
        this.$message.success(i18n.t('project.kanban.removed_tips'));
        this.loadKanban();
      });
    }
  }
}
</script>