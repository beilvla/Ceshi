<?php
header("Content-type: text/html; charset=utf-8");
require_once '../common/config.php';
//加载封装的方法
require 'public_func.php';
$uid = isset($_GET['uid'])?intval($_GET['uid']):'';
$oid = isset($_GET['oid'])?intval($_GET['oid']):'';
$token = isset($_GET['token'])?$_GET['token']:'';
if(empty($oid) || empty($uid)){
    echo "oid error or uid error!";
    exit;
}
if(empty($token) || (MD5("SODA2018!@#".$oid) != $token)){
    echo "Hacker attack!";
    exit;
}
//获取帖子详情
$detail_sql = "SELECT s.id as object_id, s.title, s.user_id, s.like_count,s.votepeoples,s.votetime,s.comment_count,s.option_list, s.object_img, s.position,s.latitude,s.longitude,
            s.comment_count, s.create_time, s.vedio_url, s.more, s.object_img, s.label_ids, s.content, s.topic_type,
            IFNULL(u.alias,u.user_name) AS user_name, u.user_head,c.circle_title FROM ecs_subject_topic as s
            LEFT JOIN ecs_circle as c ON s.circle_id = c.circle_id
            LEFT JOIN ecs_users as u ON s.user_id = u.user_id
            WHERE s.id='$oid'";
//var_dump($detail_sql);die;

if(CON_ENVIRONMENT == 'online'){
    $detail = $DB->getRow($detail_sql);
} else {
    $detail = $DB->get_row($detail_sql);
}

if (empty($detail)){
    echo '没有该帖子!';
    exit;
}
$detail['release_time'] = formatTime($detail['create_time']);
$labelids = !empty($detail['label_ids'])?explode(',',$detail['label_ids']):array();
//标签高亮
foreach($labelids as $vo){
    $detail['content'] = str_replace($vo,"<span>$vo</span>",$list[$k]['content']);
}
//图片
$detail['imgs'] = array();
if($detail['vedio_url']){
    $detail['imgs'] = array($detail['object_img']);
} else {
    $imgarr = array();
    $imgs = (array)(json_decode($detail['more'],true));
    foreach($imgs as $k=>$v){
        $imgarr[] = $v;
    }
    $detail['imgs'] = $imgarr;
    $detail['imgcount'] = count($detail['imgs']);
}
//类型为5的
if($detail['topic_type'] == 5){
    //处理投票结果
    $option_list = json_decode($detail['option_list'],true);
    //获取每项投票人数
    $people_list = array();
    $people_sql = "SELECT answer, count(user_id) as num FROM ecs_subject_vote WHERE object_id='".$detail['object_id']."' GROUP BY answer";
    if(CON_ENVIRONMENT == 'online'){
        $people_data = $DB->getAll($people_sql);
    } else {
        $people_data = $DB->get_all($people_sql);
    }
    if(!empty($people_data)){
        foreach($people_data as $k=>$v){
            $people_list[$v['answer']] =  $v['num'];
        }
    }
    if(!empty($option_list)) {
        foreach ($option_list as $kkk => $vvv) {
            $option_list[$kkk]['peoples'] = isset($people_list[$vvv['val']]) ? $people_list[$vvv['val']] : 0;
        }
    }
    $detail['votes'] = !empty($option_list)?$option_list:array();
}
//投票剩余时间
if($detail['topic_type']==5 && !empty($detail['votetime'])){
    $day1 = strtotime($detail['votetime']);
    $day2 = time();

    $day = diffBetweenTwoDays($day1,$day2);
    $detail['vate_time'] = $day>0?$detail['votepeoples']."人参与·".ceil($day)."天剩余":"已结束";
} else {
    $detail['vate_time'] = "已结束";
}

//类型为6转为数组
$detail['content'] = ($detail['topic_type']==6)?json_decode($detail['content'],true):$detail['content'];
//获取分享人信息
$fusers_sql = "SELECT IFNULL(u.alias,u.user_name) AS user_name, u.user_head FROM ecs_users as u WHERE u.user_id='$uid'";
if(CON_ENVIRONMENT == 'online'){
    $fusers = $DB->getRow($fusers_sql);
} else {
    $fusers = $DB->get_row($fusers_sql);
}

//获取点赞
//$qsql = "SELECT u.user_id,u.user_head,IFNULL(u.alias,u.user_name) AS user_name
//          FROM ecs_thumbs t
//          LEFT JOIN ecs_users u on t.user_id = u.user_id
//          WHERE `object_id` = '".$detail['object_id']."' AND `status` = 1
//          ORDER BY utime desc
//          LIMIT 3";
//if(CON_ENVIRONMENT == 'online'){
//    $praisenumbers = $DB->getAll($qsql);
//} else {
//    $praisenumbers = $DB->get_all($qsql);
//}

//获取评论
$csql = "SELECT c.comment_id,c.content,u.user_id,IFNULL(u.alias,u.user_name) AS user_name,u.user_head
            ,c.ctime,c.comment_count,c.like_count
        FROM ecs_topic_comment c 
        LEFT JOIN ecs_users u on c.user_id = u.user_id 
        WHERE  c.is_delete = 0 and c.is_show=0 AND c.object_id = '$oid' 
        ORDER BY c.ctime desc 
        LIMIT 2";
if(CON_ENVIRONMENT == 'online'){
    $comments = $DB->getAll($csql);
} else {
    $comments = $DB->get_all($csql);
}

if(!empty($comments)){
    foreach($comments as $k=>$v){
        $comments[$k]['release_time'] = formatTime($v['ctime']);
        $comment_id = $v['comment_id'];
        $reply_sql = "SELECT tr.comment_id,tr.content,tr.reply_id,tr.like_count,tr.ctime,tr.level,
                      u.user_id,IFNULL(u.alias,u.user_name) AS user_name,u.user_head,u.user_id as to_user_id,
                      IFNULL(u2.alias,u2.user_name) AS to_user_name,u2.user_head as to_user_head, tr.ctime
                      FROM ecs_topic_reply tr 
                      LEFT JOIN ecs_users u on tr.user_id = u.user_id 
                      LEFT JOIN ecs_users u2 on tr.to_user_id = u2.user_id 
                      WHERE tr.comment_id = '$comment_id' ORDER BY tr.ctime desc LIMIT 2";
        if(CON_ENVIRONMENT == 'online'){
            $replys = $DB->getAll($reply_sql);
        } else {
            $replys = $DB->get_all($reply_sql);
        }
        if(!empty($replys)){
            foreach($replys as $key=>$vo){
                $replys[$key]['release_time'] = formatTime($vo['ctime']);
            }
        }
        $comments[$k]['replys'] = $replys;
    }
}

//获取热门帖子
$related_sql = "SELECT s.id as object_id,s.topic_type,s.link_url,s.link_title,
    s.link_host,s.link_content,s.link_icon,s.link_small_icon,s.title,s.object_img_width,
    s.object_img_height,s.vedio_url,s.like_count,s.object_img,s.comment_count,s.create_time,
    u.user_id,IFNULL(u.alias,u.user_name) AS user_name,u.user_head FROM ecs_subject_topic s 
    LEFT JOIN ecs_users u on s.user_id = u.user_id 
    WHERE s.status = 1 AND s.is_delete = 1 
    AND s.recommended = 1 AND s.topic_type 
    NOT IN ('3','5') ORDER BY s.update_time desc 
    LIMIT 4";
if(CON_ENVIRONMENT == 'online'){
    $related_list = $DB->getAll($related_sql);
} else {
    $related_list = $DB->get_all($related_sql);
}

$DB->closeConnection();


?>
<!doctype html>
<class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>HEAT潮流</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/index.css">

</head>
<!-- 笔记 topic_type=6 -->
<!-- 个人信息 -->
<div style="display:<?php echo in_array($detail['topic_type'],array(6))?'block':'none'; ?>;">
    <div class='user'>
        <img class='user-icon' src='<?php echo $detail['user_head']; ?>' alt="用户头像"></image>
        <div class='user-name'><?php echo $detail['user_name']; ?></div>
        <div class='date'><?php echo $detail['release_time']; ?></div>
        <div style='flex:1;'></div>
        <image class='more' alt="更多" src='./img/more.png'></image>
    </div>
    <div class="cover" style="background-image:url('<?php echo $detail['object_img']; ?>');"></div>
    <div class="note-title"><?php echo $detail['title']; ?></div>
    <?php foreach($detail['content'] as $k=>$v){ ?>
        <?php if($v['type'] == 'title'){?>
            <!-- 小标题 -->
            <div class="note-title2"><?php echo $v['content']; ?></div>
        <?php }elseif($v['type'] == 'text'){?>
            <!-- 文本 -->
            <div class="note-text"><?php echo $v['content']; ?></div>
        <?php }elseif($v['type'] == 'img'){?>
            <!-- 图片 -->
            <img class="note-image" src="<?php echo $v['content']; ?>" />
        <?php }?>
    <?php } ?>
</div>
<!-- 其它详情 -->
<!-- 个人信息 -->
<?php if(in_array($detail['topic_type'],array(1,2,4,5))){ ?>
<div class='user'>
    <img class='user-icon' src='<?php echo $detail['user_head']; ?>' alt="用户头像"></image>
    <div class='user-name'><?php echo $detail['user_name']; ?></div>
    <div class='date'><?php echo $detail['release_time']; ?></div>
    <div style='flex:1;'></div>
    <image class='more' alt="更多" src='./img/more.png'></image>
</div>
<?php } ?>
<?php if(in_array($detail['topic_type'],array(1,2,3,4,5))){ ?>
    <div class="post-title"><?php echo $detail['title']; ?></div>
    <div class="post-content"><?php echo $detail['content']; ?></div>
<?php } ?>
<!-- 图片。问题 投票 topic_type=1、4、5 -->
<div class="post-images" style="display:<?php echo in_array($detail['topic_type'],array(1,4,5))?'flex':'none'; ?>">
    <!-- 一张图 -->
    <?php
    if($detail['imgcount'] == 1){
        foreach($detail['imgs'] as $k=>$v){
            ?>
            <div class="img-1" style="background-image:url('<?php echo $v;?>');"></div>
            <?php
        }
    }
    ?>
    <!-- 两张图 -->
    <!-- <div class="img-2" style="background-image:url('./test/photo1.jpg');"></div>
    <div class="img-2" style="background-image:url('./test/photo1.jpg');"></div> -->
    <?php
    if($detail['imgcount'] == 2){
        foreach($detail['imgs'] as $k=>$v){
            ?>
            <div class="img-2" style="background-image:url('<?php echo $v;?>');"></div>
            <?php
        }
    }
    ?>
    <!-- 三张图 -->
    <!-- <div class="img-3-left" style="background-image:url('./test/photo1.jpg');"></div>
    <div class="img-3-right">
      <div class="img-3-item" style="background-image:url('./test/photo1.jpg');"></div>
      <div class="img-3-item" style="background-image:url('./test/photo1.jpg');"></div>
    </div> -->
    <?php
    if($detail['imgcount']==3){
        ?>
        <div class="img-3-left" style="background-image:url('<?php echo $detail['imgs'][0];?>');"></div>
        <div class="img-3-right">
            <div class="img-3-item" style="background-image:url('<?php echo $detail['imgs'][1];?>');"></div>
            <div class="img-3-item" style="background-image:url('<?php echo $detail['imgs'][2];?>');"></div>
        </div>
        <?php
    }
    ?>
    <!-- 四张图 -->
    <?php
    if($detail['imgcount']>3){
        ?>
        <div class="img-4-left" style="background-image:url('<?php echo $detail['imgs'][0];?>');"></div>
        <div class="img-4-right">
            <div class="img-4-item" style="background-image:url('<?php echo $detail['imgs'][1];?>');"></div>
            <div class="img-4-bottom">
                <div class="img-4-item2" style="background-image:url('<?php echo $detail['imgs'][2];?>');"></div>
                <div class="img-4-item2" style="background-image:url('<?php echo $detail['imgs'][3];?>');"></div>
            </div>
        </div>
        <?php
    }
    ?>
    <div class="total">共<?php echo $detail['imgcount'];?>张</div>
</div>
<!-- 投票  topic_type=5 -->
<?php if($detail['topic_type']==5) { ?>
    <?php foreach($detail['votes'] as $k=>$v){?>
        <div class="vote-item download">
          <?php
			if(!empty($v['url'])){
		  ?>
          <div class="icon" style="background-image:url('<?php echo $v['url']; ?>');"></div>
          <?php } ?>
          <div class="vote">
            <!--  <div class="chose" style="width:<?php // echo round($v['peoples']/$detail['votepeoples']*100)."%";?>;"></div>-->
            <div class="chose" style="width:100%"></div>
            <div class="text"><?php echo $v['option']; ?></div>
            <!--  <div class="pre"><?php // echo round($v['peoples']/$detail['votepeoples']*100)."%";?></div>-->
          </div>
          <div class="radio" style="background-image:url('./img/radio.png');"></div>
        </div>
    <?php } ?>
<!--<div class="vote-item">-->
<!--  <div class="icon" style="background-image:url('./test/photo1.jpg');"></div>-->
<!--  <div class="vote">-->
<!--    <div class="chose" style="width:30%;"></div>-->
<!--    <div class="text">画风迥异的两个角色竟然是同一个演员</div>-->
<!--    <div class="pre">30%</div>-->
<!--  </div>-->
<!--  <div class="radio" style="background-image:url('./img/radio_check.png');"></div>-->
<!--</div>-->
<div class="vote-status"><?php echo $detail['vate_time']; ?></div>
<?php } ?>
<!-- 视频  topic_type=2 -->
<?php
if($detail['topic_type'] == 2){
    ?>
    <video controls class="video" poster='<?php echo $detail['object_img']; ?>'>
        <source src="<?php echo $detail['vedio_url']; ?>" type="video/mp4">
        您的浏览器不支持 video 标签。
    </video>
<?php } ?>
<!-- 状态栏 -->
<div class="post-status">
    <div class="zan download">
        <div class="image" style="background-image:url('./img/zan.png');"></div>
        <div class="nums"><?php echo $detail['like_count']; ?></div>
    </div>
    <div class="zan download" style="margin-left:2rem;">
        <div class="image" style="background-image:url('./img/comment.png');"></div>
        <div class="nums"><?php echo $detail['comment_count'];?></div>
    </div>
    <div class="share download" style="background-image:url('./img/share.png');"></div>
    <div style="flex:1;"></div>
    <div class="group-name download"><?php echo $detail['circle_title'];?></div>
</div>
<!-- 评论 -->
<!-- <div class="empty-comments">快来发表你的评论吧</div> -->
<!-- 评论条目 -->
<?php foreach($comments as $k=>$v){?>
<div class="comment">
        <div class="com-head" style="background-image:url('<?php echo $v['user_head'];?>');"></div>
        <div class="com-content">
            <div class="com-name"><?php echo $v['user_name'];?></div>
            <div class="com-text"><?php echo $v['content'];?></div>
            <div class="com-status">
                <div class="date"><?php echo $v['release_time'];?></div>
                <div class="reply download">回复</div>
                <div class="zan-view download">
                    <div class="image" style="background-image:url('./img/zan.png');"></div>
                    <div><?php echo $v['like_count'];?></div>
                </div>
            </div>
            <!-- 回复 -->
            <?php foreach($v['replys'] as $key=>$vo){?>
                <div class="replies">
                    <!-- 回复条目 -->
                    <div class="reply-item">
                        <div class="reply-user">
                            <div class="image" style="background-image:url('<?php echo $vo['user_head'];?>');"></div>
                            <div><?php echo $vo['user_name'];?></div>
                        </div>
                        <div class="reply-content"><?php echo $vo['content'];?></div>
                        <div class="com-status">
                            <div class="date"><?php echo $vo['release_time'];?></div>
                            <div class="zan-view download">
                                <div class="image" style="background-image:url('./img/zan.png');"></div>
                                <div><?php echo $vo['like_count'];?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

</div>
<?php } ?>

<!-- 热门帖子 -->
<div class="hot-post-title">热门帖子</div>
<div class="hot-post download">
    <?php foreach($related_list as $k=>$v){ ?>
    <div class="hot-item">
        <div class="image" style="background-image:url('<?php echo $v['object_img']; ?>');"></div>
        <div class="title"><?php echo $v['title']; ?></div>
        <div class="hot-status">
            <div class="hot-icon" style="background-image:url('<?php echo $v['user_head']; ?>');"></div>
            <div class="hot-name"><?php echo $v['user_name']; ?></div>
            <div class="hot-zan" style="background-image:url('./img/zan_red.png');"></div>
            <div class="hot-zan-nums"><?php echo $v['like_count']; ?></div>
        </div>
    </div>
    <?php } ?>
<!--    <div class="hot-item">-->
<!--        <div class="image" style="background-image:url('./test/photo1.jpg');"></div>-->
<!--        <div class="title">樱花满地集于我心，楪舞纷飞祈愿相随。</div>-->
<!--        <div class="hot-status">-->
<!--            <div class="hot-icon" style="background-image:url('./test/user_icon.jpg');"></div>-->
<!--            <div class="hot-name">Nacl_</div>-->
<!--            <div class="hot-zan" style="background-image:url('./img/zan_red.png');"></div>-->
<!--            <div class="hot-zan-nums">555</div>-->
<!--        </div>-->
<!--    </div>-->
</div>

<div class="empty"></div>
<div class="share-popup download">
    <div class="user-icon" style="background-image:url('<?php echo $fusers['user_head']; ?>');"></div>
    <div class="user-info">
        <div style="font-size:14px;"><?php echo $fusers['user_name']; ?></div>
        <div style="font-size:13px;">Heat-有趣的人一起玩</div>
    </div>
    <div class="btn">立刻下载</div>
</div>
<script>
    var oid = "<?php echo $oid; ?>";
</script>
<script src="js/vendor/modernizr-3.6.0.min.js"></script>
<script src="js/vendor/jquery-3.3.1.min.js"></script>
<script src="js/detect.js"></script>
<script src="js/plugins.js"></script>
<script src="js/main.js"></script>
<script>
    $('.share-popup').hide()
    var box = document.querySelector("body");
    var scrollTopOld = 0;

    window.onscroll = function (e) {
        var scrollTop = e.target.scrollingElement.scrollTop
        if (!$('.share-popup').is(':animated')) {
            if ($('.share-popup').is(":hidden") && (scrollTop - scrollTopOld > 0)) {
                $(".share-popup").slideDown('fast');
            }
            if (!$('.share-popup').is(":hidden") && (scrollTop - scrollTopOld < 0)) {
                $(".share-popup").slideUp('fast');
            }
        }
        scrollTopOld = scrollTop
    }

    var ua = navigator.userAgent.toLowerCase();


    /**跟踪来源**/
    var channel_id = res.getQueryString("ch");
    var log_id = '';
    var device_type = get_device_type();
    var canPost = true;
    var PAGE = {
        android_url: '',
        ios_url: 'https://itunes.apple.com/us/app/id1395981163?ls=1&mt=8',
        ios_app_url: 'https://itunes.apple.com/us/app/id1395981163?ls=1&mt=8',
        tongji_url: ''
    };
    //浏览次数加1
    $.ajax({
        type: 'POST',
        url: 'ctl.php?act=scan',
        data: {"channel_id": channel_id, "device_type": device_type,"oid":oid},
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
        var ua= navigator.userAgent;
        if (PAGE.tongji_url != '') {
            url = PAGE.tongji_url;
        } else if (device_type == 'ios') {
            if (ua.match(/MicroMessenger/i)) {
                url = PAGE.ios_url;
            } else {
                url = PAGE.ios_app_url;
            }
        } else if (device_type == 'android') {
//             alert("工程狮们正在加紧开发安卓版本，敬请期待~");
//             return;
            url = 'https://image.xymens.com/app-release_2019-05-13.apk';
        } else {
            /*url = '';
             var src = 'http://shop.xymens.com/Assets/qrcode/?url=http://api.xymens.com/Assets/download/?ch=' + channel_id;
             $('.tc img').attr('src', 'img/picture.png');
             $('.tc').show();*/
            alert('请使用移动设备下载!');
            return;
        }
//        if (ua.match(/MicroMessenger/i)) {
//            alert('请点击微信右上角按钮，选择在浏览器中打开！');
//            return;
//        }
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

</script>
</body>

</html>