<?php
namespace Cdirect;

class Info{
    private static $error = '';
    private static $errorCode = 200;
    private static $request = [];
    private static $param = [];
    private static $apiKeys = ['sspai'];
    private static $ttl = 600;
    private static $configFile;

    public static function init()
    {
        self::$ttl = getenv('CONFIG_TTL_INFO');
        self::$configFile = getenv('CONFIG_FILE_INFO');
    }

    public static function get($key, $request=[])
    {
        self::init();
        if(!$key) {
            $ttl = self::$ttl;
            $configFile = self::$configFile;
            $formatData = mubuConfig($configFile, 0, $ttl);
            $keys = array_keys($formatData);
            $data = array_merge($keys, self::$apiKeys);
            return $data;
        }
        if(in_array($key, self::$apiKeys)){
            return self::getApi($key, $request);
        } else {
            return self::getQueryList($key, $request);
        }
    }


    public static function getApi($key, $request=[])
    {
        $data = [];
        return $data;
    }

    public static function getQueryList($key, $request=[])
    {
        $cache = isset($request['cache'])&&in_array($request['cache'], [0, 1]) ? $request['cache'] : 1; // 0 实时获取更新缓存， 1 获取缓存
        $cache_pre = 'info_';
        self::$request = $request;

        try{
            $ttl = self::$ttl;
            $configFile = self::$configFile;
            $formatData = mubuConfig($configFile, $cache, $ttl);

            if(!$key) return array_keys($formatData);

            $queryName = self::keyDeal($key, $request);
            $queryNames = is_array($queryName) ? $queryName : [$queryName];
            $data = [];
            foreach($queryNames as $index => $queryName){
                if(!isset($formatData[$queryName])){
                   unset($queryNames[$index]);
                }
            }
            if(count($queryNames) == 0) return error('not found', 404);

            asort(self::$param);
            $cacheFileName = $cache_pre.implode('_', $queryNames);
            if(count(self::$param) > 0) $cacheFileName .= '_'.implode('_', self::$param);
            $cacheFileName .='.json';

            if($cache){
                $data = getTmp('', $cacheFileName);
                if($data) return $data;
            }

            foreach($queryNames as $index => $queryName){
                $url = $formatData[$queryName]['URL'];
                $url = self::urlDeal($queryName, $request, $url);
                $rules = json_decode($formatData[$queryName]['RULES'], true);
                $range = $formatData[$queryName]['RANGE'];
                $verify = __DIR__ . "/../cert/cacert.pem";
                $temp = query($url, $rules, $range, [], ['verify' => $verify]);
                if(isset($temp['error'])) {
                    self::$error = $temp['error'];
                    self::$errorCode = $temp['errorCode'];
                    break;
                }
                $data[] = $temp;
            }
            if(count($queryNames) == 1) $data = $data[0];
            if(self::$error){
                $data = error(self::$error, self::$errorCode);
            } else {
                $data = self::dataDeal($key, $request, $data);
                setTmp('', $data, $cacheFileName, $ttl);
            }
        } catch(\Exception $e){
            $data = error($e->getMessage(), $e->getCode());
        }
        return $data;
    }

    public static function keyDeal($key, $request)
    {
        $user = isset($request['user']) ? $request['user'] : ''; //用户
        $ver = isset($request['ver']) ? $request['ver'] : ''; //版本
        switch($key){
            case 'v2ex':
                if($user) {
                    $key = $key . '-user';
                    self::$param['user'] = $user;
                }
                break;
            case 'php':
                $vers = [5,7];
                if($ver && in_array($ver, $vers)){
                    $key = $key.'-'.$ver;
                } else {
                    $tKey = $key;
                    $key = [];
                    foreach($vers as $ver){
                        $key[] = $tKey.'-'.$ver;
                    }
                }
                break;
        }
        if(is_array($key)) asort($key);
        return $key;
    }

    public static function dataDeal($key, $request, $data)
    {
        return $data;
    }

    public static function urlDeal($key, $request, $url)
    {
        $user = isset($request['user']) ? $request['user'] : ''; //用户
        $ver = isset($request['ver']) ? $request['ver'] : ''; //版本
        switch($key){
            case 'v2ex-user':
                $url = $url.'/'.$user;
                break;
        }
        return $url;
    }
}