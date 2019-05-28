// Avoid `console` errors in browsers that lack a console.
$(function () {
  var method;
  var noop = function () {
  };
  var methods = [
    'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
    'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
    'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
    'timeline', 'timelineEnd', 'timeStamp', 'trace', 'warn'
  ];
  var length = methods.length;
  var console = (window.console = window.console || {});

  while (length--) {
    method = methods[length];

    // Only stub undefined methods.
    if (!console[method]) {
      console[method] = noop;
    }
  }
  var res = {
    init: function () {
      var _this = this;
      this.autoResize();
      $(window).resize(_this.autoResize); //缩放屏幕时调整大小 ;
    },

    autoResize: function () {
      var zzpageWidth = 750; //制作宽
      var sjpageWidth = 750; //设计宽
      var clientWidth = document.body.clientWidth; //获取当前可见屏幕大小 ;
      if (!clientWidth) return; //如果没有width return ;
      this.dpi = sjpageWidth / clientWidth; //页面缩放比例
      $('html').css('font-size', 20 * (clientWidth / zzpageWidth) + 'px'); //原始html font-size 大小 * (当前屏幕宽度 / 设计稿宽度) ;
    },
    getQueryString : function(name) {
      var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
      var r = location.search.substr(1).match(reg);
      if (r != null) return unescape(decodeURI(r[2])); return null;
    }
  }

  res.init();
  window.res = res;
}());

// Place any jQuery/helper plugins in here.
