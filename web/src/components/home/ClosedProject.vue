<template>
  <div class="full-height project-list">
    <a-page-header
      style="padding-left: 0px; padding-right: 0px;"
      :title="$t('home.title_closed_project')"
    />
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
        slot-scope="project"
      >
        {{ project.name }}
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
        <a-popconfirm
          title="是否重新打开项目？"
          placement="left"
          ok-text="是"
          cancel-text="否"
          @confirm="open(item.uuid)"
        >
          <a-button
            size="small"
            type="primary"
          >
            {{ $t('project.list.open') }}
          </a-button>
        </a-popconfirm>
      </template>
    </a-table>
  </div>
</template>
  
  <script>
  import api from '@/api'
  import i18n from '../../i18n';
  import {friendlyTime} from '@/helper/datatime.js';
  
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
          }
      },
  
      created() {
          
      },
      mounted() {
        this.loadProjects();
      },
      methods: {
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
              pageSize: this.pagination.pageSize,
              closed: 1
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
          
          open(uuid) {
            api.openProject({query: {uuid: uuid}}).then(() => {
              this.$message.success(i18n.t('project.opened'));
              this.loadProjects();
            });
          }
      },
      computed: {

      }
  
  }
  </script>
  
  <style scoped>
  .project-list {
    background-color: #fff;
    padding: 16px 26px 0;
  }
  .create-question-btn {
    text-align: right;
  }
  </style>