<template>
  <div class="wrapper">
    <a
      href="javascript:;"
      @click="handletoview(record, index)"
    >查看</a>
    <a-divider type="vertical" />

    <template v-if="record.status === 'ASSING'">
      <template v-if="Number(record.finishedFeedbackNum) !== Number(record.totalFeedbackNum)">
        <a
          href="javascript:;"
          @click="handleremind(record, index)"
        >提醒</a>
        <a-divider type="vertical" />
      </template>

      <template v-else>
        <a
          href="javascript:;"
          style="display: none"
          @click="handlepreviewreport(record, index)"
        >预览报告</a>
        <a-divider
          type="vertical"
          style="display: none"
        />
        <a
          href="javascript:;"
          @click="handleconfirmresult(record, index)"
        >确认结果</a>
        <a-divider type="vertical" />
      </template>
    </template>

    <template v-else-if="record.status === 'PRE_READY'">
      <template v-if="record.feedbackUsers">
        <a
          href="javascript:;"
          @click="handlemodify(record, index)"
        >修改</a>
        <a-divider type="vertical" />
        <a
          href="javascript:;"
          @click="handlepublish(record, index)"
        >发布</a>
        <a-divider type="vertical" />
      </template>
        
      <template v-else>
        <a
          href="javascript:;"
          @click="handlemodify(record, index)"
        >填写</a>
        <a-divider type="vertical" />
        <a
          v-if="record.isSelfAssign == '1'"
          href="javascript:;"
          @click="handleremind(record, index)"
        >提醒</a>
        <a-divider
          v-if="record.isSelfAssign == '1'"
          type="vertical"
        />
      </template>   
    </template>

    <template v-else-if="record.status === 'FINISHED'">
      <a
        href="javascript:;"
        @click="handledownreport(record, index)"
      >下载报告</a>
      <a-divider type="vertical" />
      <a
        href="javascript:;"
        @click="handleyourself(record, index)"
      >发送给本人</a>
      <a-divider type="vertical" />
    </template>

    <template v-if="record.status != 'FINISHED'">
      <a
        href="javascript:;"
        @click="handledelete(record, index)"
      >删除</a>
    </template>
  </div>
</template>

<script>
export default {
  components:{},
  props:{
    record: {
      type: Object,
      default: function() {
        return {};
      },
    },
    index: {
      type: Number,
      default: function() {
        return 0;
      },
    }
  },
  data(){
    return {
    }
  },
  created(){},
  mounted(){},
  computed:{},
  methods: {
    handleremind(record, index) {
      this.$emit('handleremind', record, index);
    },
    handledelete(record, index) {
      this.$emit('handledelete', record, index);
    },
    handletoview(record, index) {
      this.$emit('handletoview', record, index);
    },
    handlepreviewreport(record, index) {
      this.$emit('handlepreviewreport', record, index);
    },
    handleconfirmresult(record, index) {
      this.$emit('handleconfirmresult', record, index);
    },
    handlemodify(record, index) {
      this.$emit('handlemodify', record, index);
    },
    handlepublish(record, index) {
      this.$emit('handlepublish', record, index);
    },
    handledownreport(record, index) {
      this.$emit('handledownreport', record, index);
    },
    handleyourself(record, index) {
      this.$emit('handleyourself', record, index);
    }
  },
  watch:{},
}
</script>
<style scoped>

</style>