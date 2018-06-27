<?php
// +----------------------------------------------------------------------
// | TPR [ Design For Api Develop ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2017 http://hanxv.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axios <axioscros@aliyun.com>
// +----------------------------------------------------------------------
use think\Cache;
use think\Db;
use think\Config;

if (!function_exists('middleware')) {
    /**
     * 实例化验证器
     * @param string    $name 验证器名称
     * @param string    $layer 业务层名称
     * @param bool      $appendSuffix 是否添加类名后缀
     * @param string    $common
     * @return \think\Validate
     */
    function middleware($name = '', $layer = 'middleware', $appendSuffix = false,$common="common")
    {
        return \think\Loader::validate($name, $layer, $appendSuffix,$common);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0, $adv = false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
}

if(!function_exists('arraySort')){
    function arraySort($array,$sortRule="",$order="asc"){
        /**
         * $array = [
         *              ["book"=>10,"version"=>10],
         *              ["book"=>19,"version"=>30],
         *              ["book"=>10,"version"=>30],
         *              ["book"=>19,"version"=>10],
         *              ["book"=>10,"version"=>20],
         *              ["book"=>19,"version"=>20]
         *      ];
         */
        if(is_array($sortRule)){
            /**
             * $sortRule = ['book'=>"asc",'version'=>"asc"];
             */
            usort($array, function ($a, $b) use ($sortRule) {
                foreach($sortRule as $sortKey => $order){
                    if($a[$sortKey] == $b[$sortKey]){continue;}
                    return (($order == 'desc')?-1:1) * (($a[$sortKey] < $b[$sortKey]) ? -1 : 1);
                }
                return 0;
            });
        }else if(is_string($sortRule) && !empty($sortRule)){
            /**
             * $sortRule = "book";
             * $order = "asc";
             */
            usort($array,function ($a,$b) use ($sortRule,$order){
                if($a[$sortRule] == $b[$sortRule]){
                    return 0;
                }
                return (($order == 'desc')?-1:1) * (($a[$sortRule] < $b[$sortRule]) ? -1 : 1);
            });
        }else{
            usort($array,function ($a,$b) use ($order){
                if($a== $b){
                    return 0;
                }
                return (($order == 'desc')?-1:1) * (($a < $b) ? -1 : 1);
            });
        }
        return $array;
    }
}

if(!function_exists('arrayDataToString')){
    function arrayDataToString(&$array=[]){
        if(is_array($array)){
            foreach ($array as &$a){
                if(is_array($a)){
                    $a = arrayDataToString($a);
                }
                if(is_int($a)){
                    $a = strval($a);
                }
                if(is_null($a)){
                    $a = "";
                }
            }
        }else if(is_int($array)){
            $array = strval($array);
        }else if(is_null($array)){
            $array = "";
        }
        return $array;
    }
}

if(!function_exists('trimAll')){
    /**
     * 去除字符串内的所有空格
     */
    function trimAll($str)
    {
        $qian=array(" ","　","\t","\n","\r");
        $hou=array("","","","","");
        return str_replace($qian,$hou,$str);    
    }
}

if(!function_exists('isMobile')){
    /**
    * 验证手机号是否正确
    * @author honfei
    * @param number $mobile
    */
    function isMobile($mobile) {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,1,3,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
}

/**
 * 订单号生成规则
 * 1706065048100555
 * 用uniqid获取一个基于当前的微秒数生成的唯一不重复的字符串
 （但是他的前7位貌似很久才会发生变动，所以不用考虑可删除），
 取其第8到13位。但是这个字符串里面有英文字母，咋办？
用ord获取他的ASCII码，所以就有了下一步：用str_split把这个字符串分割为数组，用array_map去操作（速度快点）。
然后返回的还是一个数组，KO，在用implode弄成字符串，但是字符长度不定，取前固定的几位，
然后前面加上当前的年份和日期，这个方法生成的订单号，全世界不会有多少重复的。
当然，除非你把服务器时间往前调，但是调也不用怕，哥不相信他会在同一微秒内下两次订单，
网络数据传输也要点时间的，即便你是在本地。
 */
function order_no($prefix = ''){
    $no =  date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(microtime(true), true), 7, 13), 1))), 0, 10);
    switch ($prefix) {
        case 1://微信
            $no = 'WX'.$no;
            break;
        case 2://支付宝
            $no = 'ALI'.$no;
            break;
    }
    return $no;
}


/**
 * 根据经纬度计算两点之间的距离
 */
if(!function_exists('getDistance')){
    function getDistance($lat1 = 0, $lng1 = 0, $lat2 = 0, $lng2 = 0){   
        if(is_null($lat1) || is_null($lng1) || is_null($lat2) || is_null($lng2)){
            return '未知';
        }
        if( ( abs( $lat1 ) > 90  ) ||(  abs( $lat2 ) > 90 ) ){
            // return "耍我？拒绝计算！";
            return '未知';
        }
        if( ( abs( $lng1 ) > 180  ) ||(  abs( $lng2 ) > 180 ) ){
            return '未知';
        }
        $radLat1 = rad($lat1);
        $radLat2 = rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = rad($lng1) - rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
        $s = $s *6378.137 ;// EARTH_RADIUS;
	
	    $s = sprintf("%.2f", $s);
        if($s < 1 ){
            $s = 1000*$s.'m';
        }else{
            $s = $s.'km';
        }

        return $s; 
   } 
}

if(!function_exists('rad')){
    function rad($d = '')
    {
        return $d * pi() / 180.0;
    }
}

/**
 * 字符串安全过滤
 */
if(!function_exists('string')){
    function string($_str = ''){   
        $_str = strip_tags($_str);
        $_str = str_replace("'", '&#39;', $_str);
        $_str = str_replace("\"", '&quot;', $_str);
        $_str = str_replace("\\", '', $_str);
        $_str = str_replace("/", '', $_str);
        $_str = addslashes(html_escape($_str));
        return $_str;
    } 
}
/**
* @access   public
* @param    mixed
* @return   mixed
*/
if ( ! function_exists('html_escape'))
{
    function html_escape($var)
    {
        if (is_array($var))
        {
            return array_map('html_escape', $var);
        }
        else
        {
            return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
        }
    }
}
/**
 * html安全过滤
 */
if(!function_exists('html')){
    function html($_str = ''){   
        return reMoveXss($_str);
    } 
}

//过滤XSS攻击
if(!function_exists("reMoveXss")){
    function reMoveXss($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08|\x0b-\x0c|\x0e-\x19])/', '', $val);
    
        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
        }
    
        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', '<script', 'object', 'iframe', 'frame', 'frameset', 'ilayer'/* , 'layer' */, 'bgsound', 'base');
        $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);
    
        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }
}

// 时间处理
if(!function_exists('tranTime')){
    function tranTime($time){
        $rtime = date("Y-m-d H:i",$time);
        $htime = date("H:i",$time);
        $time = time() - $time;

        if ($time < 60) {
            $str = '刚刚';
        }elseif ($time < 60 * 60) {
            $min = floor($time/60);
            $str = $min.'分钟前';
        }elseif ($time < 60 * 60 * 24)  {
            $h = floor($time/(60*60));
            $str = $h.'小时前 '.$htime;
        }elseif ($time < 60 * 60 * 24 * 3)  {
            $d = floor($time/(60*60*24));
            if($d==1)
                $str = '昨天 '.$rtime;
            else
                $str = '前天 '.$rtime;
        }else{
            $str = $rtime;
        }
        return $str;
    } 
}
/**
 * 获取省级名称
 */
if(!function_exists('get_province_name')){
    function get_province_name($provinceID = 0, $list_flag = false){
        if($list_flag == false && !$provinceID){
            return '';
        }
        // 获取缓存信息
        $province_key = "province_".$provinceID;
        $province = Cache::get($province_key, false);
        if(!$province){
            $list = Db::name('hat_province')->select();
            $province = [];
            foreach ($list as $key => $value) {
                $province[$value['provinceID']] = $value['province'];
            }
            Cache::set($province_key, $province, 0, false);
        }
        if($list_flag){
            return $province;    
        }
        return isset($province[$provinceID]) ? $province[$provinceID] : '';    
    }
}
/**
 * 获取市级名称
 */
if(!function_exists('get_city_name')){
    function get_city_name($cityID, $list_flag = false){
        if($list_flag == false && !$cityID){
            return '';
        }
        // 获取缓存信息
        $city_key = "city_".$cityID;
        $city = Cache::get($city_key, false);
        if(!$city){
            $list = Db::name('hat_city')->select();
            $city = [];
            foreach ($list as $key => $value) {
                $city[$value['cityID']] = $value['city'];
            }
            Cache::set($city_key, $city, 0, false);
        }
        if($list_flag){
            return $city;
        }
        return isset($city[$cityID]) ? $city[$cityID] : '';
    }
}
/**
 * 获取区级名称
 */
if(!function_exists('get_area_name')){
    function get_area_name($areaID, $list_flag = false){
        if($list_flag == false && !$areaID){
            return '';
        }
        // 获取缓存信息
        $area_key = "area_".$areaID;
        $area = Cache::get($area_key, false);
        if(!$area){
            $list = Db::name('hat_area')->select();
            $area = [];
            foreach ($list as $key => $value) {
                $area[$value['areaID']] = $value['area'];
            }
            Cache::set($area_key, $area, 0, false);
        }
        if($list_flag){
            return $area;    
        }
        return isset($area[$areaID]) ? $area[$areaID] : '';
    }
}

/**
 * 计算年龄问题
 */
if(!function_exists('get_age')){
    function get_age($birthday = ''){
        if(!$birthday){
            return 0;
        }
        $age = date("Y")- substr($birthday, 0,4);    
        return $age;
    }
}

/**
 * 头像
 */
if(!function_exists('get_avatar')){
    function get_avatar($avatar = '', $sex = 0){
        if(!$avatar){
            $avatar = Config::get('user.avatar'.$sex);
        }else{
	    if(strpos($avatar, 'http') === false){
                $avatar = Config::get('img_url').$avatar;    
            }
            //$avatar = Config::get('img_url').$avatar;    
        }
        return $avatar;
    }
}

/**
 * 下载远程文件，默认保存在TEMP_PATH下
 * @param  string  $url     网址
 * @param  string  $filename    保存文件名
 * @param  integer $timeout 过期时间
 * @param  bool $repalce 是否覆盖已存在文件
 * @return string 本地文件名
 */
if(!function_exists('http_down')){
    function http_down($url, $filename = "", $timeout = 60) {
        if (empty($filename)) {
            $filename = TEMP_PATH . pathinfo($url, PATHINFO_BASENAME);
        }
        $path = dirname($filename);
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            return false;
        }
        $url = str_replace(" ", "%20", $url);
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $temp = curl_exec($ch);
            if (file_put_contents($filename, $temp) && !curl_error($ch)) {
                return $filename;
            } else {
                return false;
            }
        } else {
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "",
                    "timeout" => $timeout,
                ],
            ];
            $context = stream_context_create($opts);
            if (@copy($url, $filename, $context)) {
                //$http_response_header
                return $filename;
            } else {
                return false;
            }
        }
    }
}
/**
 * 获得header
 * @param  string $url 网址
 * @return string
 */
if(!function_exists('get_head')){
    function get_head($url) {
        $ch = curl_init();
        // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
        $header[] = "Content-type: application/x-www-form-urlencoded";
        $user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($ch, CURLOPT_HEADER, true);
        // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // 使用上面定义的 ua
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 不用 POST 方式请求, 意思就是通过 GET 请求
        curl_setopt($ch, CURLOPT_POST, false);
        $sContent = curl_exec($ch);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header = substr($sContent, 0, $headerSize);
        curl_close($ch);
        return $header;
    }
}


/**
 * 计算订单服务邀约信息
 */
if(!function_exists('order_options')){
    function order_options($options = ''){
        $arr = unserialize($options);
        $arr['attaches'] = unserialize($arr['attaches']);
	    foreach ($arr['attaches'] as $k => $val) {
            $arr['attaches'][$k] = Config::get('img_url').$val;
        }
        $arr['title'] = strDecode($arr['title']);
        $arr['intro'] = strDecode($arr['intro']);
        $arr['nick_name'] = strDecode($arr['nick_name']);
        $arr['avatar'] = get_avatar($arr['avatar'], $arr['sex']);
        unset($arr['ry_token']);
        unset($arr['token']);
        unset($arr['last_login_time']);
        unset($arr['last_login_ip']);
        unset($arr['login_pass']);
        unset($arr['account']);
        unset($arr['freeze_money']);
        unset($arr['birthday']);
        unset($arr['wb_identify']);
        unset($arr['qq_identify']);
        unset($arr['wx_identify']);
        unset($arr['user_uniq']);
        unset($arr['source_id']);
        unset($arr['is_del']);
        unset($arr['status']);
        unset($arr['created_uid']);
        unset($arr['created_at']);
        unset($arr['update_at']);
        unset($arr['latitude']);
        unset($arr['longitude']);
        unset($arr['attach_id']);
        unset($arr['login_name']);
        return $arr;
    }
}

/**
 * 订单冻结金额计算
 */
if(!function_exists('order_freeze_money')){
    function order_freeze_money($order = ''){
        if(!$order){
            return 0;
        }
        switch ($order['source_type']) {
            case 1://服务
                // 订单需冻结金额
                $service_fund_ratio = \think\Config::get('payment.service_fund_ratio'); 
                $money = $service_fund_ratio*$order['total_fee']/100;
                break;
            case 2://邀约
                // 订单需冻结金额
                $demand_fund_ratio = \think\Config::get('payment.demand_fund_ratio'); 
                $money = $demand_fund_ratio*$order['total_fee']/100;
                break;
            default:
                $money = 0;
                break;
        }
        return $money;
        
    }
}


/**
 * 输出xml字符
 * @throws WxPayException
 **/
if(!function_exists('arr_to_xml')){
    function arr_to_xml($arr = [])
    {
        if (!is_array($arr)
            || count($arr) <= 0
        ) {
            return '';
        }

        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}

/**
 * 将xml转为array
 * @param string $xml
 * @return array|mixed
 * @throws \wxpay\WxPayException
 */
if(!function_exists('xml_to_arr')){
    function xml_to_arr($xml = '')
    {
        if (!$xml) {
            return [];
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }
}
/**
 * 格式化参数格式化成url参数
 */
if(!function_exists('toUrlParams')){
    function toUrlParams($param = [])
    {
        $buff = "";
        foreach ($param as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
}
/**
 * 微信签名验证
 */
if(!function_exists('get_wx_sign')){
    function get_wx_sign($param = []){
        //签名步骤一：按字典序排序参数
        ksort($param);
        $string = toUrlParams($param);
        //签名步骤二：在string后加入KEY
        $wx_config = Config::get('pay.wx');
        $string = $string . "&key=" . $wx_config['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}

/**
 * 字符加密
 */
if(!function_exists('strEncode')){
    // 过滤掉emoji表情
    function strEncode($str)
    {
        return base64_encode($str);
    }
}
/**
 * 字符解密
 */
if(!function_exists('strDecode')){
    // 过滤掉emoji表情
    function strDecode($str)
    {
        return base64_decode($str);
    }
}

/**
 * 邀约时间处理
 */
function demandTime($source_id = ''){
    // 空闲时间
    $ext_where['source_id'] = $source_id;
    $time = Db::table('services_time')->where($ext_where)->select();
    if(!$time){
        return [];
    }
    $tmp_detail['time_type'] = $time['0']['time_type'];
    switch ($tmp_detail['time_type']) {
        case 0:
            $tmp_detail['date_time'] = '不限时间';
            break;
        case 1:
            $tmp_detail['date_time'] = $time['0']['date_time'];
            $tmp_detail['time_long'] = $time['0']['time_long'];
            break;
        case 2:
            $tmp_detail['date_time'] = '平时周末';
            break;
    }
    return $tmp_detail;
}

/**
 * 邀约时间处理
 */
function demandTime_new($time_type = '', $date_time = '', $time_long = ''){
    $tmp_detail['time_type'] = $time_type;
    switch ($time_type) {
        case 0:
            $tmp_detail['date_time'] = '不限时间';
            break;
        case 1:
            $tmp_detail['date_time'] = $date_time;
            $tmp_detail['time_long'] = $time_long;
            break;
        case 2:
            $tmp_detail['date_time'] = '平时周末';
            break;
    }
    return $tmp_detail;
}
/**
 * 服务订单时间处理
 */
function serviceTime($order = ''){
    if(!$order){
        return [];
    }
    $tmp_detail['time_type'] = '1';
    $tmp_detail['date_time'] = $order['date_time'];
//    $tmp_detail['date_time'] = date("Y-m-d H:i", $order['date_time']);
    $tmp_detail['time_long'] = $order['time_long'];
    return $tmp_detail;
}

/**
 * 隐藏手机号中间四位
 */
if(!function_exists('hide_phone')){
    function hide_phone($number = ''){
        if(isMobile($number)){
            $number = substr_replace($number, '****', 3, 4);
        }
        return $number;
    }
}

/**
 * 获取职业名称
 */
if(!function_exists('occupation')){
    function occupation($id = ''){
        if(!$id){
            return '未知';
        }
        // 获取缓存信息
        $cache_key = "occupation_".$id;
        $occupation = Cache::get($cache_key);
        if(!$occupation){
            $list = Db::name('configs')->select();
            $occupation = [];
            foreach ($list as $key => $value) {
                $occupation[$value['configs_id']] = $value['value'];
            }
            Cache::set($cache_key, $occupation, 0);
        }
        return isset($occupation[$id]) ? $occupation[$id] : '未知';
    }
}
/**
 * 获取情感文字
 */
if(!function_exists('marry')){
    function marry($id = ''){
        if(!$id){
            return '未知';
        }
        // 获取缓存信息
        $cache_key = "marry_".$id;
        $marry = Cache::get($cache_key);
        if(!$marry){
            $list = Db::name('configs')->select();
            $marry = [];
            foreach ($list as $key => $value) {
                $marry[$value['configs_id']] = $value['value'];
            }
            Cache::set($cache_key, $marry, 0);
        }
        return isset($marry[$id]) ? $marry[$id] : '未知';
    }
}

/**
 * 星座计算
// 水瓶座        1.20-2.18
// 双鱼座        2.19-3.20
// 白羊座        3.21-4.19
// 金牛座        4.20-5.20
// 双子座        5.21-6.21
// 巨蟹座        6.22-7.22
// 狮子座        7.23-8.22
// 处女座        8.23-9.22
// 天秤座        9.23-10.23
// 天蝎座        10.24-11.22
// 射手座        11.23-12.21
// 魔羯座        12.22-1.19
 */
if(!function_exists('get_xingzuo')){
    function get_xingzuo($birthday = ''){
        $birthday = date("Y-m-d", strtotime($birthday));
        $tmp = explode('-', $birthday);
        $month = $tmp['1'];
        $day = $tmp['2'];
        // 检查参数有效性 
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) 
        {
            return (false);
        } 
        // 星座名称以及开始日期 
        $signs = array( 
            array( "20" => "水瓶座"), 
            array( "19" => "双鱼座"), 
            array( "21" => "白羊座"), 
            array( "20" => "金牛座"), 
            array( "21" => "双子座"), 
            array( "22" => "巨蟹座"), 
            array( "23" => "狮子座"), 
            array( "23" => "处女座"), 
            array( "23" => "天秤座"), 
            array( "24" => "天蝎座"), 
            array( "22" => "射手座"), 
            array( "22" => "摩羯座") 
        );
        list($sign_start, $sign_name) = each($signs[(int)$month-1]); 
        if ($day < $sign_start) 
        {
            list($sign_start, $sign_name) = each($signs[($month -2 < 0) ? $month = 11: $month -= 2]); 
        }
        return $sign_name; 
    }
}

/**
 * 检测用户权限
 */
function check_privilege($uid = '', $license_url = ''){
    $user_license_url = Cache::get('user_privilege_'.$uid);
    if(!in_array($license_url, $user_license_url)){
        return false;
    }
    return true;
}
/**
 * 处理保存帖子内容
 */
if(!function_exists('deal_posts')){
    function deal_posts($content = ''){
        if(!$content){
            return '';
        }
        preg_match_all('/<img[\s\S]*?src\s*=\s*[\"|\'](.*?)[\"|\'][\s\S]*?>/', $content, $matches);
        $img_list = $matches['0'];
        $url_list = $matches['1'];
        if($img_list){
            foreach ($img_list as $k => $img) {
                $content = str_replace($img, '@#￥%(!---UIImageView---!)@#￥%', $content);
            }
        }
        $content = str_replace('<br>', '@#￥%<!---UIHH---!>@#￥%', $content);
        $content = str_replace('<br/>', '@#￥%<!---UIHH---!>@#￥%', $content);
        $content = str_replace('<p>', '@#￥%<!---UIHH---!>@#￥%', $content);
        $content = str_replace('</p>', '@#￥%<!---UIHH---!>@#￥%', $content);
        $content = preg_replace('/(@#￥%<!---UIHH---!>@#￥%)+/i', '\n', $content);//多个换行替换成一个
        $content = strip_tags($content);
        $return['attaches'] = $url_list;
        $return['content'] = $content;
        return $return;
    }
}
/**
 * 处理展示帖子内容
 */
if(!function_exists('deal_posts_show')){
    function deal_posts_show($content = '', $attaches = []){
        // 图片处理替换
        if(!empty($attaches)){
            $patterns = $attaches_path = [];
            foreach ($attaches as $k => $img) {
                $replace = '@#￥%(!---UIImageView---!)@#￥%';
                if( ($position = strpos($content,$replace) )!==false){ 
                    $leng = strlen($replace); 
                    $pex = substr($img, 0, 1);
                    if($pex != '/'){
                        $img = '/'.$img;
                    }
                    $content = substr_replace($content,'<br/><img src="'.$img.'" /><br/>',$position,$leng); 
                } 
            }
        }
        $content = str_replace('\n', '<br/>', $content);
        return $content;
    }
}

/**
 * 获取学校名称
 */
if(!function_exists('school_name')){
    function school_name($school_id = ''){
        $cache_name = 'school_name_'.$school_id;

        $school_name = Cache::get($cache_name, false);
        if(!$school_name){
            $where = [];
            $lists = Db::table('school')->where($where)->select();
            if($lists){
                foreach ($lists as $val) {
                    Cache::set('school_name_'.$val['school_id'], $val['school_name'], null, false);
                }
            }
             $school_name = Cache::get($cache_name, false);
        }
	return $school_name ? $school_name : '';
        return $school_name;
    }
}

/**
 * 字符串截取
 */
function truncate_utf8_string($string, $length, $etc = '...')
{
    $result = '';
    $string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'UTF-8');
    $strlen = strlen($string);
    for ($i = 0; (($i < $strlen) && ($length > 0)); $i++)
    {
        if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0'))
        {
            if ($length < 1.0)
            {
                break;
            }
            $result .= substr($string, $i, $number);
            $length -= 1.0;
            $i += $number - 1;
        }
        else
        {
            $result .= substr($string, $i, 1);
            $length -= 0.5;
        }
    }
    $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
    if ($i < $strlen)
    {
        $result .= $etc;
    }
    return $result;
}

/**
 * 明细列表账户 正负号
 // 流水分类（1：充值+；2：消费-；3：退款+；4：提现-；5：收入+;6：冻结-；7：解冻+；8：退款+；9：冻结金额扣除-；11：支付失败扣除-；12:补偿+）
 */
if(!function_exists('running_flag')){
    function running_flag($type = ''){
        switch ($type) {
            case 1:
                $flag = '+';
                break;
            case 2:
                $flag = '-';
                break;
            case 3:
                $flag = '+';
                break;
            case 4:
                $flag = '-';
                break;
            case 5:
                $flag = '+';
                break;
            case 6:
                $flag = '-';
                break;
            case 7:
                $flag = '+';
                break;
            case 8:
                $flag = '+';
                break;
            case 9:
                $flag = '-';
                break;
            case 11:
                $flag = '-';
                break;
            case 12:
                $flag = '+';
                break;
            default:
                $flag = '';
                break;
        }
        return $flag;
    }
}
