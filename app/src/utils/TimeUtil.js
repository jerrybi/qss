function getFormattedTime(timestamp) {
  let curTime = Date.parse(new Date());
  let delta = (curTime - timestamp) / 1000;
  const hour = 60 * 60;
  const day = 24 * hour;
  const month = 30 * day;
  const year = 12 * month;
  if (delta < hour) {
    // 显示多少分钟前
    let n = parseInt(delta / 60);
    if (n == 0) {
      return "刚刚";
    }
    return n + "分钟前";
  } else if (delta >= hour && delta < day) {
    return parseInt(delta / hour) + "小时前";
  } else if (delta >= day && delta < month) {
    return parseInt(delta / day) + "天前";
  } else if (delta >= month && delta < year) {
    return parseInt(delta / month) + "个月前";
  }
}

function format(date, fmt) {
  var o = {
    "M+": date.getMonth() + 1, //月份
    "d+": date.getDate(), //日
    "h+": date.getHours(), //小时
    "m+": date.getMinutes(), //分
    "s+": date.getSeconds(), //秒
    "q+": Math.floor((date.getMonth() + 3) / 3), //季度
    S: date.getMilliseconds() //毫秒
  };
  if (/(y+)/.test(fmt)) {
    fmt = fmt.replace(
      RegExp.$1,
      (date.getFullYear() + "").substr(4 - RegExp.$1.length)
    );
  }
  for (var k in o) {
    if (new RegExp("(" + k + ")").test(fmt)) {
      fmt = fmt.replace(
        RegExp.$1,
        RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length)
      );
    }
  }
  return fmt;
}

function formatChatTime(timestamp) {
  return format(new Date(timestamp * 1000), "MM月dd日 hh:mm");
}

function formatChatTime2(timestamp) {
    return format(new Date(timestamp), 'MM月dd日 hh:mm:ss');
}

function currentFormatTime() {
  return format(new Date(), 'yyyy-MM-dd hh:mm:ss');
}

function currentFormatDate() {
  return format(new Date(), 'yyyy-MM-dd');
}

function currentTime() {
    return Date.parse(new Date()) / 1000;
}
function timestampToTime(timestamp) {
  var date = new Date(timestamp);
  Y = date.getFullYear() + "年";
  M =(date.getMonth() + 1 < 10? "0" + (date.getMonth() + 1): date.getMonth() + 1) + "月";
  D = date.getDate() + "日";
  h = date.getHours() + ":";
  m = date.getMinutes(); 
  s = date.getSeconds();
  return Y + M + D + h + s//时分秒可以根据自己的需求加上
}
function timestampToTimes(timestamp) {
  var date = new Date(timestamp);
  Y = date.getFullYear() + "年";
  M =(date.getMonth() + 1 < 10? "0" + (date.getMonth() + 1): date.getMonth() + 1) + "月";
  D = date.getDate() + "日";
  h = date.getHours() + ":";
  m = date.getMinutes();
  // s = date.getSeconds();
  return Y //时分秒可以根据自己的需求加上
}
function timeMonth(timestamp) {
  var date = new Date(timestamp);
  Y = date.getFullYear() + "年";
  M =(date.getMonth() + 1 < 10?  + (date.getMonth() + 1): date.getMonth() + 1) + "月";
  D = date.getDate() + "日";
  h = date.getHours() + ":";
  m = date.getMinutes();
  // s = date.getSeconds();
  return M //时分秒可以根据自己的需求加上
}
function timeDate(timestamp) {
  var date = new Date(timestamp);
  Y = date.getFullYear() + "年";
  M =(date.getMonth() + 1 < 10?  + (date.getMonth() + 1): date.getMonth() + 1) + "月";
  D = date.getDate();
  h = date.getHours() + ":";
  m = date.getMinutes();
  // s = date.getSeconds();
  return D //时分秒可以根据自己的需求加上
}
module.exports = {
    getFormattedTime: getFormattedTime,
    formatChatTime: formatChatTime,
    currentTime: currentTime,
    formatChatTime2: formatChatTime2,
    timestampToTime:timestampToTime,
  	timestampToTimes:timestampToTimes,
  	timeMonth:timeMonth,
  	timeDate:timeDate,
    currentFormatTime:currentFormatTime,
    currentFormatDate:currentFormatDate
}
