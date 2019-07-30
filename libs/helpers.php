<?php
function response($data)
{
    $data = is_array($data) ? json_encode($data) : $data;
    exit($data);
}

function query($url, $rules, $range, $param = [], $options = [])
{
    try{
        $data = (new QL\QueryList)->get($url, $param, $options)->rules($rules)->range($range)->query()->getData();
    } catch(\Exception $e){
        $data = ['error' => $e->getMessage()];
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
                    $temp[$content['text']] = fliter(htmlspecialchars_decode(fliter(fliter($content['note'],'htmltag'))), '>');
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

function setTmpEnv($name, $value)
{
    $file = __DIR__.'/../tmp/env.json';
    if(!is_file($file)){
        $fp = fopen($file, 'w');
        fclose($fp);
    }
    $content = file_get_contents($file);
    $data = (array)json_decode($content, true);
    $data[$name] = $value;
    file_put_contents($file, json_encode($data));
    return true;
}

function getTmpEnv($name='')
{
    $file = __DIR__.'/../tmp/env.json';
    if(!is_file($file)){
        $fp = fopen($file, 'w');
        fclose($fp);
    }
    $content = file_get_contents($file);
    $data = (array)json_decode($content, true);
    if($name){
        if(isset($data[$name])) return $data[$name];
        return [];
    } else {
        return $data;
    }
}