<template>
  <div class="full-height project-list">
    <a-page-header
      style="padding-left: 0px; padding-right: 0px;"
      :title="$t('home.title_my_project')"
    >
      <template slot="extra">
        <a-button
          type="primary"
          @click="add"
        >
          <a-icon type="plus" />{{ $t('project.new') }}
        </a-button>
      </template>
    </a-page-header>
    <a-modal
      :destroy-on-close="true"
      :title="$t('project.new')"
      :width="680"
      @cancel="closeDrawer"
      @close="closeDrawer"
      :footer="null"
      :visible="drawerVisible"
      :wrap-style="{overflow: 'auto', paddingBottom: '108px'}"
    >
      <ProjectForm 
        :action="formAction"
        :project-uuid="fromProjectUuid"
        @submited="formSubmited"
        @canceled="formCanceled"
      />        
    </a-modal>
    <a-table
      :style="{background: '#fff'}"
      :columns="columns" 
      :data-source="projects"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.uuid"
      @change="tableChange"
    >
      <template
        slot="create_datetime"
        slot-scope="createdTime"
      >
        {{ createdTime|friendlyTime }}
      </template>

      <template
        slot="name"
        slot-scope="project, r, index"
      >
        <router-link :to="{name: 'ProjectOverview', params: {uuid: project.uuid}}">
          <a-avatar
            shape="square"
            size="large"
            :style="{backgroundColor: randColor(index)}"
          >
            {{ firstWord(project.name) }}
          </a-avatar>
          {{ project.name }}
        </router-link>
      </template>

      <template
        slot="create_user"
        slot-scope="creator"
      >
        {{ creator.name }}
      </template>

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
            {{ $t('project.list.detail') }}
          </a-button>
          <!-- <a-button
            size="small"
            @click="edit(item.uuid)"
            type="link"
          >
            {{ $t('project.edit') }}
          </a-button>
          <a-popconfirm
            placement="top"
            :ok-text="$t('yes')"
            :cancel-text="$t('no')"
            @confirm="deleteProject(item.uuid)"
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
  </div>
</template>

<script>
import api from '@/api'
import i18n from '../../i18n';
import projectForm from '@/components/project/Form.vue';
import {friendlyTime} from '@/helper/datatime.js';
import { randColor } from '../../utils';

const columns = [
  {
    title: i18n.t('project.list.name'),
    key: 'name',
    scopedSlots: { customRender: 'name' },
  },
  {
    title: i18n.t('project.list.kanban_count'),
    dataIndex: 'kanban_num',
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.list.member_count'),
    dataIndex: 'member_num',
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.list.creator'),
    dataIndex: 'creator',
    scopedSlots: { customRender: 'create_user' },
    align: 'center',
    width: '15%',
  },
  {
    title: i18n.t('project.list.create_time'),
    dataIndex: 'create_datetime',
    scopedSlots: { customRender: 'create_datetime' },
    align: 'center',
    width: '10%',
  },
  {
    title: i18n.t('project.list.handle'),
    key: 'action',
    scopedSlots: { customRender: 'action' },
    align: 'right',
    width: '15%',
  },
];

export default {
    components: {
      'ProjectForm': projectForm,
    },
    data() {
        return {
            loading: false,
            columns: columns,
            user: {},
            projects: [],
            pagination: {
              total: 0,
              pageSize: 10,
              current: 1,
            },
            drawerVisible: false,
            formAction: '',
            fromProjectUuid: "",
        }
    },

    created() {
        
    },
    mounted() {
      this.loadProjects();
      this.formAction = 'add';
    },
    methods: {
        randColor,
        friendlyTime(datetime) {
          return friendlyTime(datetime);
        },

        tableChange(pagination) {
          this.pagination = pagination;
          this.loadProjects();
        },
       
        loadProjects() {
          this.loading = true;
          const params = {
            page: this.pagination.current,
            pageSize: this.pagination.pageSize
          };
          api.getProjects({params: params}).then(res => {
            const pagination = { ...this.pagination };
            pagination.total = res.total_count;
            this.loading = false;
            this.projects = res.projects;
            this.pagination = pagination;
          }).catch(err => {
            this.$message.error(err.message);
          });
        },
        showDrawer() {
          this.drawerVisible = true;
        },
        closeDrawer() {
          this.drawerVisible = false;
        },
        formSubmited() {
          this.loadProjects();
          this.closeDrawer();
        },
        formCanceled() {
          this.closeDrawer();
        },
        gotoDetail(uuid) {
          this.$router.push({name: "ProjectOverview", params: {uuid: uuid}});
        },

        add() {
          this.formAction = 'add';
          this.fromProjectUuid = "";
          this.showDrawer();
        },
        
        edit(uuid) {
          this.fromProjectUuid = uuid;
          this.formAction = 'edit';
          this.showDrawer();
        },

        deleteProject(uuid) {
          let self = this;
          api.deleteProject({query: {uuid: uuid}}).then(() => {
            self.$message.success('Deleted');
            self.loadProjects();
          });
        }
    },
    computed: {
        showEmpty() {
            return !this.loading && this.projects.length == 0;
        },

        drawerTitle() {
          switch (this.formAction) {
            case 'edit':
              return 'Edit Project';
            default:
              return 'New Project';
          }
        },
    }

}
</script>

<style scoped>
.project-list {
  background-color: #fff;
  padding: 16px 26px 0;
  padding-bottom: 60px;
  overflow-y: scroll;
}
</style>