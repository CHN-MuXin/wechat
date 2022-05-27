<?php

namespace muxin\wechat;


/**
 * curl class
 *
 * 封装CURL请求
 */
class curl
{

    /**
     * 使用 pem证书发送 post请求
     *
     * @param string $url
     * @param string $body
     * @param integer $second
     * @param array $Headers
     * @param boolean|string|array $ca false|str_file_path|[str_cert_path,str_key_path]
     * @return void
     */
    public static function post_pem($url, $body, $second=30,$Headers=[],$ca=false){
        $ch = curl_init();

        $option=[];
        $option[CURLOPT_TIMEOUT]= $second;
        $option[CURLOPT_RETURNTRANSFER]= 1;
        if( count($Headers) >= 1 )
            $option[CURLOPT_HTTPHEADER]= $Headers;
        if($ca){
            if( is_array($ca) ){
                //第一种方法，cert 与 key 分别属于两个.pem文件
                //默认格式为PEM，可以注释
                $option[CURLOPT_SSLCERTTYPE]='PEM';
                $option[CURLOPT_SSLCERT]=$ca[0];
                $option[CURLOPT_SSLKEYTYPE]='PEM';
                $option[CURLOPT_SSLKEY]=$ca[1];

            }else{
                //第二种方式，两个文件合成一个.pem文件
                $option[CURLOPT_SSLCERT]=$ca;
            }
        }
        if(stripos($url,"https://")!==FALSE){
            $option[CURLOPT_SSL_VERIFYPEER]=false;
            $option[CURLOPT_SSL_VERIFYHOST]=false;
        }

        $data = curl_exec($ch);
        $Status = curl_getinfo($ch);
        $ret = self::post_raw($url,$body,$option);
        if( intval($ret[0]["http_code"])==200 )
            return $ret[1];
        else
            return false;	
    }


    /**
     * 比较友好的 curl post 请求 
     *
     * @param $url 请求地址
     * @param string $body post数据
     * @param array $headers 自定义头
     * @param array $cookie cookie信息
     * @param int $timeout 超时时间
     * @param array $options curl_setopt 参数 [ CURLOPT_XXX => value ]
     * @return bool|string
     */
    public static function post($url,$body='',$headers=[],$cookie=[],$timeout=5,$options=[]){
        $options[CURLOPT_POST]=1;
        $options[CURLOPT_RETURNTRANSFER]=1;
        $options[CURLOPT_POSTFIELDS]=$body;
        $option[CURLOPT_TIMEOUT]= $timeout;
        if( count($headers) >= 1 )
            $option[CURLOPT_HTTPHEADER]= $headers;
        if( count($cookie) >= 1 )
            $option[CURLOPT_COOKIE]= $cookie;
        $ret = self::raw($url,$options);
        if( intval($ret[0]["http_code"])==200 )
            return $ret[1];
        else
            return false;	
    }

    /**
     * 比较友好的 curl get 请求
     *
     * @param $url 请求地址
     * @param array $headers 自定义头
     * @param array $cookie cookie信息
     * @param int $timeout 超时时间
     * @param array $options curl_setopt 参数 [ CURLOPT_XXX => value ]
     * @return bool|string
     */
    public static function get($url,$headers=[],$cookie=[],$timeout=5,$options=[]){
        $options[CURLOPT_HEADER]=1;
        $options[CURLOPT_RETURNTRANSFER]=1;
        $option[CURLOPT_TIMEOUT]= $timeout;
        if( count($headers) >= 1 )
            $option[CURLOPT_HTTPHEADER]= $headers;
        if( count($cookie) >= 1 )
            $option[CURLOPT_COOKIE]= $cookie;
        $ret = self::raw($url,$options);
        if( intval($ret[0]["http_code"])==200 )
            return $ret[1];
        else
            return false;	
    }

    /**
     * 基本是原生的 curl post 请求
     *
     * @param string $url 请求地址
     * @param string $body 请求数据
     * @param array $options curl_setopt 参数 [ CURLOPT_XXX => value ]
     * @return void
     */
    public static function post_raw($url,$body='',$options=[]){
        $options[CURLOPT_POST]=1;
        $options[CURLOPT_RETURNTRANSFER]=1;
        $options[CURLOPT_POSTFIELDS]=$body;
        return self::raw($url,$options);
    }

    /**
     * 基本是原生的 curl post 请求
     *
     * @param [type] $url
     * @param array $options curl_setopt 参数 [ CURLOPT_XXX => value ]
     * @return void
     */
    public static function get_raw($url,$options=[]){
        $options[CURLOPT_HEADER]=1;
        $options[CURLOPT_RETURNTRANSFER]=1;
        return self::raw($url,$options);
    }

    /**
     * 基本是原生的 curl 请求
     *
     * @param [type] $url
     * @param array $options curl_setopt 参数 [ CURLOPT_XXX => value ]
     * @return void
     */
    public static function raw($url,$options=[]){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        foreach ($options as $key => $value) {
            curl_setopt($ch,$key,$value);
        }
        $data = curl_exec($ch);
        $Status = curl_getinfo($ch);
        curl_close($ch);
        return [$Status,$data];
    }
}
