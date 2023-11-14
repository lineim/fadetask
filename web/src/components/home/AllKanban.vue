<template>
  <div class="full-height kanban-items">
    <a-page-header
      style="padding-left: 0px; padding-right: 0px;"
      :title="$t('home.title_my_kanban')"
    >
      <template slot="extra">
        <a-button
          type="primary"
          @click="openCreateBoardModal"
        >
          <a-icon type="plus" />{{ $t('kanban.create.label') }}
        </a-button>
      </template>
    </a-page-header>
    <div class="d-flex flex-left flex-wap-flex mbxl">
      <a-input-search
        placeholder="输入关键词"
        v-model="keyword"
        enter-button
        @search="loadKanbans"
        style="max-width: 350px;"
      />
    </div>
    
    <a-table
      :columns="progressColumns"
      :data-source="kanbans"
      :pagination="false"
      :loading="loading"
      :row-key="item => item.id"
    > 
      <span
        slot="name"
        slot-scope="kanban, r, index"
      >
        <router-link
          :to="{ name: 'KanbanDetail', params: { id: kanban.uuid }}"
        >
          <a-avatar
            shape="square"
            size="large"
            :style="{backgroundColor: randColor(index)}"
          >
            {{ firstWord(kanban.name) }}
          </a-avatar>
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
      <template
        slot="action"
        slot-scope="item"
      >
        <a-button-group>
          <a-button
            size="small"
            @click="gotoDetail(item.uuid)"
            type="primary"
          >
            {{ $t('详情') }}
          </a-button>
        </a-button-group>
      </template>
    </a-table>

    <CreateBoard
      v-if="showCreateBoardModal"
      :visible="showCreateBoardModal"
      @close="closeCreateBoardModal"
      :title="$t('kanban.create.label')"
    />
  </div>
</template>
<script>
import api from '@/api';
import i18n from '../../i18n';
import CreateBoard from "@/components/kanban/CreateBoard";
import { randColor } from '../../utils';

const progressColumns = [
  {
    title: '看板',
    key: 'name',
    scopedSlots: { customRender: 'name' },
    width: '30%'
  },
  {
    title: '总卡片数',
    dataIndex: 'total',
    key: 'total',
    align: 'center',
    width: '10%'
  },
  {
    title: '已完成',
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
  {
    title: i18n.t('操作'),
    key: 'action',
    scopedSlots: { customRender: 'action' },
    align: 'right',
    width: '10%',
  },
];

export default {
  components: {
    CreateBoard: CreateBoard,
  },

  data() {
    return {
      kanbans: [],
      showCreateBoardModal: false,
      loading: false,
      keyword: '',
      progressColumns,
    };
  },
  mounted() {
    this.loadKanbans();
  },
  created() {
    
  },
  methods: {
    randColor,
    loadKanbans: function() {
      this.loading = true;
      api.kanbanBoard({params: {keyword: this.keyword}}).then(res => {
        this.kanbans = res;
        this.loading = false;
      }).catch(err => {
        this.loading = false;
        this.$message.error(err.message);
      });
    },

    todoPageChange: function() {
      this.loadMeTodo;
    },

    closeKanban: function(id) {
      api.kanbanClose({ query: { id: id } }).then(() => {
        this.loadData();
        this.$message.success('已关闭');
      });
    },

    resetForm() {
      this.$refs.kanban_form.resetFields();
      this.showKanbanForm = false;
    },

    gotoDetail: function(id) {
      this.$router.push({ name: 'KanbanDetail', params: {id}})
    },

    openCreateBoardModal() {
      this.showCreateBoardModal = true;
    },

    closeCreateBoardModal() {
      this.showCreateBoardModal = false;
    },

    gotoCard: function(card) {
      let url = '/#/kanban/' + card.kanban_id + '?card_id=' + card.id;
      window.location.href = url;
    }
  }
}
</script>

<style scoped>
.board {
  margin-left: auto;
  margin-right: auto;
}

.kanban-items {
  background-color: #fff;
  padding: 16px 26px 0;
  height: 100%;
  position: relative;
  padding-bottom: 64px;
  overflow-y: auto;
}
.kanban-item {
  text-align: center;
  font-size: 14px;
  font-weight: bold;
  color: #fff;
}

.kanban-item .ant-card {
  border-radius: 3px;
}

.kanban-item-card:hover {
  box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
}

</style>