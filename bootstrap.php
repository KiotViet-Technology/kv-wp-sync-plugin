<?php
function kiotviet_sync_get_request($key, $default = '')
{
    if (isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }

    return $default;
}

function kiotviet_sync_get_current_time()
{
    return date('Y-m-d H:i:s', time());
}

function kiotviet_sync_decode_json($string){
    return json_decode(html_entity_decode(stripslashes($string)), true);
}
