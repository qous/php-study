<?php
/**
 * 新浪
 */
require_once __DIR__ . '/helpers.php';


header("Content-type: text/html; charset=utf-8");
set_time_limit(0);

// $agent = 'wlkgjslfdkjgiphone;不得';
// echo preg_match('/不得转载|责任编辑|android/', $agent);die;

$urls = array(
    'https://interface.sina.cn/wap_api/layout_col.d.json?showcid=12635&col=12658&level=1%2C2%2C3', // qinggan  区别
    // 'https://interface.sina.cn/wap_api/layout_col.d.json?showcid=74401&col=72340%2C205144&level=1%2C2%2C3&show_num=30', // nba
);

foreach ($urls as $url) {
    $data = file_get_contents($url);
    $arr = json_decode($data, true);
    // print_r($arr);die;
    foreach ($arr['result']['data']['list'] as $k => $row) {

        $id = 'sina_' . $row['_id'];
        $title = $row['title'];
        echo $id . "<br>";
        echo $title . "<br>";
        $url = $row['pc_url'];
        echo $url . "<br>";
        $doc = file_get_contents($url);
        // echo $doc . "<br>";
        if (preg_match('/(<div[^>]*?id="artibody".*)<div\s*id="left_hzh_ad"/is', $doc, $matches)) {
            $content = $matches[1];
        } else {
            continue;
        }


        $content = format($content);
        $s = microsecond();
        // $content = finder($content);
        $content = finder1($content);
        echo 'time: ' . (microsecond()-$s) . "<br>";
        echo $content . "<br>";die;

    }
}

