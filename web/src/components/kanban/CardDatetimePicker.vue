<template>
  <a-date-picker 
    format="YYYY-MM-DD"
    v-model="date" 
    time="time"
    placeholder="选择时间" 
    :allow-clear="true"
    :show-today="false"
    :style="{width: '100%'}"
    :open="show"
    @openChange="visiableChange"
    @change="dateChange"
  >
    <div
      class="pvm"
      slot="renderExtraFooter"
    >
      <div>
        时间
      </div>

      <a-select
        :default-value="currentTime"
        style="width: 100%"
        @change="timeChange"
        class="mbm"
      >
        <a-select-option
          class="datetime-picker-notify-interval"
          v-for="(item, index) in canSelectTimes"
          :key="index"
          :value="item"
        >
          {{ item }}
        </a-select-option>
      </a-select>

      <div>
        到期提醒设置
      </div>
      <a-select
        :default-value="notifyIntervalDefault"
        style="width: 100%"
        @change="notifyIntervalChange"
        :disabled="notifyIntervalDisabled"
        class="mbm"
      >
        <a-select-option
          class="datetime-picker-notify-interval"
          v-for="(item, index) in notifyInterval"
          :key="index"
          :value="index"
        >
          {{ item }}
        </a-select-option>
      </a-select>
      <div class="clearfix">
        <a-button
          class="pull-right"
          type="primary"
          :disabled="notifyIntervalDisabled"
          @click="save"
        >
          保存
        </a-button>
        <a-button
          class="pull-right mrm"
          @click="remove"
          type="danger"
        >
          移除
        </a-button>
        <a-button
          @click="close"
          class="pull-right mrm"
        >
          取消
        </a-button>
      </div>
    </div>
    <slot name="trigger">
      <a-button 
        class="datetime-picker"
        size="small" 
        icon="calendar" 
        block
      >
        {{ text }}
        <span
          v-if="notifyIntervalVal > 0 && date"
          class="mlxs"
        >
          <a-icon
            theme="filled" 
            type="bell" 
            :style="{color: 'rgb(255, 136, 0)'}"
          />
        </span>
      </a-button>
    </slot>
  </a-date-picker>
</template>

<script>
  import moment from 'moment-timezone';
  // import FTGhostBtnVue from '../common/FTGhostBtn.vue';
  moment.tz.setDefault("Asia/Shanghai");

  const canSelectTimes = [
    '00:00', '00:05', '00:10', '00:15', '00:20', '00:25', '00:30', '00:35', '00:40', '00:45', '00:50', '00:55',
    '01:00', '01:05', '01:10', '01:15', '01:20', '01:25', '01:30', '01:35', '01:40', '01:45', '01:50', '01:55',
    '02:00', '02:05', '02:10', '02:15', '02:20', '02:25', '02:30', '02:35', '02:40', '02:45', '02:50', '02:55',
    '03:00', '03:05', '03:10', '03:15', '03:20', '03:25', '03:30', '03:35', '03:40', '03:45', '03:50', '03:55',
    '04:00', '04:05', '04:10', '04:15', '04:20', '04:25', '04:30', '04:35', '04:40', '04:45', '04:50', '04:55',
    '05:00', '05:05', '05:10', '05:15', '05:20', '05:25', '05:30', '05:35', '05:40', '05:45', '05:50', '05:55',
    '06:00', '06:05', '06:10', '06:15', '06:20', '06:25', '06:30', '06:35', '06:40', '06:45', '06:50', '06:55',
    '07:00', '07:05', '07:10', '07:15', '07:20', '07:25', '07:30', '07:35', '07:40', '07:45', '07:50', '07:55',
    '08:00', '08:05', '08:10', '08:15', '08:20', '08:25', '08:30', '08:35', '08:40', '08:45', '08:50', '08:55',
    '09:00', '09:05', '09:10', '09:15', '09:20', '09:25', '09:30', '09:35', '09:40', '09:45', '09:50', '09:55',
    '10:00', '10:05', '10:10', '10:15', '10:20', '10:25', '10:30', '10:35', '10:40', '10:45', '10:50', '10:55',
    '11:00', '11:05', '11:10', '11:15', '11:20', '11:25', '11:30', '11:35', '11:40', '11:45', '11:50', '11:55',
    '12:00', '12:05', '12:10', '12:15', '12:20', '12:25', '12:30', '12:35', '12:40', '12:45', '12:50', '12:55',
    '13:00', '13:05', '13:10', '13:15', '13:20', '13:25', '13:30', '13:35', '13:40', '13:45', '13:50', '13:55',
    '14:00', '14:05', '14:10', '14:15', '14:20', '14:25', '14:30', '14:35', '14:40', '14:45', '14:50', '14:55',
    '15:00', '15:05', '15:10', '15:15', '15:20', '15:25', '15:30', '15:35', '15:40', '15:45', '15:50', '15:55',
    '16:00', '16:05', '16:10', '16:15', '16:20', '16:25', '16:30', '16:35', '16:40', '16:45', '16:50', '16:55',
    '17:00', '17:05', '17:10', '17:15', '17:20', '17:25', '17:30', '17:35', '17:40', '17:45', '17:50', '17:55',
    '18:00', '18:05', '18:10', '18:15', '18:20', '18:25', '18:30', '18:35', '18:40', '18:45', '18:50', '18:55',
    '19:00', '19:05', '19:10', '19:15', '19:20', '19:25', '19:30', '19:35', '19:40', '19:45', '19:50', '19:55',
    '20:00', '20:05', '20:10', '20:15', '20:20', '20:25', '20:30', '20:35', '20:40', '20:45', '20:50', '20:55',
    '21:00', '21:05', '21:10', '21:15', '21:20', '21:25', '21:30', '21:35', '21:40', '21:45', '21:50', '21:55',
    '22:00', '22:05', '22:10', '22:15', '22:20', '22:25', '22:30', '22:35', '22:40', '22:45', '22:50', '22:55',
    '23:00', '23:05', '23:10', '23:15', '23:20', '23:25', '23:30', '23:35', '23:40', '23:45', '23:50', '23:55',
  ];

  export default {
    components: {
      // FTGhostBtnVue
    },
    props: {
      endDate: {
        type: String,
        default: '',
        required: false
      },
      text: {
        type: String,
        default: '截止日期',
        required: false
      },
      notifyInterval: {
        type: Object,
        default: () => new Object(),
        required: true
      },
      notifyIntervalDefault: {
        type: String,
        default: "0",
        required: false
      },
      styles: {
        type: Object,
        default: () => new Object(),
        required: false,
      }
    },

    data() {
      return {
        show: false,
        date: null,
        currentTime: '00:00',
        canSelectTimes: canSelectTimes,
        interval: null,
        notifyIntervalVal: 0, // 默认不通知
        notifyIntervalDisabled: true
      }
    },

    created() {
      this.$once('hook:beforeDestory',() => {
        this.rmClickoutSideListener();
      });
    },

    mounted() {
      this.initDatetime();
    },

    computed: {
      btnShape: function() {
        return !this.text ? 'circle' : '';
      }
    },

    watch: {
      date: {
        handler() {
          this.notifyIntervalDisabled = !this.date || (this.time.H === "" || this.time.m === "");
        },
        deep: true
      },

      show: {
        handler(newVal) {
          if (newVal) { // 展示时，注册外围点击事件
            this.addClickoutSideListener(); 
          } else {
            this.rmClickoutSideListener();
          }
        },
        deep: true
      }
    },

    methods: {

      initDatetime: function() {
        this.notifyIntervalVal = this.notifyIntervalDefault;
        if (this.endDate) {
          let datetime  = moment(this.endDate);
          this.date = datetime;

          let hour = datetime.hour();
          let min  = datetime.minute();

          min = min < 10 ? '0'+String(min) : String(min);
          hour = hour < 10 ? '0'+String(hour) : String(hour);
          this.currentTime = hour + ':' + min;

          this.time = {
            'H': String(hour),
            'm': String(min),
          };
        } else {
          let current = moment();
          this.date = current;
          let hour = current.hour();
          let min  = current.minute();

          let geweishu = min%10;
          let realGeweiMin = 0;
          let realShiweiMin = min - geweishu;
          if (geweishu < 5) {
            realGeweiMin = 5;
          }
          if (geweishu >= 5) { // 大于等于5分钟，十位进1
            realGeweiMin = 0;
            realShiweiMin += 10;
          }
          
          min = realShiweiMin+realGeweiMin;
          if (min >= 60) {
            hour += 1;
            min  = 0;
          }
          min = min < 10 ? '0'+String(min) : String(min);
          hour = hour < 10 ? '0'+String(hour) : String(hour);
          this.currentTime = hour + ':' + min;

          this.time = {
            'H': String(hour),
            'm': String(min),
          };
        }
      },

      visiableChange: function(status) {
        if (status) {
          this.show = true;
        }
      },

      addClickoutSideListener: function() {
        document.body.addEventListener("click", this.onClickOutside);
      },

      rmClickoutSideListener: function() {
        document.body.removeEventListener("click", this.onClickOutside);
      },

      onClickOutside: function(event) {
        let datepickerEl = document.getElementsByClassName('ant-calendar-picker-container')[0];
        // Out side clicked
        if (datepickerEl !== event.target && // 不是点击日期选择框本身
          event.target.className.indexOf('datetime-picker') === -1 && // 点击的不是触发按钮
          event.target.className.indexOf('time-picker-overlay') === -1 && // vue-timepicker选择项
          event.target.className.indexOf('datetime-picker-notify-interval') === -1 && // 到期提醒选择项
          !datepickerEl.contains(event.target) // 点击按钮不在日期选择框元素中
        ) {
          // 关闭日期选择框
          this.show = false;
        }
      },

      dateChange: function(date) {
        this.date = date;
      },

      timeChange: function(time) {
        this.currentTime = time;
      },

      notifyIntervalChange: function(val) {
        this.notifyIntervalVal = val;
      },

      save: function() {
        const year = this.date.year();
        const month = this.date.month() + 1; // month 返回0到11之间的数字
        const day = this.date.date();

        let dueDate = String(year) + '-' + String(month) + '-' + String(day) + ' ' + this.currentTime + ':00';
        let params = {
          date: dueDate,
          notify_interval: this.notifyIntervalVal,
        };
        this.$emit('save', params);
        this.close();
      },

      remove: function() {
        this.date = null;
        this.currentTime = '0';
        this.$emit('remove');
        // this.close();
      },

      close: function() {
        this.show = false;
        this.$emit('close');
      },

    }

  }
</script>