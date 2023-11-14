<template>
  <div class="kanban-items full-height">
    <a-page-header
      style="padding-left: 0px; padding-right: 0px;"
      :title="$t('home.title_closed_kanban')"
    />
    <a-list
      item-layout="horizontal"
      :data-source="kanbans"
      :grid="{ gutter: 16, column: 6 }"
    >
      <a-list-item
        slot="renderItem"
        slot-scope="kanban"
        class="kanban-item"
      >
        <a-card
          class="kanban-item-card"
          :bordered="false"
          :style="{'background': getRandColor(kanban.id)}"
          :body-style="{'white-space': 'nowrap', 'text-overflow': 'ellipsis', 'overflow': 'hidden', 'color': '#fff'}"
        >
          {{ kanban.name }}
          <div
            class="card-modal"
          >
            <a-popconfirm
              :title="$t('kanban.unclose_tips')"
              :ok-text="$t('yes')"
              :cancel-text="$t('no')"
              @confirm="cancelClose(kanban.id)"
            >
              <a href="javascript:;">
                {{ $t('kanban.unclose') }}
              </a>
            </a-popconfirm>
          </div>
        </a-card>
        <a-card
          class="kanban-item-card"
          :bordered="true"
          v-if="kanban.id == 0"
          @click="openCreateBoardModal"
          style="cursor: pointer;"
        >
          <a-icon type="plus" />{{ $t('kanban.create.label') }}
        </a-card>
      </a-list-item>
    </a-list>

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
const colorList = ['#2ecc71', '#1abc9c', '#1890ff', '#9b59b6', '#34495e', '#f1c40f', '#e67e22', '#e74c3c', '#95a5a6'];
import CreateBoard from "@/components/kanban/CreateBoard";
import { removeArrItem } from '../../utils';
import i18n from '../../i18n';

export default {
  components: {
    CreateBoard: CreateBoard,
  },

  data() {
    return {
      kanbans: [],
      showCreateBoardModal: false,
    };
  },
  mounted() {
    this.loadKanbans();
  },
  created() {
    
  },
  methods: {

    loadKanbans: function() {
      api.kanbanClosed().then(res => {
        this.kanbans = res;
      }).catch(err => {
        this.$message.error(err.message);
      });
    },

    cancelClose: function(id) {
      api.kanbanUnclose({ query: { id: id } }).then(() => {
        this.kanbans = removeArrItem(this.kanbans, function(item) {
            return item.id == id;
        });
        this.$message.success(i18n.t('kanban.unclose_success'));
      });
    },

    getRandColor: function(i) {
      const j = i % 9;
      return colorList[j];
    },

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
}
.kanban-item {
  text-align: center;
  font-size: 14px;
  font-weight: bold;
  color: #fff;
}

.kanban-item .ant-card {
  position: relative;
  border-radius: 3px;
}

.kanban-item-card:hover {
  box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
}

.card-modal {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(17, 17, 17, 0.785);
    display: none;
}

.kanban-item-card:hover .card-modal {
    display: block;
}

.card-modal:before,
.card-modal:after    /* :after 可以不需要 */
{
    display: inline-block;
    vertical-align: middle;
    content: '';
    height: 100%;
}

.card-modal a {
    display: inline-block;
    vertical-align: middle;
    max-width: 100%;
    color: #eee;
}

.card-modal a:hover {
    color: #fff;
}

</style>