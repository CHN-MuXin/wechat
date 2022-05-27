***

沐心 PHP 模块简介
---


因为经常开发项目的需求，很多项目又不能二次开发，所以才想把一些常用的功能封装起来以加快开发速度。



## 联系我


QQ：285169134


Email：muxin@ph233.cn


***


<h2 id="menu">目录</h2>


* [项目安装](#install)
* [微信类](#wx_class)
    * [微信公众号类](#wx_mp)
        * [微信公众号后台服务器类](#wx_mp_server)
        * [微信公众平台api调用](#wx_mp_api)
* [许可](#license)

***









<h3 id="install">- 项目安装</h3>

```bash
composer require muxin/wechat
```

<h3 id="wx_mp_server">- 微信公众号后台服务器</h3>


**index.php**
```php
<?php

    //继承公众号类实现自己的功能
    class test extends \Muxin\weixin\wechat{
        //重载 接收文本消息
        public function msg_text($data){
            //服务器下发的信息
            $开发者 = $data->ToUserName;
            $用户ID = $data->FromUserName;
            $信息创建时间 = $data->CreateTime;
            $信息内容 = $data->Content;
            $信息ID = $data->MsgId;
            
            /*
                在这里添加您的代码
            
            */
            
            
            //回复给用户的信息
            $回复内容='您给我发了信息：'.$信息内容;

            //调用回复文本消息函数回复用户信息
            return $this->ret_text($用户ID,$开发者,$回复内容);
        }

        //重载 接收用户关注事件
        public function event_subscribe($data){
            $开发者ID = $data->ToUserName;
            $用户ID = $data->FromUserName;
            $信息创建时间 = $data->CreateTime;
            $事件KEY值 = $data->EventKey;
            $二维码ticket = $data->Ticket;
            
            /*
                您的代码
            */
            
            
            $ret_text = "感谢您的关注";
            return $this->ret_text($开发者ID,$用户ID,$ret_text);

        }
        
        //重载 接收用户取消关注事件
        public function event_unsubscribe($data){
            $开发者ID = $data->ToUserName;
            $用户ID = $data->FromUserName;
            $信息创建时间 = $data->CreateTime;
            
            /*
                您的代码
            */
        }
        
    }
    

    //配置公众号参数
    $config=array(
        'APPID'=>'公众号的APPID',
        'AppSecret'=>'公众号的AppSecret',
        'TOKEN'=>'公众号TOKEN',
        'encodingAesKey'=>'公众号encodingAesKey',
    );
    
    //实例化业务对象
    $t = new test($config['APPID'],$config['TOKEN'],$config['encodingAesKey'],1);

    // 启动服务器，并返回或者输出数据
    return $t->server();
    // echo $t->server();

?>

```




[返回目录](#menu)

***


<h3 id="wx_mp_api">- 微信公众平台api调用</h3>

```php
<?php

    //继承公众号类实现自己的功能
    class test extends \Muxin\weixin\wechat{
        
    }

    //配置公众号参数
    $config=array(
        'APPID'=>'公众号的APPID',
        'AppSecret'=>'公众号的AppSecret',
        'TOKEN'=>'公众号TOKEN',
        'encodingAesKey'=>'公众号encodingAesKey',
    );
    
    //实例化业务对象
    $t = new test($config['APPID'],$config['TOKEN'],$config['encodingAesKey'],1);
    

    //获取用户列表
    $data=[
        'next_openid' => 0, //第一个拉取的OPENID，不填默认从头开始拉取
    ];
    
    $r = $t->api('/cgi-bin/user/get',0,$data);
    var_dump($r);

    //
    $data=[
        'user_list' =>[
            [
                "openid" => "otvxTs4dckWG7imySrJd6jSi0CWE", 
                "lang" => "zh_CN"
            ],
            [
                "openid" => "otvxTs4dckWG7imySrJd6jSi0CWE", 
                "lang" => "zh_CN"
            ],
        ]
    ];
    //获取用户基本信息
    $r = $t->api('/cgi-bin/user/info/batchget',1,$data);
    var_dump($r);


```

[返回目录](#menu)

***


<h3 id="license">- 许可</h3>
MIT License

[返回目录](#menu)