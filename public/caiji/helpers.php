<?php
require_once dirname(__DIR__) . '/../lib/PHPQuery/phpQuery.php';

// header("Content-type: text/html; charset=utf-8");
set_time_limit(0);

define('CAIJI_PATH', __DIR__);
define('PUBLIC_PATH', dirname(__DIR__));
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads/');
define('LOCAL_URL', 'http://php-study.local/');

function microsecond() {
    list($s1, $s2) = explode(' ', microtime());
    return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}

/**
 * 处理URL
 * @param string $url
 * @return string
 */
function setHttp($url) {
    if (strpos($url, 'http') === false) {
        $url = 'http://'.$url;
    }
    return $url;
}


/**
 * 获取文章中三张以内图片
 * @param string $content
 * @return array
 */
function img_array ($content) {
    $arrimg = array();
    $doc = new DOMDocument();
    @$doc->loadHTML($content);
    $xpath = new DOMXPath($doc);
    $result = $xpath->query("//img");

    // print_r($result);
    if (!empty($result)) {
        $img_num = 0;
        foreach ($result as $value) {
            if ($img_num >= 3) {
                break;
            }
            $imgsrc = $value->getAttribute('src');
            // echo $imgsrc;
            if ($imgsrc) {
                array_push($arrimg, $imgsrc);
            }
            $img_num ++;
        }
    }

    return $arrimg;
}

/**
 * 获取文章中三张以内图片
 * @param string $content
 * @return array
 */
function get_images($content) {
    $arrimg = array();
    $litpic = '';
    $doc = new DOMDocument('1.1', 'utf8');
    $doc->loadHTML($content);
    $xpath = new DOMXPath($doc);
    $result = $xpath->query("//img");

    // print_r($result);die;
    if (!empty($result)) {
        $img_num = 0;
        foreach ($result as $value) {
            if ($img_num >= 3) {
                break;
            }
            $imgsrc = $value->getAttribute('src');
            if ($imgsrc) {
                $arrimg[]['url'] = $imgsrc;
                if ($img_num == 0) {
                    $litpic = $imgsrc;
                }
            }
            $img_num ++;
        }
        echo $img_num;
    }
    return $arrimg;
}

function curl_get_content($url, $referer = '') {
    $curl = curl_init();
    $setopt = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_REFERER => $referer,
    );
    curl_setopt_array($curl, $setopt);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($response === false) {
        return false;
    } else {
        return $response;
    }
}

function img_download($content, $referer = '') {
    $doc = new \DOMDocument('1.0', 'utf-8');
    @$doc->loadHTML($content);
    $xpath = new \DOMXPath($doc);
    $result = $xpath->query("//img");

    if ($result->length == 0) {
        return $content;
    }
    foreach ($result as $value) {
        $imgsrc = $value->getAttribute('src');
        echo $imgsrc . "<br>";
        $lj = UPLOAD_PATH . date('ymd') . '/';
        $url = LOCAL_URL . date('ymd') . '/';
        $result = curl_get_content($imgsrc, $referer);
        $filename = $lj . md5($imgsrc);
        $file_url = $url . md5($imgsrc);
        file_put_contents($filename, $result);
        create_img($file_url, $lj);
    }
}

function img_replace($content, $referer = '') {
    $doc = new \DOMDocument('1.0', 'utf-8');
    @$doc->loadHTML($content);
    $xpath = new \DOMXPath($doc);
    $result = $xpath->query("//img");

    if ($result->length == 0) {
        return $content;
    }

    $new_src = array();
    foreach ($result as $value) {
        $imgsrc = $value->getAttribute('src');
        $lj = UPLOAD_PATH . date('ymd') . '/';

        if (strpos($imgsrc, 'baidu.com') !== false) {

            $result = curl_get_content($imgsrc, $referer);
            $filename = $lj . md5($imgsrc);
            file_put_contents($filename, $result);
            $xinarc = create_img($filename, $lj);
            @unlink($filename);

        } else {
            $xinarc = create_img($imgsrc, $lj);
        }
        $new_src[] = $xinarc;
    }

    $content = preg_replace_callback(
        '/(<img[^>]*?src=")([^>]*?)("[^>]*?>)/',
        function ($matches) use (&$new_src) {
            $src = array_shift($new_src);
            if (empty($src)) {
                return '';
            }
            return $matches[1] . $src . $matches[3];
        },
        $content);

    return $content;
}

/**
 * 生成的图片存为本地
 * @param string $content
 * @param string $flag
 * @return string
 */
function img_url_local($content, $flag = '')
{
    $doc = new \DOMDocument('1.0', 'utf-8');
    @$doc->loadHTML($content);
    $xpath = new \DOMXPath($doc);
    $result = $xpath->query("//img");
    // print_r( $result);
    foreach ($result as $value) {
        $imgsrc = $value->getAttribute('src');
        // echo $imgsrc;

        if (strpos($imgsrc, '//') === 0) {
            $himgsrc = 'http:'.$imgsrc;
        } else if (strpos($imgsrc, 'http') === false) {
            $himgsrc = 'http://'.$imgsrc;
        } else {
            $himgsrc = $imgsrc;
        }

        if ($flag == 'qu') {
            if (strpos($imgsrc, '?') === false) {
                $himgsrc = $himgsrc . '?imageView2/2/w/750/q/80/format/jpeg';
            }
        }
        if ($flag == 'wangyi') {
            if (strpos($imgsrc, '?') === false) {
                $himgsrc = $himgsrc . '?imageView&thumbnail=720y540&quality=85&type=jpg&interlace=1&enlarge=1';
            }
        }
        // echo $himgsrc . "<br>";

        $lj = dirname(__DIR__) . '/uploads/'  . date('ymd') . '/';
        $xinarc = create_img($himgsrc, $lj);
        $xinarc = str_replace(dirname(__DIR__), "http://php-study.test", $xinarc);
        $content = str_replace($imgsrc, $xinarc, $content);
    }
    return $content;
}


/**
 * 生成图片
 * @param string $img_src
 * @param string $img_path
 * @return string
 */
function create_img($img_src, $img_path)
{

    list($width, $height, $type) = getimagesize($img_src);
    $imgdst = md5($img_src);

    $new_width = $width;
    $new_height = $height;

    if (!is_dir($img_path)) {
        if (!mkdir($img_path, 0777, true)) {
            return false;
        }
    }

    $imgdst = $img_path . $imgdst;
    switch ($type) {

        case 1:
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromgif($img_src);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $imgdst . '.gif', 75);
            imagedestroy($image_wp);
            return $imgdst . '.gif';
            break;

        case 2:
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($img_src);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $imgdst . '.jpg', 75);
            imagedestroy($image_wp);
            return $imgdst . '.jpg';
            break;

        case 3:
            $image_wp = imagecreatetruecolor($new_width, $new_height);
            imagealphablending($image_wp, false);
            imagesavealpha($image_wp, true);
            $image = imagecreatefrompng($img_src);
            imagesavealpha($image, true);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagepng($image_wp, $imgdst . '.png');
            imagedestroy($image_wp);
            return $imgdst . '.png';
            break;
    }

}





/**
 * 简化图片属性
 * @param string $content
 * @return string
 */
function simplify_img($content) {
    return preg_replace('/(<img)[\s\S]*?(src="[\s\S]*?")[\s\S]*?(>)/', '$1 $2$3', $content);
}

/**
 * 检查是否有图片
 * phpQuery
 * @param string $content
 * @param boolean $phpquery
 * @return string
 */
function check_img($content, $phpquery = false) {
    // 检查是否有图片
    if ($phpquery) {
        $content = \phpQuery::newDocumentHTML($content);
        return $content->find('img')->count() ? true : false;
        // $res = $content->find('img');
        // $data = $res->elements;
        // return empty($data) ? false : true;
    } else {
        return preg_match('/<img[\s\S]*?>/', $content) ? true : false;
    }
}


/**
 * phpQuery 过滤
 * @param string $content
 * @param array $appends
 * @return string
 */
function phpquery($content, $appends = array()) {
    // 过滤方法
    $content = \phpQuery::newDocumentHTML($content);
    $patterns = array(
        '不得转载','责任编辑', '本文来源','原标题', '原文链接', '作者',
        '公众号', '一点号', '微信号', '头条号', '微信平台', '蓝字', '搜狐知道', '新浪女性',
        '加威信', '加微心', '关注我们', '关注我',
    );
    $patterns = array_merge($patterns, $appends);
    foreach ($patterns as $pa) {
        $content->find("p:contains('".$pa."')")->remove();
    }
    $content = $content->html();
    return trim($content);
}


/**
 * 查找关键字过滤
 * phpQuery
 * @param string $content
 * @param array $appends 追加
 * @return string
 */
function finder($content, $appends = array()) {
    $patterns = array(
        '不得转载','责任编辑', '本文来源','原标题', '原文链接', '作者',
        '公众号', '一点号', '微信号', '头条号', '微信平台', '蓝字', '搜狐知道', '新浪女性',
        '加威信', '加微心', '关注我们', '关注我',
    );
    $patterns = array_merge($patterns, $appends);
    $pattern = implode('|', $patterns);

    return preg_replace_callback('/<p[\s\S]*?>([\s\S]*?)<\/p>/', function ($matches) use($pattern) {
        // 通常: $matches[0]是完成的匹配
        // $matches[1]是第一个捕获子组的匹配
        // 以此类推

        if (preg_match('/'.$pattern.'/', $matches[1])) {
            return '';
        } else if (! trim($matches[1])) {
            return '';
        }
        return '<p>' .trim($matches[1]) . '</p>';
    }, $content);
}
// TODO 测试显示 finder效率高于finder1；待大数据测试
function finder1($content, $appends = array()) {
    $patterns = array(
        '不得转载','责任编辑', '本文来源','原标题', '原文链接', '作者',
        '公众号', '一点号', '微信号', '头条号', '微信平台', '蓝字', '搜狐知道', '新浪女性',
        '加威信', '加微心', '关注我们', '关注我',
    );
    $patterns = array_merge($patterns, $appends);

    return preg_replace_callback('/<p[\s\S]*?>([\s\S]*?)<\/p>/', function ($matches) use($patterns) {
        // 通常: $matches[0]是完成的匹配
        // $matches[1]是第一个捕获子组的匹配
        // 以此类推

        // foreach ($patterns as $pattern) {
        //     if ($pattern != '' && mb_stripos($matches[1], $pattern) !== false) {
        //         return '';
        //     } else if (! trim($matches[1])) {
        //         return '';
        //     }
        // }
        foreach ($patterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $matches[1])) {
                return '';
            } else if (! trim($matches[1])) {
                return '';
            }
        }
        return '<p>' .trim($matches[1]) . '</p>';
    }, $content);
}


/**
 * 格式化标签
 * phpQuery
 * @param string $content
 * @return string
 */
function format($content) {
    // $content = str_replace(array("\r", "\n", "\t"), '', $content);
    $content = preg_replace('/<!--[\s\S]*?-->/', '', $content);
    $content = preg_replace('/<(div)[^<>]*?display:\s*none[^<>]*?>[\s\S]*?<\/\1>/i', '', $content);
    $content = preg_replace('/\s??(style|class|id)=("|\')[^"\']*?\2/', '', $content);

    $content = str_replace(array('<html>','</html>','<body>','</body>'), '', $content);
    $content = preg_replace('/<script[\s\S]*?<\/script>/', '', $content);

    //  特殊标签 video embed /
    $content = preg_replace('/<video[\s\S]*?<\/video>/', '', $content);
    $content = preg_replace('/<embed[\s\S]*?<\/embed>/', '', $content); // 插件标签

    $content = preg_replace('/<p[^>]*?>(\s|<br>)*<\/p>/', '', $content);
    $content = preg_replace('/<(div|span)[^>]*?>\s*<\/\1>/is', '', $content);

    // $content = preg_replace('/<(h\d{1})[^>]*>([\s\S]*?)<\/\1>/i', '<p>$2</p>', $content);
    $content = preg_replace('/(<\/?)h\d{1}[^>]*?(>)/i', '$1p$2', $content);
    $content = preg_replace('/(<img)[^<>]*?(src="[^<>"\']*?")[^<>]*?(>)/is', '$1 $2$3', $content);
    /*$content = preg_replace('/<a[^>]*?href=[^>]*?>/is', '', $content);*/
    // $content = preg_replace('/<\/a>/is', '', $content);
    $content = preg_replace('/<a[^>]*?href=[^>]*?>([^<>]*)<\/a>/is', '$1', $content);
    // $content = preg_replace('/href="[^"]*?"/', 'href="javascript:void(0);"', $content);

    return trim($content);
}

/**
 * 检查关键字
 * @param string $str
 * @param array|string $words
 * @return bool
 */
function has_keyword($str, $words) {
    foreach ((array) $words as $w) {
        if (mb_stripos($str, $w) !== false ) {
            return true;
        }
    }
    return false;
}

function preg_str($content, $words) {
    $pattern = implode('|', (array)$words);
    return preg_match('/'.$pattern.'/', $content);
}

/**
 * 检查字符串长度 是否大于100
 * @param string $content
 * @return bool
 */
function strlt100($content) {
    if (mb_strlen(strip_tags($content)) < 100) {
        return true;
    } else {
        return false;
    }
}

$keywords = array(
    '不得转载','责任编辑', '本文来源','原标题', '原文链接', '作者',
    '公众号', '一点号', '微信号', '头条号', '微信平台', '蓝字', '搜狐知道', '新浪女性',
    '加威信', '加微心', '关注我们', '关注我',
);