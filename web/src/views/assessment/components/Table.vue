<template>
  <div>
    <a-table
      :columns="columns"
      :data-source="tableData"
      :pagination="pagination"
      row-key="id"
      @change="handleablechange"
    >
      <span
        slot="status"
        slot-scope="text,record,index"
        v-if="has_status"
      >
        {{ statusFormatter(text) }}
        <div>
          <a
            v-if="record.status == 'ASSING'"
            href="javascript:;"
            @click="handlepreviewreport(record, index)"
          >{{ record.finishedFeedbackNum }}/{{ record.totalFeedbackNum }}</a>
          <span v-else>{{ record.finishedFeedbackNum }}/{{ record.totalFeedbackNum }}</span>
        </div>
      </span>

      <span
        slot="createdTime"
        slot-scope="text"
        v-if="has_createdTime"
      >
        {{ text|datetime }}
      </span>

      <span
        slot="questionnaireInfo"
        slot-scope="text"
      >
        {{ text.name }}
      </span>
      
      <span
        slot="superior_feedbackUsers"
        slot-scope="text"
      >
        <slot
          name="superior_feedbackUsers"
          :text="text"
        />
      </span>  

      <span
        slot="action"
        slot-scope="text, record, index"
      >
        <Action 
          :record="record" 
          :index="index"
          @handleremind="handleremind"
          @handledelete="handledelete"
          @handletoview="handletoview"
          @handlepreviewreport="handlepreviewreport"
          @handleconfirmresult="handleconfirmresult"
          @handlemodify="handlemodify"
          @handlepublish="handlepublish"
          @handledownreport="handledownreport"
          @handleyourself="handleyourself"
        />
      </span>
    </a-table>

    <a-modal
      title="发送给本人"
      :visible="resultVisible"
      @ok="handleResultOk"
      :confirm-loading="resultConfirmLoading"
      @cancel="() => resultVisible = false"
      ok-text="确认"
      cancel-text="取消"
    >
      <p>{{ resultModalText }}</p>
    </a-modal>
  </div>
</template>
<script>
import Action from "./Action";
import mixins from "@/mixins/assessment-action";

export default {
  data() {
    return {
    };
  },
  props:{
    has_status: {
      type: Boolean,
      default: function() {
        return true;
      },
    },
    has_createdTime: {
      type: Boolean,
      default: function() {
        return true;
      },
    },
    pagination: {
      type: Object,
      default: function() {
        return {};
      },
    },
    tableData: {
      type: Array,
      default: function() {
        return [];
      },
    },
    columns: {
      type: Array,
      default: function() {
        return [];
      },
    }
  },
  components: {
    Action,
  },
  mixins: [mixins],
  created() {},
  mounted() {},
  computed: {},
  methods: {
    handlepreviewreport(record, index) {
      this.$emit('handlepreviewreport', record, index);
    },
    handleablechange(pagination) {
      this.$emit('handleablechange', pagination);
    },
    statusFormatter(status) {
      const map = {
        ["PRE_READY"]: "准备中",
        ["ASSING"]: "评估中",
        ["FINISHED"]: "评估结束"
      };

      return map[status];
    }
  },
  watch: {}
};
</script>

<style scoped>
.create-assessment-btn {
  margin-bottom: 20px;
  text-align: right;
}
</style>