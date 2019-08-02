<?php

use Cmubu\Cmubu;

function response($data)
{
    $res = ['error' => '', 'errorCode' => 200];
    if(isset($data['error'])){
        $res['error'] = $data['error'];
        unset($data['error']);
    }
    if($res['error']){
        if(isset($data['errorCode'])){
            $res['errorCode'] = $data['errorCode'];
            unset($data['errorCode']);
        } else {
            $res['errorCode'] = 500;
        }
    }
    $res['data'] = isset($data['data']) ? $data['data'] : $data;
    $httpCode = intval($res['errorCode']);
    $httpCode = ($httpCode >= 100 && $httpCode <=599) ? $httpCode : 500;
    http_response_code($httpCode);
    header("Content-Type: application/json");
    exit(json_encode($res));
}

function error($error, $errorCode)
{
    return ['error' => $error, 'errorCode' => $errorCode];
}

function _error($error, $errorCode)
{
    throw new \Exception($error, $errorCode);
}

function query($url, $rules, $range, $param = [], $options = [])
{
    try{
        $data = (new QL\QueryList)->get($url, $param, $options)->rules($rules)->range($range)->query()->getData();
    } catch(\Exception $e){
        $data = error($e->getMessage(), $e->getCode());
    }
    return $data;
}

function formatQuery($data, $type = 1)
{
    $res = [];
    switch($type){
        default:
            foreach($data as $key => $item){
                $temp = [];
                foreach($item['children'] as $content){
                    $temp[$content['text']] = (isset($content['note']) && $content['note']) ? fliter(htmlspecialchars_decode(fliter(fliter($content['note'],'htmltag'))), '>') : '';
                }
                $res[$item['text']] = $temp;
            }
            break;
    }
    return $res;
}

function fliter($str, $type = 1)
{
    switch($type){
        case 'htmltag':
            $str = preg_replace('/<[^>]*>/', '', $str);
            break;
        case '>':
            $str = preg_replace('/>/', ' > ', $str);
            break;
        default:
            $str = preg_replace('/\s/', '', $str);
            break;
    }
    return $str;
}

function setTmp($name, $value, $file='env.json', $ttl=0)
{
    $expire = $ttl ? time()+intval($ttl) : 0;

    $file = __DIR__.'/../tmp/'.$file;
    if(!is_file($file)){
        $fp = fopen($file, 'r+');
        fclose($fp);
    }
    if($name) {
        $content = file_get_contents($file);
        $data = (array)json_decode($content, true);
        $data[$name] = $value;
    } else {
        $data = $value;
    }
    $content = [
        'expire' => $expire,
        'content' => $data,
    ];
    file_put_contents($file, json_encode($content));
    return true;
}

function getTmp($name='', $file='env.json')
{
    $file = __DIR__.'/../tmp/'.$file;
    if(!is_file($file)){
        $fp = fopen($file, 'r+');
        fclose($fp);
    }
    $content = file_get_contents($file);
    $content = (array)json_decode($content, true);
    if(isset($content['expire']) && $content['expire'] > 0){
        if(time() > $content['expire']) return [];
    }
    $data = isset($content['content']) ? $content['content'] : [];
    if($name){
        if(isset($data[$name])) return $data[$name];
        return [];
    } else {
        return $data;
    }
}

function mubuConfig($file, $cache=0, $ttl=0)
{
    $cacheName = 'mubu_config_'.md5($file).'.json';
    if($cache){
        $data = getTmp('', $cacheName);
        if($data) return $data;
    }
    $cCookiesKey = 'CMUBU_COOKIES'; //获取配置
    $cUserame = getenv('CMUBU_USERNAME');
    $cPassword = getenv('CMUBU_PASSWORD');
    $cCookies = getenv($cCookiesKey) ?: getTmp($cCookiesKey);
    $cCookies = $cCookies ? json_decode($cCookies, true) : [];
    $config = [
        'username' => $cUserame,
        'password' => $cPassword,
        'cookies'  => $cCookies,
    ];
    $cmubu = new Cmubu($config);
    $docInfo = $cmubu->docInfoByPath($file);
    $content = $cmubu->docContent($docInfo['id']);
    $cookies = $cmubu->cookies();
    if($cookies) setTmp($cCookiesKey, json_encode($cookies));
    $data = formatQuery($content);
    setTmp('', $data, $cacheName, $ttl); //缓存配置
    return $data;
}