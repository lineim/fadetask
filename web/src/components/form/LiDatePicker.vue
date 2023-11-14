<template>
  <a-date-picker
    :allow-clear="true"
    :show-today="false"
    :style="styles"
    format="YYYY-MM-DD"
    v-model="date"
    @change="dateChange"
  >
    <slot name="title">
      {{ title }}
    </slot>
    <div
      v-if="date"
      class="clearfix"
      slot="renderExtraFooter"
    >
      <a-button
        class="pull-right"
        @click="clean"
        type="link"
      >
        移除
      </a-button>
    </div>
  </a-date-picker>
</template>
<script>
import moment from 'moment-timezone';
import {timeToDate} from "@/utils/index";

moment.tz.setDefault("Asia/Shanghai");

export default {
    components: {
    },
    props: {
      id: {
        type: Number,
        default: 0,
        required: false
      },
      title: {
        type: String,
        default: "日期选择",
        require: false
      },
      initDate: {
        type: Number,
        default: 0,
        required: false
      },
      styles: {
        type: Object,
        default: () => {},
        required: false
      }
    },

    mounted() {
        if (this.initDate) {
            this.date = moment(timeToDate(this.initDate));
        }
    },

    data() {
        return {
            date: null,
        };
    },

    methods: {
        dateChange: function(dateMoment, dateString) {
            this.$emit('change', this.id, dateString);
            console.log(dateMoment);
        },

        clean: function() {
            this.date = null;
            this.$emit('clean', this.id);
        }
    }
}
</script>
