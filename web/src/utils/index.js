import { mainColors } from "./colors";
/**
 * Parse the time to string
 * @param {(Object|string|number)} time
 * @param {string} cFormat
 * @returns {string | null}
 */
export function parseTime(time, cFormat) {
  if (arguments.length === 0) {
    return null
  }
  const format = cFormat || "{y}-{m}-{d} {h}:{i}:{s}"
  let date
  if (typeof time === "object") {
    date = time
  } else {
    if ((typeof time === "string") && (/^[0-9]+$/.test(time))) {
      time = parseInt(time)
    }
    if ((typeof time === "number") && (time.toString().length === 10)) {
      time = time * 1000
    }
    date = new Date(time)
  }
  const formatObj = {
    y: date.getFullYear(),
    m: date.getMonth() + 1,
    d: date.getDate(),
    h: date.getHours(),
    i: date.getMinutes(),
    s: date.getSeconds(),
    a: date.getDay()
  }
  const time_str = format.replace(/{([ymdhisa])+}/g, (result, key) => {
    const value = formatObj[key]
    // Note: getDay() returns 0 on Sunday
    if (key === "a") { return ["日", "一", "二", "三", "四", "五", "六"][value] }
    return value.toString().padStart(2, "0")
  })
  return time_str
}

export function capitalize(str) {
  return str && str[0].toUpperCase() + str.slice(1);
}

export function getRequestName(defaultName, isMyRoute) {

  if(isMyRoute) {
    return "my" + capitalize(defaultName);
  }

  return defaultName;
}

export const firstWord = (s) => {
  return s.slice(0, 1);
}

export const copyToPlaster = (content) => {
  let transfer = document.createElement('input');
    document.body.appendChild(transfer);
    transfer.value = content;  // 这里表示想要复制的内容
    transfer.focus();
    transfer.select();
    if (document.execCommand('copy')) {
        document.execCommand('copy');
    }
    transfer.blur();
    document.body.removeChild(transfer);
};

export const isEmail = (email) => {
  if (email.match(/^\w+@\w+\.\w+$/i)) {
    return true;
  }
  return false;
}

export const isMobile = (mobile) => {
  if (mobile.match(/^1\d{10}$/)) {
    return true;
  }
  return false;
}

export const priorities = [
  {
    level: 0,
    name: '紧急',
  },
  {
    level: 1,
    name: '高',
  },
  {
    level: 2,
    name: '普通',
  },
  {
    level: 3,
    name: '低',
  }
];

export const prioritiesColor = {
  0: 'rgb(235, 91, 70)',
  1: 'rgb(255, 158, 25)',
  2: 'rgb(96, 189, 78)',
  3: 'rgb(178, 186, 197)',
};

export const getPriorityName = (level) => {
  for (let index in priorities) {
    let priority = priorities[index];
    if (priority.level == level) {
      return priority.name;
    }
  }
  return '';
};

export const randColor = (i) => {
  const j = i % mainColors.length;
  return mainColors[j];
}

export const removeArrItem = (array, cb) => {
  let len = array.length;
  for( var i = 0; i < len; i++) {
    if(typeof cb == "function" && typeof array[i] != "undefined") {
      if (cb(array[i])) {
        array.splice(i, 1);
      }
    }
  }
  return array;
}

export const mutilArrHasItem = (array, field, val) => {
  let len = array.length;
  for( var i = 0; i < len; i++) {
    const item = array[i];
    if (item[field] == val) {
      return true;
    }
  }
  return false;
}

export const timeToDate = (time) => {
  const date = new Date(parseInt(time)*1000);
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();

  return year + '-' + month.toString() + '-' + day.toString();
}

export const isMobileDevice = () => {
  if (navigator.userAgent.match(/ipad/i)) {
    return false;
  }
  return navigator.userAgent.match(/(phone|pod|iPhone|iPod|ios|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i);
}

export const isImage = (ext) => {
  return ['jpg', 'jpeg', 'gif', 'png', 'webp'].indexOf(ext) != '-1';
}

export const isPdf = (ext) => {
  return ext == 'pdf';
}

export const hexToRgb = (hexStr, opacity = 1) => {
  const hex = hexStr.replace('#', '');
  const bigint = parseInt(hex, 16);
  var r = (bigint >> 16) & 255;
  var g = (bigint >> 8) & 255;
  var b = bigint & 255;

  return "rgb(" + r + "," + g + "," + b +"," + opacity +")";
}

export const isFloat = (n) => {
  return Number(n) === n && n % 1 !== 0;
}

export const isInt = (n) => {
  return Number(n) === n && n % 1 === 0;
}
