<template>
  <a-row>
    <a-col
      :span="24"
    >
      <a-layout class="board">
        <a-layout-sider
          class="home-layout-sider"
          v-model="collapsed"
          collapsible
          :trigger="null"
          :style="{
            display: 'flex', 
            position: 'absolute', 
            height: '100%',
            minHeight: '100%',
            flex: '0 0 200px',
            flexDirection: 'row-reverse',
            zIndex: 5,
            transform: 'translate3d(0%, 0, 0)',
            transition: 'transform 100ms ease-in'
          }"
        >
          <!-- <div 
            class="side-logo clearfix" 
            :style="{paddingLeft: collapsed ? '22px' : '24px'}"
          >
            <img
              src="/logo_without_txt.png"
              width="36"
            >
            <span
              v-if="!collapsed"
              style="display: inline-block;"
            >FadeTask</span>
          </div> -->
          <span
            class="collapsed-trigger"
            :style="{right: collapsed ? '-12px' : '-12px'}"
          >
            <a-button 
              shape="circle" 
              size="small" 
              :icon="collapsed ? 'double-right' : 'double-left'" 
              @click.stop="() => (collapsed = !collapsed)" 
            />
          </span>
          <a-menu
            theme="light"
            mode="inline"
            v-model="current"
            @click="menuClick"
            :open-keys="openKeys"
            @openChange="onOpenChange"
            :default-selected-keys="['Dashboard']"
          >
            <a-menu-item key="Dashboard">
              <a-icon type="home" />
              <span>{{ $t('home.menu_home') }}</span>
            </a-menu-item>

            <!-- <a-sub-menu key="Project">
              <span slot="title">
                <a-icon type="project" />
                <span>{{ $t('home.menu_project') }}</span>
                <a @click.stop="() => {}"><a-icon type="plus" /></a>
              </span>
              <a-menu-item key="KanbanFavorited">
                <span>{{ $t('home.menu_favorited_kanban') }}</span>
              </a-menu-item>
            </a-sub-menu> -->

            <a-menu-item key="ProjectList">
              <a-icon type="project" />
              <span>{{ $t('home.menu_project') }}</span>
            </a-menu-item>
            <a-menu-item key="KanbanAll">
              <a-icon type="appstore" />
              <span>{{ $t('home.menu_kanban') }}</span>
            </a-menu-item>
            <a-sub-menu key="My">
              <span slot="title">
                <a-icon type="user" />
                <span>{{ $t('home.menu_my') }}</span>
              </span>
              <a-menu-item key="KanbanFavorited">
                <span>{{ $t('home.menu_favorited_kanban') }}</span>
              </a-menu-item>
            </a-sub-menu>
            <a-sub-menu key="Deleted">
              <span slot="title">
                <a-icon type="delete" />
                <span>{{ $t('home.menu_delete') }}</span>
              </span>
              <a-menu-item key="ClosedProject">
                <span>{{ $t('home.menu_closed_project') }}</span>
              </a-menu-item>
              <a-menu-item key="KanbanClosed">
                <span>{{ $t('home.menu_closed_kanban') }}</span>
              </a-menu-item>
            </a-sub-menu>
          </a-menu>
        </a-layout-sider>
        <a-layout
          class="home-layout-content"
          :style="{paddingLeft: contentPaddingLeft}"
        >
          <router-view />
        </a-layout>
      </a-layout>
    </a-col>
  </a-row>
</template>
<script>

export default {
  components: {
  },

  data() {
    return {
      current: ['Dashboard'],
      rootSubmenuKeys: ['My', 'Deleted'],
      openKeys: [],
      collapsed: false
    };
  },
  mounted() {
  },
  created() {
    this.setCurrentMenu();
  },
  watch: {
    '$route': function() {
      this.setCurrentMenu();
    },
  },
  computed: {
    contentPaddingLeft: function() {
      return this.collapsed ? '80px' : '200px';
    }
  },
  methods: {
    setCurrentMenu: function() {
      var key = this.$route.name;
      if (key === 'ProjectDetail') {
        key = 'ProjectList';
      }
      this.current = [key];
      if (key === 'KanbanClosed' || key === 'ClosedProject') {
        this.openKeys = ['Deleted'];
      } else if (key === 'KanbanFavorited') {
        this.openKeys = ['My'];
      }
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
      this.$router.push({name: event.key});
    }
  }
}
</script>

<style scoped lang="less">

.home-layout-sider {
  background: rgb(255, 255, 255, 0);
  width: 200px;
  display: flex;
  position: absolute;
}

.home-layout-content {
  display: flex;
  flex-direction: column;
  flex: 1 1 0%;
  overflow-y: auto;
  padding-left: 200px;
}

.ant-menu-item:first {
  margin-top: 0;
}

.home-layout-sider ul {
  height: 100%;
}

.side-logo {
  font-size: 16px; 
  height: 52px; 
  color: #000; 
  padding: 8px 8px 8px 16px; 
  border-right: 1px solid #e8e8e8;
  transform: translate3d(0%, 0px, 0px);
  transition: transform 100ms ease-in 0s;
}

</style>