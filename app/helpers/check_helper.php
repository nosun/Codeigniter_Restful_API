<?php
/*
 * phone check
 * int $mobilePhone
 *
 */
function is_mobile($mobilePhone) {
    if (preg_match("/^(13\d|15\d|17\d|18\d)\d{8}$/", $mobilePhone)) {
        return true;
    } else {
        return false;
    }
}

//将数据库中查询出来的结果集进行过滤，如果发现结果集中有空value，过滤掉
function resultFilter($array){
    $list = array();
    foreach($array as $row){
        array_push($list, paraFilter($row));
    }
    return $list;
}

function paraFilter($para) {
    $para_filter = array();
    while (list ($key, $val) = each ($para)) {
        if(isset($val) and $val !='' ) $para_filter[$key] = $para[$key];
    }
    return $para_filter;
}