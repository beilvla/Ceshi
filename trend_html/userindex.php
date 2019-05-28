<?php
require_once '../common/config.php';
//加载封装的方法
require 'public_func.php';
$uid = isset($_GET['uid'])?intval($_GET['uid']):'';
$token = isset($_GET['token'])?$_GET['token']:'';
if(empty($uid)){
    echo "user_id error!";
    exit;
}
/*if(empty($token) || (MD5("SODA2018!@#".$uid) != $token)){
    echo "Hacker attack!";
    exit;
}*/

//获取用户信息
$user_sql = "SELECT user_id as user_id,IFNULL(alias,user_name) AS NickName,email as Email,
                          user_head as UserImg,'' as UserHeight,'' as UserWidth,'' as UserLevel,sex as gender,province,city,labels,birthday FROM ecs_users WHERE user_id='$uid'";
if(CON_ENVIRONMENT == 'online'){
    $users = $DB->getRow($user_sql);
} else {
    $users = $DB->get_row($user_sql);
}

if (empty($users)){
    echo '没有该用户!';
    exit;
}
//整合用户信息
$users['labels'] = !empty($users['labels'])?explode(",", $users['labels']):array();
$users['age'] = calcAge($users['birthday']);
//var_dump($users);die;

$subject_sql = "SELECT s.id as object_id, s.content, s.more, s.vedio_url, s.like_count, s.object_img, 
                s.position,s.latitude,s.longitude,s.comment_count,s.create_time,s.type, u.user_id, 
                IFNULL(u.alias,u.user_name) AS user_name, u.user_head, s.label_ids FROM ecs_subject_topic as s
                LEFT JOIN ecs_users as u ON s.user_id = u.user_id
                WHERE s.is_delete=1 AND s.user_id='$uid' 
                ORDER BY s.create_time desc
                LIMIT 20";
if(CON_ENVIRONMENT == 'online'){
    $list = $DB->getAll($subject_sql);
} else {
    $list = $DB->get_all($subject_sql);
}

foreach($list as $k=>$v){
    $list[$k]['imgs'] = (array)(json_decode($v['more']));
    $list[$k]['release_time'] = formatTime($v['create_time']);
    //获取评论
    $csql = "SELECT c.id,c.parent_id,c.content,u.user_id,IFNULL(u.alias,u.user_name) AS user_name,
            u.user_head ,c.level,
            FROM_UNIXTIME(c.create_time,'%Y-%m-%d') as ctime 
            FROM ecs_comment c 
            LEFT JOIN ecs_users u on c.user_id = u.user_id 
            WHERE c.status = 1 AND c.is_delete = 1 AND c.level=1 AND c.object_id = '".$v['object_id']."' 
            ORDER BY c.create_time desc 
            LIMIT 3";
    if(CON_ENVIRONMENT == 'online'){
        $comments = $DB->getAll($csql);
    } else {
        $comments = $DB->get_all($csql);
    }

    $list[$k]['comments'] = $comments;
    //热门标签使用数量
    $labelids = !empty($v['label_ids'])?explode(',',$v['label_ids']):array();
    $agreement_labels = array();
    if(!empty($labelids)){
        $asql = "SELECT `label_name`,`label_img`,`desc` FROM `ecs_label` WHERE `is_index` = 2 AND `label_name` IN ('".implode("','",$labelids)."')";

        if(CON_ENVIRONMENT == 'online'){
            $agreement_labels = $DB->getAll($asql);
        } else {
            $agreement_labels = $DB->get_all($asql);
        }
    }
    $list[$k]['agreement_labels'] = !empty($agreement_labels)?count($agreement_labels):0;
    //标签高亮
    foreach($labelids as $vo){
        $list[$k]['content'] = str_replace($vo,"<span>$vo</span>",$list[$k]['content']);
    }
}
//echo "<pre />";
//var_dump($list);die;
$DB->closeConnection();
?>
<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>HEAT</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="img/icon.png" type="image/x-icon" />

    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/soda.css">
    <link rel="stylesheet" href="css/swiper-4.3.3.min.css">
</head>

<body>
<div class="header clearfix">
    <div class="title"><?php echo $users['NickName'];?>的主页</div>
    <div class="user-view">
        <img src="" class="user-icon" onerror="this.src='img/picture.png';this.onerror=null;">
    </div>
    <div class="user-info"><?php echo $users['NickName'];?> / <?php echo $users['age'];?>岁<?php echo !empty($users['city'])?' / '.$users['city']:'';?></div>
    <div class="user-tags">
        <?php foreach($users['labels'] as $v){echo '<div class="user-tag">'.$v.'</div>';}?>
       <!-- <div class="user-tag">室内冲浪</div>
        <div class="user-tag">天蝎座</div>
        <div class="user-tag">天蝎座</div>-->
    </div>
    <div class="follow download">关注</div>
</div>
<?php foreach($list as $v){?>
<div class="post">
    <div class="post-header clearfix">
        <img src="" class="header-icon download" onerror="this.src='<?php echo $v['user_head'];?>';this.onerror=null;">
        <div style="margin-left: .36rem;overflow: hidden;">
            <div class="header-name"><?php echo $v['user_name'];?></div>
            <div class="header-date"><?php echo $v['release_time'];?></div>
        </div>
    </div>
    <?php if(!empty($v['vedio_url'])){?>
        <div class="video">
            <video controls width="100%" height="240">
                <source src="<?php echo $v['vedio_url'];?>" type="video/mp4">
                您的浏览器不支持 video 标签。
            </video>
        </div>
    <?php }else{ ?>
        <div class="photos">
            <div class="swiper-wrapper ">
                <?php foreach($v['imgs'] as $img){?>
                <div class="swiper-slide">
                    <div class="photo" style="background-image:url(<?php echo $img;?>)"></div>
                </div>
                <?php } ?>
                <!--<div class="swiper-slide">
                    <div class="photo" style="background-image:url(test/photo_02.png)"></div>
                </div>
                <div class="swiper-slide">
                    <div class="photo" style="background-image:url(test/photo_03.png)"></div>
                </div>-->
            </div>
            <div class="swiper-pagination"></div>
        </div>
    <?php } ?>
    <div class="content"><?php echo $v['content'];?><!--<span>#Dr.Martens#</span>--></div>
    <div class="menu">
        <img  class="download" src="img/icon_like.png" style="margin-left: 0;">
        <img  class="download" src="img/icon_comment.png">
        <img  class="download" src="img/icon_share.png">
        <div style="flex:1;"></div>
        <img class="download" src="img/icon_link.png">
        <div class="download"><?php echo $v['agreement_labels'];?></div>
    </div>
    <?php if($v['comment_count']>0){?>
    <div class="read-all download">查看全部<?php echo $v['comment_count']; ?>条评论</div>
    <div class="comments">
        <?php foreach($v['comments'] as $vo){?>
            <div class="comment download"><?php echo $vo['user_name']."：".$vo['content']; ?></div>
        <?php } ?>
        <!--<div class="comment">Fyffb：很有味道的车</div>
        <div class="comment">coming哥哥：迪通拿w</div>-->
    </div>
    <?php } ?>
</div>
<?php } ?>
<!--<div class="post">
    <div class="post-header clearfix">
        <img src="" class="header-icon" onerror="this.src='img/picture.png';this.onerror=null;">
        <div style="margin-left: .36rem;overflow: hidden;">
            <div class="header-name">Neo Retros</div>
            <div class="header-date">1小时前</div>
        </div>
    </div>
    <div class="video">
        <video controls width="100%" height="240">
            <source src="test/video.mp4" type="video/mp4">
            您的浏览器不支持 video 标签。
        </video>
    </div>
    <div class="content">这是一个悲伤的故事，我站在ATM机前，却取不到钱这
        是什么什么什么什么 <span>#Dr.Martens#</span></div>
    <div class="menu">
        <img class="download" src="img/icon_like.png" style="margin-left: 0;">
        <img class="download" src="img/icon_comment.png">
        <img class="download" src="img/icon_share.png">
        <div style="flex:1;"></div>
        <img class="download" src="img/icon_link.png">
        <div class="download">2</div>
    </div>
    <div class="read-all download">查看全部24条评论</div>
    <div class="comments">
        <div class="comment">林校长y：衬衫几时上</div>
        <div class="comment">Fyffb：很有味道的车</div>
        <div class="comment">coming哥哥：迪通拿w</div>
    </div>
</div>-->

<script src="js/vendor/modernizr-3.6.0.min.js"></script>
<script src="js/vendor/jquery-3.3.1.min.js"></script>
<script src="js/vendor/swiper-4.3.3.min.js"></script>
<script src="js/vendor/vconsole.min.js"></script>
<script src="js/detect.js"></script>
<script src="js/plugins.js"></script>
<script src="js/main.js"></script>
<script src="js/public.js"></script>
</body>

</html>

