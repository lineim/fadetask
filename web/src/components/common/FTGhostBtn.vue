<template>
  <div class="ft-ghost-btn">
    <a-button 
      type="dashed"
      :shape="shape"
      :icon="icon"
      :size="size"
      @click="onclick"
      :style="btnstyle"
      ghost 
    >
      {{ text }}
    </a-button>
  </div>
</template>

<script>
export default {  
    props: {
      shape: {
        type: String,
        default: 'circle',
        required: false,
      },
      icon: {
        type: String,
        default: '',
        required: false
      },
      size: {
        type: String,
        default: 'default',
        required: false
      },
      text: {
        type: String,
        default: '',
        required: false
      },
      btnstyle: {
        type: Object,
        default: () => new Object(),
        required: false
      }
    },

    methods: {
      onclick: function() {
        this.$emit('click');
      }
    }
}
</script>

<style scoped lang="less">
.ft-ghost-btn {
  display: inline-block;
  .ant-btn-background-ghost {
    border-color: #b6b6b6;
    color: #b6b6b6;
    &:hover {
      color: rgb(24, 144, 255);
      border-color: rgb(24, 144, 255);
    }
  }
}
</style>