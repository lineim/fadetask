<template>
  <label
    :class="getCheckLabelClass"
    :style="styles"
  >
    <span :class="getCheckSpanClass">
      <input
        type="checkbox"
        :checked="getCheckStatus()"
        :disabled="disabled"
        class="ant-checkbox-input"
        @change="change"
      >
      <span class="ant-checkbox-inner" />
    </span>
    <!-- <span :style="getLabelstyle()"> -->
    <slot name="label">
      <span>{{ label }}</span>
    </slot>
  </label>
</template>
<script>
export default {
  props: ['label', 'checked', 'value', 'disabled', 'styles'],
  data() {
    return {
        changedOnThis: false,
        myChecked: false,
    }
  },
  
  watch: {
    checked: function() {
        this.myChecked = this.checked;
    }
  },

  computed: {
    getCheckSpanClass: function() {
      let classes = 'ant-checkbox';
      if (this.changedOnThis) {
        if (this.myChecked) {
            classes += ' ant-checkbox-checked';
        }
      }
      if (this.myChecked || this.checked) {
        classes += ' ant-checkbox-checked';
      }
      if (this.disabled) {
        classes += ' ant-checkbox-disabled';
      }
      return classes;
    },

    getCheckLabelClass: function() {
      let classes = 'ant-checkbox-wrapper';
      if (this.disabled) {
        classes += ' ant-checkbox-wrapper-disabled';
      }
      return classes;
    },
  },

  methods: {
    getCheckStatus: function() {
      if (this.changedOnThis) {
        return this.myChecked;
      }
      return this.checked || this.myChecked;
    },

    getLabelstyle: function() {
      if (this.changedOnThis) {
        return this.myChecked ? {'text-decoration': 'line-through'} : {};
      }
      return this.checked || this.myChecked ? {'text-decoration': 'line-through'} : {};
    },
    change: function(e) {
      this.changedOnThis = true;
      this.myChecked = e.target.checked;
      this.$emit('change', this.value, e.target.checked, e);
    }
  }
}
</script>
<style>

</style>