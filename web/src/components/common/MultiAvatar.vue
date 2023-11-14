<template>
  <div
    class="multi-avatar"
    :style="styles"
  >
    <span
      class="avatar"
      v-if="members.length > num"
    >
      <a-tooltip>
        <template slot="title">
          <span>{{ hideNames }}</span>
        </template>
        <a-avatar
          :size="size"
          style="color: #fff; backgroundColor: #1890ff;"
        >
          ...
        </a-avatar>
      </a-tooltip>
    </span>
    <span 
      class="avatar" 
      v-for="(m, index) in showMembers" 
      :key="m.id"
    >
      <a-tooltip>
        <template slot="title">
          <span>{{ m.name }}</span>
        </template>
        <a-avatar
          :size="size"
          v-if="!m.avatar && index < num"
          style="color: #fff; backgroundColor: #1890ff;"
        >
          {{ firstWord(m.name) }}
        </a-avatar>
        <a-avatar 
          :size="size"
          v-if="m.avatar && index < num" 
          :src="m.avatar" 
        />
      </a-tooltip>
      
      <span
        v-if="close"
        class="close"
        @click.stop="onRm(m)"
      ><a-icon
        theme="filled"
        type="close-circle"
      /></span>
    </span>
  </div>
</template>
<script>
export default {
  components: {},

  props: {
    members: {
      type: Array,
      default: () => [],
      required: false,
    },
    num: {
      type: Number,
      default: 3,
      required: false,
    },
    styles: {
      type: Object,
      default: () => {},
      required: false
    },
    size: {
      type: String,
      default: 'default',
      required: false
    },
    close: {
      type: Boolean,
      default: false,
      required: false
    }

  },
  computed: {
    showMembers: function() {
      return this.members.slice(0, this.num);
    },
    hideNames: function() {
      let names = new Array();
      for (let index = 0; index < this.members.length; index++) {
        if (index < this.num) {
          continue;
        }
        const member = this.members[index];
        names.push(member.name);
      }
      return names.join(', ');
    }
  },
  methods: {
    onRm: function(member) {
      this.$emit('remove', member);
    }
  }
};
</script>

<style scoped lang="less">
.multi-avatar {
  display: inline-flex;
  flex-grow: 0;
  flex-shrink: 0;
  flex-direction: row-reverse;
  flex-wrap: wrap;
  justify-content: end;
  align-items: center;
  padding-left: 12px;
  .avatar {
    margin-left: -12px;
    /* width: 20px; */
    /* height: 32px; */
    text-align: center;
    color: #fff;
    border-radius: 50%;
    border: 1px solid #fff;
    position: relative;
    &:hover {
      .close {
        display: inline-block;
      }
      cursor: pointer;
    }
    .close {
      position: absolute;
      right: 2px;
      color: #949393;
      border-radius: 50%;
      background: #fff;
      height: 16px;
      width: 16px;
      line-height: 16px;
      display: none;
      font-weight: 500;
      font-size: 16px;
      &:hover {
        color: rgb(245, 74, 69);
      }
    }
    .ant-avatar-circle {
      border: 0.5px solid #fff;
    }
  }
}

</style>
