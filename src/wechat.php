<?php

namespace muxin\wechat;

use muxin\wechat\server\WXBizMsgCrypt;

/**
 * 微信公众平台类
 */
class wechat {
    /**
     * 微信api接口 如果有变动可以重写
     *
     * @var string
     */
    public $api='https://api.weixin.qq.com';
    /**
     * Undocumented variable
     *
     * @var [type]
    */
    public $appid;
    /**
     * Undocumented variable
     *
     * @var [type]
    */
    public $secret;
    /**
     * Undocumented variable
     *
     * @var [type]
    */
    public $token;
    /**
     * Undocumented variable
     *
     * @var [type]
    */
    public $encodingAesKey;
    /**
     * Undocumented variable
     *
     * @var [type]
    */
    public $mode; //消息模式 0明文模式 1加密模式
    /**
     * 加密类变量
     *
     * @var WXBizMsgCrypt
    */
    public $pc;

    /**
     * 构造函数
     *
     * @param string $appid
     * @param string $secret
     * @param string $token
     * @param string $aeskey
     * @param array $post post数据数组 json xml form 表单转换后的数据
     * @param array $get get数据
     * @param integer $mode 消息模式 0明文模式 1加密模式
    */
    public function __construct($appid,$secret,$token,$aeskey,$mode=0,$post=[],$get=[]){

        $this->appid=$appid;
        $this->secret=$secret;
        $this->token=$token;
        $this->encodingAesKey=$aeskey;
        $this->mode=$mode;
        $this->post=$post;
        $this->get=$get;
    }
    /**
     * 析构函数
     */
    public function __destruct(){
        
    }

    /**
     * 启动服务端
     *
    * @return string
    */
    public function server(){

        if($this->checkSignature())
        {
            try {
                //明文模式
                if($this->mode == 0){
                    $data = $this->post;
                }else{
                    $this->pc = new WXBizMsgCrypt($this->token,$this->encodingAesKey,$this->appid);
                    $data = $this->decryptData($this->post);
                }
            } catch (\Throwable $th) {
                $data=null;
            }

            return $this->check_msg_type($data);
        }else{
            return '';
        }
    }
    /**
     * 从服务器获取新的 access_token
     *
     * @return array [ 'access_token'=>'access_token' , '']
     */
    public function accessToken(){
        $url = $this->api."/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $result = curl::get($url);
        if(!$result)
             return ['access_token'=>''];
        $result = json_decode($result, true);
        if(!$result || !isset($result['access_token']) )
            return ['access_token'=>''];
        return $result;
    }

    /**
     * 获取 access_token
     * @param mixed $name 一个没有意义的标识 主要是注解缓存区分
     * 如果要做缓存应该重写此类 使用 $this->accessToken()获取最新accessToken
     * @return string access_token
     */
    public function getAccessToken($name=''){
        $token = $this->accessToken();
        return $token['access_token'];
    }

    /**
     * 公众平台API接口请求
     * @param string $path api接口地址 /api/xxx
     * @param int $type 请求类型 0:get,1:post
     * @param array|string $data 要传递的数组 post默认对数组进行 Json_encode处理，如果要传递xml请直接传字符串
     * @return string
     */
    public function api($path,$type=0,$data=[]){
        $url=$this->api.$path.'?access_token='.$this->getAccessToken($this->appid);
        if($type){
            if( $data && is_array($data) )
                $data = json_encode($data,JSON_UNESCAPED_UNICODE);
            return curl::post($url,$data);
        }else{
            if($data && is_array($data))
            $url.='&'. http_build_query( $data );
            return curl::get($url);
        }
    }

    //验证服务器IP或信息有效性
    public function checkSignature(){
        if (empty($this->token))
            throw new \Exception('TOKEN 没有设置!');
        try {
            $signature = $this->get['signature'];
            $timestamp = $this->get['timestamp'];
            $nonce = $this->get["nonce"];
              $token = $this->token;
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );
        } catch (\Throwable $th) {
            return false;
        }
        if( $tmpStr == $signature )
            return true;
        else
            return false;
    }

    //接收加密信息
    public function decryptData($data){
        $encrypt = $data['Encrypt'];
        $ToUserName = $data['ToUserName'];
        $msg_sign=$this->get['msg_signature'];
        $timeStamp=$this->get['timestamp'];
        $nonce=$this->get['nonce'];
        $format = "<xml><ToUserName><![CDATA[%s]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $ToUserName , $encrypt);
        $msg = '';
        $errCode = $this->pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0) {
            try {
                $xml_parser = xml_parser_create();
                if(!xml_parse($xml_parser,$msg,true)){
                    $data =  json_decode($msg);
                }else{
                    $data =  simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                }
                xml_parser_free($xml_parser);
                if($data){
                    return $data;
                }
            } catch (\Exception $e) {
                return false;
            }

        }
        return false;
    }

    /**
     * 获取加密后的数据
     *
     * @param string $xml
     * @return void
    */
    public function get_send_string($xml){
        if($this->mode > 0){
            $xmlb=null;
            $timeStamp=$this->get['timestamp'];
            $nonce=$this->get['nonce'];
            if($this->pc->encryptMsg($xml,$timeStamp,$nonce,$xmlb) == 0)
                $xml=$xmlb;
        }
        return $xml;
    }
    
    /**
     * 处理服务器事件路由
     *
     * @param object $data
     * @return string
    */
    public function check_msg_type($data){
        try {
            //路由函数名生成
            $function_name = 'msg_'.$data->MsgType;

            //事件推送路由函数名
            if( $data->MsgType == 'event' )
                $function_name = 'event_'.$data->Event;
            
            //判断函数是否定义
            if(! method_exists($this,$function_name))
                $function_name = "msg_default";
        } catch (\Throwable $th) {
            $function_name = "msg_default";
        }

        
        //调用相应处理函数
        return call_user_func(array($this,$function_name),$data);

    }


    //消息默认处理函数
    public function msg_default ($data){
        //服务器验证
        try {
            $echoStr = $this->get["echostr"];
        } catch (\Throwable $th) {
            $echoStr='';
        }
        return $echoStr;
    }
    
    //文本信息 text
    public function msg_text($data){
        /*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $信息内容 = $data->Content;
        $信息ID = $data->MsgId;


        //$回复内容 ='您发送的信息：“'.$信息内容;
        $this->ret_text($用户ID,$开发者,$回复内容);
        */
    }

    //image 图片消息 通过 图片ID 可调用多媒体文件下载接口提取图片
    public function msg_image($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $图片链接 = $data->PicUrl;
        $图片ID = $data->MediaId;
        $信息ID = $data->MsgId;
        $回复内容 = "您向我发送了图片。 \r\n图片地址:".$图片链接;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //语音消息 voice 通过 语音ID 可调用多媒体文件下载接口提取音频
    public function msg_voice($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;    
        $语音ID = $data->MediaId;
        $语音格式 = $data->Format;
        $语音结果 = $data->Recognition;
        $信息ID = $data->MsgId;
        
        
        $回复内容 = "您发送了语音 \r\n语音内容：".$语音结果;
        $this->ret_text($用户ID,$开发者,$回复内容);
        */
    }              

    //视频消息 video  可通过ID 调用多媒体下载接口提取数据
    public function msg_video($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $视频ID = $data->MediaId;
        $视频缩略图ID = $data->ThumbMediaId;
        $信息ID = $data->MsgId;
    
        $回复内容 = "您发送了视频";
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }                    

    //小视频消息 shortvideo  可通过ID 调用多媒体下载接口提取数据
    public function msg_shortvideo($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $视频ID = $XML对象->MediaId;
        $视频缩略图ID = $XML对象->ThumbMediaId;
        $信息ID = $XML对象->MsgId;

        $回复内容 = "您发送了小视频！";
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //地理位置消息推送 location
    public function msg_location($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        
        $地理位置维度 = $data->Location_X;
        $地理位置经度 = $data->Location_Y;
        $地图缩放大小 = $data->Scale;
        $地图位置信息 = $data->Label;
        $信息ID = $data->MsgId;


        $回复内容 = "您发送了地理位置！ \r\n维度:".$地理位置维度." \r\n经度:".$地理位置经度." \r\n缩放:".$地图缩放大小." \r\n位置信息:".$地图位置信息;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }                  

    //链接消息 link
    public function msg_link($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;

        $连接标题 = $data->Title;
        $连接描述 = $data->Description;
        $连接URL = $data->Url;
        $信息ID = $data->MsgId;
        
        $回复内容 = "您发送了连接！\r\n连接标题：".$连接标题." \r\n连接描述：".$连接描述."\r\nURL：".$连接URL;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //subscribe(订阅事件)
    public function event_subscribe($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
    
        $事件KEY值 = $data->EventKey;
        $二维码ticket = $data->Ticket;

        if($事件KEY值 != ""){
            //如果事件KEY不等于空 说明用户是扫描参数二维码关注的
            $回复内容 = "感谢您的关注！微信平台开发中，更多功能敬请期待！(您是通过扫描二维码关注)\r\n二维码值：".$事件KEY值;
            $this->ret_text($用户ID,$开发者,$回复内容);
            $time=time();
            $file=fopen("dy.txt","a+");
            fwrite($file,"[ 开发者:".$开发者." 用户:".$用户ID." 订阅！时间:".date("Y-m-d H:i:s",$time+28800)." ]\r\n");
            fclose($file);

        }else{
            //否者用户是扫描普通二维码或者其他方式关注的
            $回复内容 = "感谢您的关注！微信平台开发中，更多功能敬请期待！";
            $this->ret_text($用户ID,$开发者,$回复内容);
            $time=time(); 
            $file=fopen("dy.txt","a+");
            fwrite($file,"[ 开发者:".$开发者." 用户:".$用户ID." 订阅！时间:".date("Y-m-d H:i:s",$time+28800)." ]\r\n");
            fclose($file);
        }*/
    }

    //unsubscribe(取消订阅)
    public function event_unsubscribe($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;

        $time=time();
        $file=fopen("qxdy.txt","a+");
        fwrite($file,"[ 开发者:".$开发者." 用户:".$用户ID." 取消订阅！时间:".date("Y-m-d H:i:s",$time+28800)." ]\r\n");
        fclose($file);*/
    }

    //已关注的用户扫描二维码 SCAN
    public function event_SCAN($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $事件KEY值 = $data->EventKey;
        $二维码ticket = $data->Ticket;

        $回复内容 = "您扫描了二维码！二维码KEY：".$事件KEY值;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }
    
    //未关注的用户扫码二维码 二维码2 scancode_push
    public function event_scancode_push ($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $事件KEY值 = $data->EventKey;
        $二维码ticket = $data->Ticket;

        $回复内容 = "您扫描了二维码！二维码KEY：".$事件KEY值;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }
    
    //二维码3 scancode_waitmsg
    public function event_scancode_waitmsg ($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $事件KEY值 = $data->EventKey;
        $二维码ticket = $data->Ticket;

        $回复内容 = "您扫描了二维码！二维码KEY：".$事件KEY值;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //上报地理位置 LOCATION
    public function event_LOCATION($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $坐标维度 = $data->Latitude;
        $坐标经度 = $data->Longitude;
        $坐标精度 = $data->Precision;
        $time=time();
        $file=fopen("zb.txt","a+");
        fwrite($file,"用户:".$用户ID." 时间:".date("Y-m-d H:i:s",$time+28800)." 维度:".$坐标维度." 经度:".$坐标经度." 精度:".$坐标精度."]\r\n");
        fclose($file);
        $回复内容 = "您上报了地理位置！\r\n维度:".$坐标维度." \r\n经度:".$坐标经度." \r\n精度:".$坐标精度;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //自定义菜单-点击 CLICK 事件KEY 与自定义菜单接口中的KEY值对应
    public function event_CLICK($data){/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;

        $事件KEY值 = $data->EventKey;

        $回复内容 = "您点击了自定义菜单按钮！ 按钮KEY:".$事件KEY值;
        $this->ret_text($用户ID,$开发者,$回复内容);*/
    }

    //自定义菜单-跳转 VIEW  事件KEY 与设置的跳转URL对应
    public function event_VIEW($data) {/*
        $开发者 = $data->ToUserName;
        $用户ID = $data->FromUserName;
        $信息创建时间 = $data->CreateTime;
        $事件KEY值 = $data->EventKey;
        //自定义菜单无法回复信息
        //$回复内容 = "您点击了自定义菜单按钮！按钮连接：";
        //$this->ret_text($用户ID,$开发者,$回复内容);

        $time=time();
        $file=fopen("zdycdtz.txt","a+");
        fwrite($file,"[用户:".$用户ID." 时间:".date("Y-m-d H:i:s",$time+28800)." URL：".$事件KEY值."]\r\n");
        fclose($file);*/
    }
                     
    public function ret_text($用户,$开发者,$文本内容){
        $文本消息模板 = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>"; 
        $时间 = time();
        $类型 = "text";
        $回复 = sprintf($文本消息模板, $用户, $开发者, $时间, $类型, $文本内容);
        $this->get_send_string($回复);
    }

    public function ret_image($用户,$开发者,$图片ID){
        $图片消息模板 = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType><![CDATA[image]]></MsgType>
        <Image>
        <MediaId><![CDATA[media_id]]></MediaId>
        </Image>
        </xml>";
        $时间 = time();
        $类型 = "image";
        $回复 = sprintf($图片消息模板, $用户, $开发者, $时间, $类型, $图片ID);
        $this->get_send_string($回复);

    }

    public function ret_voice($用户,$开发者,$语音ID){
        $语音消息模板 = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType><![CDATA[voice]]></MsgType>
        <Voice>
        <MediaId><![CDATA[media_id]]></MediaId>
        </Voice>
        </xml>";

        $时间 = time();
        $类型 = "voice";
        $回复 = sprintf($语音消息模板, $用户, $开发者, $时间, $类型,$语音ID);
        $this->get_send_string($回复);
    }

    public function ret_voide($用户,$开发者,$视频ID,$标题,$描述){
        $视频消息模板 = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType><![CDATA[video]]></MsgType>
        <Video>
        <MediaId><![CDATA[media_id]]></MediaId>
        <Title><![CDATA[title]]></Title>
        <Description><![CDATA[description]]></Description>
        </Video> 
        </xml>";

        $时间 = time();
        $类型 = "video";
        $回复 = sprintf($视频消息模板, $用户, $开发者, $时间, $类型, $视频ID,$标题,$描述);
        $this->get_send_string($回复);
    }

    public function ret_music($用户,$开发者,$标题,$描述,$连接,$高品质,$缩略图ID){
        $音乐消息模板 = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType><![CDATA[music]]></MsgType>
        <Music>
        <Title><![CDATA[TITLE]]></Title>
        <Description><![CDATA[DESCRIPTION]]></Description>
        <MusicUrl><![CDATA[MUSIC_Url]]></MusicUrl>
        <HQMusicUrl><![CDATA[HQ_MUSIC_Url]]></HQMusicUrl>
        <ThumbMediaId><![CDATA[media_id]]></ThumbMediaId>
        </Music>
        </xml>";

        $时间 = time();
        $类型 = "music";
        $回复 = sprintf($音乐消息模板, $用户, $开发者, $时间, $类型, $标题,$描述,$连接,$高品质,$缩略图ID);
        $this->get_send_string($回复);
    }

    public function ret_news($用户,$开发者,$个数,$信息,$标题,$描述,$图片连接,$跳转){
        $图文消息模板 = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>12345678</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>2</ArticleCount>
        <Articles>
        <item>
        <Title><![CDATA[title1]]></Title> 
        <Description><![CDATA[description1]]></Description>
        <PicUrl><![CDATA[picurl]]></PicUrl>
        <Url><![CDATA[url]]></Url>
        </item>
        <item>
        <Title><![CDATA[title]]></Title>
        <Description><![CDATA[description]]></Description>
        <PicUrl><![CDATA[picurl]]></PicUrl>
        <Url><![CDATA[url]]></Url>
        </item>
        </Articles>
        </xml>";

        $时间 = time();
        $类型 = "news";
        $回复 = sprintf($图文消息模板, $用户, $开发者, $时间, $类型,$个数,$信息,$标题,$描述,$图片连接,$跳转);
        $this->get_send_string($回复);
    }
    
    //获取随机字符串
    public function get_str($size=16){

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < $size; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}


?>