import moment from 'moment';
import { parseTime } from "@/utils";
import {friendlyTime} from '@/helper/datatime.js';
import { randColor } from '../utils';

const filters = [
  {
    name: "defalut",
    handler (val, defalutVal = "") {
      if (!val && val !== 0) {
        return defalutVal;
      }
      return val;
    }
  },
  {
    name: "trim",
    handler(value) {
      return value.replace(/(^\s*)|(\s*$)/g, "");
    },
  },
  {
    name: "datetime",
    handler(value) {
      return parseTime(value);
    },
  },

  {
    name: "datefmt",
    handler(timestamp, fmtstring) {// 当input为时间戳，需转为Number类型
      if (timestamp) {
        return moment(timestamp).format(fmtstring);
      } else {
        return "";
      }
    }
  },
  {
    name: "friendlyTime",
    handler(datestr, localdate = null) {
      return friendlyTime(datestr, localdate);
    }
  },
  {
    name: "projectMemberRole",
    handler(role) {
      const memberRoleMap = {
        0: 'project.member.role_label.owner',
        1: 'project.member.role_label.admin',
        2: 'project.member.role_label.user',
      };
      return memberRoleMap[role] || '';
    }
  },
  {
    name: "randColor",
    handler(i) {
      return randColor(i);
    }
  },
  {
    name: "ellipsis",
    handler(value, maxLen = 10) {
      if (!value) {
        return ''
      }
      if (value.length > maxLen) {
        return value.slice(0, maxLen) + '...'
      }
      return value
    }
  }
]

export default {
  install(Vue) {
    Vue.formatter = {};
    filters.map((item) => {
      Vue.filter(item.name, item.handler);
      Vue.formatter[item.name] = item.handler;
    });
    Object.defineProperty(Vue.prototype, "$formatter", {
      get() {
        return Vue.formatter
      }
    });
  },
};
