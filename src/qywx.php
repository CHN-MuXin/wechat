<?php

namespace muxin\wechat;


/**
 * qywx class
 *
 * 封装企业微信回调
 */
class qywx
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $corpId;
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
     * 加密类变量
     *
     * @var \muxin\wechat\server\QYWXBizMsgCrypt
     */
    public $pc;

    /**
     * 构造函数
     *
     * @param string $corpId
     * @param string $token
     * @param string $encodingAesKey
     * @param array $post post数据数组 json xml form 表单转换后的数据
     * @param array $get get数据
     */
    public function __construct($corpId, $token, $encodingAesKey, $post = [], $get = [])
    {

        $this->corpId = $corpId;
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->post = $post;
        $this->get = $get;
    }

    //解密回调数据
    public function decryptData($data)
    {
        $this->pc = new server\QYWXBizMsgCrypt($this->token, $this->encodingAesKey, $this->corpId);
        $msg_sign = $_GET['msg_signature'];
        $timeStamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $data = $this->pc->DecryptMsg($msg_sign, $timeStamp, $nonce, $data['post_json'], $sEchoStr);
        if ($data == 0) {
            return $sEchoStr;
        } else {
            return false;
        }
    }
}
