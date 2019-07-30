<?php
namespace Cdirect;
use Cmubu\Cmubu;
use GuzzleHttp\Exception\GuzzleException;

class Info{
    public static function get($name)
    {
        try{
            if(!$name) return [];
            $cCookiesKey = 'CMUBU_COOKIES';
            $cUserame = getenv('CMUBU_USERNAME');
            $cPassword = getenv('CMUBU_PASSWORD');
            $cCookies = getenv($cCookiesKey) ?: getTmpEnv($cCookiesKey);
            $cCookies = $cCookies ? json_decode($cCookies, true) : [];
            $cConfig = getenv('CMUBU_CONFIG');
            $config = [
                'username' => $cUserame,
                'password' => $cPassword,
                'cookies' => $cCookies,
            ];
            $cmubu = new Cmubu($config);
            $docInfo = $cmubu->docInfoByPath($cConfig);
            $content = $cmubu->docContent($docInfo['id']);
            $cookies = $cmubu->cookies();
            if($cookies) setTmpEnv($cCookiesKey, json_encode($cookies));
            $formatData = formatQuery($content);
            if(isset($formatData[$name])){
                $url = $formatData[$name]['URL'];
                $rules = json_decode($formatData[$name]['RULES'], true);
                $range = $formatData[$name]['RANGE'];
                $verify = __DIR__ . "/../cert/cacert.pem";
                $data = query($url, $rules, $range, [], ['verify' => $verify]);
            } else {
                $data = ['error' => "not found"];
            }
        } catch(\Exception $e){
            $data = ['error' => $e->getMessage()];
        } catch(GuzzleException $e) {
            $data = ['error' => $e->getMessage()];
        }
        return $data;
    }
}