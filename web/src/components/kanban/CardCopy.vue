<template>
  <a-popover 
    overlay-class-name="no-arrow-popover"
    trigger="click"
    v-model="popoverVisiable"
    @visibleChange="visibleChange"
    placement="rightTop"
  >
    <div slot="title">
      {{ title }}
    </div>
    <div
      class="middle-popover-content"
      slot="content"
    >
      <a-form-model
        ref="moveForm"
        :model="moveToOtherForm"
        :rules="moveToOtherRules"
        :label-col="{ span: 0 }"
        :wrapper-col="{ span: 24 }"
      > 
        <a-form-model-item prop="boardId">
          <a-select 
            v-model="moveToOtherForm.boardId" 
            :placeholder="$t('task.copy.kanban')" 
            @change="moveToBoardChange()"
          >
            <a-select-option 
              v-for="kanban in myKanbans" 
              :value="kanban.uuid" 
              :key="kanban.uuid"
            >
              {{ kanban.name }}
            </a-select-option>
          </a-select>
        </a-form-model-item>
  
        <a-form-model-item prop="listId">
          <a-select 
            v-model="moveToOtherForm.listId" 
            :placeholder="$t('task.copy.list')"
          >
            <a-select-option 
              v-for="list in canMoveToList" 
              :disabled="list.wip > 0 && list.task_count + 1 > list.wip"
              :value="list.id" 
              :key="list.id"
            >
              {{ list.name }} <span v-if="list.wip > 0 && list.task_count + 1 > list.wip">(WIP超限)</span>
            </a-select-option>
          </a-select>
        </a-form-model-item>
  
        <a-form-model-item>
          <a-button 
            type="primary" 
            @click="moveToOtherSubmit()"
          >
            {{ $t('task.copy.label') }}
          </a-button>
        </a-form-model-item>
      </a-form-model>
    </div>
    <slot name="trigger">
      <a-button 
        size="small" 
        icon="export" 
        style="margin-bottom: 10px;" 
        block
      >
        {{ $t('task.copy.label') }}
      </a-button>
    </slot>
  </a-popover>
</template>
  
<script>
  import api from "@/api";
  import i18n from '../../i18n';
  
  export default {
      props: {
        boardId: {
          type: String,
          default: '',
          required: true
        },
  
        cardId: {
          type: Number,
          default: 0,
          required: true
        },
  
        title: {
          type: String,
          default: i18n.t('task.copy.title'),
          required: true
        },
      },
  
      data() {
        return {
          popoverVisiable: false,
          myKanbans: [],
          moveToOtherForm: {
            boardId: undefined,
            listId: undefined
          },
          moveToOtherRules: {
            boardId: [{ required: true, message: i18n.t('task.kanban_require_tips'), trigger: 'change' }],
            listId: [{ required: true, message: i18n.t('task.list_require_tips'), trigger: 'change' }],
          },
        };
      },
  
      created() {
        // this.loadMyKanbans();
        this.moveToOtherForm.boardId = this.boardId;
      },
  
      computed: {
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
            if (kanban.uuid == this.moveToOtherForm.boardId) {
              return kanban.list;
            }
          }
          return [];
        },
      },
  
      methods: {
        visibleChange: function(v) {
          if (v) {
            this.loadMyKanbans();
          } else {
            this.myKanbans = [];
          }
        },

        loadMyKanbans: function() {
          api.myKanbans().then(res => {
            this.myKanbans = res;
          });
        },
  
        moveToBoardChange: function() {
          this.moveToOtherForm.listId = undefined;
        },
  
        moveToOtherSubmit: function() {
          this.$refs.moveForm.validate(valid => {
            if (!valid) {
              return false;
            }
            if (this.moveToOtherForm.boardId != this.boardId) { // copy to other
                api.cardCopyToOther({
                    query: {boardId: this.boardId, cardId: this.cardId},
                    data: {to_board_id: this.moveToOtherForm.boardId, to_list_id: this.moveToOtherForm.listId}
                }).then(() => {
                    this._copied();
                    this.$emit('copied_to_other', this.cardId);
                });
            } else { // copy in board
                api.cardCopy({
                    query: {boardId: this.boardId, cardId: this.cardId},
                    data: {to_list_id: this.moveToOtherForm.listId}
                }).then(() => {
                    this._copied();
                    this.$emit('copied', this.cardId);
                });
            }
            
          });
        },

        _copied() {
            this.reset();
            this.closePopover();
            this.$message.success('复制成功!');
        },
  
        closePopover: function() {
          this.popoverVisiable = false;
        },
  
        reset: function() {
            this.myKanbans = [];
          this.moveToOtherForm = Object.assign(
            {}, 
            {
              boardId: undefined,
              listId: undefined
            }
          );
        },
  
      }
  
  }
  </script>