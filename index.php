<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$request_scheme = $_SERVER['REQUEST_SCHEME'];
$request = $_REQUEST;

$dotenv = Dotenv::create(__DIR__,'.env');
$dotenv->load();

preg_match_all('/\/([\w:]+)/i', $request_uri, $matches);
$params = isset($matches[1]) ? $matches[1] : '';
$type = isset($params[0]) ? $params[0] : '';
$types = ['telegram'];
if(!in_array($type, $types)) response(['ok' => false, 'error_code' => 500, 'description' => 'invalid request']);

$client = new Client();
switch($type){
    case 'telegram': //Telegram
        $url = 'https://api.telegram.org';
        $data = ($request_method == 'GET') ? $_GET : $_POST;
        $token = isset($params[1]) ? $params[1] : '';
        $method = isset($params[2]) ? $params[2] : '';
        if($token && $method){
            $url .= "/bot{$token}/{$method}";
            unset($data['token']);
            unset($data['method']);
        } else{
            $response = ['ok' => false, 'error_code' => 404, 'description' => 'need token and method'];
            break;
        }
        try{
            $verify = __DIR__ . "/cert/cacert.pem";
            $timeout = getenv('TELEGRAM_TIMEOUT') ?: 10;
            $options = ['verify' => $verify, 'timeout' => $timeout, 'query' => $data];
            $proxy = getenv('TELEGRAM_PROXY');
            if($proxy) $options['proxy'] = $proxy;
            $res = $client->request($request_method, $url, $options);
            $response = $res->getBody()->getContents();
        } catch(GuzzleException $e){
            $response = $e->getResponse()->getBody()->getContents();
            if(!$response){
                $response = ['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()];
            }
        }
        break;
    default:
        $response = [
            'ok' => false,
            'error_code' => 404,
            'description' => 'not found'
        ];
        break;
}

response($response);