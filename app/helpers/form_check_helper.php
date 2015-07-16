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