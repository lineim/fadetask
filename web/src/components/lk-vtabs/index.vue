<script>
  import TabNav from './tab-nav';
  function noop() {}
  export default {
    name: 'ElTabs',

    components: {
      TabNav
    },

    props: {
      type: {
          type: String,
          default: '',
      },
      activeName: {
          type: String,
          default: '',
      },
      closable: Boolean,
      value: {},
      tabPosition: {
        type: String,
        default: 'top'
      },
      beforeLeave: {
          type: Function,
          default: noop
      },
      stretch: Boolean
    },

    provide() {
      return {
        rootTabs: this
      };
    },

    data() {
      return {
        currentName: this.value || this.activeName,
        showHeader: false,
        showMenus: true,
        panes: []
      };
    },

    watch: {
      activeName(value) {
        this.setCurrentName(value);
      },
      value(value) {
        this.setCurrentName(value);
      },
      currentName() {
        if (this.$refs.nav) {
          this.$nextTick(() => {
            this.$refs.nav.$nextTick(() => {
              this.$refs.nav.scrollToActiveTab();
            });
          });
        }
      }
    },

    methods: {
      calcPaneInstances(isForceUpdate = false) {
        if (this.$slots.default) {
          const paneSlots = this.$slots.default.filter(vnode => vnode.tag &&
            vnode.componentOptions && vnode.componentOptions.Ctor.options.name === 'ElTabPane');
          const panes = paneSlots.map(({ componentInstance }) => componentInstance);
          const panesChanged = !(panes.length === this.panes.length && panes.every((pane, index) => pane === this.panes[index]));
          if (isForceUpdate || panesChanged) {
            this.panes = panes;
          }
        } else if (this.panes.length !== 0) {
          this.panes = [];
        }
      },
      handleBack(ev) {
        this.setCurrentName('');
        this.showHeader = false;
        this.showMenus = true;
        this.$emit('tab-show-menus', '', ev);
      },
      handleTabClick(tab, tabName, event) {
        if (tab.disabled) {
            return ;
        }
        this.setCurrentName(tabName);
        this.showHeader = true;
        this.showMenus = false;
        this.$emit('tab-click', tab, event);
      },
      handleTabRemove(pane, ev) {
        if (pane.disabled) {
            return;
        }
        ev.stopPropagation();
        this.$emit('edit', pane.name, 'remove');
        this.$emit('tab-remove', pane.name);
      },
      handleTabAdd() {
        this.$emit('edit', null, 'add');
        this.$emit('tab-add');
      },
      setCurrentName(value) {
        const changeCurrentName = () => {
          this.currentName = value;
          this.$emit('input', value);
        };
        if (this.currentName !== value && this.beforeLeave) {
          const before = this.beforeLeave(value, this.currentName);
          if (before && before.then) {
            before
              .then(() => {
                changeCurrentName();
                this.$refs.nav && this.$refs.nav.removeFocus();
              }, () => {
                // https://github.com/ElemeFE/element/pull/14816
                // ignore promise rejection in `before-leave` hook
              });
          } else if (before !== false) {
            changeCurrentName();
          }
        } else {
          changeCurrentName();
        }
      }
    },

    render() {
      let {
        type,
        handleTabClick,
        handleTabRemove,
        currentName,
        showHeader,
        showMenus,
        handleBack,
        panes,
        stretch
      } = this;

      const navData = {
        props: {
          currentName,
          onTabClick: handleTabClick,
          onTabRemove: handleTabRemove,
          type,
          panes,
          stretch
        },
        ref: 'nav'
      };
      const header = showHeader ? (
        <div class={['lk-v-tabs__header']} v-if="showHeader">
          <span
            class="mrs cursor-pointer"
            on-click={(ev) => { handleBack(ev); }}
          ><a-icon type="left" /></span>
          {this.currentName}
        </div>
      ) : null;
      const menus = showMenus ? (
          <div class={['lk-v-tabs__menus']} v-if="showMenus">
            <tab-nav { ...navData }></tab-nav>
          </div>
      ) : null;
      const panels = (
        <div class="lk-v-tabs__content">
          {this.$slots.default}
        </div>
      );

      return (
        <div class={{
          'lk-v-tabs': true
        }}>
          { [header, menus, panels] }
        </div>
      );
    },
  
    created() {
      if (!this.currentName) {
        this.setCurrentName('0');
      }

      this.$on('tab-nav-update', this.calcPaneInstances.bind(null, true));
    },

    mounted() {
      this.calcPaneInstances();
    },

    updated() {
      this.calcPaneInstances();
    }
  };
</script>

<style scoped>
  .lk-v-tabs {
    background: #fff;
    width: 100%;
  }

  .lk-v-tabs__menus {
    text-align: center;
  }

  .lk-v-tabs__header {
    font-size: 14px;
    font-weight: 500;
    color: rgba(0, 0, 0, 0.85);
    padding: 10px 0;
  }
</style>