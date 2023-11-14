<template>
  <a-popover 
    class="no-arrow-popover"
    overlay-class-name="no-arrow-popover"
    :destroy-tooltip-on-hide="true"
    :placement="placement" 
    @visibleChange="visibleChange"
    trigger="click" 
    :style="popoverStyle"
  >
    <div slot="title">
      {{ title }}
    </div>
    <div
      class="lg-popover-content"
      slot="content"
    >
      <!-- <a-input-search 
        placeholder="搜索成员" 
        @search="onMemberSearch" 
      />
      <a-divider /> -->
      <a-list
        class="demo-loadmore-list"
        :loading="false"
        item-layout="horizontal"
        :data-source="members"
        :bordered="false"
        :split="false"
        :style="{'maxHeight': '300px', 'overflowY': 'auto'}"
      >
        <a-list-item 
          slot="renderItem" 
          slot-scope="m"
        >
          <a slot="actions">
            <LiCheckBox 
              :checked="m.isMember || selectedIds.indexOf(m.id) != -1" 
              :value="m.id" 
              label="" 
              @change="memberAddOrRemove" 
            />
          </a>
          <a-list-item-meta>
            <a slot="title">{{ m.name }}</a>
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
    </div>
  
    <slot name="trigger">
      <a-button 
        size="small" 
        icon="user-add" 
        style="margin-bottom: 10px;" 
        block
      >
        成员
      </a-button>
    </slot>
  </a-popover>
</template>
  
  <script>
  import api from "@/api";
  import liCheckBox from "@/components/form/LiCheckBox";
  
  export default {
      components: {
          LiCheckBox: liCheckBox,
      },
  
      props: {
          kanbanId: {
              type: Number,
              default: 0,
              required: false
          },
  
          cardId: {
              type: Number,
              default: 0,
              required: true
          },

          selectedIds: {
            type: Array,
            default: () => [],
            required: false
          },

          checklistId: {
            type: Number,
            default: 0, 
            required: true
          },
  
          title: {
              type: String,
              default: '',
              required: true
          },
  
          placement: {
            type: String,
            default: 'bottomLeft'
          },

          popoverStyle: {
            type: Object,
            default: () => {}
          }
      },
  
      data() {
          return {
              members: [],
              memberSearchKey: '',
          };
      },
  
      created() {
          // this.onMemberSearch('');
      },
  
      methods: {
          visibleChange: function(visible) {
            if (!visible) {
              return;
            }
            this.onMemberSearch('');
          },
  
          onMemberSearch: function(value) {
            this.memberSearchKey = value;
            api.checkListMembers({
              query: {id: this.checklistId, taskId: this.cardId}, 
            }).then(res => {
              this.members = res;
            });
          },
  
          memberAddOrRemove: function(id, isAdd) {
            if (isAdd) {
              if (!this.checklistId) {
                return this.$emit('memberadd', this.cardId, this.checklistId, this._getMember(id));
              }
              api.addCheckListMember({query: {taskId: this.cardId, id: this.checklistId}, data: {member_id: id}}).then((member) => {
                // this.onMemberSearch(this.memberSearchKey);
                if (member) {
                  this._addMember(id);
                  this.$emit('memberadd', this.cardId, this.checklistId, member);
                }
              });
            } else {
              if (!this.checklistId) {
                return this.$emit('memberremove', this.cardId, this.checklistId, id);
              }
              api.delCheckListMember({query: {taskId: this.cardId, id: this.checklistId, memberId: id}}).then(() => {
                // this.onMemberSearch(this.memberSearchKey);
                this._removeMember(id);
                this.$emit('memberremove', this.cardId, this.checklistId, id);
              });
            }
          },
  
          _removeMember: function(memberId) {
              const len = this.members.length;
              for (let i = 0; i < len; i++) {
                  const member = this.members[i];
                  if (member.id == memberId) {
                      this.members[i].isMember = false;
                      break;
                  }
              }
          },
  
          _addMember: function(memberId) {
              const len = this.members.length;
              for (let i = 0; i < len; i++) {
                  const member = this.members[i];
                  if (member.id == memberId) {
                      this.members[i].isMember = true;
                      break;
                  }
              }
          },

          _getMember: function(id) {
            const len = this.members.length;
              for (let i = 0; i < len; i++) {
                  const member = this.members[i];
                  if (member.id == id) {
                      return member;
                  }
              }
          }

      }
  
  }
  </script>