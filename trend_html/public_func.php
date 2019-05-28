<?php
//计算年龄
function calcAge($birthday) {
    $iage = 0;
    if (!empty($birthday) && $birthday != '0000-00-00') {
        $year = date('Y',strtotime($birthday));
        $month = date('m',strtotime($birthday));
        $day = date('d',strtotime($birthday));
        $now_year = date('Y');
        $now_month = date('m');
        $now_day = date('d');

        if ($now_year > $year) {
            $iage = $now_year - $year - 1;
            if ($now_month > $month) {
                $iage++;
            } else if ($now_month == $month) {
                if ($now_day >= $day) {
                    $iage++;
                }
            }
        }
    }
    return $iage;
}

//人性化时间显示
function formatTime($time){
    $day = date('Y');
    $tian = 365;
    if ($day%4==0&&($day%100!=0 || $day%400==0)){
        $tian = 366;
    }else{
        $tian = 365;
    }
    $ntime = date("Y-m-d",$time);
    $rtime = date("m-d",$time);
    $htime = date("H:i",$time);
    $time = time() - $time;
    if ($time < 60){
        $str = '刚刚';
    }elseif($time < 60 * 60){
        $min = floor($time/60);
        $str = $min.'min ';
    }elseif($time < 60 * 60 * 24){
        $h = floor($time/(60*60));
        $str = $h.'h ';
    }elseif($time < 60 * 60 * 24 * 3){
        $d = floor($time/(60*60*24));
        if($d==1){
            $str = '1d';
        }elseif($time < 60 * 60 * 24 * 3){
            $str = '2d';
        }
    }elseif($time<(60*60*24*$tian)){
        $str = $rtime;
    } else {
        $str = $ntime;
    }
    return $str;
}

function diffBetweenTwoDays($day1, $day2)
{
    if($day1 < $day2){
        return "已结束";
    }
    return ($day1 - $day2) / 86400;
}

?>