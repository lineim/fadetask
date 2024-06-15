<template>
  <div>
    <a-modal
      v-if="showCardMoal"
      ok-text="Save"
      :footer="null"
      :centered="true"
      :width="modalWidth"
      :visible="showCardMoal"
      @ok="submit"
      @cancel="cancel"
      wrap-class-name="card-modal-wrap"
    >
      <nav class="task-nav">
        <a-space :size="'large'" v-if="loaded">
          <a-badge :count="card.subtask_count">
            <a-button type="primary" size="small" v-if="showSubTaskSide" @click="switchSubtaskSide" icon="menu-unfold"></a-button>
            <a-button size="small" v-if="!showSubTaskSide" @click="switchSubtaskSide" icon="menu-fold"></a-button>
          </a-badge>
          <a-space :size="4">
            <div v-if="card.kanban.project.uuid">
              {{ card.kanban.project.name }}
            </div>
            <div v-if="card.kanban.project.uuid">
              /
            </div>
            <div>
              {{ card.kanban.name }}
            </div>
            <div>
              /
            </div>
            <div>
              {{ card.list.name }}
            </div>
          </a-space>
        </a-space>
      </nav>
      <a-row v-if="loaded">
        <a-col v-if="showSubTaskSide" span="4" class="subtask-side">
          <div class="subtask-side-title modal-main-label">
            {{ $t('task.subtask_label') }}
          </div>
          <div class="subtask-side-body">
            <a-space class="subtask-items" :size="2" direction="vertical">
              <div 
                class="subtask-item" 
                v-bind:class="{'subtask-item-active': cardId == subtaskTree.id}"
                @click.stop="switchToTask(subtaskTree.id)" 
              >
                <span class="circle-dot mrxs"></span>{{ subtaskTree.title }}
              </div>
              <div 
                class="subtask-item mlm" 
                v-for="subtask in subtaskTree.subtasks" 
                :key="subtask.id" 
                v-bind:class="{'subtask-item-active': cardId == subtask.id}"
                @click.stop="switchToTask(subtask.id)" 
              >
                <span class="circle-dot mrxs"></span>{{ subtask.title }}
              </div>
            </a-space>
            <div class="mtl">
              <a-input-search placeholder="Add subtask" v-model="newSubtaskTitle" @search="addSubtask">
                <a-button type="primary" :disabled="newSubtaskTitle.length <= 0" slot="enterButton">
                  Add
                </a-button>
              </a-input-search>
            </div>
          </div>
        </a-col>
        <a-col :span="showSubTaskSide ? 20 : 24">
          <a-row
            class="card-header"
          >
            <a-col
              class="card-header-left"
              :span="17"
            >
              <div class="d-flex">
                <a-space 
                  align="center"
                  size="large"
                  style="flex: 80% 0;"
                >
                  <!-- 改变列 -->
                  <div class="modal-card-list">
                    <a-select
                      v-model="card.list_id"
                      style="width: 140px"
                      @change="listChange"
                    >
                      <a-select-option
                        v-for="list in card.kanban_list"
                        :key="list.id"
                        :value="list.id"
                        :disabled="list.wip > 0 && list.task_count >= list.wip"
                      >
                        {{ list.name }}
                        <span v-if="list.wip > 0 && list.task_count >= list.wip">
                          ({{ $t('task.change.wip_limited_label') }})
                        </span>
                      </a-select-option>
                    </a-select>
                  </div>

                  <!-- 完成状态 -->
                  <div>
                    <a-tag
                      :color="card.done ? '#2ea121' : ''"
                      class="modal-card-done"
                    >
                      <LiCheckBox
                        :styles="card.done ? {color: '#fff'} : {}"
                        :checked="card.done" 
                        :value="cardId" 
                        :label="card.done ? $t('task.done') : $t('task.mark_as_done')" 
                        @change="doneOrUndone"
                      />
                    </a-tag>
                  </div>

                  <!-- 卡片成员 -->
                  <div 
                    class="members" 
                  >
                    <div
                      class="modal-card-labels"
                      style="display: inline-block;"
                    >
                      <MultiAvatar
                        v-if="card.members.length > 0"
                        :num="3"
                        :members="card.members"
                        @remove="removeMember"
                        :close="true"
                        :size="'large'"
                        :style="{display: 'inline-flex', marginRight: '5px'}"
                      />

                      <CardMember
                        :card-id="cardId"
                        :kanban-id="boardId"
                        :title="$t('task.member.label')"
                        @memberadd="memberAdded"
                        @memberremove="memberRemoved"
                      >
                        <div
                          slot="trigger"
                          class="cursor-pointer d-inline-block"
                          :style="{verticalAlign: 'middle'}"
                        >
                          <FTGhostBtnVue
                            shape="circle"
                            icon="user-add"
                            size="large"
                          />
                        </div>
                      </CardMember>
                    </div>
                  </div>
                  <div class="modal-card-divider">
                    <a-divider type="vertical" />
                  </div>

                  <!-- 优先级 -->
                  <div>
                    <a-popover 
                      title="优先级" 
                      placement="bottomLeft" 
                      trigger="click"
                      class="no-arrow-popover"
                      overlay-class-name="no-arrow-popover"
                    >
                      <div
                        class="lg-popover-content"
                        slot="content"
                      >
                        <div 
                          class="modal-label-list" 
                          v-for="priority in priorities" 
                          :key="priority.level" 
                          @click="priorityChange(priority.level)"
                        >
                          <span 
                            class="modal-label-color-block" 
                            :style="{background: prioritiesColor[priority.level]}"
                          >
                            <span class="modal-label-select-icon">
                              <a-icon 
                                v-if="priorityChecked(priority.level)" 
                                type="check" 
                              />
                            </span>
                            {{ priority.name }}
                            <!-- <span class="modal-label-edit-icon">
                          <a-icon type="edit" />
                        </span> -->
                          </span>
                        </div>
                      </div>

                      <a-tag
                        class="cursor-pointer"
                        :color="prioritiesColor[card.priority]"
                      >
                        <a-icon
                          type="flag"
                        />
                        {{ getPriorityName(card.priority) }}
                      </a-tag>
                    </a-popover>
                  </div>
                </a-space>
                <div
                  class="d-flex"
                  style="flex: 0 20%; justify-content: end;"
                >
                  <a-space>
                    <a-tooltip>
                      <template slot="title">
                        {{ $t('task.share.link') }}
                      </template>
                      <a-button 
                        size="small" 
                        icon="share-alt" 
                        block 
                        @click="shareLink()"
                      >
                        {{ $t('task.share.label') }}
                      </a-button>
                    </a-tooltip>
                    <a-dropdown :trigger="['click']">
                      <a
                        class="ant-dropdown-link"
                        @click="e => e.preventDefault()"
                      >
                        <a-icon type="more" />
                      </a>
                      <a-menu
                        slot="overlay"
                        class="pvs"
                      >
                        <CardCopy
                          :card-id="cardId"
                          :board-id="boardId"
                          :title="$t('task.copy.title')"
                          @copied="_cardCopiedInBoard"
                        >
                          <a-menu-item
                            slot="trigger"
                            class="card-more-menu-item"
                            key="0"
                          >
                            <a class="text-center d-inline-block width-full fts13">
                              <a-icon type="copy" />
                              {{ $t('task.copy.label') }}
                            </a>
                          </a-menu-item>
                        </CardCopy>

                        <CardMove
                          :card-id="cardId"
                          :board-id="parseInt(boardId)"
                          title="移动卡片"
                          @moved="_cardMovedToOtherBoard"
                        >
                          <a-menu-item
                            slot="trigger"
                            class="card-more-menu-item"
                            key="1"
                          >
                            <a class="text-center d-inline-block width-full fts13">
                              <a-icon type="export" />
                              移动卡片
                            </a>
                          </a-menu-item>
                        </CardMove>

                        <a-popconfirm
                          title="确认归档卡片？"
                          ok-text="是"
                          cancel-text="否"
                          placement="rightTop"
                          @confirm="archive"
                        >
                          <a-menu-item
                            key="2"
                            class="card-more-menu-item"
                          >
                            <a class="text-center d-inline-block width-full fts13 pvhxs">
                              <a-icon type="save" />
                              归档卡片
                            </a>
                          </a-menu-item>
                        </a-popconfirm>

                        <!-- <a-menu-item key="3">
                          <a-button 
                            size="small" 
                            icon="eye" 
                            style="margin-bottom: 10px;" 
                            block
                          >
                            关注
                          </a-button>
                        </a-menu-item> -->
                      </a-menu>
                    </a-dropdown>
                  </a-space>
                </div>
              </div>
            </a-col>
            <a-col
              :span="7"
              class="card-header-right"
            >
              <a-space
                size="large"
              >
                <!-- 创建时间 -->
                <div class="create-date">
                  <div class="card-info-item">
                    {{ $t('task.create_info') }} 
                  </div>
                  <div class="card-info-val">
                    {{ card.created_date|friendlyTime }} &nbsp;By&nbsp; {{ card.creator.name }}
                  </div>
                </div>
                <div class="modal-card-divider">
                  <a-divider type="vertical" />
                </div>
                <!-- 过期时间 -->
                <div class="create-date">
                  <div class="card-info-item">
                    {{ $t('task.due_date') }} 
                    <a-tag
                      v-if="card.is_due_soon && !card.done"
                      class="mlxs"
                      color="#ffc60a"
                    >
                      {{ $t('task.due_soon') }}
                    </a-tag>
                    <a-tag
                      v-if="card.overfall && !card.done"
                      class="mlxs"
                      color="#f54a45"
                    >
                      {{ $t('task.due_overfall') }}
                    </a-tag>
                  </div>
                  <div class="card-info-val">
                    <CardDatetimePicker
                      :end-date="card.origin_end_date"
                      :notify-interval="card.dueNotifyTimes"
                      :notify-interval-default="String(card.due_notify_interval)"
                      :text="card.origin_end_date|friendlyTime"
                      :style="{width: 'none'}"
                      @save="dueDateSave"
                      @remove="dueDateRemove"
                    />
                  </div>
                </div>
              </a-space>
            </a-col>
          </a-row>
      
          <a-row 
            class="card-body"
          >
            <a-col 
              :span="17" 
              class="card-body-left"
            >
              <!-- 标签 -->
              <div 
                class="labels mbl" 
              >
                <div class="modal-card-labels">
                  <span 
                    v-for="label in card.labels" 
                    :key="label.id" 
                    class="modal-card-label" 
                    :style="{background: label.color}"
                  >
                    {{ label.name }}
                    <span
                      class="label-del"
                      @click="rmCardLabel(label.id)"
                    >
                      <a-icon
                        class="label-del-icon"
                        type="close"
                      />
                    </span>
                  </span>

                  <LabelPopover
                    :labels="card.kanban_labels"
                    :colors="card.kanban_label_colors"
                    :kanban-id="parseInt(boardId)"
                    :title="$t('task.tags.label')"
                    :selected-labels="card.labels"
                    @change="labelSelectChange"
                    @newlabel="newlabelCreated"
                    @labelupdated="labelupdated"
                    @labeldeleted="labeldeleted"
                    @sorted="sortedLabels"
                  >
                    <FTGhostBtnVue
                      slot="trigger"
                      shape="circle"
                      icon="tags"
                      size="small"
                      :btnstyle="{height: '31px', lineHeight: '31px', borderRadius: '4px', minWidth: '64px', fontSize: '16px'}"
                    />
                    <!-- <a-button 
                      slot="trigger"
                      size="small"
                      type="primary"
                      style="height: 31px; line-height: 31px; border-radius: 4px; min-width: 64px;"
                    >
                      <a-icon 
                        type="tags" 
                      />
                    </a-button> -->
                  </LabelPopover>
                </div>
              </div>

              <!-- 标题展示/修改 -->
              <div class="card-title mbl">
                <a-input 
                  size="large" 
                  v-model="card.title" 
                  placeholder="Task title"
                  @pressEnter="titleSave()"
                  @blur="titleSave()"
                  @focus="titleInputFocus(card.title)"
                />
              </div>
              <!-- 描述 -->
              <div class="card-desc mbl">
                <div 
                  class="card-desc-container"
                  :style="[showDescForm ? {borderColor: '#fff'} : {}]"
                >
                  <div
                    class="empty"
                    v-if="!card.desc && !showDescForm"
                    @click="desEdit()"
                  >
                    <span>点击编辑描述</span>
                  </div>
                  <div
                    class="desc-editor clearfix"
                    v-if="showDescForm"
                  >
                    <mavon-editor
                      v-model="card.desc"
                      :ishljs="true"
                      :subfield="false"
                      :toolbars="mavonToolbars"
                      :box-shadow="false"
                      placeholder="请输入卡片描述"
                      default-open="edit"
                      ref="mdEditor"
                      @fullScreen="mavonFullScreen"
                      @imgAdd="imageAdded"
                      @save="descSave()"
                    />
                    <div style="float: right; margin-top: 10px;">
                      <a-space>
                        <a-button
                          @click="cancelDescEdit()"
                        >
                          {{ $t('cancel') }}
                        </a-button>
                        <a-button
                          @click="descSave()"
                          type="primary"
                        >
                          {{ $t('submit') }}
                        </a-button>
                      </a-space>
                    </div>
                  </div>
                  <div
                    v-if="card.desc && !showDescForm"
                    @click="desEdit()"
                  >
                    <vue-markdown
                      :source="card.desc"
                      @rendered="markdownUpdated"
                    />
                  </div>
                </div>
              </div>
              <!-- 自定义字段 -->
              <div
                class="desc mbxl"
              >
                <div class="modal-main-label">
                  <span class="mrs">自定义字段</span>
                  <CustomField
                    :customfields="card.customfields"
                    :colors="card.kanban_label_colors"
                    :kanban-id="boardId"
                    :title="$t('custom_fields.label')"
                    :selected-labels="card.labels"
                    @newcustomfield="newCustomFieldCreated"
                    @optionchange="customFieldOptionChange"
                    @optionadded="customFieldOptionAdded"
                    @customfieldeleted="customFieldDel"
                    @namechanged="cardChange"
                    @showfrontchanged="cardChange"
                  >
                    <FTGhostBtnVue
                      slot="trigger"
                      :text="''"
                      :icon="'plus'"
                      :shape="'circle'"
                      :size="'small'"
                    />
                  </CustomField>
                </div>
                <CustomFieldSet
                  :customfields="card.customfields"
                  :customfieldvals="card.customfield_vals"
                  :card-id="cardId"
                  @field_val_change="fieldValChange"
                  :wrap-style="{padding: '0 10px'}"
                />
              </div>
              <!-- 检查项 -->
              <div 
                class="checklist mbxl"
              >
                <div class="modal-main-label">
                  检查项
                </div>
                <div 
                  class="checklist-item" 
                  v-for="check_list in card.check_list" 
                  :key="check_list.id"
                >
                  <div class="checklist-item-left">
                    <div style="display: flex;">
                      <span
                        class="checklist-item-done-icon"
                        v-if="check_list.is_done"
                        @click="checkListChange(check_list.id, false)"
                      ><a-icon
                        theme="filled"
                        type="check-circle"
                      /></span>
                      <span
                        class="checklist-item-undone-icon"
                        v-else
                        @click="checkListChange(check_list.id, true)"
                      />
                      <div
                        class="d-inline-block"
                        style="padding-left: 5px; flex: 0 90%"
                      >
                        <a-input
                          v-model="check_list.title"
                          size="small"
                          :placeholder="$t('checklist.title_required_tips')"
                          @focus="updateCheckListFocus(check_list.title)"
                          @pressEnter="updateCheckList(check_list.id, check_list.title)"
                          @blur="updateCheckList(check_list.id, check_list.title)"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="checklist-item-right">
                    <a-space
                      :size="'middle'"
                      :style="{verticalAlign: 'middle'}"
                    >
                      <div>
                        <CheckListMember
                          :card-id="cardId"
                          :kanban-id="parseInt(boardId)"
                          :checklist-id="check_list.id"
                          :title="$t('member')"
                          :popover-style="{'display': 'inline-block', 'cursor': 'pointer', float: 'right'}"
                          @memberadd="checkListMemberAdd"
                          @memberremove="checkListMemberRm"
                        >
                          <FTGhostBtnVue
                            slot="trigger"
                            :text="''"
                            :icon="'user-add'"
                            :shape="'circle'"
                            :btnstyle="{verticalAlign: 'middle'}"
                            :size="'small'"
                          />
                        </CheckListMember>
                        <MultiAvatar
                          v-if="check_list.members.length > 0"
                          :members="check_list.members"
                          @remove="checkListMemberRm"
                          :size="'small'"
                        />
                      </div>
                      <div>
                        <span
                          v-if="check_list.due_time"
                        >
                          {{ timeToDate(check_list.due_time) }}
                        </span>

                        <LiDatePicker
                          :id="check_list.id"
                          :init-date="check_list.due_time"
                          @change="checkListDueDateChange"
                          @clean="checkListDueDateClean"
                        >
                          <FTGhostBtnVue
                            slot="title"
                            :text="''"
                            :icon="'calendar'"
                            :shape="'circle'"
                            :size="'small'"
                            :btnstyle="{verticalAlign: 'middle'}"
                          />
                        </LiDatePicker>
                      </div>
                      <a-dropdown
                        :trigger="['click']"
                      >
                        <a
                          class="ant-dropdown-link"
                          @click="e => e.preventDefault()"
                        >
                          <a-icon type="more" />
                        </a>
                        <a-menu slot="overlay">
                          <a-menu-item>
                            <a-popconfirm
                              :title="$t('checklist.del_confirm_msg')"
                              :ok-text="$t('yes')"
                              :cancel-text="$t('no')"
                              @confirm="delCheckList(check_list)"
                            >
                              <a 
                                href="javascript:;" 
                                class="del-icon"
                              ><a-icon
                                class="mrs"
                                type="delete"
                              />{{ $t('delete') }}</a>
                            </a-popconfirm>
                          </a-menu-item>
                          <!-- <a-menu-item>
                            <a href="javascript:;"><a-icon class="mrs" type="credit-card" />{{ $t('convert_to_card') }}</a>
                          </a-menu-item> -->
                        </a-menu>
                      </a-dropdown>
                    </a-space>
                  </div>
                </div>
                <div class="checklist-item mtl">
                  <a-space size="middle">
                    <a-input
                      v-model="currentCheckList.title"
                      :placeholder="$t('checklist.new_placeholder')"
                      @pressEnter="checkListSave()"
                      style="width: 360px"
                    />

                    <div>
                      <MultiAvatar
                        v-if="newCheckListMembers.length > 0"
                        :members="newCheckListMembers"
                        :close="true"
                        @remove="_newCheckListMemberRm"
                        :styles="{'display': 'inlineFlex', 'align-items': 'start'}"
                      />
                      <CheckListMember
                        :card-id="cardId"
                        :kanban-id="parseInt(boardId)"
                        :checklist-id="0"
                        :selected-ids="newCheckListMemberIds"
                        :title="$t('member')"
                        @memberadd="newCheckListMemberAdd"
                        @memberremove="newCheckListMemberRm"
                        placement="rightBottom"
                      >
                        <FTGhostBtnVue
                          slot="trigger"
                          :text="''"
                          :icon="'user-add'"
                          :shape="'circle'"
                          :btnstyle="{verticalAlign: 'middle'}"
                        />
                      </CheckListMember>
                    </div>

                    <LiDatePicker
                      :init-date="null"
                      @change="newCheckListDueDateChange"
                      @clean="newCheckListDueDateClean"
                    >
                      <span
                        class="cursor-pointer"
                        slot="title"
                      >
                        <span
                          class="mrxs"
                          v-if="newCheckListDuedate"
                          :style="{verticalAlign: 'middle'}"
                        >{{ newCheckListDuedate + ' 00:00:00' | friendlyTime }}</span>
                        <FTGhostBtnVue
                          slot="trigger"
                          :text="''"
                          :icon="'calendar'"
                          :shape="'circle'"
                          :btnstyle="{verticalAlign: 'middle'}"
                        />
                      </span>
                    </LiDatePicker>
                    <a-button
                      type="primary"
                      :disabled="!currentCheckList.title || checklistSubmiting"
                      @click="checkListSave"
                    >
                      {{ $t('submit') }}
                    </a-button>
                  </a-space>
                </div>
              </div>
              <!-- 附件 -->
              <div 
                class="attachment mbl" 
              >
                <div class="modal-main-label">
                  {{ $t('task.attachment.label') }}
                  <CardAttachment
                    :card-id="cardId"
                    :kanban-id="boardId"
                    @success="attachmentUploaded"
                    @deleted="attachmentDeleted"
                    title="上传附件"
                  >
                    <FTGhostBtnVue
                      slot="trigger"
                      :text="''"
                      :icon="'plus'"
                      :shape="'circle'"
                      :size="'small'"
                    />
                  </CardAttachment>
                </div>
                <div>
                  <div 
                    class="attachment-item" 
                    v-for="attachment in card.attachments" 
                    :key="attachment.id"
                  >
                    <a 
                      @click="previewAttachment(attachment)"
                      href="javascript:;"
                      class="d-inline-block"
                      style="height: 32px; line-height: 32px;"
                    >
                      <!-- <iconfont
                        class="attachment-icon"
                        type="icon-icon_gif"
                      /> -->
                      <AttachmentIconVue
                        class="mrm"
                        :type="attachment.extension"
                      />
                      <div
                        class="d-inline-block"
                        style="vertical-align: top;"
                      >{{ attachment.org_name }}</div>
                    </a>
                    <a-popconfirm
                      title="确认删除附件？"
                      ok-text="是"
                      cancel-text="否"
                      @confirm="delAttachment(attachment)"
                    >
                      <a-tooltip>
                        <template slot="title">
                          {{ $t('task.attachment.del') }}
                        </template>
                        <a 
                          href="#" 
                          class="pull-right"
                        ><a-icon type="delete" /></a>
                      </a-tooltip>
                    </a-popconfirm>
                    <a-tooltip>
                      <template slot="title">
                        {{ $t('task.attachment.download') }}
                      </template>
                      <a
                        class="pull-right mrs"
                        href="javascript:;" 
                        @click="downloadAttachment(attachment)"
                      >
                        <a-icon type="download" />
                      </a>
                    </a-tooltip>

                    <a-tooltip>
                      <template slot="title">
                        {{ $t('task.attachment.preview') }}
                      </template>
                      <a
                        class="pull-right mrs"
                        @click="previewAttachment(attachment)"
                        href="javascript:;"
                      >
                        <a-icon type="eye" />
                      </a>
                    </a-tooltip>
                  </div>
                </div>
              </div>
            </a-col>
            <a-col 
              class="card-body-right" 
              :span="7"
            >
              <div class="desc mbs card-comment">
                <a-timeline class="mtm">
                  <a-timeline-item 
                    v-for="activity in activities" 
                    :key="activity.id"
                    color="rgb(241, 75, 169)"
                    style="margin-left: 2px; padding-bottom: 5px;"
                  >
                    <a-icon 
                      v-if="activity.action == 'add_comment'"
                      slot="dot" 
                      type="message" 
                      style="color: rgb(241, 75, 169);" 
                    />
                    <div v-if="activity.action == 'add_comment'">
                      <a-comment
                        :author="activity.user.name"
                        class="card-comment-item"
                      >
                        <a-avatar 
                          slot="avatar"
                          style="color: rgb(255, 255, 255);; backgroundColor: rgb(24, 144, 255);"
                        >
                          {{ activity.user.name.slice(0, 1) }}
                        </a-avatar>
                        <template
                          slot="actions"
                          v-if="activity.user.id == userId && !isCommentEditing(activity.id)"
                        >
                          <span
                            @click="editComment(activity.id)"
                          ><a-icon type="edit" /></span>
                          <span>
                            <a-popconfirm
                              title="确认删除评论？"
                              ok-text="是"
                              cancel-text="否"
                              @confirm="commentDelete(activity.id)"
                            >
                              <a-icon type="delete" />
                            </a-popconfirm>
                          </span>
                        </template>
                        <div slot="content">
                          <p v-if="!isCommentEditing(activity.id)">
                            {{ activity.show_msg }}
                          </p>
                          <div v-if="isCommentEditing(activity.id)">
                            <a-form-model>
                              <a-form-item :style="{'marginBottom': '10px'}">
                                <a-textarea
                                  :rows="2"
                                  v-model="activity.show_msg"
                                  :style="{'width': '100%'}"
                                />
                              </a-form-item>
                              <a-form-item>
                                <a-button
                                  size="small"
                                  @click="cancelEditComment(activity.id)"
                                  class="mrs"
                                >
                                  {{ $t('cancel') }}
                                </a-button>
                                <a-button
                                  size="small"
                                  html-type="submit"
                                  :loading="editCommentSubmitting"
                                  type="primary"
                                  :disabled="isEmptyCommentContent(activity.id)"
                                  @click="updateComment(activity.id)"
                                >
                                  {{ $t('save') }}
                                </a-button>
                              </a-form-item>
                            </a-form-model>
                          </div>
                        </div>

                        <a-tooltip
                          slot="datetime"
                          :title="activity.sample_date"
                        >
                          <span>{{ activity.sample_date }}</span>
                        </a-tooltip>
                      </a-comment>
                    </div>
                    <div v-else>
                      <p>
                        {{ activity.show_msg }}
                        <span class="fts12 mlm">
                          {{ activity.sample_date }}
                        </span>
                      </p>
                    </div>
                  </a-timeline-item>
                </a-timeline>
              </div>
              <div calss="card-comment-add">
                <a-textarea
                  :rows="2"
                  placeholder="评论内容"
                  v-model="commentForm.value"
                  @change="commentChange"
                  class="mbm"
                />
                <a-button
                  class="pull-right"
                  html-type="submit"
                  :loading="commentSubmitting"
                  type="primary"
                  :disabled="commentForm.value === '' || commentSubmitting"
                  @click="commentSubmit"
                >
                  {{ $t('submit') }}
                </a-button>
              </div>
            </a-col>
          </a-row>
        </a-col>
      </a-row>
      <a-skeleton
        v-if="!loaded"
        style="min-height: 729px;"
        :paragraph="{ rows: 4 }"
        avatar
        active
      />
    </a-modal>
    <a-modal
      v-model="showAttachmentPreview"
      dialog-class="attachment-preview"
      :footer="null"
      :z-index="1011"
      :destroy-on-close="true"
      @cancel="attachmentPreviewClose"
      width="80%"
    >
      <div slot="title">
        <span class="mrm">{{ currentAttachment.org_name }}</span> <a-button
          icon="download"
          @click="downloadAttachment(currentAttachment)"
        />
      </div>
      <div
        class="tac"
        v-if="isImage(currentAttachment.extension)"
      >
        <img
          :src="currentPreviewUrl"
          :style="{maxWidth: '100%'}"
        >
      </div>
      <div
        class="tac"
        v-if="isPdf(currentAttachment.extension)"
      >
        <a-progress
          v-if="pdfLoading"
          type="circle"
          :percent="pdfLoadPercent"
        />
        <div :style="{display: pdfLoading ? 'none' : 'block'}">
          <pdf
            v-for="page in pdfPageNumber"
            :key="page"
            :page="page"
            :src="pdfSrc"
          />
        </div>
      </div>

      <div
        v-if="!isImage(currentAttachment.extension) && !isPdf(currentAttachment.extension)"
        class="tac"
      >
        <a-empty>
          <div
            slot="image"
            style="font-size: 68px; line-height: 100px;"
          >
            <a-icon type="eye-invisible" />
          </div>
          <span slot="description"> {{ $t('task.attachment.no_preview') }} </span>
          <a-button
            @click="downloadAttachment(currentAttachment)"
            type="primary"
            icon="download"
          >
            {{ $t('task.attachment.download') }}
          </a-button>
        </a-empty>
      </div>
    </a-modal>
  </div>
</template>
<script>
import api from "@/api";
import axios from "axios";

// import moment from 'moment';
import moment from 'moment-timezone';
import store from "@/store";
import liCheckBox from "@/components/form/LiCheckBox";
import LiDatePicker from "@/components/form/LiDatePicker.vue";
import LabelPopover from '@/components/kanban/LabelPopover';
import CardAttachment from "./CardAttachment.vue";
import CustomField from '@/components/kanban/CustomField';
import CustomFieldSet from '@/components/kanban/CustomFieldSet';
import CardMember from '@/components/kanban/CardMember';
import CheckListMember from '@/components/kanban/CheckListMember';
import CardCopy from '@/components/kanban/CardCopy';
import CardMove from '@/components/kanban/CardMove';
import CardDatetimePicker from "@/components/kanban/CardDatetimePicker.vue";
import MultiAvatar from '@/components/common/MultiAvatar.vue';
import {copyToPlaster, priorities, prioritiesColor, getPriorityName, removeArrItem, timeToDate, isImage, isPdf} from "@/utils/index";
import {miniToolbars, fullToolbars} from '@/utils/mavon-config';
import VueMarkdown from 'vue-markdown';
import pdf from 'vue-pdf';
import Prism from "prismjs";
import "prismjs/themes/prism-okaidia.css";  // theme
import 'prismjs/components/prism-go.min';  // language
import 'prismjs/components/prism-java.min';  // language
import 'prismjs/components/prism-c.min';  // language
import 'prismjs/components/prism-javascript.min';  // language
import 'prismjs/components/prism-css.min';  // language
// import mavonEditor from 'mavon-editor';
import 'mavon-editor/dist/css/index.css';
import FTGhostBtnVue from '../common/FTGhostBtn.vue';
import AttachmentIconVue from '../common/AttachmentIcon.vue';
import i18n from '../../i18n';
// import { Icon } from 'ant-design-vue';

var mavonEditor = require('mavon-editor');
moment.tz.setDefault("Asia/Shanghai");

// const IconFont = Icon.createFromIconfontCN({
//   scriptUrl: '//at.alicdn.com/t/c/font_4307405_o66sb8t5szp.js', // 在 iconfont.cn 上生成
//   extraCommonProps: {class: 'aaaaa'}
// });

const defaultCard = {
    title: "",
    desc: "",
    list: {
        name: "",
    },
    kanban_labels: [], // task kanban labels
    labels: [], // task labels
    end_date: '',
    end_time: 0,
    check_list: [],
    members: [],
    attachments: [],
    done: false,
    priority: 2,
    due_notify_interval: "0",
    customfields: [],
    creator: {
      name: '',
      uuid: ''
    },
    subtask_count: 0,
    kanban: {
      name: '',
      uuid: '',
      project: {
        name: '',
        uuid: ''
      }
    }
};

export default {
    components: {
      'mavon-editor': mavonEditor.mavonEditor,
      LiCheckBox: liCheckBox,
      LiDatePicker,
      LabelPopover,
      CardAttachment,
      CustomField,
      VueMarkdown,
      CardMember,
      CardCopy,
      CardMove,
      CardDatetimePicker,
      CustomFieldSet,
      CheckListMember,
      MultiAvatar,
      FTGhostBtnVue,
      AttachmentIconVue,
      pdf
    },
    props: ['boardId', 'cardId', 'showCardMoal'],
    data() {
      return {
        card: defaultCard,
        cardOriginTitle: '',
        activities: [],
        showDescForm: false,
        mavonToolbars: miniToolbars,
        loaded: false,
        uploadHeader: {'X-Auth-Token': store.state.token },
        showCheckListInput: false,
        currentCheckList: {title: ''},
        checklistSubmiting: false,
        currentEditCheckListTittle: '',
        memberVisible: false,
        memberVisibleMain: false,
        members: [],
        boardMembers: [],
        priorities: priorities,
        prioritiesColor: prioritiesColor,
        comments: [],
        commentCount: 0,
        commentSubmitting: false,
        commentForm: {value: ""},
        commentEditStatus: {},
        commentOriginContents: {},
        editCommentSubmitting: false,
        cardLabelIds: [],
        sideDatePickerOpen: false,
        newCheckListMembers: [],
        newCheckListMemberIds: [],
        newCheckListDuedate: null,
        currentAttachment: {},
        showAttachmentPreview: false,
        currentPreviewUrl: '',
        pdfSrc: null,
        pdfPageNumber: undefined,
        pdfLoading: false,
        pdfLoadPercent: 0,
        showSubTaskSide: true,
        newSubtaskTitle: '',
        subtaskTree: {
          id: 0,
          uuid: "",
          title: "",
          subtasks: []
        }
      }        
    },

    created() {
      // this.onVueCreated();
    },

    watch: {
      cardId: function() {
        this.card = defaultCard;
        this.comments = []; // 不加此行，modal关闭后再打开，评论列表会保留关闭前的数据
        this.loadCard();
      },
      memberVisible: function(v) {
        if (v) {
          this.onMemberSearch('');
        }
      },
      memberVisibleMain: function(v) {
        if (v) {
          this.onMemberSearch('');
        }
      }
    },

    computed: {
      modalWidth: function() {
        if (this.showSubTaskSide) {
          return '98%';
        }
        const windowWidth = window.innerWidth;
        if (windowWidth >= 2560) {
          return '2000px';
        }
        if (windowWidth >= 1440) {
          return '1440px';
        }
        return '90%';
      },
      uploadAction: function() {
        return '/api/kanban/' + this.cardId + '/task/attachment';
      },
      showCheckList: function() {
        return this.showCheckListInput || this.card.check_list.length > 0;
      },
      hasMember: function() {
        return this.card.members.length > 0;
      },
      hasLabel: function() {
        return this.card.labels.length > 0;
      },
      canMoveToKanbans: function() {
        let boards = [];
        for (const index in this.myKanbans) {
          let kanban = this.myKanbans[index];
          if (kanban.id != this.boardId) {
            boards.push(kanban);
          }
        }
        return boards;
      },
      canMoveToList: function() {
        for (const index in this.myKanbans) {
          let kanban = this.myKanbans[index];
          if (kanban.id == this.moveToOtherForm.boardId) {
            return kanban.list;
          }
        }
        return [];
      },
      priorityChecked() {
        return function (priority) {
          return priority == this.card.priority;
        }
      },
      
      showCommentLoadingMore: function() {
        return this.comments.length < this.commentCount;
      },

      isEmptyCommentContent() {
        return function(activityId) {
          const comment = this.getActivityById(activityId);
          console.log(comment);
          return comment.show_msg == null || comment.show_msg == '';
        }
      },

      isCommentEditing() {
        return function(commentId) {
          if (!(commentId in this.commentEditStatus)) {
            return false;
          }
          return this.commentEditStatus[commentId];
        }
      },

      userName: function() {
        let name = store.state.user.name;
        if (typeof(name) == 'undefined') {
          return "";
        }
        return name;
      },
      userId: function() {
        return store.state.user.id;
      }
    },

    methods: {
        getPriorityName,
        timeToDate,
        isImage,
        isPdf,

        loadCard: function(withMember = true, withActivity = true, showLoading = true) {
          if (!this.cardId) {
            return;
          }
          if (showLoading) this.loaded = false;
          api.card({query: {boardId: this.boardId, taskId: this.cardId}}).then(resp => {
            resp.origin_end_date = resp.end_date;
            if (!resp.end_date) {
              resp.end_date = null;
            } else {
              resp.end_date = moment(resp.end_date, 'YYYY-MM-DD HH:mm');
            }
            this.card = resp;
            // this.members = resp.members;
            this.loaded = true;
            withMember && this.onMemberSearch('');
            withActivity && this.loadCardActivity();
            this.loadSubtasks();
          });
        },

        loadSubtasks: function() {
          api.cardSubtasks({query: {boardId: this.boardId, taskId: this.cardId}}).then(r => {
            this.subtaskTree = r;
          })
        },

        loadCardActivity: function() {
          api.cardActivity({query: {boardId: this.boardId, taskId: this.cardId}}).then(resp => {
            this.activities = resp;
          });
        },

        cancel: function() {
            this.loaded = false;
            this.showTitleForm = false;
            this.showDescForm = false;
            this.$emit('close');
        },
        submit: function() {
            this.loaded = false;
            this.$emit('close');
        },
        cardTitleFormCancel: function() {
            this.showTitleForm = false;
        },

        newlabelCreated: function(label) {
          this.card.kanban_labels.push(label);
          this.cardChange();
        },

        labelupdated: function(label) {
          let labels = this.card.labels;
          for (let i = 0; i < labels.length; i ++) {
            const tmpLabel = labels[i];
            if (tmpLabel.id == label.id) {
              labels[i] = label;
              break;
            }
          }
          this.card.labels = [];
          this.card.labels = labels;
          this.cardChange();
        },

        labeldeleted: function(id) {
          let kanbanLabels = this.card.kanban_labels;
          let tmpKanbanLabels = [];
          for (let i = 0; i < kanbanLabels.length; i ++) {
            const tmpLabel = kanbanLabels[i];
            if (tmpLabel.id != id) {
              tmpKanbanLabels.push(tmpLabel);
            }
          }
          this.card.kanban_labels = [];
          this.card.kanban_labels = tmpKanbanLabels;

          let labels = this.card.labels;
          let tmpLabels = [];
          for (let i = 0; i < labels.length; i ++) {
            const tmpLabel = labels[i];
            if (tmpLabel.id != id) {
              tmpLabels.push(tmpLabel);
            }
          }
          this.card.labels = [];
          this.card.labels = tmpLabels;
          this.cardChange();
        },

        sortedLabels: function(sortedLabels) {
          this.card.kanban_labels = [];
          this.card.kanban_labels = sortedLabels;
        },

        labelSelectChange: function(labelId, isSelected) {
          if (isSelected) {
            api.addLabel({query: {taskId: this.cardId, labelId: labelId}}).then((taskLabels) => {
              this.card.labels = taskLabels;
              this.cardChange(); // 需要放在回调里面，api 请求是异步的
            });
          } else {
            this.rmCardLabel(labelId);
          }
        },

        rmCardLabel: function(labelId) {
          api.rmLabel({query: {taskId: this.cardId, labelId: labelId}}).then(() => {
            let labels = this.card.labels;
            let len = labels.length;
            
            let newLabels = [];
            for (let i = 0; i < len; i++) {
              if (labels[i].id != labelId) {
                newLabels.push(labels[i]);
              }
            }
            this.card.labels = newLabels;
            this.cardChange();
          });
        },

        taskHasLabel: function(labelId) {
          let cardLabelIds = [];
          for (let index in this.card.labels) {
            let label = this.card.labels[index];
            cardLabelIds.push(label.id);
          }
          return -1 !== cardLabelIds.indexOf(labelId);
        },

        newCustomFieldCreated: function(field) {
          this.card.customfields.push(field);
          this.cardChange();
        },

        customFieldDel: function(id) {
          this.card.customfields = removeArrItem(this.card.customfields, function(item) {
            return item.id == id;
          });
        },

        customFieldOptionChange: function() {
          this.loadCard(false, false);
          this.cardChange();
        },

        customFieldOptionAdded: function() {
         
        },

        fieldValChange: function() {
          this.loadCard(false, false, false);
          this.cardChange();
        },

        desEdit: function() {
          this.showDescForm = true;
        },
        cancelDescEdit: function() {
          this.showDescForm = false;
        },

        mavonFullScreen: function(status) {
          this.mavonToolbars = status ? fullToolbars : miniToolbars;
        },

        imageAdded: function(pos, file) {
          if (file.size > 6291456) {
            this.$message.error('文件体积不能超过6M！');
          }
          let formData = new FormData();
          formData.append('attachment', file);
          
          axios({
            url: this.uploadAction,
            method: 'post',
            data: formData,
            headers: {
              'Content-Type': 'multipart/form-data', 
              "X-Auth-Token": store.state.token
            },
          }).then( r => {
            this.attachmentUploaded(r.attachments);
            const mdEditor = this.$refs.mdEditor;
            const url = '/api/kanban/task/attachment?file=' + r.current.file_uri + '&time=' + Date.now();
            mdEditor.$img2Url(pos, url);
          })
        },

        uploadOssProcess: function(file, p, mdEditor, pos) {
          const percent = parseInt(p*100);
          if (percent >= 100) {
            api.taskAttahcmentFinished({query: {taskId: this.cardId}, data: {uuid: file.uuid}}).then(resp => {
              const attachment = resp.current;
              this.downloadUrl(attachment).then(url => {
                mdEditor.$img2Url(pos, url);
              });
              this.$message.success('文件《' + file.name + '》上传完成！');
            });
          }
        },

        titleInputFocus: function(title) {
          this.cardOriginTitle = title;
        },

        titleSave: function() {
          if (!this.card.title) {
            this.card.title = this.cardOriginTitle;
            return;
          }
          if (this.card.title === this.cardOriginTitle) {
            return;
          }
          api.saveTitle({query: {boardId: this.boardId, id: this.cardId}, data: {title: this.card.title}}).then(() => {
            this.cardOriginTitle = this.card.title; // 防止回车后再失去焦点，触发提交
            // this.loadCard();
            this.loadCardActivity();
            this.showTitleForm = false;
            this.cardChange();
            this.loadSubtasks();
          });
        },

        descSave: function() {
          api.saveDesc({query: {boardId: this.boardId, id: this.cardId}, data: {desc: this.card.desc}}).then(() => {
            // this.loadCard();
            this.loadCardActivity();
            this.showDescForm = false;
            this.mavonFullScreen(false);
            this.cardChange();
          });
        },

        listChange: function(listId) {
          api.listChange({query: {id: this.cardId}, data: {list_id: listId}}).then((res) => {
            if (res.task_finished) {
              this.card.done = 1;
              this.card.is_finished = 1;
            }
            // this.loadCard();
            this.loadCardActivity();
            // this.card.list_id = listId;
            this.cardChange();
          });
        },

        dueDateSave: function(data) {
          api.setDate({
            query: {boardId: this.boardId, id: this.cardId}, 
            data: {end_date: data.date, due_notify_time: data.notify_interval}
          }).then((card) => {
            this.sideDatePickerOpen = false;
            // this.loadCard();
            this.card.done = card.done;
            this.card.end_time = card.end_time;
            this.card.end_date = card.end_date;
            this.card.origin_end_date = card.end_date;
            this.card.finished_time = card.finished_time;
            this.card.is_due_soon = card.is_due_soon;
            this.card.is_finished = card.is_finished;
            this.card.overfall = card.overfall;
            this.card.start_time = card.start_time;
            this.card.updated_time = card.updated_time;
            this.card.due_notify_interval = card.due_notify_interval;
            this.card.due_notify_time = card.due_notify_time;
            this.cardChange();
            this.loadCardActivity();
          });
        },

        dueDateRemove: function() {
          api.clearDueDate({
            query: {boardId: this.boardId, id: this.cardId}          
          }).then(() => {
            this.sideDatePickerOpen = false;

            this.card.due_notified = 0;
            this.card.due_notify_interval = 0;
            this.card.due_notify_time = 0;
            this.card.end_date = "";
            this.card.origin_end_date = "";
            this.card.end_time = 0;
            this.card.finished_time = 0;
            this.card.id = 222;
            this.card.is_delete = 0;
            this.card.is_due_soon = false;
            this.card.is_finished = 0;
            this.card.overfall = false;
            this.loadCardActivity();
            this.cardChange();
            this.loadCardActivity();
          });
        },

        downloadUrl: async function(attachment) {
          if (attachment.storage == 'oss') {
            const url = await api.taskAttahcmentUrl({query: {uuid: attachment.uuid, taskId: attachment.task_id}});
            // 图片实时预览
            this.currentPreviewUrl = url;
            return url;
          } else {
            const time = Date.now();
            const url = '/api/kanban/task/attachment?file=' + attachment.file_uri + '&time=' + time;
            // 图片实时预览
            this.currentPreviewUrl = url;
            return Promise.resolve(url);
          }
        },

        downloadAttachment: function(attachment) {
          this.downloadUrl(attachment).then(url => {
            window.open(url);
          });
        },

        previewAttachment: function(attachment) {
          this.currentAttachment = attachment;
          
          this.downloadUrl(attachment).then(url => {
            this.currentPreviewUrl = url;
            if (this.isPdf(attachment.extension)) {
              this.pdfLoading = true;
              this.pdfSrc = pdf.createLoadingTask(
                {
                  url: url,
                }, 
                {onProgress: this.pdfOnProgress}
              );
              this.pdfSrc.promise.then(pdf => {
                this.pdfPageNumber = pdf.numPages;
              });
            }
          });
          
          this.showAttachmentPreview = true;
        },

        pdfOnProgress: function(e) {
          if (e.loaded >= e.total) {
            this.pdfLoadPercent = 0;
            this.pdfLoading = false;
          } else {
            this.pdfLoadPercent = parseInt(e.loaded/e.total*100);
          }
        },

        attachmentPreviewClose: function() {
          this.pdfLoadPercent = 0;
          this.pdfLoading = false;
          this.pdfSrc = null;
        },

        uploadProcess: function(info) {
          if (info.file.status === 'done') {
            this.loadCardActivity();
            this.card.attachments = info.file.response.data;
            this.cardChange();
          }
        },

        attachmentUploaded: function(file) {
          this.loadCardActivity();
          this.card.attachments = file;
          this.cardChange();
        },

        delAttachment: function(attachment) {
          const fileId = attachment.uuid;
          api.delAttachment({query: {taskId: this.cardId, id: fileId}}).then(() => {
            this.attachmentDeleted(attachment.uuid);
          });
        },

        attachmentDeleted: function(uuid) {
          this.loadCardActivity();
          this.card.attachments = removeArrItem(this.card.attachments, function(item) {
            return item.uuid == uuid;
          });
          this.cardChange();
        },

        delCheckList: function(checklist) {
          const id = checklist.id;
          api.delCheckList({query: {taskId: this.cardId, id: id}}).then(() => {
            this._delCardCheckList(id);
            this.cardChange();
            // this.loadCard();
          });
        },

        addCheckList: function() {
          this.showCheckListInput = true;
        },

        checkListSave: function() {
          this.currentCheckList.title = this.currentCheckList.title.replace(/(^\s*)|(\s*$)/g, "");
          if (!this.currentCheckList.title) {
            return;
          }
          let data = {
            title: this.currentCheckList.title, 
            member_ids: this.newCheckListMemberIds,
            due_date: this.newCheckListDuedate
          };
          this.checklistSubmiting = true;
          api.addCheckList({query: {taskId: this.cardId}, data: data}).then((newList) => {
            this.card.check_list.push(newList);
            this.currentCheckList.title = '';
            this.newCheckListMemberIds = [];
            this.newCheckListMembers = [];
            this.checklistSubmiting = false;
            this.cardChange();
          }).then(() => {
            this.checklistSubmiting = false;
          });
        },

        checkListChange: function(v, checked) {
          if (checked) {
            api.doneCheckList({query: {taskId: this.cardId, id: v}}).then(() => {
              this._changeCardCheckListStatus(v, true);
              this.cardChange();
              // this.loadCard();
            });
            return;
          }
          api.undoneCheckList({query: {taskId: this.cardId, id: v}}).then(() => {
            this._changeCardCheckListStatus(v, false);
            this.cardChange();
            // this.loadCard();
          });
        },

        updateCheckListFocus: function(originTitle) {
          this.currentEditCheckListTittle = originTitle;
        },

        updateCheckList: function(id, title) {
          let filterTitle = title.replace(/(^\s*)|(\s*$)/g, "");
          if (!filterTitle) {
            for (const index in this.card.check_list) {
              const tmpCheckList = this.card.check_list[index];
              if (tmpCheckList.id == id) {
                this.card.check_list[index].title = this.currentEditCheckListTittle;
                break;
              }
            }
            return;
          }
          if (filterTitle == this.currentEditCheckListTittle) {
            return;
          }
          api.updateCheckList({query: {taskId: this.cardId, id: id}, data: {title: filterTitle}}).then(() => {
          });
        },

        _changeCardCheckListStatus: function(listId, status) {
          for (let i = 0; i < this.card.check_list.length; i++) {
            let checkList = this.card.check_list[i];
            if (checkList.id == listId) {
               this.card.check_list[i].is_done = status;
            }
          }
        },

        _delCardCheckList: function(listId) {
          this.card.check_list = removeArrItem(this.card.check_list, function(item) {
            return item.id == listId;
          });
        },

        showMemberContainer: function() {
          this.memberVisible = true;
          this.loadCardMember();
        },

        memberAdded: function(cardId, member) {
          console.log(cardId);
          if (member) {
            this.card.members.push(member);
          }
          this.loadCardActivity();
          this.cardChange();
        },

        removeMember: function(member) {
          console.log(member);
          api.cardMemberRm({query: {cardId: this.cardId}, data: {member_id: member.id}}).then(() => {
            this.memberRemoved(this.cardId, member.id);
          });
        },

        memberRemoved: function(cardId, memberId) {
          this.card.members = removeArrItem(this.card.members, function(m) {
            return m.id == memberId;
          });
          this.loadCardActivity();
          this.cardChange();
        },

        memberAddOrRemove: function() {
          this.loadCard();
          this.cardChange();
          // if (idAdd) {
          //   api.cardMemberAdd({query: {cardId: this.cardId}, data: {member_id: id}}).then(() => {
          //     this.loadCard();
          //     // this.onMemberSearch(this.memberSearchKey);
          //     this.cardChange();
          //   });
          // } else {
          //   api.cardMemberRm({query: {cardId: this.cardId}, data: {member_id: id}}).then(() => {
          //     this.loadCard();
          //     // this.onMemberSearch(this.memberSearchKey);
          //     this.cardChange();
          //   });
          // }
        },

        newCheckListMemberAdd: function(cardId, checklistId, member) {
          this.newCheckListMemberIds.push(member.id);
          this.newCheckListMembers.push(member);
        },

        _newCheckListMemberRm: function(member) {
          this.newCheckListMemberRm(0, 0, member.id);
        },

        newCheckListMemberRm: function(cardId, checklistId, memberId) {
          this.newCheckListMemberIds = removeArrItem(this.newCheckListMemberIds, function(id) {
            return memberId == id;
          });
          this.newCheckListMembers = removeArrItem(this.newCheckListMembers, function(m) {
            return m.id == memberId;
          });
        },

        checkListMemberAdd: function(cardId, checkListId, member) {
          let checklists = [];
          for (let i = 0; i < this.card.check_list.length; i++) {
            let checklist = this.card.check_list[i];
            if (checklist.id == checkListId) {
              checklist.members.push(member);
            }
            checklists.push(checklist);
          }
          this.card.check_list = checklists;
        },

        checkListMemberRm: function(cardId, checkListId, id) {
          let checklists = [];
          for (let i = 0; i < this.card.check_list.length; i++) {
            let checklist = this.card.check_list[i];
            if (checklist.id == checkListId) {
              checklist.members = removeArrItem(checklist.members, function(item) {
                return item.id == id;
              });
            }
            checklists.push(checklist);
          }
          this.card.check_list = checklists;
        },

        checkListDueDateChange: function(checklistId, date) {
          api.checkListDuedate({query: {taskId: this.cardId, id: checklistId}, data: {due_date: date}}).then((unixtime) => {
            for (let i = 0; i < this.card.check_list.length; i++) {
              let checklist = this.card.check_list[i];
              if (checklist.id == checklistId) {
                this.card.check_list[i].due_time = unixtime;
              }
            }
          });
        },

        checkListDueDateClean: function(checklistId) {
          let date = '';
          api.checkListDuedate({query: {taskId: this.cardId, id: checklistId}, data: {due_date: date}}).then(() => {
            for (let i = 0; i < this.card.check_list.length; i++) {
              let checklist = this.card.check_list[i];
              if (checklist.id == checklistId) {
                this.card.check_list[i].due_time = date;
              }
            }
          });
        },

        newCheckListDueDateChange: function(id, date) {
          this.newCheckListDuedate = date;
        },

        newCheckListDueDateClean: function() {
          this.newCheckListDuedate = null;
        },

        doneOrUndone: function(id, idDone) {
          if (idDone) {
            api.taskDone({query: {id: id, boardId: this.boardId}}).then(() => {
              // this.loadCard();
              this.card.done = 1;
              this.loadCardActivity();
              this.cardChange();
            });
          } else {
            api.taskUndone({query: {id: id, boardId: this.boardId}}).then(() => {
              // this.loadCard();
              this.card.done = 0;
              this.loadCardActivity();
              this.cardChange();
            });
          }
        },

        priorityChange: function(priority) {
          api.taskPriority({
            query: {id: this.card.id, boardId: this.boardId}, 
            data: {priority: priority}
          }).then(() => {
            this.card.priority = priority;
            this.loadCardActivity();
            this.cardChange();
          });
        },

        commentChange: function() {

        },

        commentSubmit: function() {
          this.commentSubmitting = true;
          api.createComment({
            query: {taskId: this.cardId}, 
            data: {content: this.commentForm.value}
          })
            .then(comment => {
              if (comment) {
                this.commentCount += 1;
                this.comments.unshift(comment);
                this.commentForm.value = '';
                this.$message.success('评论成功！');
                this.loadCardActivity();
              } else {
                this.$message.error('评论失败！');
              }
              this.commentSubmitting = false;
            });
        },

        commentDelete: function(id) {
          api.deleteComment({query: {taskId: this.cardId, id: id}}).then(res => {
            if (res) {
              this.$message.success('评论已删除！');
              let comments = removeArrItem(this.comments, function(item) {
                return item.id == id;
              });
              this.comments = comments;
              this.commentCount -= 1;
              this.loadCardActivity();
            }
          });
        },

        editComment: function(id) {
          let comment = this.getActivityById(id);
          this.commentOriginContents[comment.id] = comment.show_msg;

          this.updateCommentEditStatus(id, true);
        },

        cancelEditComment: function(id) {
          this.updateCommentEditStatus(id, false);

          // 恢复编辑前的数据
          this.revertCommentContent(id);
        },

        updateCommentEditStatus: function(id, status) {
          this.commentEditStatus[id] = status;
          let tmp = Object.assign(this.commentEditStatus, {});
          this.commentEditStatus = {};
          this.commentEditStatus = tmp;
        },

        revertCommentContent: function(id) {
          let activities = this.activities;
          for (let key in activities) {
            let activity = activities[key];
            if (activity.id == id) {
              const originContent = this.commentOriginContents[id];
              activities[key].show_msg = originContent;
              break;
            }
          }
          this.activities = [];
          this.activities = Object.assign(activities, []);
        },

        updateComment: function(id) {
          let comment = this.getActivityById(id);
          if (!comment) {
            return;
          }
          let data = {content: comment.show_msg};
          this.editCommentSubmitting = true;
          api.editComment({query: {taskId: this.cardId, id: id}, data: data}).then(() => {
            this.$message.success('修改成功！');
            this.editCommentSubmitting = false;
            this.updateCommentEditStatus(id, false);
            this.loadCardActivity();
          });
        },

        getActivityById: function(id) {
          let len = this.activities.length;
          for (let i = 0; i < len; i++) {
            let activity = this.activities[i];
            if (activity.id == id) {
              return activity;
            }
          }
          return false;
        },

        // getPriorityName: function(level) {
        //   for (let index in this.priorities) {
        //     let priority = this.priorities[index];
        //     if (priority.level == level) {
        //       return priority.name;
        //     }
        //   }
        //   return '';
        // },

        loadCardMember: function() {
          api.cardMembers({query: {cardId: this.cardId}}).then(res => {
            this.members = res;
          });
        },

        onMemberSearch: function(value) {
          this.memberSearchKey = value;
          api.kanbanMemberSearch({
            query: {id: this.boardId}, 
            params: {keyword: value, for_task_id: this.cardId}
          }).then(res => {
            this.members = res;
          });
        },

        archive: function() {
          api.archive({
            query: {cardId: this.cardId}
          }).then(res => {
            if (res) {
              this.$message.success("已归档");
              this.cardChange();
              this.onArchive();
            } else {
              this.$message.error("归档失败");
            }
          });
        },

        switchSubtaskSide: function() {
          this.showSubTaskSide = !this.showSubTaskSide;
        },

        addSubtask: function() {
          let data = {
            list_id: this.card.list_id,
            title: this.newSubtaskTitle,
            parent_id: this.card.id,
          };
          api.cardCreate({query: {boardId: this.card.kanban_id}, data: data})
            .then(() => {
              this.$message.success(i18n.t('task.create.success_msg'));
              this.cardChange();
              this.newSubtaskTitle = '';
              this.loadSubtasks();
            })
            .catch((e) => {
              console.log(e)
            });
        },

        switchToTask: function(id) {
          this.$emit('switch-to-task', id);
        },

        shareLink: function() {
          let link = window.location.href;
          copyToPlaster(link);
          this.$message.success('链接 ' + link + ' 已复制到剪贴板');
        },

        _cardCopiedInBoard: function() {
          this.$emit('cardchange');
        },

        _cardMovedToOtherBoard: function(cardId) {
          this.moveToOtherVisible = false;
          this.$emit('moved-to-other-board', cardId);
        },

        markdownUpdated: function() {
          this.$nextTick(() => {
            Prism.highlightAll();
          });
        },

        onVueCreated: function() {
        },

        cardChange: function() {
          this.$emit('cardchange');
        },

        onArchive: function() {
          this.$emit('archive');
          this.$bus.$emit('card-archived');
        },

        clearn: function() {
          this.comments = [];
        },

        handleMenuClick: function() {
          // console.info('actions clicked');
        }
    }
}
</script>
<style scoped lang="less">

.modal-main-label {
  color: rgba(0,0,0,.65);
  transition: all .3s;
  margin-right: 10px;
  margin-bottom: 15px;
  font-weight: 500;
  font-size: 16px;
}

.task-nav {
  background: #eff0f3;
  width: calc(~"100% + 24px");
  display: flex;
  height: 48px;
  line-height: 48px;
  margin-top: -24px;
  // margin-left: -24px;
  // margin-right: -24px;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  border-bottom: 1px solid #dcdfe4;
  padding: 0 24px;
  position: relative;
  .task-nav-breadcrumb {
    position: absolute;
    border: 1px solid #dcdfe4;
    background: #fff;
    height: 28px;
    line-height: 28px;
    padding: 0 15px;
    border-radius: 4px;
    top: calc(~"50% - 14px");
  }
}

.card-header {
  flex-shrink: 0;
  margin-right: -24px;
  display: flex;
  border-bottom: 1px solid #f4f4f4;
  background: #fff;
  border-radius: 0;
  min-height: 80px;
  .ant-row {
    width: 100%;
  }
}

.card-header-left {
  border-right: 1px solid #e8e8e8;
  min-height: 80px;
  height: 80px;
  padding: 24px;
}

.card-header-right {
  min-height: 80px;
  height: 80px;
  padding: 24px;
}

.card-info-item {
  font-weight: 500;
  color: #777777;
  letter-spacing: .2px;
  // margin-bottom: 3px;
}

.card-info-val {
  font-size: 13px;
  font-weight: 400;
  color: #777;
  padding: 2px 0;
}

.modal-main-label .anticon {
  margin-right: 5px;
}

.card-more-menu-item {
  a {
    padding: 5px 15px;
    color: #777;
    &:hover {
      color: #1890ff;
    }
    &:active {
      color: #1890ff;
    }
  }
}

.subtask-side {
  display: flex;
  flex-direction: column;
  border-right: 1px solid #e8e8e8;
  // box-shadow: 2px 0px 4px 0px #e8e8e8;
  background-color: #fbfbfb;
  padding: 24px;
  z-index: 1;
  @media screen and (min-width: 2000px) {
    height: 980px;
  }
  @media screen and (max-width: 1920px) {
    height: 780px;
  }
  @media screen and (max-width: 1440px) {
    height: 680px;
  }
  
  .subtask-side-title {
    // height: 64px;
    padding: 10px;
    border-bottom: 1px solid #e8e8e8;
  }
  .subtask-side-body {
    flex: 1;
    .subtask-items {
      .subtask-item {
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
        overflow : hidden;
        white-space: normal;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 1; /*超出1行就显示省略号，可以填3或者其它正整数*/
        &:hover {
          background-color: #e8e8e8;
        }
        .circle-dot {
          border: 2px solid #666;
          width: 10px;
          height: 10px;
          display: inline-block;
          border-radius: 50%;
        }
      }
      .subtask-item-active {
        background-color: #e8e8e8;
        font-weight: 500;
        .circle-dot {
          border-color: #1890ff;
          border-width: 3px;
        }
      }
    }
  }
}

.modal-card-title {
  padding-bottom: 10px;
  padding-top: 10px;
  font-size: 16px;
  font-weight: normal;
  display: inline-block;
  border-radius: 5px;
}

.modal-card-title:hover {
  background: #dfe4ea;
  cursor: pointer;
}

.card-title {
  width: 100%;
  display: inline-block;
  .ant-input {
    border-color: #fff;
    font-size: 24px;
    margin-left: -12px;
    &:hover {
      border-color: #40a9ff;
    }
  }
}

.card-desc {
  .card-desc-container {
    padding: 6px 12px;
    margin-left: -12px;
    border: 1px solid #fff;
    border-radius: 4px;
    transition: all 0.3s;
    overflow-x: auto;
    img {
      max-width: 840px;
    }
    cursor: text;
    .empty {
      cursor: text;
      padding: 0px 0px 64px 0px;
      color: rgba(0, 0, 0, 0.25);
    }
    &:hover {
      border-color: #40a9ff;
    }
  }
}

.title {
  width: 100%;
  display: inline-block;
}

.title .sub {
  color: rgba(0,0,0,.45);
}

.modal-card-actions {
  float: right;
  margin-right: 15px;
}

.modal-card-list {
  display: inline-block;
}

.modal-card-done, .modal-card-divider {
  height: 30px;
  line-height: 30px;
}

.modal-card-divider {
  .ant-divider-vertical {
    height: 100%;
  }
}

.modal-label-list {
  height: 37px;
  color: #ffffff;
}

.modal-card-label {
  position: relative;
  margin-right: 10px;
  margin-bottom: 10px;
  height: 31px;
  min-width: 64px;
  display: inline-block;
  text-align: center;
  vertical-align: top;
  color: #ffffff;
  border-radius: 4px;
  padding: 5px 10px;
  transition: all 0.5s ease 0s !important;
}

.modal-card-label:hover {
  padding-right: 32px;
}


.modal-card-label:hover .label-del {
  display: inline-block;
}

.label-del {
  position: absolute;
  right: 10px;
  cursor: pointer;
  transition: all 0.5s;
  display: none;
}

.card-body {
  margin-right: -24px;
  position: relative;
  @media screen and (min-width: 2000px) {
    height: 900px;
  }
  @media screen and (max-width: 1920px) {
    height: 700px;
  }
  @media screen and (max-width: 1440px) {
    height: 600px;
  } 
  .card-body-left {
    border-right: 1px solid #e8e8e8;
    @media screen and (min-width: 2000px) {
      height: 900px;
    }
    @media screen and (max-width: 1920px) {
      height: 700px;
    }
    @media screen and (max-width: 1440px) {
      height: 600px;
    } 
    overflow-y: auto; 
    padding: 24px;
  }
  .card-body-right {
    background-color: rgb(251, 251, 251);
    padding: 24px;
    height: 100%;
    position: relative;
    .card-comment {
      height: calc(~"100% - 90px");
      overflow-y: auto;
    }
    .card-comment-add {
      // position: absolute;
      // bottom: 0;
      // left: 0;
      // width: 100%;
      // margin-left: -24px;
    }
    .card-comment-item {
      .ant-comment-inner {
        padding-top: 0px;
      }
    }
  }
}

.attachment-item {
  padding: 5px 10px;
  border-radius: 5px;
  height: 42px; 
  line-height: 42px;
  &:hover {
    background: #fafbfc;
  }
}

.attachment-item:not(:last-child) {
    border-bottom: 1px solid #f4f4f4;
}

.checklist-item {
  padding: 0px 10px;
  margin-bottom: 5px;
  display: flex;
  justify-content:baseline;
  flex-wrap: nowrap;
  align-items: center;
  &:hover {
    border-radius: 5px;
  }
  .checklist-item-left {
    padding-right: 15px;
    flex: 65% 0;
    .ant-input {
      border: none;
    }
    .checklist-item-done-icon {
      font-size: 18px; 
      color: #1890ff; 
      cursor: pointer;
      width: 18px; 
      height: 18px; 
      line-height: 18px;
      border-radius: 50%;
      &:hover {
        box-shadow: 0px 0px 4px #1890ff;
      }
    }
    .checklist-item-undone-icon {
      border: 1px solid #1890ff; 
      width: 18px; 
      height: 18px; 
      border-radius: 50%; 
      cursor: pointer;
      &:hover {
        box-shadow: 0px 0px 4px #1890ff;
      }
    }
  }
}

.checklist-item-right {
  text-align: right;
  flex: 0 35%;
}

.checklist-item-duetime {
  flex: 0 0 15%;
  text-align: center;
}

.checklist-item-avatar {
  flex: 0 0 15%;
  text-align: center;
}

.attachment-item .del-icon {
  float: right;
}

.attachment-item:hover .del-icon {
  display: inline;
}

.desc-fullscreen-edit .desc-fullscreen-preview {
  padding-left: 10px;
}

.desc-fullscreen-edit textarea {
  border: none;
}

.card-comment .ant-tabs-content {
  padding-right: 24px;
}

</style>