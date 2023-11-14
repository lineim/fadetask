<template>
  <div>
    <div
      v-if="isManager"
      class="clearfix mbs"
    >
      <a-button
        class="pull-right"
        icon="plus"
        type="primary"
        @click.stop="showInvert"
      >
        {{ $t('project.member.invert') }}
      </a-button>
    </div>
    <a-table
      :style="{background: '#fff'}"
      :columns="columns" 
      :data-source="members"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
      @change="tableChange"
    >
      <template
        slot="join_time"
        slot-scope="joinTime"
      >
        {{ joinTime|friendlyTime }}
      </template>

      <template
        slot="name"
        slot-scope="name"
      >
        <a-avatar
          class="mrs"
          style="color: #f56a00; backgroundColor: #fde3cf"
        >
          {{ firstWord(name) }}
        </a-avatar>
        {{ name }}
      </template>

      <template
        slot="role"
        slot-scope="member"
      >
        <a-select
          :default-value="member.project_role"
          style="width: 100px"
          :v-model="member.project_role"
          @change="roleChange($event, member.id)"
          v-if="isManager && member.project_role > 0"
        >
          <a-select-option 
            :value="1"
          >
            {{ translateRole(1) }}
          </a-select-option>
          <a-select-option
            :value="2"
          >
            {{ translateRole(2) }}
          </a-select-option>
        </a-select>
        <a v-else>
          {{ translateRole(member.project_role) }}
        </a>
      </template>

      <template
        slot="action"
        slot-scope="member"
      >
        <a-button-group>
          <a-dropdown trigger="click">
            <a-menu slot="overlay" @click="handleMenuClick">
              <a-menu-item key="1">
                1st item
              </a-menu-item>
              <a-menu-item key="2">
                2nd item
              </a-menu-item>
              <a-menu-item key="3">
                <a-popconfirm
                  placement="topLeft"
                  :ok-text="$t('yes')"
                  :cancel-text="$t('no')"
                  @confirm="removeMember(member.id)"
                >
                  <template slot="title">
                    <p>{{ $t('project.member.remove_tips') }}</p>
                  </template>
                  <a-button
                    :disabled="!(isManager && member.project_role > 0)"
                    size="small"
                    type="danger"
                  >
                    {{ $t('project.member.remove') }}
                  </a-button>
                </a-popconfirm>
              </a-menu-item>
            </a-menu>
            <a-button> Actions <a-icon type="down" /> </a-button>
          </a-dropdown>

          <!-- <a-button
            size="small"
            @click="edit(member.uuid)"
            type="link"
          >
            {{ $t('project.edit') }}
          </a-button>
          <a-popconfirm
            placement="top"
            :ok-text="$t('yes')"
            :cancel-text="$t('no')"
            @confirm="deleteProject(member.uuid)"
          >
            <template slot="title">
              {{ $t('project.delete_tips') }}
            </template>
            <a-button
              size="small"
              type="link"
            >
              {{ $t('project.delete') }}
            </a-button>
          </a-popconfirm> -->
        </a-button-group>
      </template>
    </a-table>
    <a-modal
      :title="$t('project.member.invert')"
      :visible="invertVisible"
      width="640px"
      :confirm-loading="false"
      :footer="null"
      @cancel="invertCancel"
    >
      <!-- <a-textarea
        v-model="emails"
        :placeholder="$t('project.member.invert_email_placeholder')"
        :auto-size="{ minRows: 5, maxRows: 5 }"
      /> -->
      <div class="pvm tac">
        <a-icon
          type="link"
          class="mrxs"
        /> {{ $t('project.member.invert_email_tips') }}，
        <a
          class="mrs"
          @click.stop="copyLink"
        >{{ $t('copy_link') }}</a>
      </div>
      <!-- <div class="clearfix">
        <a-button
          class="pull-right"
          type="primary"
        >
          提交
        </a-button>
      </div> -->
    </a-modal>
  </div>
</template>
<script>
import api from '@/api';
import i18n from '../../i18n';
import { copyToPlaster } from '../../utils';

const columns = [
  {
    title: i18n.t('project.member.name'),
    key: 'name',
    dataIndex: 'name',
    scopedSlots: { customRender: 'name' },
  },
  
  {
    title: i18n.t('project.member.role'),
    scopedSlots: { customRender: 'role' },
    align: 'center',
    width: '15%',
  },
  {
    title: i18n.t('project.member.join_time'),
    dataIndex: 'project_join_date',
    scopedSlots: { customRender: 'join_time' },
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.member.handle'),
    key: 'action',
    scopedSlots: { customRender: 'action' },
    align: 'right',
    width: '15%',
  },
];

export default {
    props: {
    },

    data() {
      return {
        members: [],
        columns,
        loading: false,
        isManager: false,
        pagination: {
          total: 0,
          pageSize: 10,
          current: 1,
        },
        invertVisible: false,
        emails: ''
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

    computed: {
      
    },

    methods: {
      init() {
        this.uuid = this.$route.params.uuid;
        this.loadMember();
      },
        loadMember: function() {
          this.loading = true;
          const params = {
            page: this.pagination.current,
            pageSize: this.pagination.pageSize
          };
          api.projectMember({query: {uuid: this.uuid}, params: params}).then(resp => {
            this.members = resp.members;
            this.isManager = resp.is_manager;
            this.loading = false;
            const pagination = { ...this.pagination };
            pagination.total = resp.total;
            this.pagination = pagination;
          });
        },

        roleChange: function (role, memberId) {
          const data = {member_id: memberId, role: role};
          api.projectMemberRole({query: {uuid: this.uuid}, data: data}).then(() => {
            this.$message.success(i18n.t('project.member.role_changed'));
          });
        },

        tableChange(pagination) {
          this.pagination = pagination;
          this.loadMember();
        },

        showInvert() {
          this.invertVisible = true;
        },

        invertCancel() {
          this.invertVisible = false;
        },

        copyLink() {
          api.projectInvertLink({query: {uuid: this.uuid}}).then(res => {
            copyToPlaster(res);
            this.$message.success(i18n.t('project.member.invert_link_copied'));
          });
        },

        removeMember(memberId) {
          const data = {member_id: memberId};
          const query = {uuid: this.uuid};
          api.projectMemberRemove({query: query, data: data}).then(() => {
            this.$message.success(i18n.t('project.member.removed_tips'));
            this.loadMember();
          });
        },

        translateRole(role) {
          const memberRoleMap = {
            0: 'project.member.role_label.owner',
            1: 'project.member.role_label.admin',
            2: 'project.member.role_label.user',
          };
          const v = memberRoleMap[role] || '';
          return i18n.t(v);
        }
    }
}
</script>