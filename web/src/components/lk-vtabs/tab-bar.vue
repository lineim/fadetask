<template>
  <div
    class="lk-v-tabs__active-bar"
    :class="`is-${ rootTabs.tabPosition }`"
    :style="barStyle"
  />
</template>
<script>
  export default {
    name: 'TabBar',

    props: {
      tabs: Array
    },

    inject: ['rootTabs'],

    computed: {
      barStyle: {
        get() {
          let style = {};
          let offset = 0;
          let tabSize = 0;
          const sizeName = ['top', 'bottom'].indexOf(this.rootTabs.tabPosition) !== -1 ? 'width' : 'height';
          const sizeDir = sizeName === 'width' ? 'x' : 'y';
          const firstUpperCase = str => {
            return str.toLowerCase().replace(/( |^)[a-z]/g, (L) => L.toUpperCase());
          };
          this.tabs.every((tab) => {
            let $el = '';
            if (!$el) { return false; }

            if (!tab.active) {
              offset += $el[`client${firstUpperCase(sizeName)}`];
              return true;
            } else {
              tabSize = $el[`client${firstUpperCase(sizeName)}`];
              const tabStyles = window.getComputedStyle($el);
              if (sizeName === 'width' && this.tabs.length > 1) {
                tabSize -= parseFloat(tabStyles.paddingLeft) + parseFloat(tabStyles.paddingRight);
              }
              if (sizeName === 'width') {
                offset += parseFloat(tabStyles.paddingLeft);
              }
              return false;
            }
          });

          const transform = `translate${firstUpperCase(sizeDir)}(${offset}px)`;
          style[sizeName] = tabSize + 'px';
          style.transform = transform;
          style.msTransform = transform;
          style.webkitTransform = transform;

          return style;
        }
      }
    }
  };
</script>