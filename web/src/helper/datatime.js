export var toDateTime = function (timestamp) {
    let time = parseInt(timestamp) * 1000;
    let dateTime = new Date(time),
        y = dateTime.getFullYear(),
        m = dateTime.getMonth() + 1,
        d = dateTime.getDate();

    return y + "-" + (m < 10 ? "0" + m : m) + "-" + (d < 10 ? "0" + d : d) + " " + dateTime.toTimeString().substr(0, 8);
};

export var friendlyTime = function (dateTime) {
    var currentTime = new Date();
    var arr = dateTime.split(/\s+/gi);
    var arr1, arr2, oldTime, delta;
    var getIntValue = function(ss, defaultValue){
        try {
            return parseInt(ss, 10);
        } catch (e) {
            return defaultValue;
        }
    };
    var getWidthString = function(num){
        return num < 10 ? ("0" + num) : num;
    };
    if (arr.length >= 2) {
        // eslint-disable-next-line no-useless-escape
        arr1 = arr[0].split(/[\/\-]/gi);
        arr2 = arr[1].split(":");
        oldTime = new Date();
        oldTime.setYear(getIntValue(arr1[0], currentTime.getFullYear()));
        oldTime.setMonth(getIntValue(arr1[1], currentTime.getMonth() + 1) - 1);
        oldTime.setDate(getIntValue(arr1[2], currentTime.getDate()));

        oldTime.setHours(getIntValue(arr2[0], currentTime.getHours()));
        oldTime.setMinutes(getIntValue(arr2[1], currentTime.getMinutes()));
        oldTime.setSeconds(getIntValue(arr2[2], currentTime.getSeconds()));

        let prefix = '';
        if (currentTime.getTime() > oldTime.getTime()) {
            delta = currentTime.getTime() - oldTime.getTime();
            prefix = '前';
        } else {
            delta = oldTime.getTime() - currentTime.getTime();
            prefix = '后';
        }

        if (delta <= 6000){
            return "1分钟内";
        }
        else if (delta < 60 * 60 * 1000){
            return Math.floor(delta / (60 * 1000)) + "分钟" + prefix;
        }
        else if(delta < 24 * 60 * 60 * 1000){
            return Math.floor(delta / (60 * 60 * 1000)) + "小时" + prefix;
        }
        else  if (delta < 2 * 24 * 60 * 60 * 1000) {
            return currentTime.getTime() > oldTime.getTime() ? '昨天' : '明天';
        }
        else if (delta < 7 * 24 * 60 * 60 * 1000){
            return Math.floor(delta / (24 * 60 * 60 * 1000)) + "天" + prefix;
        }
        else if (currentTime.getFullYear() != oldTime.getFullYear()){
            return [getWidthString(oldTime.getFullYear()), getWidthString(oldTime.getMonth() + 1), getWidthString(oldTime.getDate())].join("-")
        }
        else {
            return [getWidthString(oldTime.getMonth() + 1), getWidthString(oldTime.getDate())].join("-");
        }
    }
    return '';
}
