<?php
require_once '../common/config.php';
//加载封装的方法
require 'public_func.php';
$label = isset($_GET['label'])?$_GET['label']:'';
$token = isset($_GET['token'])?$_GET['token']:'';
if(empty($label)){
    echo "label error!";
    exit;
}
/*if(empty($token) || (MD5("SODA2018!@#".$label) != $token)){
    echo "Hacker attack!";
    exit;
}*/
//获取标签信息
$label_sql = "SELECT `label_name`,`label_img`,`desc` FROM `ecs_label` WHERE `is_index` = 2 AND `label_name` = '$label' LIMIT 1";

if(CON_ENVIRONMENT == 'online'){
    $labels = $DB->getRow($label_sql);
} else {
    $labels = $DB->get_row($label_sql);
}
if (empty($labels)){
    echo '没有该标签!';
    exit;
}
$subject_like_sql = "SELECT s.id as subject_id,s.content,s.more,s.vedio_url,s.like_count,s.object_img,s.comment_count,
                s.create_time,u.user_id,IFNULL(u.alias,u.user_name) AS user_name,u.user_head,s.label_ids 
                FROM ecs_subject_topic s 
                LEFT JOIN ecs_users u on s.user_id = u.user_id 
                WHERE s.status = 1 AND (s.content LIKE '%$label%' OR label_ids LIKE '%$label%')
                ORDER BY s.like_count desc LIMIT 20";

if(CON_ENVIRONMENT == 'online'){
    $like_list = $DB->getAll($subject_like_sql);
} else {
    $like_list = $DB->get_all($subject_like_sql);
}
$subject_new_sql = "SELECT s.id as subject_id,s.content,s.more,s.vedio_url,s.like_count,s.object_img,s.comment_count,
                s.create_time,u.user_id,IFNULL(u.alias,u.user_name) AS user_name,u.user_head,s.label_ids 
                FROM ecs_subject_topic s 
                LEFT JOIN ecs_users u on s.user_id = u.user_id 
                WHERE s.status = 1 AND (s.content LIKE '%$label%' OR label_ids LIKE '%$label%')
                ORDER BY s.create_time desc LIMIT 20";

if(CON_ENVIRONMENT == 'online'){
    $new_list = $DB->getAll($subject_new_sql);
} else {
    $new_list = $DB->get_all($subject_new_sql);
}

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
    <link rel="stylesheet" href="css/topic.css">
</head>

<body>
<div class="header clearfix">
    <img class="topic-icon" src="<?php echo $labels['label_img']; ?>"/>
    <div class="topic-title"><?php echo $labels['label_name']; ?></div>
    <div class="topic-info">
        <span><?php echo $labels['desc']; ?></span>
        <img src="img/icon_enter.png"/>
    </div>
    <div class="follow download">关注</div>
</div>
<div class="tab-view">
    <div id="hot" data-show="#hot" data-hide="#new" class="tab select">热门</div>
    <div id="new" data-show="#new" data-hide="#hot" class="tab">最新</div>
</div>
<div id="hotPhotos" class="photos clearfix">
    <?php foreach($new_list as $v){ ?>
        <div class="box download">
            <img src="<?php echo $v['object_img']; ?>">
        </div>
    <?php } ?>
    <!--<div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_02.png">
    </div>
    <div class="box">
        <img src="test/photo_01.jpg">
    </div>
    <div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_02.png">
    </div>
    <div class="box">
        <img src="test/photo_01.jpg">
    </div>
    <div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_02.png">
    </div>-->

</div>
<div id="newPhotos" class="photos">
    <?php foreach($like_list as $v){ ?>
        <div class="box download">
            <img src="<?php echo $v['object_img']; ?>">
        </div>
    <?php } ?>
    <!--<div class="box">
        <img src="test/photo_01.jpg">
    </div>
    <div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_01.jpg">
    </div>
    <div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_02.png">
    </div>
    <div class="box">
        <img src="test/photo_01.jpg">
    </div>
    <div class="box">
        <img src="test/photo_03.png">
    </div>
    <div class="box">
        <img src="test/photo_02.png">
    </div>-->
</div>
<script src="js/vendor/modernizr-3.6.0.min.js"></script>
<script src="js/vendor/jquery-3.3.1.min.js"></script>
<script src="js/detect.js"></script>
<script src="js/plugins.js"></script>
<script src="js/main.js"></script>
<script>
    $(document).ready(function () {
        $('#newPhotos').hide();
    });

    $('.tab').on('click', function (e) {
        var show = e.currentTarget.dataset.show
        var hide = e.currentTarget.dataset.hide
        if (!$(show).hasClass('select')) {
            console.log(show, hide)
            $(show).addClass('select')
            $(show + 'Photos').show();
            $(hide).removeClass('select')
            $(hide + 'Photos').hide();
        }
    })
</script>
<script src="js/public.js"></script>
</body>

</html>
