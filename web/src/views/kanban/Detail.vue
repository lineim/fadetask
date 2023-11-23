<template>
  <div 
    id="board" 
    ref="board"
    class="board" 
    :style="{height: bodyHeight}"
  >
    <!-- 已初始化好后，不再展示loading组件(包含拖拽等操作) -->
    <a-spin v-if="loading && !inited" />
    <draggable
      :disabled="draggDisabled"
      @change="listMove"
      style="display: inherit"
      v-model="list"
      :group="{ name: 'list' }"
      class="lists"
      filter=".not-draggle"
    > 
      <div 
        class="list" 
        v-for="(l, listIndex) in list" 
        :key="listIndex"
      >
        <div class="list-inner">
          <div class="list-header">
            {{ l.name }}&nbsp;
            <!-- show wip -->
            <span v-if="l.wip > 0">{{ l.task_count }}&nbsp;/&nbsp; Max {{ l.wip }}</span>
            <span v-else>{{ l.task_count }}</span>

            <!-- show if is completed list -->
            <a-tooltip v-if="l.completed">
              <template slot="title">
                <span>{{ $t('list.complete.tips') }}</span>
              </template>
              <a-icon
                style="color: rgb(46, 161, 33);"
                type="check-circle"
                class="mlm"
              />
            </a-tooltip>

            <!-- List handle popover -->
            <a-popover 
              :style="{float: 'right'}" 
              placement="bottom"
            >
              <template slot="content">
                <p>
                  <span 
                    :style="{ cursor: 'pointer'}" 
                    @click="switchListEdit(l.id, l.name)"
                  > 
                    <a-icon 
                      type="edit" 
                      :style="{'margin-right': '5px'}"
                    />{{ $t('list.edit.label') }}
                  </span>
                </p>
                <p>
                  <span 
                    v-if="!l.completed"
                    :style="{ cursor: 'pointer'}"
                    @click="markAsCompleteList(l.id)"
                  > 
                    <a-icon
                      type="check-circle"
                      :style="{'margin-right': '5px'}"
                    />{{ $t('list.complete.label_complete') }}
                  </span>
                  <span 
                    v-if="l.completed"
                    :style="{ cursor: 'pointer'}" 
                    @click="markAsUnCompleteList(l.id)"
                  > 
                    <a-icon
                      type="close-circle"
                      :style="{'margin-right': '5px'}"
                    />{{ $t('list.complete.label_uncomplete') }}
                  </span>
                </p>
                <p>
                  <a-popconfirm
                    :title="$t('list.archive.confirm_txt')"
                    :ok-text="$t('list.archive.confirm_yes')"
                    :cancel-text="$t('list.archive.confirm_no')"
                    @confirm="archiveList(l.id)"
                  >
                    <span 
                      :style="{ cursor: 'pointer'}" 
                    >
                      <a-icon 
                        type="save" 
                        :style="{'margin-right': '5px'}"
                      />{{ $t('list.archive.label') }}
                    </span>
                  </a-popconfirm>
                </p>
              </template>
              <a-button 
                type="link" 
                icon="ellipsis" 
              />
            </a-popover>
            <!-- edit list -->
            <div class="mtm not-draggle">
              <div v-if="showListEdit(l.id)">
                <a-input 
                  ref="listinput"  
                  v-model="l.name"
                  @click="inputFocus"
                  @pressEnter="listNameSubmit(l.id, l.name)" 
                  :placeholder="$t('list.create.placeholer')"
                />
                <div class="clearfix mtxs">
                  <a-button size="small" class="pull-right" type="primary" @click.stop="listNameSubmit(l.id, l.name)">{{ $t('submit') }}</a-button>
                  <a-button size="small" class="pull-right mrxs" @click.stop="switchListEdit(l.id, l.name)">{{ $t('cancel') }}</a-button>
                </div>
              </div>
            </div>
          </div>
          <div
            class="list-body" 
            :style="getListBodyStyleV2(l.id)"
          >
            <draggable
              :disabled="draggDisabled"
              :group="{ name: 'card' }"
              :move="cardMove"
              @change="cardChange"
              v-model="l.tasks"
              @start="cardDragStart"
              @end="cardDragEnd"
              class="draggable-container"
              :empty-insert-threshold="100"
            > 
              <div 
                v-for="card in l.tasks" 
                :key="card.id" 
                @click="onCardClick(card.id)" 
                class="card"
                :id="'card-' + card.id"
              >
                <div
                  class="mbs"
                  v-if="card.labels.length > 0"
                >
                  <span
                    class="label-small" 
                    v-for="label in card.labels" 
                    :key="label.id" 
                    :style="{background: label.color}" 
                  >{{ label.name }}</span>
                </div>
                <div 
                  class="card-title mbs"
                  :class="{'text-de-line-through': card.done}"
                >
                  <!-- <LiCheckBox 
                    :checked="card.done" 
                    :value="card.id" 
                    label="" 
                    @change="doneOrUndone"
                  /> -->
                  {{ card.title }}
                  <!-- 展示完成状态 -->
                  <a-tooltip
                    v-if="card.done"
                    :title="$t('task.done')"
                  >
                    <a-icon
                      style="color: #2ea121"
                      class="card-footer-item mrxs"
                      type="check-circle"
                    />
                  </a-tooltip>
                  <a-tooltip
                    v-if="card.priority != 2"
                    :title="'优先级：' + getPriorityName(card.priority)"
                  >
                    <a-tag :color="prioritiesColor[card.priority]">
                      <a-icon
                        type="flag"
                      />
                      {{ getPriorityName(card.priority) }}
                    </a-tag>
                  </a-tooltip>
                </div>
                <div
                  class="mbs"
                  v-if="card.members.length > 0"
                >
                  <a-tooltip 
                    v-for="(m, index) in card.members" 
                    :key="index"
                  >
                    <template slot="title">
                      {{ m.name }}
                    </template>

                    <a-avatar 
                      v-if="!m.avatar" 
                      style="color: #f56a00; backgroundColor: #fde3cf;marginRight: 5px;"
                    >
                      {{ firstWord(m.name) }}
                    </a-avatar>
                    <a-avatar 
                      v-if="m.avatar" 
                      :src="m.avatar" 
                      style="marginRight: 5px;" 
                    />
                  </a-tooltip>
                </div>
                <div
                  v-if="Object.keys(customFields).length > 0"
                  class="mbs custom-fields"
                >
                  <div
                    class="mrs"
                    v-for="field in customFields"
                    :key="field.id + '-' + card.id"
                  >
                    {{ field.name }} : 
                    <span v-if="field.type == 'checkbox'">
                      <a-icon
                        v-if="card.task_custom_field_vals[field.id] && card.task_custom_field_vals[field.id] !== '0'"
                        type="check"
                      />
                      <span v-else>--</span>
                    </span>
                    <span v-else>
                      {{ !(field.id in card.task_custom_field_vals) ? '--' : card.task_custom_field_vals[field.id] }}
                    </span>
                  </div>
                </div>
                <div class="card-footer">
                  <!-- 附件 -->
                  <a-tooltip
                    v-if="card.attachment_num"
                    :title="$t('task.attachment.label') + ': ' + card.attachment_num"
                  >
                    <span 
                      class="card-footer-item"
                    >
                      <a-icon type="file-zip" />{{ card.attachment_num }}
                    </span>
                  </a-tooltip>
                  <!-- 检查项 -->
                  <a-tooltip
                    v-if="card.check_list_num > 0"
                    :title="$t('checklist.label') +': '+$t('checklist.total')+card.check_list_num+', '+$t('checklist.finished') + card.check_list_finished_num"
                  >
                    <span
                      class="card-footer-item"
                    >
                      <span
                        v-if="card.check_list_finished_num == card.check_list_num"
                        style="color: #2ea121"
                      >
                        <a-icon
                          type="check-square"
                        /> {{ card.check_list_finished_num }}/{{ card.check_list_num }}
                      </span>
                      <span v-else>
                        <a-icon
                          type="check-square"
                        /> {{ card.check_list_finished_num }}/{{ card.check_list_num }}
                      </span>
                    </span>
                  </a-tooltip>
                  <!-- 展示截止日期 -->
                  <a-tooltip
                    v-if="card.end_time" 
                    :title="card.done ? $t('task.done') : (card.is_due_soon ? $t('task.due_soon') : (card.overfall ? $t('task.due_overfall') : card.end_date + '到期'))"
                  >
                    <span
                      class="card-footer-item due-date" 
                      :style="getDueDateStyle(card)"
                    >
                      <!-- <a-icon :type="card.done ? 'check-circle' : 'history'" /> -->
                      {{ card.end_date }}
                    </span>
                  </a-tooltip>

                  <a
                    class="ant-dropdown-link"
                    @click.stop.prevent="cardQuickModal($event, card.id)"
                  >
                    <div
                      class="card-footer-item edit-card pull-right"
                    >
                      <a-icon type="edit" />
                    </div>
                  </a>
                </div>
              </div>
            </draggable>
          </div>
          <div class="list-footer">
            <a-button 
              icon="plus" 
              :disabled="l.wip > 0 && l.task_count >= l.wip"
              v-if="!showNewCard[l.id]"
              @click="switchNewCard(l.id, true)" 
              block
            >
              {{ $t('task.create.create_btn_label') }}<span v-if="l.wip > 0 && l.task_count >= l.wip">({{ $t('task.create.wip_limited_label') }})</span>
            </a-button>
            <a-form-model 
              v-if="showNewCard[l.id]" 
              ref="cardForm"  
              :rules="cardFormRules" 
              :model="cardForm" 
              layout="horizontal"
              class="not-draggle card"
            >
              <a-form-model-item 
                ref="title" 
                prop="title" 
                :style="{'margin-bottom': '10px'}"
              >
                <a-input 
                  v-model="cardForm.title"
                  @click="newCardInputClick"
                  @blur="cardTitleBlur()"
                  @keyup.enter="cardSubmit(l.id)"
                  style="margin-buttom: 10px;"
                  :placeholder="$t('task.title_placeholder')"
                />
              </a-form-model-item>
              <a-form-model-item>
                <a-button 
                  style="margin-right: 15px;" 
                  @click="switchNewCard(l.id, false)"
                >
                  {{ $t('cancel') }}
                </a-button>
                <a-button 
                  type="primary"
                  :disabled="submitDisable[l.d]"
                  @click="cardSubmit(l.id)"
                >
                  {{ $t('submit') }}
                </a-button>
              </a-form-model-item>
            </a-form-model>
          </div>
        </div>
        <transition-group />
      </div>
    </draggable>

    <div
      v-if="list.length > 0"
      class="list"
    >
      <div class="list-inner">
        <div class="list-header">
          <a-button 
            v-if="!showNewList" 
            type="link" 
            icon="plus" 
            @click="newList" 
            block
          >
            {{ $t('list.create.label') }}
          </a-button>
          <a-form-model 
            v-if="showNewList" 
            ref="listForm"  
            :rules="listFormRules" 
            :model="listForm" 
            layout="horizontal"
          >
            <a-form-model-item 
              ref="name" 
              prop="name" 
              :style="{'margin-bottom': '5px'}"
            >
              <a-input 
                v-model="listForm.name" 
                @blur="
                  () => {
                    $refs.name.onFieldBlur();
                  }" 
                :placeholder="$t('list.create.placeholer')" 
              />
            </a-form-model-item>
            <a-form-model-item>
              <div class="clearfix">
                <a-button 
                  class="pull-right"
                  type="primary" 
                  size="small" 
                  @click="listSubmit()"
                >
                  {{ $t('submit') }}
                </a-button>
                <a-button 
                  class="mrxs pull-right"
                  size="small" 
                  @click="newListCancel()"
                >
                  {{ $t('cancel') }}
                </a-button>
              </div>
            </a-form-model-item>
          </a-form-model>
        </div>
      </div>
    </div>

    <cardModal
      :card-id="showCardId"
      :board-id="uuid"
      :show-card-moal="showCardModal"
      @close="closeCardModal"
      @cardchange="onCardChange"
      @archive="closeCardModal"
      @moved="closeCardModal"
      @moved-to-other-board="_cardMovedToOtherBoardFromModal"
    />
    <a-modal
      v-model="showCardQuickModal"
      dialog-class="card-quick-edit-modal"
      :footer="null"
      :width="cardQuickStyle.width"
      :dialog-style="cardQuickStyle"
      :destroy-on-close="true"
    >
      <span
        slot="closeIcon"
        class="quick-edit-close"
      />
      <div
        v-if="currentQuickEditCard.labels.length"
        class="card-quick-edit-label mbs clearfix"
      >
        <span
          class="label-small" 
          v-for="label in currentQuickEditCard.labels" 
          :key="label.id" 
          :style="{background: label.color}"
        />
      </div>
      <a-textarea
        class="mbs card-quick-edit-input"
        v-model="currentQuickEditCard.title"
        placeholder=""
        :auto-size="{ minRows: 4, maxRows: 6 }"
      />
      <div>
        <a-tooltip 
          v-for="(member, mIndex) in currentQuickEditCard.members" 
          :key="mIndex"
        >
          <template slot="title">
            {{ member.name }}
          </template>

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
        </a-tooltip>
      </div>

      <div class="card-footer">
        <span 
          v-if="currentQuickEditCard.attachment_num" 
          class="card-footer-item"
        >
          <a-icon type="file-zip" />{{ currentQuickEditCard.attachment_num }}
        </span>
        <span 
          v-if="currentQuickEditCard.end_time" 
          class="card-footer-item due-date" 
          :style="getDueDateStyle(currentQuickEditCard)"
        >
          <a-icon type="history" />
          {{ currentQuickEditCard.end_date }}
        </span>
      </div>

      <div
        class="more-quick-handle"
        :style="moreQuickHandleStyle"
      >
        <div class="mbxs">
          <a-button
            icon="folder-open"
            size="small"
            @click="onCardClick(currentQuickEditCard.id)"
          >
            {{ $t('open') }}
          </a-button>
        </div>

        <div class="mbxs">
          <LabelPopover
            :labels="kanbanLabels"
            :colors="labelsColors"
            :kanban-id="parseInt(id)"
            :title="$t('label')"
            :selected-labels="currentQuickEditCard.labels"
            @change="labelSelectChange"
            @newlabel="newlabelCreated"
            @labelupdated="labelupdated"
            @labeldeleted="labeldeleted"
          >
            <a-button
              slot="trigger"
              icon="tags"
              size="small"
            >
              {{ $t('label') }}
            </a-button>
          </LabelPopover>
        </div>
        
        <div class="mbxs">
          <CardMember
            :card-id="currentQuickEditCard.id"
            :kanban-id="uuid"
            :title="$t('member')"
            @memberadd="_cardMemberAdd"
            @memberremove="_cardMemberRemove"
          >
            <a-button
              slot="trigger"
              icon="user-add"
              size="small"
            >
              {{ $t('member') }}
            </a-button>
          </CardMember>
        </div>

        <div class="mbxs">
          <CardMove
            :card-id="currentQuickEditCard.id"
            :board-id="parseInt(id)"
            :title="$t('task.move.title')"
            @moved="_cardMovedToOtherBoard"
          >
            <a-button
              slot="trigger"
              icon="export"
              size="small"
            >
              {{ $t('task.move.label') }}
            </a-button>
          </CardMove>
        </div>

        <div class="mbxs">
          <a-popconfirm
            :title="$t('task.archive.confirm_txt')"
            :ok-text="$t('task.archive.confirm_yes')"
            :cancel-text="$t('task.archive.confirm_no')"
            @confirm="archive(currentQuickEditCard.id)"
          >
            <a-button
              icon="save"
              size="small"
            >
              {{ $t('task.archive.label') }}
            </a-button>
          </a-popconfirm>
        </div>
        <div class="mbxs">
          <a-button
            @click.prevent.stop="closeQuickEdit($event);"
            icon="close"
            size="small"
          >
            {{ $t('quit') }}
          </a-button>
        </div>
      </div>
      <div class="clearfix">
        <a-button
          type="primary"
          class="pull-right"
          :disabled="currentQuickEditCard.title === ''"
          size="small"
          style="margin-top: 10px;"
          @click.prevent.stop="_quickSaveTitle()"
        >
          {{ $t('save') }}
        </a-button>
      </div>
    </a-modal>
  </div>
</template>
<script>
import draggable from "vuedraggable";
// import moment from 'moment';
import api from "@/api";
import store from '@/store';
import * as types from '@/store/mutation-types';
// import Card from '@/components/kanban/Card.vue';
import CardModal from '@/components/kanban/CardModal.vue';
import {priorities, prioritiesColor, getPriorityName, removeArrItem, isMobileDevice} from "@/utils/index";
import LabelPopover from '@/components/kanban/LabelPopover';
import CardMember from '@/components/kanban/CardMember';
import CardMove from '@/components/kanban/CardMove';
import i18n from '../../i18n';

// import liCheckBox from "@/components/form/LiCheckBox";

const PAGE_HEADER_HEADER = 58;
const DEFAULT_FOOTER_HEIGHT = 50; // list footer 默认高度
const FOOTER_EXPEND_HEIGHT = 121; // list footer 展开后的高度
const DEFAULT_HEADER_HEIGHT = 45; // list header 默认高度
const DEFAULT_BODY_V_PADDING = 0; // list body 垂直 padding
const DEFAULT_LIST_EDIT_FORM_HEIGHT = 61+15; // list edit form 高度
const DEFAULT_BOARD_NAV_HEIGHT = 36; // 看板导航高度
const DEFAULT_ICP_HEIGHT = 0; // 备案信息展示栏高度

function onBus(vueInst, uuid) {
  vueInst.$bus.$on('card-unarchived', function() {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('list-unarchived', function() {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('kanban-search-cond-change', cond => {
    vueInst.searchCond = cond;
    console.log('kanban-search-cond-changed!');
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('wip-changed', () => {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('member-removed', () => {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('label-updated', () => {
    vueInst.loadData(uuid);
    // vueInst.labelupdated(label);
  });

  vueInst.$bus.$on('label-added', () => {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('label-deleted', () => {
    vueInst.loadData(uuid);
  });

  vueInst.$bus.$on('associating-Ppoject', () => {
    vueInst.loadData(uuid);
  });
}

function offBus(vueInst) {
  vueInst.$bus.$off('card-unarchived');
  vueInst.$bus.$off('list-unarchived');
  vueInst.$bus.$off('kanban-search-cond-change');
  vueInst.$bus.$off('wip-changed');
  vueInst.$bus.$off('member-removed');
  vueInst.$bus.$off('label-updated');
  vueInst.$bus.$off('label-added');
  vueInst.$bus.$off('label-deleted');
}

export default {
  components: {
      // card: Card,
      cardModal: CardModal,
      // LiCheckBox: liCheckBox,
      draggable,
      LabelPopover,
      CardMember,
      CardMove,
  },
  data() {
    return {
      id: 0,
      uuid: '',
      busEventInited: false,
      bodyHeight: document.documentElement.clientHeight,
      inited: false,
      loading: true,
      kanban: {},
      kanbanLabels: [],
      labelsColors: [],
      isKanbanFirstLoad: true,
      list: [],
      listCards: {},
      cards: [],
      customFields: {},
      currentQuickEditCard: {
        title: '',
        labels: [],
        members: []
      },
      draggDisabled: false,
      searchCond: {
        keyword: "",
        prioritys: [],
        labels: [],
        userIds: [],
      },
      newCardTitle: '',
      showNewCard: {},
      submitDisable: {},
      newListName: '',
      showNewList: false,
      showCardQuickModal: false,
      cardQuickStyle: {
        position: 'absolute',
        top: 0,
        left: 0,
        marginLeft: 0,
        width: '244px'
      },
      moreQuickHandleStyle: {
        
      },
      cardForm: {
        title: ''
      },
      cardFormRules: {
        title: [
          {required: true, message: '请输入卡片标题', trigger: 'blur'}
        ]
      },

      listForm: {
        name: ''
      },
      listFormRules: {
        name: [
          {required: true, message: '名称', trigger: 'blur'},
          {min: 1, max: 16, message: '名称需要在1到16个字符之间', trigger: 'blur' }
        ]
      },
      editList: {},
      listOriginName: {},
      listEditFormHeight: {},
      showCardModal: false,
      showCardId: 0,
      footerAcivityListIds: [],
      priorities: priorities,
      prioritiesColor: prioritiesColor,
      canShowDragMsg: true,
      listFooterHeight: DEFAULT_FOOTER_HEIGHT
    };
  },

  created() {
    store.commit(types.CUR_BOARD, {}); // 每次进入，重置当前board
    var self = this;
    var uuid = this.$route.params.id;
    this.uuid = uuid;
    this.loadData(uuid);
    // 销毁时移除监听
    this.$once('hook:beforeDestory',()=>{
      offBus(self);
    });

  },

  mounted() {
    var kanbanBody = document.getElementById('board');
    kanbanBody.style.height = (Number(document.documentElement.clientHeight)-PAGE_HEADER_HEADER-DEFAULT_BOARD_NAV_HEIGHT-DEFAULT_ICP_HEIGHT) + 'px';
    window.addEventListener('resize', this.listenResize); 
    // 销毁时移除监听
    this.$once('hook:beforeDestory',()=>{
      window.removeEventListener("resize", this.listenResize);
    });
    this.draggDisabled = this.isMobileDevice();
    this.inited = true;
    let hash = location.hash;
    let pos = hash.indexOf('?');
    if (pos > -1) {
      let paramsStr = hash.substring(pos, hash.length);
      let params = new URLSearchParams(paramsStr);
      let cardId = parseInt(params.get('card_id'));
      if (cardId > 0) {
        this.onCardClick(cardId, false);
      }
    }

  },

  watch: {
    '$route.path':function() {
      this.uuid = this.$route.params.id;
      offBus(this);
      this.loadData(this.uuid);
    },

    '$route.query': function() {
      const cardId = this.$route.query.card_id;
      if (typeof(cardId) != 'undefined' && cardId != this.showCardId) {
        this.onCardClick(parseInt(cardId), false);
      }
    },

    kanban: function(data) {
      store.commit(types.CUR_BOARD, data);
    },
    bodyHeight: function(val) {
      var kanbanBody = document.getElementById('board');
      kanbanBody.style.height = (Number(val)-PAGE_HEADER_HEADER-DEFAULT_BOARD_NAV_HEIGHT-DEFAULT_ICP_HEIGHT) + 'px';
    },
    list: function() {
      for(let I = 0; I < this.list.length; I++) {
        let list = this.list[I];
        if (list.id in this.showNewCard) {
            continue;
        } else {
          this.showNewCard[list.id] = false;
        }

        if (list.id in this.submitDisable) {
            continue;
        } else {
          this.submitDisable[list.id] = false;
        }
      }
    }
  },

  computed: {
    getListCards() {
      return function(listId) {
        return this.listCards[listId];
      }
    },
    getListBodyStyleV2() {
      return function (listId) {
        const listHeaderH = DEFAULT_HEADER_HEIGHT;
        const listBodyVPadding = DEFAULT_BODY_V_PADDING;

        let listFooterHeight = DEFAULT_FOOTER_HEIGHT;
        if (this.footerAcivityListIds.indexOf(listId) > -1) { // 展开
          listFooterHeight = FOOTER_EXPEND_HEIGHT;
        }
        let listEditFormHeight = (typeof(this.listEditFormHeight[listId]) == "undefined" || !this.listEditFormHeight[listId]) ? 0 : this.listEditFormHeight[listId];
        const height = this.bodyHeight - PAGE_HEADER_HEADER - DEFAULT_ICP_HEIGHT - DEFAULT_BOARD_NAV_HEIGHT - listHeaderH - listEditFormHeight - listBodyVPadding - listFooterHeight;

        return {'maxHeight': height + 'px'};
      }
    }
  },

  methods: {
    getPriorityName,

    isMobileDevice,

    initBusEvent() {
      if (this.busEventInited) {
        return true;
      }
      // 注册bus事件
      onBus(this, this.uuid);
      this.busEventInited = true;
    },

    setTitle(title) {
      document.title = title + " | " + document.title;
    },

    inputFocus(e) {
      e.target.focus(); // 解决 vue draggable 增加 filter 后不能点击输入框问题
    },

    getListDraggGroup(list) {
      let put = true;
      if (list.wip > 0 && list.task_count > list.wip) {
        put = false;
      }
      return {name: 'list', put: put};
    },

    getWipText(wip) {
      return wip === 0 ? '∞' : wip;
    },

    newCardInputClick(e) {
      e.preventDefault();
      e.target.focus(); // 解决 vue draggable 增加 filter 后不能点击输入框问题
    },

    loadData: function(uuid) {
      this.loading = true;
      let searchCond = {
        keyword: this.searchCond.keyword,
        userIds: '',
        labels: '',
        prioritys: '',
        status: '',
        due: '',
        sort: this.searchCond.sort,
        sort_method: this.searchCond.sortMethod
      };
      if (this.searchCond.labels.length > 0) {
        searchCond.labels = this.searchCond.labels.join(',');
      }
      if (this.searchCond.prioritys.length > 0) {
        searchCond.prioritys = this.searchCond.prioritys.join(',');
      }
      if (this.searchCond.userIds.length > 0) {
        searchCond.userIds = this.searchCond.userIds.join(',');
      }
      if (this.searchCond.status !== '') {
        searchCond.status = this.searchCond.status;
      }
      if (this.searchCond.due != '') {
        searchCond.due = this.searchCond.due;
      }

      api
        .kanbanDetail({ query: { id: uuid }, params: searchCond })
        .then(data => {
          this.kanban = data.kanban;
          this.id = this.kanban.id;
          this.kanbanLabels = data.kanban_labels;
          this.labelsColors = data.label_colors;
          this.list = data.list;
          this.listCards = data.list_tasks;
          this.cards = data.tasks;
          this.customFields = data.custom_fields;
          store.commit(types.CUR_BOARD_LABELS, data.kanban_labels);
          store.commit(types.CUR_BOARD_LIST, data.list);
          store.commit(types.CUR_BOARD_LABELS_COLORS, data.label_colors);
          if (typeof(this.kanban.project.uuid) != 'undefined') {
            store.commit(types.CUR_PROJECT, this.kanban.project);
          }
          if (this.isKanbanFirstLoad) {
            this.setTitle(this.kanban.name);
            this.isKanbanFirstLoad = false;
          }
          this.loading = false;
          this.kanban.card_count = this.cards.length;
          this.initBusEvent();
          this.$bus.$emit('kanban-loaded', this.kanban);
        })
        .catch(() => {
          this.loading = false;
        });
    },

    firstWord: function(s){
      return s.slice(0, 1);
    },

    listenResize: function() {
      this.bodyHeight = document.documentElement.clientHeight; //窗口高度
    },

    getListBodyStyle: function() {
      const listHeaderH = 45;
      const listBodyVPadding = 14;
      // const listFooter = 39;
      const height = this.bodyHeight - listHeaderH - listBodyVPadding - PAGE_HEADER_HEADER - this.listFooterHeight;

      return {'maxHeight': height + 'px'};
    },

    cardQuickModal: function(e, id) {
      let cardEl = document.getElementById("card-" + id);
      let card = this._getCardById(id);
      if (card) {
        this.currentQuickEditCard = card;
      }
      const bodyWidth = document.body.clientWidth;
      const pos = cardEl.getBoundingClientRect();
      const cardPosLeft = pos.left || 0;
      const cardPosY = pos.top || 0;
      const cardWidth = pos.width || 0;
      const cardPosRight = pos.right || 0;

      if (bodyWidth - cardPosRight < 220) {
        delete this.moreQuickHandleStyle.right;
        this.moreQuickHandleStyle.left = '-70px';
      } else {
        this.moreQuickHandleStyle.right = '-70px';
        delete this.moreQuickHandleStyle.left;
      }

      this.cardQuickStyle.width = cardWidth + 'px';
      this.cardQuickStyle.top = cardPosY + 'px';
      this.cardQuickStyle.left = cardPosLeft + 'px';
      
      this.showCardQuickModal = true;
    },

    closeQuickEdit: function() {
      this.showCardQuickModal = false;
    },

    _quickSaveTitle: function() {
      const query = {
        boardId: this.id,
        id: this.currentQuickEditCard.id,
      };
      const data = {
        title: this.currentQuickEditCard.title
      };
      api.saveTitle({query: query, data: data}).then(() => {
        this.$message.success('修改成功！');

        const listLen = this.list.length;
        let changed = false;
        for (let l = 0; l < listLen; l ++) {
          if (changed) {
            break;
          }
          let list = this.list[l];
          let cards = list.tasks;
          const cardsCount = cards.length;
          for (let i = 0; i < cardsCount; i ++) {
            let card = cards[i];
            if (card.id == this.currentQuickEditCard.id) {
              cards[i].title = this.currentQuickEditCard.title
              list.tasks = cards;
              this.list[l] = list;
              changed = true;
              break;
            }
          }
        }
        this.closeQuickEdit();
      })
    },

    _cardMovedToOtherBoardFromModal: function(cardId) {
      this._cardMovedToOtherBoard(cardId);
      this.closeCardModal();
    },

    _cardMovedToOtherBoard: function(cardId) {
      this._rmCardFromList(cardId, this.currentQuickEditCard.list_id);
      this.closeQuickEdit();
    },

    _cardMemberRemove: function(cardId, memberId) {
      let members = this.currentQuickEditCard.members;
      this.currentQuickEditCard.members = removeArrItem(members, function(item) {
        return item.id == memberId;
      });

      const llen = this.list.length;
      for (let l = 0; l < llen; l ++) {
        let list = this.list[l];
        const clen = list.tasks.length;
        for (let i = 0; i < clen; i ++) {
          let task = list.tasks[i];
          if (task.id != cardId) {
            continue;
          }
          task.members = removeArrItem(task.members, function(m) {
            return m.id == memberId;
          });
          list.tasks[i] = task;
          this.list[l] = list;
          break;
        }
      }
    },

    _cardMemberAdd: function(cardId, member) {
      this.currentQuickEditCard.members.push(member);
      const llen = this.list.length;
      for (let l = 0; l < llen; l ++) {
        let list = this.list[l];
        const clen = list.tasks.length;
        for (let i = 0; i < clen; i ++) {
          let task = list.tasks[i];
          if (task.id != cardId) {
            continue;
          }
          task.members.push(member);
          list.tasks[i] = task;
          this.list[l] = list;
          break;
        }
      }
    },

    labelSelectChange: function(labelId, isSelected) {
      if (isSelected) {
        api.addLabel({query: {taskId: this.currentQuickEditCard.id, labelId: labelId}}).then((cardLabels) => {
          this.currentQuickEditCard.labels = cardLabels;
          this._updateCardLabels(this.currentQuickEditCard.id, this.currentQuickEditCard.list_id, cardLabels);
        });
        return;
      } 
      api.rmLabel({query: {taskId: this.currentQuickEditCard.id, labelId: labelId}}).then(() => {
        let labels = this.currentQuickEditCard.labels;
        let len = labels.length;
        
        let newLabels = [];
        for (let i = 0; i < len; i++) {
          if (labels[i].id != labelId) {
            newLabels.push(labels[i]);
          }
        }
        this.currentQuickEditCard.labels = newLabels;
        this._updateCardLabels(this.currentQuickEditCard.id, this.currentQuickEditCard.list_id, newLabels);
      });
    },

    _updateCardLabels: function(cardId, listId, labels) {
      const len = this.list.length;
      let list = {};
      for (var l = 0; l < len; l ++) {
        const tmp = this.list[l];
        if (tmp.id == listId) {
          list = tmp;
          break;
        }
      }

      let cards = list.tasks;

      const clen = cards.length;
      for (let i = 0; i < clen; i ++) {
        const card = cards[i];
        if (card.id == cardId) {
          card.labels = labels;
          list.tasks[i] = card;
          this.list[l] = list;
          break;
        }
      }
    },

    _getListById: function(id) {
      const len = this.list.length;
      let list = {};
      for (let l = 0; l < len; l ++) {
        const tmp = this.list[l];
        if (tmp.id == id) {
          list = tmp;
          break;
        }
      }
      return list;
    },

    archive: function(cardId) {
      api.archive({
        query: {cardId: cardId}
      }).then(res => {
        if (res) {
          this.$message.success("已归档");
          this.$bus.$emit('card-archived');
          this._rmCardFromList(cardId, this.currentQuickEditCard.list_id);
          this.closeQuickEdit();
        } else {
          this.$message.error("归档失败");
        }
      });
    },

    _rmCardFromList: function(cardId, listId) {
      const llen = this.list.length;
      for (let l = 0; l < llen; l ++) {
        let list = this.list[l];
        if (list.id != listId) {
          continue;
        }
        list.tasks = removeArrItem(list.tasks, function(item) {
          return item.id == cardId;
        });
        list.task_count --;
        this.list[l] = list;
        break;
      }
    },

    newlabelCreated: function(label) {
      this.kanbanLabels.push(label);
    },

    labelupdated: function(label) {
      const kanbanLabelsLen = this.kanbanLabels.length;
      for (let kll = 0; kll < kanbanLabelsLen; kll ++) {
        let tmp = this.kanbanLabels[kll];
        if (tmp.id == label.id) {
          this.kanbanLabels[kll] = label;
        }
      }

      const listLen = this.list.length;
      for (let l = 0; l < listLen; l ++) {
        let list = this.list[l];
        let cards = list.tasks;
        const cardsCount = cards.length;
        for (let i = 0; i < cardsCount; i ++) {
          let card = cards[i];
          let labels = card.labels;
          const labelLen = labels.length;

          let changed = false;
          for (let ll = 0; ll < labelLen; ll ++) {
            const tmpLabel = labels[ll];
            if (label.id == tmpLabel.id) {
              labels[ll] = label;
              changed = true;
            }
          }
          if (changed) {
            card.labels = labels;
            cards[i] = card;
          }
        }
        list.tasks = cards;
        this.list[l] = list;
      }
    } ,
    
    labeldeleted: function(labelId) {
      this.currentQuickEditCard.labels = removeArrItem(this.currentQuickEditCard.labels, function(item) {
        return item.id == labelId;
      });

      this.kanbanLabels = removeArrItem(this.kanbanLabels, function(item) {
        return item.id == labelId;
      });
      
      const listLen = this.list.length;
      for (let l = 0; l < listLen; l ++) {
        let list = this.list[l];
        let cards = list.tasks;
        const cardsCount = cards.length;
        for (let i = 0; i < cardsCount; i ++) {
          let card = cards[i];
          card.labels = removeArrItem(card.labels, function(item) {
            return item.id == labelId;
          });
          cards[i] = card;
        }
        list.tasks = cards;
        this.list[l] = list;
      }
    },

    _getCardById: function(id) {
      const len = this.cards.length;
      for (let i = 0; i < len; i ++) {
        const card = this.cards[i];
        if (card.id == id) {
          return card;
        }
      }
      return false;
    },
    
    cardMove: function(evt) {
      let relatedContext = evt.relatedContext;
      let draggedContext = evt.draggedContext;

      // 没有定位到目标列表, 当目标列表没有卡片时，会没有element，因此这里要返回true
      if (!Object.prototype.hasOwnProperty.call(relatedContext, 'element')) { // 没有拖拽到容器中
        return true;
      }

      // 同一列中拖拽，不做wip的限制
      if (draggedContext.element.list_id == relatedContext.element.list_id) {
        if (this.searchCond.sort && this.searchCond.sort != 'default') {
          if (this.canShowDragMsg) { // 防止重复展示错误信息
            this.canShowDragMsg = false;
            this.$message.error(i18n.t('task.drag.sort_can_drag_tips')).then(() => {
              this.canShowDragMsg = true;
            });
          }
          return false;
        }
        return true;
      }

      let newListId = evt.relatedContext.element.list_id;
      let list = this._getListById(newListId);

      if (list.wip == 0) { // WIP无限制
        return true;
      }
      
      if (list.task_count >= list.wip && list.wip > 0) {
        if (this.canShowDragMsg) { // 防止重复展示错误信息
          this.canShowDragMsg = false;
          const msg = i18n.t('task.drag.wip_limit_can_drag_tips', {listName: list.name, wipLimit: list.wip});
          this.$message.error(msg).then(() => {
            this.canShowDragMsg = true;
          });
        }
        // this.$message.warning(list.name + ' wip限制为' + list.wip + '，不能拖拽到该列！');
        return false;
      }
      return true;
    },

    cardChange: function(evt) {
      var cardId = 0;
      var cardOldListId = 0;
      var cardList = null; // 卡片变更后，所在的列
      if ('moved' in evt) {
        cardOldListId = evt.moved.element.list_id;
        cardId = evt.moved.element.id;
      }
      if ('added' in evt) {
        cardOldListId = evt.added.element.list_id;
        cardId = evt.added.element.id;
      }
      if (!cardId) {
        return;
      }
      for (let i = 0; i < this.list.length; i ++) {
        let list = this.list[i];
        for (let j = 0; j < list.tasks.length; j ++) {
          let card = list.tasks[j];
          if (card.id == cardId) {
            list.tasks[j].list_id = list.id; // 修改card中的list_id字段
            cardList = list;
            break;
          }
        }
        if (cardList) { // 找到了，停止循环
          break;
        }
      }
      if (!cardList) {
        return;
      }
      let cardsIndex = {};
      let currentCard = {};
      for (let k = 0; k < cardList.tasks.length; k ++) { // 计算每个卡片对应的索引
        currentCard = cardList.tasks[k];
        cardsIndex[currentCard.id] = k;
      }
      let postData = {
        cardListId: cardList.id,
        cardsSort: cardsIndex
      };

      api.cardMove({query: {id: this.id, taskId: cardId}, data: postData}).then((data) => {
        if (data.cardChanged) {
          this.loadData(this.uuid);
        }
      });
      this.changeListCardCountOnCardMove(cardList.id, cardOldListId);
    },

    changeListCardCountOnCardMove(increaseListId, reduceListId) {
      let increased = false;
      let reduced = false;
      if (increaseListId == reduceListId) {
        return;
      }
      for (let i = 0; i < this.list.length; i ++) {
        if (increased && reduced) {
          break;
        }
        const list = this.list[i];
        if (list.id == increaseListId) {
          this.list[i].task_count ++;
          increased = true;
        }
        if (list.id == reduceListId) {
          this.list[i].task_count --;
          reduced = true;
        }
      }
    },

    listMove: function(evt) {
      if ('moved' in evt) {
        let postData = {};
        for (let i = 0; i < this.list.length; i ++) {
          postData[this.list[i].id] = i;
        }
        api.listSort({query: {id: this.id}, data: postData}).then(() => {});
      }
    },

    cardDragStart: function() {
      // let minHeight = evt.from.querySelector('div.card').offsetHeight;
      // let containers = document.querySelectorAll("div.list-body > div.draggable-container");
      // containers.forEach(function(container) {
      //   container.style.minHeight = minHeight + 'px';
      // });
    },

    cardDragEnd: function() {
      let containers = document.querySelectorAll("div.list-body > div.draggable-container");
      containers.forEach(function(container) {
        container.style.removeProperty('min-height');
      });
    },

    switchNewCard: function(listId, on = true) {
        let tmp = this.showNewCard;

        if (on) { // TODO: 优化，一个list编辑，不能影响其他list的高度。
          this.listFooterHeight = FOOTER_EXPEND_HEIGHT;
        } else {
          this.listFooterHeight = DEFAULT_FOOTER_HEIGHT;
        }

        if (on) { // 展示某个列表下的card表单，需要关闭其他列表下的表单
          for (var key in tmp){
            tmp[key] = false;
          }
          // 将底部展开的list id 加入到数组
          this.footerAcivityListIds = [listId];
        } else {
          this.footerAcivityListIds = []; 
        }
        this.showNewCard = {};

        tmp[listId] = on;
        this.showNewCard = tmp;
    },

    newList: function() {
      this.showNewList = true;
    },

    cardTitleBlur: function() {
      this.$refs.title[0].onFieldBlur();
    },

    cardSubmit: function(listId) {
      this.$refs.cardForm[0].validate(valid => {
        if (valid) {
          let postData = this.cardForm;
          postData.list_id = listId;
          this.submitDisable[listId] = true;
          api
            .cardCreate({query: {boardId: this.id}, data: postData})
            .then(() => {
              this.$message.success(i18n.t('task.create.success_msg'));
              this.loadData(this.uuid);
              this.submitDisable[listId] = false;
              this.$refs.cardForm[0].resetFields();
              this.$refs.cardForm[0].focus();
              // this.switchNewCard(listId, false);
            })
            .catch(() => {
              this.submitDisable[listId] = false;
            });
        } else {
          return false;
        }
      });
    },

    listSubmit: function() {
      this.$refs.listForm.validate(valid => {
        if (valid) {
          api
            .listCreate({query: {id: this.id}, data: this.listForm})
            .then(() => {
              this.$message.success(i18n.t('list.create.success_msg'));
              this.loadData(this.uuid);
              this.$refs.listForm.resetFields();
              this.showNewList = false;
            })
            .catch(err => {
              this.$message.error(err.message);
            });
        } else {
          return false;
        }
      });
    },

    showListEdit: function(listId) {
      return this.editList[listId];
    },

    switchListEdit: function(listId, listName) {
      let show = false;
      if (typeof(this.editList[listId]) == "undefined" || !this.editList[listId]) {
        show = false;
        this.listOriginName[listId] = listName;
        this.listEditFormHeight[listId] = DEFAULT_LIST_EDIT_FORM_HEIGHT;
        // todo: 编辑框自动获取交点
      } else {
        show = true;
        this.listEditFormHeight[listId] = 0;
        let originName = this.listOriginName[listId];
        if (!originName) {
          return;
        }
        let list = this.list;
        for (let i = 0; i < list.length; i ++) {
          if (list[i].id == listId) {
            list[i].name = originName;
          }
        }
        this.list = [];
        this.list = list;
      }
      this.editList = {};
      let tmp = {};
      tmp[listId] = !show;
      this.editList = tmp;
    },

    archiveList: function(listId) {
      api.archiveList({query: {listId: listId}}).then(() => {
        this.loadData(this.uuid);
        this.$bus.$emit('list-archived');
      });
    },

    closeListEdit: function(listId) {
      this.editList = {};
      let tmp = {};
      tmp[listId] = false;
      this.editList = tmp;
      this.listEditFormHeight = {};
    },

    listNameSubmit: function(listId, listName) {
      if (!listName) {
        this.$message.error('List Name Error!');
        return;
      }
      api.listUpdate({query: {id: this.id, listId: listId}, data: {name: listName}}).then((resp) => {
        if (resp) {
          this.closeListEdit(listId);
        }
      });
    },

    getListEditIcon: function(listId) {
      if (typeof(this.editList[listId]) == "undefined" || !this.editList[listId]) {
        return "edit";
      }
      return this.editList[listId] ? 'close-circle' : 'edit';
    },

    newListCancel: function() {
        this.$refs.listForm.resetFields();
        this.showNewList = false;
    },

    markAsCompleteList: function(id) {
      api.listAsComplete({query: {id: id}}).then(() => {
        for (let i = 0; i < this.list.length; i ++) {
          let list = this.list[i];
          console.log(list.id, id);
          if (list.id == id) {
            list.completed = 1;
            this.$set(this.list, i, list);
            console.log(this.list[i]);
            break;
          }
        }
      });
    },

    markAsUnCompleteList: function(id) {
      api.listAsUnComplete({query: {id: id}}).then(() => {
        for (let i = 0; i < this.list.length; i ++) {
          let list = this.list[i];
          if (list.id == id) {
            list.completed = 0;
            this.$set(this.list, i, list);
            console.log(this.list[i]);
            break;
          }
        }
      });
    },

    getDueDateStyle: function(card) {
      // let endDate = card.end_date;
      // let isDone = card.done;
      // const now = moment();
      // const end = moment(endDate);
      let styles = {};
      // 已完成，不管是否过期都展示绿色
      if (card.done) {
        styles.color = 'rgb(96, 189, 78)';
        return styles;
      }
      // 即将过期
      if (card.is_due_soon && !card.done) {
        styles.color = '#ffc60a';
        return styles;
      }
      // 已过期
      if (card.overfall && !card.done) {
        styles.color = 'rgb(235, 91, 70)';
      }
      return styles;
    },

    doneOrUndone: function(id, idDone, e) {
      e.preventDefault();
      if (idDone) {
        api.taskDone({query: {id: id, boardId: this.id}}).then(() => {
          this.loadData(this.uuid);
        });
      } else {
        api.taskUndone({query: {id: id, boardId: this.id}}).then(() => {
          this.loadData(this.uuid);
        });
      }
    },
    // getListCards: function(listId) {
    //     return this.listCards[listId];
    // },

    // Card
    // Card 点击事件，弹出模态框
    onCardClick: function(cardId, handleHash = true) {
      this.showCardQuickModal = false;
      this.showCardModal = true;
      this.showCardId = cardId;
      if (handleHash) {
        let newHash = location.hash + "?card_id="+cardId;
        location.hash = newHash;
      }
    },

    // Card 模态框关闭
    closeCardModal: function() {
      let originHash = location.hash;
      const pos = originHash.indexOf('?');
      if (pos > -1) {
        let newHash = originHash.substring(0, pos);
        location.hash = newHash;
      }
      this.showCardModal = false;
      this.showCardId = 0;
    },

    // onCardChange 
    onCardChange: function() {
      this.loadData(this.uuid);
    },

  }
};
</script>
<style lang="less" scoped>
.board {
  padding-top: 5px;
  display: flex;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.list-inner {
  position: relative;
}

.draggable-container {
  &::before{
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    width: 100%;
  }
}

.list {
  margin-left: 5px;
  margin-right: 5px;
  border-radius: 4px;
  display: inline-block;
  height: 100%;
  background: rgb(235 235 235);
}
.list:hover {
  // box-shadow: 1px 2px 1px rgba(9, 30, 66, 0.25);
  box-shadow: 0px 2px 4px rgba(9, 30, 66, 0.25);
  background: rgb(235 235 235, 0.73);
}
.list-inner {
  width: 270px;
  padding: 0px 0px;
  background: rgb(235 235 235);
  border-radius: 4px;
  height: 100%;
  overflow-y: hidden;
  overflow-x: hidden;
  position: relative;
}
.list-header {
  // background: rgb(235 235 235);
  line-height: 21px;
  min-height: 19px;
  margin-top: 0;
  flex: 0 0 auto;
  -ms-flex: 0 0 auto;
  -webkit-flex: 0 0 auto;
  position: relative;
  -webkit-box-flex: 0;
  -moz-box-flex: 0;
  font-size: 14px;
  font-weight: bold;
  padding: 7px 10px;
  border-radius: 4px;
  z-index: 1;
}
.list-body {
  padding: 0px 10px 0px;
  overflow-y: overlay;
  -webkit-overflow-scrolling: touch;
  -ms-overflow-style: scrollbar;
}

.list:first-child {
  margin-left: 15px;
}
.list-footer {
  padding: 0px 9px 7px;
}

.card {
  padding: 10px 10px 2px;
  border-radius: 4px;
  background: #ffffff;
  box-shadow: 1px 1px 4px 1px rgba(9, 30, 66, 0.10);
  margin-bottom: 8px;
  cursor: pointer;
  position: relative;
  z-index: 1;
}

.card:hover {
  box-shadow: 1px 1px 4px 3px rgba(9, 30, 66, 0.10);
}

.card-labels {
  overflow: hidden;
  /* padding: 6px 8px 2px; */
  position: relative;
  z-index: 10;
  margin-bottom: 2px;
}

.label-small {
  height: 24px;
  line-height: 24px;
  min-width: 60px;
  border-radius: 4px;
  display: inline-block;
  margin-right: 5px;
  padding: 0px 10px;
  color: #ffffff;
  margin-bottom: 5px;
  text-align: center;
  vertical-align: top;
  font-size: 12px;
}

.card-quick-edit-label .label-small {
  height: 8px;
  width: 40px;
  min-width: 40px;
  margin-bottom: 5px;
  float: left;
}

.custom-fields {
  font-size: 13px;
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  grid-template-columns: repeat(2, 100px);
}

.card-title {
  clear: both;
  display: block;
  /* overflow: hidden; */
  text-decoration: none;
  word-wrap: break-word;
  word-break: break-all;
  overflow-wrap: break-word;
  color: #172b4d;
}

.card-footer {
  overflow: hidden;
}

.card-footer .card-footer-item {
  font-size: 12px;
  margin-right: 10px;
}

.card-footer .due-date {
  font-size: 12px;
}

.card-footer .edit-card {
  font-size: 16px;
  padding-left: 2px;
  padding-right: 2px;
  margin-right: 0;
  visibility: hidden;
  cursor: pointer;
  position: absolute;
  right: 5px;
  bottom: 0px;
}

.card-footer .edit-card:hover {
  color: #40a9ff;
}

.card:hover .card-footer .edit-card {
  display: block;
  visibility: visible;
}

.card-quick-edit-input {
  padding: 0;
  // border: solid #fff 0px;
  resize: none;
}

.card-quick-edit-input:hover {
  // border: none;
}

.more-quick-handle {
  top: 0px;
  // right: -250px;
  // left: -75px;
  position: absolute;
  width: 65px
}

</style>
