<?php
namespace Huoyan\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

use Throwable;

class Services
{
    /**
     * @var mixed|string
     */
    private  $access_key;
    /**
     * @var mixed|string
     */
    private  $access_secret;
    /**
     * @var mixed|string
     */
    private  $baseUrl;
    private  $client;
    private $access_token;
    /**
     * @var mixed|string
     */
    private $controller;

    public function __construct(array $config)
    {
        $this->baseUrl=$config['api_url']??'';
        $this->client = new Client([
            'base_uri'=>$this->baseUrl,
            'timeout' => $config ['timeout']??3,
        ]);

        $this->access_key=$config['access_key']??'';
        $this->access_secret=$config['access_secret']??'';
        $this->controller=$config['controller']??'';


        $this->access_token=$this->getAccessToken();


    }

    private function getAccessToken()
    {
        $token = Redis::get('huoyan_services:' . $this->access_key.':'.'access_token');
        if(!empty($token)){
            return $token;
        }
        $auth_data=$this->sendPost('api/auth/login',[
            'headers' => ['Content-Type' => 'application/json'],'json'=>[
                'appid'=>$this->access_key,
                'password'=>$this->access_secret,
            ]]);


        if(!empty($auth_data['code']) && $auth_data['code']=='200'){
            Redis::set('huoyan_services:' . $this->access_key.':'.'access_token',$auth_data['data']['access_token']);
            Redis::expire('huoyan_services:' . $this->access_key.':'.'access_token',$auth_data['data']['expires_in']);
            return $auth_data['data']['access_token'];
        }else{
            return false;
        }



    }


    //魔术方法，直接调用基类里有的方法
    public function __call($method, $args)
    {

        if(empty($this->access_token)){
            return return_huoyan_data('token获取失败，请检查配置文件','403',[]);
        }
        $method=huoyan_uncamelize($method);
        if(empty(array_column($args,'send_type'))){
            $send_type=ucwords('post');
            $send_to_function='send'.$send_type;
        }else{
            $send_type=ucwords(array_column($args,'send_type')[0]);
            $send_to_function='send'.$send_type;
            unset($args[0]['send_type']);
        }
        if(!in_array($send_type,['Post','Get'])){
            return return_huoyan_data('发送方式错误','504',[]);
        }


        unset($args['send_type']);
        return $this->$send_to_function("api/$this->controller/$method",[
            'headers' => ['Content-Type' => 'application/json','Authorization'=>$this->access_token],'json'=>$args]);

    }


    private function sendPost($url='',$data=[]){
        return $this->send($url,$data,'post');
    }
    private function sendGet($url='',$data=[]){
        return $this->send($url,$data,'get');
    }

    private function send($url='',$data=[],$type='post'){

        if($type=='get'){
            $params=array_to_huoyan_url_prarm($data);
            $url.='&'.$params;
        }

        try{
            $response=$this->client->$type($url,$data)->getBody();

            $bodyStr = (string)$response;

            $info= json_decode($bodyStr,true);
        }catch(throwable $e){
            $return['errcode']=-1;
            $return['errmsg']=$e->getMessage();

            return return_huoyan_data('访问失败','503',$return);
        }
        return $info;

    }




}