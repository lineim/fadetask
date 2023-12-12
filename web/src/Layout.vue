<template>
  <a-config-provider :locale="locale">
    <a-spin :spinning="spinning">
      <a-layout id="home">
        <a-layout-header>
          <a
            href="javascript:;"
            @click="goHome()" 
            style="margin-right: 2em"
            class="logo"
          >
            <img
              src="/logo_without_txt.png"
              height="36px"
              style="margin-top: -4px;"
            >
          </a>
          <div class="nav-menu">
            <span class="nav-menu-item mrl">
              <a @click.stop="goHome">
                <a-icon type="home" /> {{ $t('nav.homepage') }}
              </a>
            </span>
            <span class="nav-menu-item mrl">
              <a @click.stop="goProject">
                <a-icon type="folder-open" /> {{ $t('nav.project') }}
              </a>
            </span>
            <span class="nav-menu-item mrxl">
              <a @click.stop="goAllKanban">
                <a-icon type="appstore" /> {{ $t('nav.kanban') }}
              </a>
            </span>
            <a-auto-complete
              v-model="searchKeyword"
              style="width: 250px"
              :placeholder="$t('last_view_and_go')"
              :backfill="false"
              @focus="onSearchRecentlyFocus"
              @search="searchRecentlyVisit"
              @select="searchKanbanSelect"
            >
              <template slot="dataSource">
                <a-select-opt-group
                  v-for="data, index in searchKanbans"
                  :key="index"
                >
                  <span slot="label">
                    <span v-if="index == 'projects'">{{ $t('home.menu_project') }}</span>
                    <span v-if="index == 'kanbans'">{{ $t('home.menu_kanban') }}</span>
                  </span>
                  <a-select-option
                    v-for="opt in data"
                    :key="opt.uuid"
                    :value="index+'||'+opt.uuid"
                  >
                    {{ opt.name }}
                  </a-select-option>
                </a-select-opt-group>
              </template>
            </a-auto-complete>

            <a-popover>
              <template slot="content">
                <a-button 
                  type="primary" 
                  class="mbm"
                  icon="project"
                  @click="() => createProjectModal = true"
                  block
                >
                  {{ $t('project.new') }}
                </a-button>
                <a-button 
                  type="primary" 
                  icon="appstore"
                  @click="openCreateBoardModal"
                  block
                >
                  {{ $t('kanban.create.label') }}
                </a-button>
              </template>
              <a-button
                class="mlm"
                type="default"
                shape="circle"
                icon="plus"
              />
            </a-popover>
          </div>

          <CreateBoard
            v-if="showCreateBoardModal"
            :visible="showCreateBoardModal"
            :title="$t('kanban.create.label')"
            @close="closeCreateBoardModal"
          />
          <a-modal
            :destroy-on-close="true"
            :title="$t('project.new')"
            :width="680"
            :footer="null"
            :visible="createProjectModal"
            :wrap-style="{overflow: 'auto', paddingBottom: '108px'}"
            @cancel="() => createProjectModal = false"
          >
            <ProjectForm 
              :action="'add'"
              @submited="goProjectDetail"
              @canceled="() => createProjectModal = false"
            />        
          </a-modal>

          <div class="home-header-right">
            <a-dropdown
              :trigger="['click']"
              @visibleChange="notificationShowChange"
              v-model="notificationVisible"
            >
              <div class="home-user mrm">
                <a-badge
                  :count="hasUnReadNotification ? 1 : 0"
                  dot
                >
                  <a-icon type="bell" />{{ $t('notification.name') }}
                </a-badge>         
              </div>
              <a-menu
                slot="overlay"
                class="home-notification-drop-menu"
                style="padding: 5px 15px;"
                @click.stop="notificationClick()"
              >
                <a-empty
                  v-if="!notificationLoading && notifications.length < 1"
                  :description="$t('notification.empty')"
                />

                <a-list
                  v-if="notifications.length > 0"
                  item-layout="horizontal"
                  class="notification-container"
                  :data-source="notifications"
                >
                  <a-list-item
                    slot="renderItem"
                    slot-scope="item"
                    class="notification-item"
                  >
                    <a-list-item-meta>
                      <a-avatar
                        style="background: rgb(96, 189, 78);"
                        slot="avatar"
                        icon="bell"
                      />
                      <div
                        slot="title"
                      >
                        <i18n
                          v-if="item.template == 'notification.task_due_notify'"
                          :path="item.template"
                          tag="span"
                        >
                          <router-link
                            place="card"
                            :to="{name: 'KanbanDetail', params: {id: item.params.kanban_id}, query: {card_id: item.params.task_id}}"
                          >
                            {{ item.params.title }}
                          </router-link>
                          <span place="date">{{ item.params.due_time }}</span>
                        </i18n>
                        <i18n
                          v-if="item.template == 'notification.join_task'"
                          :path="item.template"
                          tag="span"
                        >
                          <span place="who">{{ item.params.operator.name }}</span>
                          <router-link
                            place="card"
                            :to="{name: 'KanbanDetail', params: {id: item.params.task.kanban_uuid}, query: {card_id: item.params.task.id}}"
                          >
                            {{ item.params.task.title }}
                          </router-link>
                        </i18n>
                      </div>
                      <div slot="description">
                        {{ item.created_date }}
                      </div>
                    </a-list-item-meta>
                  </a-list-item>

                  <div
                    v-if="showLoadingMore"
                    slot="loadMore"
                    :style="{ textAlign: 'center', marginTop: '12px', height: '32px', lineHeight: '32px' }"
                  >
                    <a-spin v-if="notificationLoading" />
                    <a-button
                      v-else
                      @click="loadNotifications(true)"
                    >
                      {{ $t('load_more') }}
                    </a-button>
                  </div>
                </a-list>
              </a-menu>
            </a-dropdown>

            <a-dropdown :trigger="['click']">
              <div class="home-user">
                <a-icon type="user" /> {{ userName }} <a-icon type="down" />
              </div>
              <a-menu
                slot="overlay"
                class="home-drop-menu"
              >
                <a-menu-item @click="userSetting()">
                  {{ $t('menu.personal_setting') }}
                </a-menu-item>
                <a-menu-item v-if="isSysAdmin">
                  <router-link :to="{path: '/kanban/setting/user'}">
                    {{ $t('menu.system_setting') }}
                  </router-link>
                </a-menu-item>
                <a-menu-item>
                  <a 
                    href="javascript:;" 
                    @click="handleLogout"
                  >{{ $t('logout') }}</a>
                </a-menu-item>
              </a-menu>
            </a-dropdown>
          </div>
        </a-layout-header>

        <AccountSetting
          v-if="showUserSettingModal"
          :visible="showUserSettingModal"
          @close="userSettingClose"
        />

        <div
          id="main-container"
          class="main-container"
          ref="main-container"
        >
          <boardNav 
            v-if="currBoardId"
            :board-id="currBoardId"
          />
          <a-layout-content
            :style="{ margin: '0px'}"
          >
            <router-view />
          </a-layout-content>
        </div>
      </a-layout>
    </a-spin>
  </a-config-provider>
</template>
<script>
  import zhCN from 'ant-design-vue/lib/locale-provider/zh_CN';
  import store from "@/store"
  // import i18n from '@/i18n';
  import * as types from '@/store/mutation-types'
  import boardNav from "@/views/kanban/BoardNav"
  import AccountSetting from "@/components/user/AccountSetting";
  import CreateBoard from "@/components/kanban/CreateBoard";
  import projectForm from '@/components/project/Form.vue';
  import api from '@/api';
  import i18n from './i18n';

  export default {
    directives: {  },
    components: {
      boardNav: boardNav,
      AccountSetting: AccountSetting,
      CreateBoard: CreateBoard,
      ProjectForm: projectForm
    },
    data() {
      return {
        locale: zhCN,
        collapsed: true,
        searchKeyword: '',
        searchKanbans: [],
        searchLastTrigger: 0,
        showUserSettingModal: false,
        showCreateBoardModal: false,
        hasUnReadNotification: false,
        notificationVisible: false,
        notifications: [],
        showLoadingMore: true,
        notificationBusy: false,
        notificationLoading: false,
        createProjectModal: false,
        document: document
      };
    },

    created() {
      api.meHasNotification().then(has => {
        this.hasUnReadNotification = has;
      });
    },

    watch: {
      '$route': function() {
        this.notificationVisible = false;
      },
    },

    methods: {
      goHome() {
        this.$router.push({name: 'Dashboard'});
      },
      goAllKanban() {
        this.$router.push({name: 'KanbanAll'});
      },
      goProject() {
        this.$router.push({name: 'ProjectList'});
      },
      goProjectDetail(uuid) {
        this.$message.success(i18n.t('project.create.success_msg'));
        this.createProjectModal = false;
        this.$router.push({name: "ProjectOverview", params: {uuid: uuid}});
      },
      menuClick(item) {
        if(this.$route.path === item.key) {
          return;
        }

        store.commit(types.SET_MENU, item.key);
        this.$router.push({path: item.key});
      },
      userSetting() {
        this.showUserSettingModal = true;
      },

      userSettingClose() {
        this.showUserSettingModal = false;
      },

      openCreateBoardModal() {
        this.showCreateBoardModal = true;
      },

      closeCreateBoardModal() {
        this.showCreateBoardModal = false;
      },

      loadNotifications(fromLoadMore = false) {
        this.notificationLoading = true;
        let offset = this.notifications.length;
        let limit = 10;
        api.meNotifications({params: {offset: offset, limit: limit}}).then((data) => {
          if (data.notifications.length < 1 && fromLoadMore) {
            this.$message.info(i18n.t('no_more'));
          }
          let notifications = this.notifications;
          this.notifications = notifications.concat(data.notifications.map((item, index) => ({ ...item, index })));
          this.hasUnReadNotification = data.has_unread;
          this.notificationLoading = false;
        });
      },

      notificationClick() {
        // this.notificationVisible = false;
      },

      notificationShowChange(visible) {
        if (visible) {
          api.readNotification().then(() => {
            this.loadNotifications();
          });
        }
      },

      handleLogout() {
        const data = { user: {}, token: ''};

        store.commit(types.USER_LOGOUT, data);
        this.$router.push({path: '/login'})
      },
      onSearchRecentlyFocus() {
        if (this.searchKanbans.length <= 0) {
          this.searchRecentlyVisit();
        }
      },
      searchRecentlyVisit() {
        if (!this.searchKeyword) {
          api.recentlyVisit().then(res => {
            this.searchKanbans = res;
          });
        } else {
          api.search().then(() => {
            // this.searchKanbans = res;
          });
        }
      },
      searchKanbanSelect(idStr) {
        const myArray = idStr.split("||");
        if (myArray[0] == 'kanbans') {
          this.$router.push({ name: 'KanbanDetail', params: {id: myArray[1]}})
        }
        if (myArray[0] == 'projects') {
          this.$router.push({name: "ProjectOverview", params: {uuid: myArray[1]}});
        }
      },
    },
    computed: {
      spinning() {
        return this.$store.state.loading;
      },
      userName() {
        return store.state.user.name;
      },
      userRole() {
        return store.state.user.role;
      },
      isSysAdmin() {
        return store.state.user.role === 'ADMIN';
      },
      currBoardId() {
        return store.state.boardId;
      }
    }
  };
</script>
<style lang="less" scoped>
  #home {
    .logo {
      height: 32px;
      text-align: center;
      font-size: 16px;
      color: #ffffff;
      display: inline-block;
    }
    .trigger {
      font-size: 18px;
      line-height: 64px;
      padding: 0 24px;
      cursor: pointer;
      transition: color 0.3s;
      &:hover {
        color: #1890ff;
      }
    }
    .nav-menu {
      display: inline-block; 
      font-size: 15px;
      .nav-menu-item {
        display: inline-block;
        &:hover {
          font-weight: 500;
        };
        a {
          color: #ffffff;
        }
      }
    }
  }
  
  .logo-picture {
    height: 100%;
  }

  .home-header-right {
    display: flex;
    float: right;
    height: 44PX;
    /* padding-right: 24px; */
    margin-left: auto;
    overflow: hidden;
    font-size: 20px;
    color: #ffffff;
    text-align: center;
  }

  .home-drop-menu {
    min-width: 200px;
  }

  .home-notification-drop-menu {
    width: 340px;
    padding: 10px 5px;
    overflow-y: auto;
    max-height: 460px;
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: scrollbar;
  }

  .home-drop-menu .ant-dropdown-menu-item {
    text-align: center;
    padding-top: 8px;
    padding-bottom: 8px;
  }

  .home-user {
    font-size: 15px;
    cursor: pointer;
  }

  .home-user > .ant-badge {
    font-size: 15px;
  }

  .notification-container {
    /* max-height: 460px; */
    /* overflow-y: overlay; */
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: scrollbar;
  }

  .notification-item .ant-list-item-meta-title{
    font-weight: normal;
  }

</style>
