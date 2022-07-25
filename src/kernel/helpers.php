<?php
function array_to_url_prarm($array){
    $prarms = [];
    foreach ($array as $key => $val) {
        $prarms[] = $key . '=' . str_replace(' ', '+', $val);
    }
    return implode('&', $prarms);
}
function return_huoyan_data($msg = '', $code = 0, $data = [])
{
    return response()->json(array('code' => $code, 'msg' => $msg, 'data' => $data), 200)->getContent();

}
//下划线命名到驼峰命名
function camelize($uncamelized_words,$separator='_')
{
    $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
}


//驼峰命名转下划线命名
function uncamelize($camelCaps,$separator='_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}
