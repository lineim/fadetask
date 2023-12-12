<template>
  <div class="board-nav">
    <a-breadcrumb 
      class="d-inline-block"
      v-if="currentBoard.id"
      @click="(e) => console.log(e)"
    >
      <a-breadcrumb-item>
        <a @click.stop="gotoHome()">
          <a-icon
            class="mrxs"
            type="home"
          />
          <span>{{ $t('home.menu_home') }}</span>
        </a>
      </a-breadcrumb-item>

      <a-breadcrumb-item
        v-if="currentBoard.project.uuid"
      >
        <a @click.stop="gotoProjectKanban(currentBoard.project.uuid)">
          <a-icon
            class="mrxs"
            type="folder-open"
          />
          <span>{{ currentBoard.project.name }}</span>
        </a>
      </a-breadcrumb-item>

      <a-breadcrumb-item
        v-else
      >
        <a @click.stop="gotoAllKanban()">
          <a-icon
            class="mrxs"
            type="appstore"
          />
          <span>所有看板</span>
        </a>
      </a-breadcrumb-item>

      <a-breadcrumb-item>
        <span class="board-name">
          {{ currentBoard.name }}
        </span>
      </a-breadcrumb-item>
    </a-breadcrumb>
    <a-divider type="vertical" />
    <a-select
      v-if="currentBoard.id"
      default-value="kanban"
      v-model="view"
      @change="viewChange"
      size="small"
      style="width: 120px;"
    >
      <a-select-option value="kanban">
        <a-icon
          class="mrs"
          type="folder-open"
        />{{ $t('board_nav.view.kanban') }}
      </a-select-option>
      <a-select-option value="dashboard">
        <a-icon
          class="mrs"
          type="dashboard"
        />{{ $t('board_nav.view.dashboard') }}
      </a-select-option>
    </a-select>
    <a-divider type="vertical" />
    <a-tooltip v-if="currentBoard.id">
      <template slot="title">
        {{ $t(favorite_label) }}
      </template>
      <a
        href="javascript:;"
        @click.stop="favoriteHandler"
      ><a-icon
        type="star"
        :theme="favorite_icon_theme"
        style="color: rgb(255, 158, 25);"
      /></a>
    </a-tooltip>
    
    <!-- <a-divider type="vertical" /> -->
    <!-- <a-icon type="info-circle" /> -->
    <div
      v-if="currentBoard.id"
      class="board-header-handler"
    >
      <a-popover
        trigger="click"
        placement="bottomRight"
        @visibleChange="filterVisibleChange"
      >
        <div
          class="popover-title"
          slot="title"
        >
          {{ $t('kanban.filter.title') }}
        </div>
        <div
          slot="content"
          class="popover-content filter-container"
        >
          <a-input-search
            class="mbl"
            :placeholder="$t('kanban.filter.keyword')"
            v-model="searchCond.keyword"
            @search="onCardSearchKeywordChanged"
          />

          <div class="filter-box">
            <div class="filter-title">
              {{ $t('kanban.filter.member') }}
            </div>
            <div class="filter-body">
              <span
                style="display: block;"
                class="mvs"
              >
                <LiCheckBox 
                  :value="0"
                  :key="0"
                  :checked="memberInCondition(0)"
                  @change="filterMemberChange"
                >
                  <span
                    slot="label"
                    class="mlm"
                    style="width: 210px; display: inline-block;"
                  >
                    <a-avatar
                      icon="user"
                      style="marginRight: 5px;"
                    />
                    {{ $t('kanban.filter.no_member') }}
                  </span>
                </LiCheckBox>
              </span>
              <span
                v-for="member in members"
                :key="member.id"
                style="display: block;"
                class="mvs"
              >
                <LiCheckBox 
                  :value="member.id"
                  :key="member.id"
                  :label="member.name" 
                  :checked="memberInCondition(member.id)"
                  @change="filterMemberChange"
                >
                  <span
                    slot="label"
                    class="mlm"
                    style="width: 210px; display: inline-block;"
                  >
                    <a-avatar 
                      v-if="!member.avatar" 
                      style="color: #f56a00; backgroundColor: #fde3cf;marginRight: 5px;"
                    >
                      {{ firstWord(member.name) }}
                    </a-avatar>
                    <a-avatar 
                      v-if="member.avatar" 
                      :src="member.avatar" 
                      style="marginRight: 5px;"
                    />
                    {{ member.name }}
                  </span>
                </LiCheckBox>
              </span>
            </div>
          </div>

          <div class="filter-box">
            <div class="filter-title">
              {{ $t('kanban.filter.tags') }}
            </div>
            <div class="filter-body">
              <span
                style="display: block;"
                class="mvs"
              >
                <LiCheckBox 
                  :value="0"
                  :key="0"
                  :checked="labelInCondition(0)"
                  @change="filterLabelChange"
                >
                  <span
                    slot="label"
                    class="mlm"
                    style="width: 210px; display: inline-block;"
                  >
                    {{ $t('kanban.filter.no_tags') }}
                  </span>
                </LiCheckBox>
              </span>
              <span
                v-for="label in currentBoardLabels" 
                :value="label.id"
                :key="label.id"
                style="display: block;"
                class="mvs"
              >
                <LiCheckBox 
                  :value="label.id"
                  :key="label.id"
                  :label="label.name" 
                  :checked="labelInCondition(label.id)"
                  @change="filterLabelChange"
                >
                  <span
                    slot="label"
                    class="label-filter-item mlm" 
                    :style="{background: label.color, color: '#fff', verticalAlign: 'middle'}"
                  >
                    {{ label.name }}
                  </span>
                </LiCheckBox>
              </span>
            </div>
          </div>

          <div class="filter-box">
            <div class="filter-title">
              {{ $t('kanban.filter.priority') }}
            </div>
            <div class="filter-body">
              <span
                v-for="priority in priorities" 
                :value="priority.level"
                :key="priority.level"
                style="display: block;"
                class="mvs"
              >
                <LiCheckBox 
                  :value="priority.level"
                  :key="priority.level"
                  :label="priority.name" 
                  :checked="priorityInCondition(priority.level)"
                  @change="filterPriorityChange"
                >
                  <span
                    slot="label"
                    class="label-filter-item mlm" 
                    :style="{background: prioritiesColor[priority.level], verticalAlign: 'middle', color: '#fff'}"
                  >
                    {{ priority.name }}
                  </span>
                </LiCheckBox>
              </span>
            </div>
          </div>

          <div class="filter-box">
            <div class="filter-title">
              {{ $t('kanban.filter.finish.label') }}
            </div>
            <div class="filter-body">
              <a-radio-group
                v-model="searchCond.status"
                :style="{width: '100%'}"
                @change="filterStatusChange"
              >
                <a-radio
                  :style="dueRadioStyle"
                  :value="''"
                >
                  {{ $t('kanban.filter.finish.all') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'1'"
                >
                  {{ $t('kanban.filter.finish.finished') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'0'"
                >
                  {{ $t('kanban.filter.finish.unfinished') }}
                </a-radio>
              </a-radio-group>
            </div>
          </div>

          <div class="filter-box">
            <div class="filter-title">
              {{ $t('kanban.filter.due.label') }}
            </div>
            <div class="filter-body">
              <a-radio-group
                v-model="searchCond.due"
                :style="{width: '100%'}"
                @change="filterDueChange"
              >
                <a-radio
                  :style="dueRadioStyle"
                  :value="''"
                >
                  {{ $t('kanban.filter.due.all') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'over_due'"
                >
                  {{ $t('kanban.filter.due.over_due') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'today_due'"
                >
                  {{ $t('kanban.filter.due.today_due') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'this_week_due'"
                >
                  {{ $t('kanban.filter.due.this_week_due') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'next_week_due'"
                >
                  {{ $t('kanban.filter.due.next_week_due') }}
                </a-radio>
                <a-radio
                  :style="dueRadioStyle"
                  :value="'no_due'"
                >
                  {{ $t('kanban.filter.due.no_due') }}
                </a-radio>
              </a-radio-group>
            </div>
          </div>

          <a-divider />
          <a-button
            type="danger"
            :disabled="!hasSearchCond"
            @click="clearSearchCond"
            block
          >
            {{ $t('kanban.filter.clear') }}
          </a-button>
        </div>
        <span
          class="board-header-handler-item"
          :class="{'active-menu': hasSearchCond}"
        >
          <a-icon type="filter" /> 
          {{ $t('kanban.filter.label') }}
          <span v-if="hasSearchCond">
            ({{ card_count }})
            <a-tooltip>
              <template slot="title">
                {{ $t('kanban.menu.filter_clean_tips') }}
              </template>
              <a-button
                type="danger"
                icon="close"
                shape="circle"
                size="small"
                @click.stop="clearSearchCond"
              />
            </a-tooltip>
          </span>
        </span>
      </a-popover>

      <!-- 成员管理 -->
      <a-popover
        v-model="memberVisible"
        trigger="click"
        placement="bottomRight"
      >
        <div
          class="popover-title"
          slot="title"
        >
          看板成员
        </div>
        <div 
          slot="content"
          class="popover-content"
          @click="hideMemberContainer()"
        >
          <a-input-search
            placeholder="搜索成员"
            @search="onMemberSearch"
          />
          <a-divider v-if="isBoardAdmin" />
          <a-list
            class="demo-loadmore-list"
            :loading="false"
            item-layout="horizontal"
            :data-source="members"
            :bordered="false"
            :split="false"
            :style="{'maxHeight': '260px', 'overflowY': 'auto'}"
          >
            <a-list-item
              slot="renderItem"
              slot-scope="m"
            >
              <a-dropdown
                slot="actions"
                v-if="isBoardAdmin && !isOwner(m)"
              >
                <a
                  class="ant-dropdown-link"
                  @click="e => e.preventDefault()"
                >
                  {{ $t('action') }} <a-icon type="down" />
                </a>
                <a-menu
                  slot="overlay"
                >
                  <a-menu-item v-if="isMember(m)">
                    <a
                      href="javascript:;"
                      @click.stop="setMemberAdmin(m)"
                    >{{ $t('kanban.member.action_set_admin') }}</a>
                  </a-menu-item>
                  <a-menu-item v-if="isAdmin(m)">
                    <a
                      href="javascript:;"
                      @click.stop="setMemberUser(m)"
                    >{{ $t('kanban.member.action_set_member') }}</a>
                  </a-menu-item>
                  <a-menu-item v-if="isBoardAdmin">
                    <a
                      href="javascript:;"
                      @click.stop="memberAddOrRemove(m.id, false)"
                    >{{ $t('kanban.member.action_remove') }}</a>
                  </a-menu-item>
                </a-menu>
              </a-dropdown>

              <a-list-item-meta :description="getRoleName(m.memeber_role)">
                <a
                  class="popover-title"
                  slot="title"
                >{{ m.name }}</a>
                <a-avatar
                  slot="avatar"
                  v-if="!m.avatar"
                  style="color: #f56a00; backgroundColor: #fde3cf"
                >
                  {{ m.name.slice(0, 1) }}
                </a-avatar>
                <a-avatar
                  slot="avatar"
                  v-if="m.avatar"
                  :src="m.avatar"
                />
              </a-list-item-meta>
            </a-list-item>
          </a-list>
          <a-divider v-if="isBoardAdmin" />
          <a-button
            v-if="!inviteForm && isBoardAdmin"
            type="primary"
            icon="user-add"
            @click="showInvietForm()"
            block
          >
            邀请成员
          </a-button>
          <div v-if="inviteForm && isBoardAdmin">
            <div class="mbm">
              <a-textarea
                v-model="inviteEmails"
                placeholder="请输入邮箱地址，多个邮箱地址用英文逗号隔开。"
                :auto-size="{ minRows: 4, maxRows: 6 }"
              />
            </div>
            <div class="text-right">
              <a-button
                class="mrm"
                @click="closeInviteForm"
              >
                取消
              </a-button>
              <a-button
                type="primary"
                @click="sendInviteEmails"
                :disabled="inviteEmails === '' || inviteBtnDisable"
              >
                邀请
              </a-button>
              <a-button
                class="mtm"
                type="primary"
                icon="link"
                @click="copyInviteLink"
                block
              >
                通过链接邀请
              </a-button>
            </div>
          </div>
        </div>
        <div
          class="board-header-handler-item"
          @click="showMemberContainer()"
        >
          <a-icon type="usergroup-add" /> 成员
        </div>
      </a-popover>

      <a-popover
        trigger="click"
        placement="bottomRight"
      >
        <div
          class="popover-title"
          slot="title"
        >
          卡片排序
        </div>
        <div
          slot="content"
          class="popover-content"
        >
          <div>
            <a-radio-group
              v-model="searchCond.sort"
              @change="sortChange"
            >
              <a-radio
                :style="sortRadioStyle"
                :value="sortDefault"
              >
                默认排序
              </a-radio>
              <a-radio
                :style="sortRadioStyle"
                :value="sortPriority"
              >
                优先级
              </a-radio>
              <a-radio
                :style="sortRadioStyle"
                :value="sortName"
              >
                卡片名称
              </a-radio>
              <a-radio
                :style="sortRadioStyle"
                :value="sortDueDate"
              >
                截止日期
              </a-radio>
              <a-radio
                :style="sortRadioStyle"
                :value="sortCreated"
              >
                创建时间
              </a-radio>
            </a-radio-group>
          </div>
          <div
            class="mtm"
            v-if="searchCond.sort != sortDefault"
          >
            <a-select
              label-in-value
              :style="{'width': '120px'}"
              :default-value="{ key: 'desc' }"
              @change="sortMethodChange"
            >
              <a-select-option value="desc">
                降序
              </a-select-option>
              <a-select-option value="asc">
                升序
              </a-select-option>
            </a-select>
          </div>
        </div>
        <span
          class="board-header-handler-item"
          :class="{'active-menu': hasSortCond}"
        >
          <a-icon type="sort-ascending" /> 
          <a-badge
            :dot="hasSortCond"
            text=""
          >排序</a-badge>
        </span>
      </a-popover>

      <span
        v-if="isBoardAdmin"
        class="board-header-handler-item"
        @click="() => {this.settingDrawerVisiable = true;}"
      ><a-icon type="more" /> 更多</span>
    </div>
    <CreateBoard
      v-if="showCreateBoardModal"
      :visible="showCreateBoardModal"
      :title="'以看板【'+ currentBoard.name +'】为模版创建新看板'"
      :from-kanban="currentBoard"
      @close="closeCreateBoardModal"
    />
    <a-drawer
      placement="right"
      :closable="true"
      :mask="true"
      :destroy-on-close="true"
      :visible="settingDrawerVisiable"
      :wrap-style="{ top: '44px', height: 'calc(100% - 44px)'}"
      :width="'380px'"
      :mask-style="{backgroundColor: 'rgba(0, 0, 0, 0.0)'}"
      @close="onSettingDrawerClose"
    >
      <div
        class="popover-title"
        slot="title"
      >
        <span
          class="mrs cursor-pointer"
          v-if="!showSettingBtns"
          @click="closeSettingContainer"
        ><a-icon type="left" /></span>
        {{ settingTitle }}
      </div>
      <div
        v-if="showSettingBtns"
        class="setting-btns"
      >
        <!-- <a-button
              type="link"
              icon="user"
              block
            >
              按人员展示
            </a-button> -->

        <a-button
          type="link"
          icon="edit"
          @click="showBoardEdit"
          block
        >
          {{ $t('board_nav.kanban.edit') }}
        </a-button>

        <!-- TODO: new feature -->
        <!-- <a-button
              type="link"
              icon="alert"
              @click="showAlert"
              block
            >
              提醒设置
            </a-button> -->
        
        <a-button
          type="link"
          icon="tags"
          @click="showLabel"
          block
        >
          {{ $t('board_nav.label.label') }}
        </a-button>

        <a-button
          type="link"
          icon="alert"
          @click="showWip"
          block
        >
          {{ $t('board_nav.kanban.wip') }}
        </a-button>

        <a-tooltip
          placement="left"
        >
          <template slot="title">
            {{ $t('kanban.kanban_template_help_short') }}
          </template>
          <a-button
            type="link"
            icon="copy"
            @click="createKanban"
            block
          >
            {{ $t('board_nav.kanban.create_from') }}
          </a-button>
        </a-tooltip>
        <a-button
          type="link"
          icon="project"
          @click="associatingProjectOpen"
          block
        >
          {{ $t('board_nav.kanban.to_project') }}
        </a-button>
        <a-button
          type="link"
          icon="save"
          @click="showArchived"
          block
        >
          {{ $t('board_nav.archive.project') }}
        </a-button>

        <a-divider />

        <a-popconfirm
          :title="$t('kanban.close_tips')"
          :ok-text="$t('kanban.close')"
          :cancel-text="$t('cancel')"
          @confirm="closeKanban()"
        >
          <a-button
            type="danger"
            icon="delete"
            block
          >
            关闭看板
          </a-button> 
        </a-popconfirm>
      </div>
      <div
        v-if="!showSettingBtns"
        class="setting-container"
      >
        <div v-if="isShowBoardEdit">
          <a-input
            :placeholder="editBoard.name"
            v-model="editBoard.name"
            :max-length="32"
            class="mbl"
          />

          <a-textarea
            placeholder="请输入看板描述"
            v-model="editBoard.desc"
            :max-length="128"
            :auto-size="{ minRows: 3, maxRows: 5 }"
          />

          <a-divider />
          <a-button
            type="primary"
            icon="save"
            :disabled="btnDisabled"
            block
            @click="updateKanban()"
          >
            保存
          </a-button> 
        </div>

        <!-- 设置标签 -->
        <div v-if="isShowLabel">
          <Label
            :kanban-id="currentBoard.id"
            :labels="currentBoardLabels"
            :selected-labels="[]"
            :colors="labelColors"
            @labelupdated="labelUpdated"
            @newlabel="labelAdded"
            @labeldeleted="labelDeleted"
          />
        </div>

        <div v-if="isShowAlert">
          <a-input :placeholder="currentBoard.name" />
          <a-divider />
          <a-button
            type="primary"
            icon="save"
            :disabled="btnDisabled"
            block
          >
            保存
          </a-button> 
        </div>
            
        <!-- 设置归档项 -->
        <div v-if="isShowArchived">
          <div v-if="isBoardAdmin">
            <a-tabs
              default-active-key="1"
              @change="archiveTabChange"
            >
              <a-tab-pane
                key="1"
                :tab="$t('board_nav.archive.cards')"
              >
                <a-empty 
                  v-if="archivedTasks <= 0"
                  :description="$t('no_data_now')"
                />
                <p
                  v-for="(t, key) in archivedTasks"
                  :key="key"
                >
                  {{ t.title|ellipsis(12) }}
                  <a-button
                    class="pull-right"
                    type="link"
                    @click="unArchive(t.id)"
                  >
                    {{ $t('board_nav.archive.restore') }}
                  </a-button>
                </p>
              </a-tab-pane>
              <a-tab-pane 
                key="2" 
                :tab="$t('board_nav.archive.list')"
              >
                <p
                  v-for="(l, key) in archivedList"
                  :key="key"
                >
                  {{ l.name }}
                  <a-button
                    class="pull-right"
                    type="link"
                    @click="unArchiveList(l.id)"
                  >
                    {{ $t('board_nav.archive.restore') }}
                  </a-button>
                </p>
                <a-empty 
                  v-if="archivedList <= 0"
                  :description="$t('no_data_now')"
                />
              </a-tab-pane>
            </a-tabs>
          </div>
        </div>

        <div v-if="isShowWip">
          <!-- 按列设置 -->
          <div
            class="mbm"
          >
            <div
              v-for="list in wipsSetting"
              :key="list.id"
              class="mbs"
            >
              <span class="mrm list-name">{{ list.name }}</span>
              <a-input-number
                class="pull-right"
                size="small"
                v-model="list.val"
                :min="list.task_count"
                placeholder="无限制"
              />
            </div>
          </div>

          <div class="mbm">
            <a-button
              type="primary"
              class="pull-right"
              @click="wipSave"
            >
              保存
            </a-button>
            <a-button
              type="danger"
              class="pull-right mrm"
              @click="wipReset"
            >
              重置
            </a-button>
            <div class="clearfix" />
          </div>
        </div>
      </div>
    </a-drawer>
    <AssociatingProject
      v-if="currentBoard.id"
      :visible="showAssociatingProject"
      :kanban-uuid="currentBoard.uuid"
      :selected-project="currentBoard.hasOwnProperty('project') ? currentBoard.project.uuid : ''"
      @closed="associatingProjectClosed"
      @associated="associatingProjectSuccess"
    />
  </div>
</template>
<script>
const MEMBER_ROLE_OWNER = 0;
const MEMBER_ROLE_ADMIN = 1;
const MEMBER_ROLE_USER  = 2;

// const USER_ROLE_ADMIN = "ADMIN";

const ROLE_NAMES = {};
ROLE_NAMES[MEMBER_ROLE_ADMIN] = '管理员';
ROLE_NAMES[MEMBER_ROLE_OWNER] = '创建者';
ROLE_NAMES[MEMBER_ROLE_USER] = '成员';

const DEFAULT_SETTING_TITLE = '更多设置';

const SORT_DEFAULT = 'default';
const SORT_PRIORITY = 'priority';
const SORT_CREATED_TIME = 'time';
const SORT_NAME = 'name';
const SORT_DUEDATE = 'due_date';

const VIEW_KANBAN = 'kanban';
const VIEW_DASHBOARD = 'dashboard';

const DEFAULT_SEARCH_COND = {
  keyword: "",
  prioritys: [],
  labels: [],
  userIds: [],
  status: '',
  due: '',
  sort: SORT_DEFAULT,
  sortMethod: 'desc',
};

const BREADCRUMB_ROUTES = [
  {
    routeName: 'Dashboard',
    label: '首页',
    icon: 'home'
  },
  {
    routeName: 'KanbanAll',
    label: '所有看板',
    icon: 'appstore'
  },
  {
    routeName: 'ProjectKanban',
    label: '项目',
    icon: 'project'
  },
  {
    routeName: 'Kanban'
  }
];

import api from "@/api";
import {priorities, prioritiesColor, copyToPlaster} from "@/utils/index";
import {isEmail} from "@/utils/index";
import store from '@/store';
import i18n from "../../i18n";
import { removeArrItem } from "../../utils";
import LiCheckBox from "../../components/form/LiCheckBox.vue";
import * as types from '@/store/mutation-types';
import CreateBoard from "@/components/kanban/CreateBoard";
import Label from "@/components/common/Label";
import AssociatingProject from "@/components/kanban/AssociatingProject";

export default {
    components: {
      LiCheckBox,
      CreateBoard,
      AssociatingProject,
      Label
    },
    props: {
      boardId: {
        type: String,
        required: true
      }
    },
    filters: {
      ellipsis(value, maxLen = 10) {
        if (!value) return '';
        if (value.length > maxLen) {
          return value.slice(0, maxLen) + '...'
        }
        return value
      }
    },
    created() {
      this.inviteForm = false;
      this.isBoardAdmin = false;

      var self = this;
      this.$bus.$on('card-archived', function() {
        self.loadArchivedCard();
      });
      // this.$bus.$on('card-unarchived', function() {
      //   self.loadArchivedCard();
      // });

      this.$bus.on('list-archived', function() {
        self.loadArchivedList();
      });
      // this.$bus.on('list-unarchived', function() {
      //   self.loadArchivedList();
      // });
      this.$bus.on('kanban-loaded', function(kanban) {
        self.favorited = kanban.is_favorited; // 这里不会触发watch
        self.card_count = kanban.card_count;
      });

      const routeName = this.$route.name;
      if (routeName == 'KanbanDetail') {
        this.view = VIEW_KANBAN;
      }
      if (routeName == 'KanbanDashboard') {
        this.view = VIEW_DASHBOARD;
      }

      api.isKanbanAdmin({query: {id: this.boardId}}).then(res => {
        this.isBoardAdmin = res;
      });
    },

    mounted() {

    },
    
    beforeDestroy() {
      this.$bus.$off('kanban-loaded');
      this.$bus.$off('card-archived');
      this.$bus.$off('card-unarchived');
      this.$bus.$off('list-archived');
      this.$bus.$off('list-unarchived');
    },
    data() {
      return {
        document: document,
        memberVisible: false,
        members: [],
        isBoardAdmin: false,
        archivedTasks: [],
        archivedList: [],
        inviteBtnDisable: false,
        inviteForm: true,
        inviteEmails: "",
        inviteLink: "",
        showSettingDrawer: false,
        settingTitle: DEFAULT_SETTING_TITLE,
        showSettingBtns: true,
        isShowBoardEdit: false,
        isShowAlert: false,
        isShowArchived: false,
        isShowWip: false,
        isShowLabel: false,
        btnDisabled: false,
        priorities: priorities,
        prioritiesColor: prioritiesColor,
        editBoard: {name: '', desc: ''},
        searchCond: Object.assign({}, DEFAULT_SEARCH_COND),
        sortDefault: SORT_DEFAULT,
        sortPriority: SORT_PRIORITY,
        sortCreated: SORT_CREATED_TIME,
        sortName: SORT_NAME,
        sortDueDate: SORT_DUEDATE,
        showCreateBoardModal: false,
        sortRadioStyle: {
          display: 'block',
          height: '30px',
          lineHeight: '30px',
        },
        dueRadioStyle: {
          display: 'block',
          height: '30px',
          lineHeight: '30px',
          width: '100%',
        },
        wipsSetting: [],
        favorited: false,
        favorite_label: 'kanban.favorite',
        favorite_icon_theme: 'outlined',
        card_count: 0,
        view: VIEW_KANBAN,
        settingDrawerVisiable: false,
        settingDrawerDisplay: 'none',
        showAssociatingProject: false,
        BREADCRUMB_ROUTES,
      };
    },
    computed: {
      currentBoard: function() {
        return store.state.board;
      },

      currentBoardLabels: function() {
        return store.state.board_labels;
      },

      labelColors: function() {
        return store.state.board_colors;
      },

      memberInCondition() {
        return function(id) {
          return this.searchCond.userIds.indexOf(id) > -1;
        }
      }, 
      
      labelInCondition() {
        return function(id) {
          return this.searchCond.labels.indexOf(id) > -1;
        }
      },

      priorityInCondition() {
        return function(level) {
          return this.searchCond.prioritys.indexOf(level) > -1;
        }
      },

      currentBoardList: function() {
        const list = store.state.board_list;
        let render = [];
        for (let l of list) {
          let tmp = {name: l.name};
          tmp.default_val = l.wip == 0 ? '' : l.wip;
          tmp.min = l.task_count;
          render.push(tmp);
        }
        return render;
        // return store.state.board_list;
      },

      hasSearchCond: function() {
        return this.searchCond.keyword.length > 0 
          || this.searchCond.labels.length > 0 
          || this.searchCond.prioritys.length > 0
          || this.searchCond.userIds.length > 0
          || this.searchCond.status !== ''
          || this.searchCond.due != '';
      },

      hasSortCond: function() {
        return this.searchCond.sort != SORT_DEFAULT;
      }
    },

    watch: {
        isBoardAdmin: function() {
          // this.loadArchivedCard();
          // this.loadArchivedList();
        },

        boardId: function() {
          this._clearSearchCond();
          api.isKanbanAdmin({query: {id: this.boardId}}).then(res => {
            this.isBoardAdmin = res;
            if (this.isBoardAdmin) {
              // this.loadArchivedCard();
              // this.loadArchivedList();
            }
          });
          this.wipsSettingInit();
        },

        '$store.state.board_list': function() {
          this.wipsSettingInit();
        },

        '$store.state.board.name': function(newVal) {
          const defaultTitle = 'FadeTask 看板 | 任务管理 | 项目管理 | 敏捷看板';
          document.title = newVal + ' | ' + defaultTitle;
        },
        
        settingDrawerVisiable: function(newVal) {
          this.settingDrawerDisplay = newVal ? 'block' : 'none';
        },

        favorited: {
          handler: function() {
            this.initFavorite();
          },
          deep: true
        }
    },

    methods: {
      gotoHome() {
        this.$router.push({name: 'Dashboard'});
      },
      gotoAllKanban() {
        this.$router.push({name: 'KanbanAll'});
      },
      gotoProjectKanban(uuid) {
        this.$router.push({name: 'ProjectKanban', params: {uuid: uuid}});
      },
        getDrawerContainer() {
          let HTMLElement = document.getElementById('main-container');
          console.log(HTMLElement);
          return HTMLElement;
        },
        showMemberContainer() {
          this.memberVisible = true;
          this.loadMember();
        },
        onMemberFilterFocus() {
          this.loadMember();
        },
        hideMemberContainer() {
            // this.memberVisible = false;
        },
        loadMember: function() {
          api.kanbanMembers({query: {id: this.boardId}}).then(res => {
            this.members = res;
          });
        },
        filterVisibleChange(visible) {
          if (visible) {
            this.loadMember();
          }
        },
        loadArchivedCard() {
          if (this.isBoardAdmin) {
            api.archivedTasks({query: {id: this.boardId}}).then(res => {
              this.archivedTasks = res;
            });
          }
        },

        loadArchivedList() {
          if (this.isBoardAdmin) {
            api.archivedList({query: {id: this.boardId}}).then(res => {
              this.archivedList = res;
            });
          }
        },

        getRoleName: function(role) {
          return ROLE_NAMES[role];
        },

        filterMemberChange: function(memberId, checked) {
          if (checked) {
            this.searchCond.userIds.push(memberId);
          } else {
            this.searchCond.userIds = removeArrItem(this.searchCond.userIds, function(id) {
              return id == memberId;
            });
          }
          this.onCardSearchCondChanged();
        },

        filterLabelChange: function(labelId, checked) {
          if (checked) {
            this.searchCond.labels.push(labelId);
          } else {
            this.searchCond.labels = removeArrItem(this.searchCond.labels, function(id) {
              return id == labelId;
            });
          }
          this.onCardSearchCondChanged();
        },

        filterPriorityChange: function(level, checked) {
          if (checked) {
            this.searchCond.prioritys.push(level);
          } else {
            this.searchCond.prioritys = removeArrItem(this.searchCond.prioritys, function(levelInCond) {
              return levelInCond == level;
            });
          }
          this.onCardSearchCondChanged();
        },

        filterStatusChange: function() {
          this.onCardSearchCondChanged();
        },

        filterDueChange: function() {
          this.onCardSearchCondChanged();
        },

        _clearSearchCond: function() {
          this.searchCond.userIds = [];
          this.searchCond.labels = [];
          this.searchCond.prioritys = [];
          this.searchCond.status = '';
          this.searchCond.due = '';
        },

        clearSearchCond: function() {
          this._clearSearchCond();
          this.onCardSearchCondChanged();
        },

        onMemberSearch: function(value) {
          if (value) {
            api.kanbanMemberSearch({query: {id: this.boardId}, params: {keyword: value}}).then(res => {
              this.members = res;
            });
            return;
          }
          this.loadMember();
        },

        initFavorite: function() {
          if (this.favorited) {
            this.favorite_label = 'kanban.unfavorite';
            this.favorite_icon_theme = 'filled';
          } else {
            this.favorite_label = 'kanban.favorite';
            this.favorite_icon_theme = 'outlined';
          }
        },

        favoriteHandler: function() {
          if (this.favorited) {
            api.kanbanUnFavorite({query: {id: this.boardId}}).then(() => {
              this.favorited = false;
              this.$message.success(i18n.t('kanban.unfavorite_success_msg'));
            });
          } else {
            api.kanbanFavorite({query: {id: this.boardId}}).then(() => {
              this.favorited = true;
              this.$message.success(i18n.t('kanban.favorite_success_msg'));
            });
          }
        },

        viewChange: function(view) {
          this.view = view;
          if (view == VIEW_KANBAN) {
            this.$router.push({ name: 'KanbanDetail', params: {id: this.boardId}});
          }
          if (view == VIEW_DASHBOARD) {
            this.goDashboard();
          }
        },

        /**
         * 是否是看板拥有者
         * 
         * @param {Object} m
         */
        isOwner: function(m) {
          return MEMBER_ROLE_OWNER == m.memeber_role;
        },

        /**
         *  是否是看板管理员
         * 
         * @param {Object} m 
         */
        isAdmin: function(m) {
          return MEMBER_ROLE_ADMIN == m.memeber_role;
        },

        /**
         * 是否是看板普通成员
         * 
         * @param {Object} m
         */
        isMember: function(m) {
          return MEMBER_ROLE_USER == m.memeber_role;
        },
        
        /**
         * 设置为管理员
         * @param {Object} m 
         */
        setMemberAdmin: function(m) {
          api.kanbanMemberSetAdmin({query: {id: this.boardId, memberId: m.id}}).then(() => {
            this.$message.success(i18n.t('kanban.member.action_set_admin_success_msg'));
            for (let i = 0; i < this.members.length; i ++) {
              let tmp = this.members[i];
              if (tmp.id == m.id) {
                this.members[i].memeber_role = MEMBER_ROLE_ADMIN;
                break;
              }
            }
          });
        },

        /**
         * 设置为普通成员
         * @param {Object} m 
         */
        setMemberUser: function(m) {
          api.kanbanMemberSetUser({query: {id: this.boardId, memberId: m.id}}).then(() => {
            this.$message.success(i18n.t('kanban.member.action_set_member_success_msg'));
            for (let i = 0; i < this.members.length; i ++) {
              let tmp = this.members[i];
              if (tmp.id == m.id) {
                this.members[i].memeber_role = MEMBER_ROLE_USER;
                break;
              }
            }
          });
        },

        memberAddOrRemove: function(memberId, selected) {
          if (selected) {
            api.kanbanNewMember({query: {id: this.boardId}, data: {member_id: memberId}}).then(res => {
                console.log(res);
            });
            return ;
          }
          api.kanbanMemberRemove({query: {id: this.boardId, memberId: memberId}}).then(() => {
            this.members = removeArrItem(this.members, function(member) {
              return member.id == memberId;
            });
            this.$bus.$emit('member-removed');
          });
        },
        memberActionDisabled: function(m) {
          return MEMBER_ROLE_OWNER == m.memeber_role;
        },

        inviteMember: function() {
          api.kanbanMemberInvite({query: {id: this.boardId}})
            .then(() => {
          });
        },

        unArchive: function(cardId) {
          api.unarchive({query: {cardId: cardId}}).then(() => {
            this.loadArchivedCard();
            this.$bus.$emit('card-unarchived');
          });
        },

        unArchiveList: function(listId) {
          api.unarchiveList({query: {listId: listId}}).then(() => {
            this.loadArchivedList();
            this.$bus.$emit('list-unarchived');
          });
        },

        showInvietForm: function() {
          this.inviteForm = true;
        },

        copyInviteLink: function() {
          api.kanbanMemberInviteLink({query: {id: this.boardId}}).then(link => {
            this.inviteLink = link.url;
            copyToPlaster(this.inviteLink);
            this.$message.success('加入链接已复制到剪贴板');
          });
        },

        labelUpdated: function(newLabel) {
          this.$bus.$emit('label-updated', newLabel);
        },

        labelAdded: function(newLabel) {
          this.$bus.$emit('label-added', newLabel);
        },

        labelDeleted: function(id) {
          this.$bus.$emit('label-deleted', id);
        },

        closeInviteForm: function(e) {
          this.inviteForm = false;
          this.inviteEmails = '';
          this.inviteBtnDisable = false;
          e.preventDefault();
        },

        sendInviteEmails: function() {
          let emails = this.inviteEmails.split(',');
          let validEmails = [];
          for (let email of emails) {
            let trimedEmail = this.$options.filters.trim(email);
            if (!isEmail(trimedEmail)) {
              this.$message.error("邀请邮箱 " + trimedEmail + " 格式错误");
              return false;
            }
            validEmails.push(trimedEmail);
          }
          if (validEmails.length > 0) {
            this.inviteBtnDisable = true;
            api.kanbanMemberInvite({
              query: {id: this.boardId}, 
              data: {'emails': validEmails.join(',')}
            }).then(() => {
              this.$message.success("邀请邮件已发送！");
              this.inviteBtnDisable = false;
              this.inviteEmails = '';
              // this.closeInviteForm();
            }).catch(() => {
              this.inviteBtnDisable = false;
            });
          }
        },

        closeKanban: function() {
          api.kanbanClose({query: {id: this.boardId}}).then((res) => {
            if (res.closed) {
              this.$message.success('看板已关闭！');
              this.$router.push({name: 'Dashboard'});
            }
          });
        },

        updateKanban: function() {
          this.btnDisabled = true;
          api.kanbanUpdate({query: {id: this.boardId}, data: {name: this.editBoard.name, desc: this.editBoard.desc}})
            .then(r => {
              if (r) {
                store.commit(types.CUR_BOARD, this.editBoard);
                this.$message.success('保存成功！');
              } else {
                this.$message.success('保存失败！');
              }
              this.btnDisabled = false;
            }).catch(() => {
              this.btnDisabled = false;
            });
        },

        wipsSettingInit: function() {
          const list = store.state.board_list;
          let render = [];
          for (let l of list) {
            let tmp = {name: l.name};
            tmp.id = l.id;
            tmp.default_val = l.wip == 0 ? '' : l.wip;
            tmp.min = l.task_count;
            tmp.val = tmp.default_val;
            render.push(tmp);
          }

          this.wipsSetting = render;
        },

        wipSave: function() {
          let data = {};
          for (let index = 0; index < this.wipsSetting.length; index++ ) {
            const wip = this.wipsSetting[index];
            data[wip.id] = wip.val;
          }
          api.wipSet({query: {id: this.boardId}, data: data}).then(() => {
            this.$message.success('保存成功！');
            this.$bus.$emit('wip-changed', data);
            // this.wipsSettingInit();
          });
        },

        wipReset: function() {
          let data = {};
          for (let index = 0; index < this.wipsSetting.length; index++ ) {
            const wip = this.wipsSetting[index];
            data[wip.id] = 0;
          }
          api.wipSet({query: {id: this.boardId}, data: data}).then(() => {
            this.$message.success('重置成功！');
            this.$bus.$emit('wip-changed', data);
            // this.wipsSettingInit();
          });
        },

        sortChange: function() {
          this.onCardSearchCondChanged();
        },

        sortMethodChange: function(option) {
          this.searchCond.sortMethod = option.key;
          this.onCardSearchCondChanged();
        },

        onCardSearchCondChanged: function() {
          this.$bus.$emit('kanban-search-cond-change', this.searchCond);
        },

        onCardSearchKeywordChanged: function() {
          this.onCardSearchCondChanged();
        },

        onSettingDrawerClose: function() {
          this.settingDrawerVisiable = false;
          this.closeSettingContainer();
        },

        closeSettingContainer: function() {
          this.settingTitle = DEFAULT_SETTING_TITLE;
          this.showSettingBtns = true;

          this.isShowAlert = false;
          this.isShowArchived = false;
          this.isShowBoardEdit = false;
          this.isShowWip = false;
          this.isShowLabel = false;
        },

        showArchived: function() {
          this.settingTitle = '归档项目';
          this.showSettingBtns = false;
          this.isShowAlert = false;
          this.isShowArchived = true;
          this.isShowBoardEdit = false;
          this.isShowWip = false;
          this.isShowLabel = false;
          this.loadArchivedCard();
        },

        showBoardEdit: function() {
          this.settingTitle = '编辑看板';
          this.showSettingBtns = false;
          this.isShowAlert = false;
          this.isShowArchived = false;
          this.isShowBoardEdit = true;
          this.editBoard = Object.assign({}, this.currentBoard);
          this.isShowWip = false;
          this.isShowLabel = false;
        },

        showAlert: function() {
          this.settingTitle = '提醒设置';
          this.isShowArchived = false;
          this.showSettingBtns = false;
          this.isShowBoardEdit = false;
          this.isShowAlert = true;
          this.isShowWip = false;
          this.isShowLabel = false;
        },

        showWip: function() {
          this.settingTitle = 'WIP设置';
          this.isShowArchived = false;
          this.showSettingBtns = false;
          this.isShowBoardEdit = false;
          this.isShowAlert = false;
          this.isShowLabel = false;
          this.isShowWip = true;
          this.wipsSettingInit();
        },

        showLabel: function() {
          this.settingTitle = '标签管理';
          this.isShowArchived = false;
          this.showSettingBtns = false;
          this.isShowBoardEdit = false;
          this.isShowAlert = false;
          this.isShowWip = false;
          this.isShowLabel = true;
        },
        
        hideArchivedContainer: function() {
          // this.isShowArchived = false;
        },

        archiveTabChange: function(key) {
          if (!this.isBoardAdmin) {
            return;
          }
          if (key == 1) {
            this.loadArchivedCard();
          }
          if (key == 2) {
            this.loadArchivedList();
          }
        },

        createKanban() {
          this.showSettingDrawer = false; // 关闭设置弹窗
          this.showCreateBoardModal = true;
        },

        closeCreateBoardModal() {
          this.showCreateBoardModal = false;
        },

        associatingProjectOpen() {
          this.showAssociatingProject = true;
        },


        associatingProjectSuccess() {
          this.associatingProjectClosed();
          this.$bus.$emit('associating-Ppoject');
        },

        associatingProjectClosed() {
          this.showAssociatingProject = false;
        },

        showSetting: function() {
          this.showSettingDrawer = true;
        },

        onSettingClose: function() {
          this.showSettingDrawer = false;
        },

        goDashboard: function() {
          this.$router.push({ name: 'KanbanDashboard', params: { id: this.boardId }});
        }
    }
}
</script>

<style scoped>
  .board-nav {
    height: 36px;
    line-height: 36px;
    overflow: hidden;
    font-size: 16px;
    background: #ffffff;
    padding: 0px 24px;
    border-bottom: 1px solid #ddd;
  }

  .board-nav .board-name {
    /* padding-left: 15px; */
  }

  .board-header-handler {
    /* display: inline-block; */
    /* margin-right: 36px; */
    height: 36px;
    line-height: 36px;
    /* color: #00; */
    font-size: 14px;
    float: right;
  }

  .board-header-handler-item:hover {
    color: #48a5fc;
    cursor: pointer;
    border-radius: 3px;
  }

  .popover-content{
    width: 280px;
  }

  .popover-title {
    padding: 10px 0;
  }

  .filter-container {
    max-height: 460px;
    overflow-y: overlay;
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: scrollbar;
  }

  .label-filter-item {
    padding-left: 5px;
    padding-right: 5px;
    min-width: 210px;
    line-height: 24px;
    display: inline-block;
    height: 24px;
    cursor: pointer;
    border-radius: 4px;
  }

  .filter-box .filter-title {
    font-weight: bold;
  }

  .filter-box .filter-body {
    padding-left: 15px;
  }

  .board-header-handler-item {
    display: inline;
    height: 32px;
    vertical-align: middle;
    line-height: 36px;
    /* display: inline-block; */
    padding: 5px;
    text-align: center;
    /*border: 1px solid #ffffff;
    height: 32px;
    width: 32px;
    -webkit-border-radius: 16px 16px 16px 16px;
    border-radius: 16px;
    line-height: 36px;
    text-align: center;
    vertical-align: middle; */
  }

  .board-header-handler-item i {
    /* margin-top: -32px; */
  }

  .active-menu {
    background: #f0f2f5;
    border-radius: 5px;
    border: 1px solid #e4e4e4;
  }

  .label-filter .ant-select-selection--multiple .ant-select-selection__choice {
    padding-left: 0px;
    font-size: 12px;
  }

  .label-filter .ant-select-selection__choice .ant-select-selection__choice__content {
    min-width: 40px;
    line-height: 12px;
    text-align: center;
  }

  .list-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 60%;
    display: inline-block;
  }

</style>
