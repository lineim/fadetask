<template>
  <div class="full-height my-work">
    <a-row
      :gutter="16"
      class="my-work-container"
    >
      <a-col
        :span="18"
      >
        <a-page-header
          style="padding-left: 0px; padding-right: 0px;"
          :title="$t('home.my_todo')"
        />
        <div class="mbl">
          <div class="mbs">
            <a
              @click="showToday = !showToday"
            >
              <a-icon
                :type="showToday ? 'caret-down' : 'caret-right'"
              />
              {{ $t('today') }}
            </a>
          </div>
          <transition name="fade">
            <a-list
              v-if="showToday"
              item-layout="horizontal"
              bordered
              :data-source="todos.today"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="item"
                class="todos-item"
                @click="gotoCard(item)"
              >
                <div
                  slot="actions"
                  class="todos-item-action"
                />
                <a-list-item-meta>
                  <div
                    slot="title"
                    class="todos-item-title"
                  >
                    {{ item.title }}
                  </div>
                  
                  <div
                    class="clearfix description"
                    slot="description"
                  >
                    <a-space>
                      <a
                        v-if="item.project_uuid"
                        @click.stop="gotoProject(item.project_uuid)"
                      ><a-icon
                        class="mrxs"
                        type="project"
                      />{{ item.project }}</a>
                      <span v-if="item.project_uuid">/</span>
                      <a @click.stop="gotoDetail(item.kanban.uuid)"><a-icon
                        class="mrxs"
                        type="appstore"
                      />{{ item.kanban.name }}</a> 
                      <span>/</span>
                      <span>{{ item.list }}</span>
                    </a-space>
                  </div>
                </a-list-item-meta>
              </a-list-item>
            </a-list>
          </transition>
        </div>

        <div class="mbl">
          <div class="mbs">
            <a
              @click="showOverDue = !showOverDue"
            >
              <a-icon
                :type="showOverDue ? 'caret-down' : 'caret-right'"
              />
              {{ $t('task.due_overfall') }}
            </a>
          </div>
          <transition name="fade">
            <a-list
              v-if="showOverDue"
              item-layout="horizontal"
              bordered
              :data-source="todos.overdue"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="item"
                class="todos-item"
                @click="gotoCard(item)"
              >
                <div
                  slot="actions"
                  class="todos-item-action"
                  style="color: rgb(245, 74, 69);"
                >
                  {{ item.due_date|friendlyTime }}
                </div>
                <a-list-item-meta>
                  <div
                    slot="title"
                    class="todos-item-title"
                  >
                    {{ item.title }}
                  </div>
                  
                  <div
                    class="clearfix description"
                    slot="description"
                  >
                    <a-space>
                      <a
                        v-if="item.project_uuid"
                        @click.stop="gotoProject(item.project_uuid)"
                      ><a-icon
                        class="mrxs"
                        type="project"
                      />{{ item.project }}</a>
                      <span v-if="item.project_uuid">/</span>
                      <a @click.stop="gotoDetail(item.kanban.uuid)"><a-icon
                        class="mrxs"
                        type="appstore"
                      />{{ item.kanban.name }}</a> 
                      <span>/</span>
                      <span>{{ item.list }}</span>
                    </a-space>
                  </div>
                </a-list-item-meta>
              </a-list-item>
            </a-list>
          </transition>
        </div>

        <div class="mbl">
          <div class="mbs">
            <a
              @click="showNext = !showNext"
            >
              <a-icon
                :type="showNext ? 'caret-down' : 'caret-right'"
              />
              {{ $t('task.due_next') }}
            </a>
          </div>
          <transition name="fade">
            <a-list
              v-if="showNext"
              item-layout="horizontal"
              bordered
              :data-source="todos.next"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="item"
                class="todos-item"
                @click="gotoCard(item)"
              >
                <div
                  slot="actions"
                  class="todos-item-action"
                >
                  {{ item.due_date|friendlyTime }}
                </div>
                <a-list-item-meta>
                  <div
                    slot="title"
                    class="todos-item-title"
                  >
                    {{ item.title }}
                  </div>
                  
                  <div
                    class="clearfix description"
                    slot="description"
                  >
                    <a-space>
                      <a
                        v-if="item.project_uuid"
                        @click.stop="gotoProject(item.project_uuid)"
                      ><a-icon
                        class="mrxs"
                        type="project"
                      />{{ item.project }}</a>
                      <span v-if="item.project_uuid">/</span>
                      <a @click.stop="gotoDetail(item.kanban.uuid)"><a-icon
                        class="mrxs"
                        type="appstore"
                      />{{ item.kanban.name }}</a> 
                      <span>/</span>
                      <span>{{ item.list }}</span>
                    </a-space>
                  </div>
                </a-list-item-meta>
              </a-list-item>
            </a-list>
          </transition>
        </div>

        <div class="mbl">
          <div class="mbs">
            <a
              @click="showNotScheduled = !showNotScheduled"
            >
              <a-icon
                :type="showNotScheduled ? 'caret-down' : 'caret-right'"
              />
              {{ $t('task.not_scheduled') }}
            </a>
          </div>
          <transition name="fade">
            <a-list
              v-if="showNotScheduled"
              item-layout="horizontal"
              bordered
              :data-source="todos.unscheduled"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="item"
                class="todos-item"
                @click="gotoCard(item)"
              >
                <!-- <a slot="actions" class="todos-item-action">more</a> -->
                <a-list-item-meta>
                  <div
                    slot="title"
                    class="todos-item-title"
                  >
                    {{ item.title }}
                  </div>
                  
                  <div
                    class="clearfix description"
                    slot="description"
                  >
                    <a-space>
                      <a
                        v-if="item.project_uuid"
                        @click.stop="gotoProject(item.project_uuid)"
                      ><a-icon
                        class="mrxs"
                        type="project"
                      />{{ item.project }}</a>
                      <span v-if="item.project_uuid">/</span>
                      <a @click.stop="gotoDetail(item.kanban.uuid)"><a-icon
                        class="mrxs"
                        type="appstore"
                      />{{ item.kanban.name }}</a> 
                      <span>/</span>
                      <span>{{ item.list }}</span>
                    </a-space>
                  </div>
                </a-list-item-meta>
              </a-list-item>
            </a-list>
          </transition>
        </div>
      </a-col>
      <a-col :span="6">
        <div class="right-content">
          <h3 style="padding: 16px 24px 10px 24px;">
            {{ $t('home.project_recent_vist') }}
          </h3>
          <div
            class="kanban-items mbl"
          >
            <a-list
              item-layout="horizontal"
              :bordered="false"
              :data-source="projects"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="project"
                class="kanban-item"
                @click="gotoProject(project.uuid)"
              >
                {{ project.name }}
              </a-list-item>
            </a-list>
          </div>
          <div style="padding: 0px 16px;">
            <a-divider style="padding: 0px 16px;" />
          </div>
          <h3 style="padding: 16px 24px 10px 24px;">
            {{ $t('home.kanban_recent_vist') }}
          </h3>
          <div
            class="kanban-items mbl"
          >
            <a-list
              item-layout="horizontal"
              :bordered="false"
              :data-source="kanbans"
            >
              <a-list-item
                slot="renderItem"
                slot-scope="kanban"
                class="kanban-item"
                @click="gotoDetail(kanban.uuid)"
              >
                {{ kanban.name }}
              </a-list-item>
            </a-list>
          </div>
        </div>
      </a-col>
    </a-row>

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

export default {
  components: {
    CreateBoard: CreateBoard,
  },

  data() {
    return {
      kanbans: [],
      projects: [],
      todos: [],
      todosTotal: 0,
      todosPageSize: 5,
      todosPage: 1,
      showCreateBoardModal: false,
      showToday: true,
      showOverDue: true,
      showNext: true,
      showNotScheduled: true
    };
  },
  mounted() {
    this.loadRecentVist();
    this.loadMeTodo();
  },
  created() {
    
  },
  methods: {

    loadMeTodo: function() {
      api.meTodo({params: {page: this.todosPage}}).then(res => {
        this.todosTotal = parseInt(res.total);
        this.todosPage = parseInt(res.page);        
        this.todosPageSize = parseInt(res.page_size);
        this.todos = res.data;
      });
    },

    loadRecentVist: function() {
      api.recentlyVisit().then(res => {
        this.kanbans = res.kanbans;
        this.projects = res.projects;
      }).catch(err => {
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

    gotoProject: function(uuid) {
      this.$router.push({ name: 'ProjectOverview', params: {uuid}})
    },

    getRandColor: function(i) {
      const j = i % 9;
      return colorList[j];
    },

    openCreateBoardModal() {
      this.showCreateBoardModal = true;
    },

    closeCreateBoardModal() {
      this.showCreateBoardModal = false;
    },

    gotoCard: function(card) {
      let url = '/#/kanban/' + card.kanban.uuid + '?card_id=' + card.id;
      window.location.href = url;
    }
  }
}
</script>

<style scoped lang="less">
.a {
  color: rgba(0, 0, 0, 0.75);
}
a:hover {
  color: #1890ff;
}
.my-work {
  background-color: #fff;
  height: 100%;
  position: relative;
  /* overflow-y: auto; */
  padding: 16px 26px 0;
  padding-bottom: 60px;
}

.my-work-container {
  overflow-y: auto;
}

.todos-item {
  cursor: pointer;
  border-bottom-style: solid;
  border-bottom-width: 1px;
  .todos-item-title {
    font-size: 14px;
    font-weight: 400;
  }
  .todos-item-action {
    font-size: 13px;
  }
  a {
    color: rgba(0, 0, 0, 0.45);
    &:hover {
      color: rgb(24, 144, 255);
    }
  }
  &:hover {
    background-color: rgb(230, 247, 255);
    border-block-end-color: rgb(230, 247, 255);
    border-bottom-color: rgb(230, 247, 255);
    // border-bottom-left-radius: 3px;
    // border-bottom-right-radius: 3px;
    box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px 0px;
    box-sizing: border-box;
    .todos-item-title {
      color: rgb(24, 144, 255);
    }
  }
}

.fade-enter-active, .fade-leave-active {
  transition: opacity .2s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
}

.description {
  font-size: 13px;
}

.board {
  margin-left: auto;
  margin-right: auto;
}

.right-content {
  position: fixed;
}

.kanban-items {
  padding: 0 24px;
  .kanban-item {
    border-bottom: none;
    padding-top: 6px;
    padding-bottom: 6px;
    white-space: 'nowrap';
    text-overflow: 'ellipsis';
    overflow: 'hidden';
    color: rgb(24, 144, 255);
    cursor: pointer;
  }
}

.kanban-item .ant-card {
  border-radius: 3px;
}

.kanban-item-card:hover {
  box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
}

</style>