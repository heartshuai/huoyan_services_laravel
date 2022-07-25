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

    public function __construct(array $config)
    {
        $this->baseUrl=$config['api_url']??'';
        $this->client = new Client([
            'base_uri'=>$this->baseUrl,
            'timeout' => $config ['timeout']??3,
        ]);

        $this->access_key=$config['access_key']??'';
        $this->access_secret=$config['access_secret']??'';


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


        if($auth_data['code']=='200'){
            Redis::set('huoyan_services:' . $this->access_key.':'.'access_token',$auth_data['data']['access_token']);
            Redis::expire('huoyan_services:' . $this->access_key.':'.'access_token',$auth_data['data']['expires_in']);
            return $auth_data['data']['access_token'];
        }



    }


    //魔术方法，直接调用基类里有的方法
    public function __call($method, $args)
    {


        $method=uncamelize($method);
        $send_type=ucwords($args['send_type']??'post');
        if(!in_array($send_type,['Post','Get'])){
            return return_data('发送方式错误','504',[]);
        }
        $send_to_function='send'.$send_type;
        unset($args['send_type']);
        return $this->$send_to_function("api/license/$method",[
            'headers' => ['Content-Type' => 'application/json'],'json'=>$args]);

    }


    private function sendPost($url='',$data=[]){
        return $this->send($url,$data,'post');
    }
    private function sendGet($url='',$data=[]){
        return $this->send($url,$data,'get');
    }

    private function send($url='',$data=[],$type='post'){

        if($type=='get'){
            $params=array_to_url_prarm($data);
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