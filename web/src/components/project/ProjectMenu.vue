<template>
  <a-menu
    theme="light"
    mode="inline"
    v-model="selectedMenu"
    :open-keys="openKeys"
    @click="menuClick"
    :style="{height: '100%'}"
  >
    <a-menu-item
      key="Header"
      class="project-menu-header"
    >
      <a-avatar
        shape="square"
        :size="48"
        class="project-menu-avatar mrxs"
        :class="{'collapsed-project-menu-header': collapsed}"
        style="backgroundColor:#1abc9c; font-size: 32px; font-weight: 400;"
      >
        {{ firstWord(project.name) }}
      </a-avatar>
      <span
        class="project-menu-name"
        v-if="!collapsed"
      >{{ project.name }}</span>
    </a-menu-item>
    <a-menu-item key="Dashboard">
      <a-icon type="dashboard" />
      <span>{{ $t('project.detail.dashboard') }}</span>
    </a-menu-item>
    <a-menu-item
      key="Kanban"
      v-if="!inBoard"
    >
      <a-icon type="appstore" />
      <span>{{ $t('project.detail.kanban') }}</span>
    </a-menu-item>
    <a-sub-menu
      v-if="inBoard"
      key="Kanban"
      class="project-kanban-sub-menu"
      @titleClick="openChanged"
    >
      <span slot="title">
        <a-icon type="rollback" />
        <span>{{ $t('project.detail.kanban_list') }}</span>
      </span>
      <a-menu-item
        v-for="kanban in kanbans"
        :key="'kanban-'+kanban.id"
      >
        {{ kanban.name }}
      </a-menu-item>
    </a-sub-menu>
   
    <a-menu-item key="Member">
      <a-icon type="user" />
      <span>{{ $t('project.detail.member') }}</span>
    </a-menu-item>
  </a-menu>
</template>

<script>
import api from '@/api';
const MENU_ROUTE_MAP = {
  'Header': 'ProjectOverview',
  'Dashboard': 'ProjectOverview',
  'Kanban': 'ProjectKanban',
  'Member': 'ProjectMember'
};
const ROUTE_MENU_MAP = {
  'ProjectOverview': 'Dashboard',
  'ProjectKanban': 'Kanban',
  'ProjectMember': 'Member'
};

export default {
  props: {
    project: {
      type: Object,
      default: () => {},
      requeired: true
    },
    collapsed: {
      type: Boolean,
      default: false,
      requeired: false
    }
  },
  data() {
    return {
      selectedMenu: ["Dashboard"],
      openKeys: ['Kanban'],
      inBoard: false,
      kanbans: [],
      kanbanId: 0
    }
  },

  mounted() {
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

  methods: {
    init() {
      const routeName = this.$route.name;
      this.inBoard = ['KanbanDashboard', 'KanbanDetail'].includes(routeName);
      if (this.inBoard) {
        this.kanbanId = this.$route.params.id;
        this.selectedMenu = ['kanban-' + this.kanbanId];
      } else {
        this.kanbanId = 0;
        const menu = ROUTE_MENU_MAP[routeName];
        this.selectedMenu = [menu];
      }
    },
    loadKanbans() {
      this.loading = true;
      const params = {
        page: 0,
        pageSize: 1000
      };
      api.projectKanban({query: {uuid: this.project.uuid}, params: params}).then(kanbans => {
        this.kanbans = kanbans;
        this.loading = false;
      });
    },

    onOpenChange(openKeys) {
      const latestOpenKey = openKeys.find(key => this.openKeys.indexOf(key) === -1);
      if (this.rootSubmenuKeys.indexOf(latestOpenKey) === -1) {
        this.openKeys = openKeys;
      } else {
        this.openKeys = latestOpenKey ? [latestOpenKey] : [];
      }
    },

    menuClick: function(event) {
      const key = event.key;
      if (typeof(MENU_ROUTE_MAP[key]) != 'undefined') {
        const routerName = MENU_ROUTE_MAP[key];
        if (key == 'Header') {
          this.selectedMenu = ['Dashboard'];
        } else {
          this.selectedMenu = [key];
        }
        this.$router.push({name: routerName, params: {uuid: this.project.uuid}});
      } else {
        const arr = key.split('-');
        const kanbanId = arr[1];
        this.$router.push({name: 'KanbanDetail', params: {id: kanbanId}});
      }
      
    },

    openChanged() {
      this.$router.push({name: 'ProjectKanban',  params: {uuid: this.project.uuid}})
      // if (this.openKeys.length > 0) {
      //   this.openKeys = [];
      // } else {
      //   this.openKeys = ['Kanban'];
      // }
    }
  }
}
</script>

<style scoped lang="less">
.collapsed-project-menu-header {
  position: absolute;
  top: 12px;
  left: 15px;
}

.project-kanban-sub-menu {
  li {
    overflow-x: hidden;
    max-width: 199px;
  }
}
.project-menu-header {
  height: 66px;
  line-height: 66px;
  overflow-x: hidden;
  max-width: 199px;
  .project-menu-name {
    position: absolute;
    display: inline-block;
    width: 110px;
    font-size: 14px;
    white-space: nowrap;
    overflow-x: hidden;
    text-overflow: ellipsis;
    -o-text-overflow:ellipsis;
  }
}
</style>