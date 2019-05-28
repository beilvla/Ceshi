var ua = navigator.userAgent.toLowerCase();


/**跟踪来源**/
var channel_id = res.getQueryString("ch");
var log_id = '';
var device_type = get_device_type();
var canPost = true;
var PAGE = {
    android_url: '',
    ios_url: '',
    ios_app_url: 'https://itunes.apple.com/us/app/id1395981163?ls=1&mt=8',
    tongji_url: ''
};
//浏览次数加1
$.ajax({
    type: 'POST',
    url: 'ctl.php?act=scan',
    data: {"channel_id": channel_id, "device_type": device_type},
    dataType: 'json',
    success: function (res) {
        //console.log(res);
        if (res.status == 0) {
            log_id = res.data.log_id;
            PAGE.android_url = res.data.android;
            PAGE.tongji_url = res.data.tongji;
        }
    }
});
//下载次数加1
$('.download').on('click', function () {
    var target = $(this);
    if (log_id != '' && parseInt(log_id) > 0) {
        if (canPost) {
            canPost = false;
            $.ajax({
                type: 'POST',
                url: 'ctl.php?act=click',
                data: {"log_id": log_id},
                dataType: 'json',
                success: function (res) {
                    canPost = true;
                    process_url();
                },
                error:function () {
                    canPost = true;
                }
            });
        }
    } else {
        process_url();
    }
});

//处理连接
function process_url() {
    var url = '';
    //alert(device_type);
    if (PAGE.tongji_url != '') {
        url = PAGE.tongji_url;
    } else if (device_type == 'ios') {
        if (ua.match(/MicroMessenger/i)) {
            url = PAGE.ios_url;
        } else {
            url = PAGE.ios_app_url;
        }
    } else if (device_type == 'android') {
        alert("工程狮们正在加紧开发安卓版本，敬请期待~");
        return;
        // url = PAGE.android_url;
    } else {
        /*url = '';
        var src = 'http://shop.xymens.com/Assets/qrcode/?url=http://api.xymens.com/Assets/download/?ch=' + channel_id;
        $('.tc img').attr('src', 'img/picture.png');
        $('.tc').show();*/
        alert('请使用移动设备下载!');
        return;
    }
    if (ua.match(/MicroMessenger/i)) {
        alert('请点击微信右上角按钮，选择在浏览器中打开！');
        return;
    }
    //alert(url);
    if (!!url) {
        window.location.href = url;
    }
}

//获取设备
function get_device_type() {
    var device = 'web';
    if ($.os.ios) {
        device = 'ios';
    }
    else if ($.os.android) {
        device = 'android';
    }
    else if ($.os.webos) {
        device = 'webos';
    }
    else if ($.os.blackberry) {
        device = 'blackberry';
    }
    else if ($.os.bb10) {
        device = 'bb10';
    }
    else if ($.os.rimtabletos) {
        device = 'rimtabletos';
    }
    return device;
}
