<?php
/**
 * Created by : fonnie
 * Date: 2021/07/01
 * Time: 12:46:18
 */

class Wxpay extends Controller
{
    //----------------------微信支付----------------------------
    //商户，小程序appid
    protected $appid = "wx5e3db2a71eaa31b8";
    protected $secret = "ad08fc6c8e2134b3e4decc640e28da34";
    protected $mchid = "1530315891";
    protected $secretkey = "mwaichanmwaichanmwaichanmwaichan";

    //证书路径 //绝对路径
    protected $key_path =  "/config/cert/apiclient_key.pem";
    protected $cert_path =  "/config/cert/apiclient_cert.pem";

    //统一下单
    public function wxpay($out_trade_no, $openid, $money, $back_url)
    {
        $data['appid'] = $this->appid;
        $data['mch_id'] = $this->mchid;
        $nonce_str = $this->rand_code();
        $data['body'] = "支付";
        $data['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
// 		$money=0.01;
        $data['total_fee'] = $money * 100;
        $data['out_trade_no'] = $out_trade_no;
        $data['nonce_str'] = $nonce_str;
        $data['notify_url'] = $back_url;
        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $openid;
        $data['sign'] = $this->getSign($data);
        $xml = $this->ToXml($data);
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $res_tmp = $this->req_Post($url, $xml);
        if ($res_tmp) {
            $resdata = $this->FromXml($res_tmp);
            //var_dump($resdata);
            if ($resdata['return_code'] != 'SUCCESS') {
                return false;
            } else {
                $pdata['appId'] = $this->appid;
                $pdata['timeStamp'] = time() . '';
                $pdata['nonceStr'] = $this->rand_code();
                $pdata['package'] = 'prepay_id=' . $resdata['prepay_id'];
                $pdata['signType'] = 'MD5';
                $pdata['sign'] = $this->getSign($pdata);
                $pdata['prepay_id'] = $resdata['prepay_id'];
                return $pdata;
            }
        } else {
            return false;
        }
    }

    public function paysuccess()
    {
        $resdata = $this->paynotifyurl();
        //$resdata['out_trade_no']='121020713591000006485';
        if ($resdata['out_trade_no']) {
            $out_trade_no = $resdata['out_trade_no'];
            if ($out_trade_no[0] == 1) {
                $order = db('orderinfo')->where(array('out_trade_no' => $out_trade_no))->field('id,uid,out_trade_no,address,place,goods,phone,realname,total_num,price,msg')->find();
                $res = db('orderinfo')->where(array('is_pay' => 0, 'id' => $order['id']))->update(array('is_pay' => 1,
                    'state' => 2,
                    'state_text' => '待发货',
                    'pay_type' => 1,
                    'pay_time' => date("Y-m-d H:i:s")));
                if ($res) {
                    $goods = json_decode($order['goods'], true);
                    $id_arr = array();
                    foreach ($goods as $t) {
                        $id_arr[] = $t['goods_id'];
                    }
                    $id_str = implode(',', $id_arr);
                    $ress = db()->query("update goodsinfo set sales=sales+1 where id in ($id_str) ");
                    $updatas = array('goods' => $order['goods'], 'phone' => $order['phone'],
                        'name' => $order['realname'], 'total_price' => $order['price'], 'sum' => $order['total_num'], 'shop_mem_id' => $order['uid'], 'shop_order_no' => $order['out_trade_no'], 'address' => $order['realname'] . ' ' . $order['phone'] . ' ' . $order['address'] . $order['place'], 'msg' => $order['msg']);
                    $urls = $_SERVER['REQUEST_SCHEME'] . "://mwaichan.aly.xxnmkj.cn/gcxs2/salesorder/savesaleinfo";
                    $a = $this->req_Post($urls, $updatas);
                    $money = 0;
                    $info = db('setinfo')->field('spread_ratio,share_ratio')->find();
                    $user = db('userinfo')->where('id', $order['uid'])->field('id,belong')->find();
                    if ($user['belong']) {
                        $money = $info['spread_ratio'] * $order['price'];
                        $ress = db('userinfo')->execute("update userinfo set balance=balance+$money where id='" . $user['belong'] . "'");
                        $belong1 = db('userinfo')->where('id', $user['belong'])->field('id,belong')->find();
                        if ($belong1['belong']) {
                            $money = $info['share_ratio'] * $order['price'];
                            $ress = db('userinfo')->execute("update userinfo set balance=balance+$money where id='" . $belong1['belong'] . "'");
                        }
                    }

                }
            } else {
                $order = db('rechargeinfo')->where(array('out_trade_no' => $out_trade_no))->field('id,out_trade_no,money,uid')->find();
                $res = db('rechargeinfo')->where(array('is_pay' => 0, 'id' => $order['id']))->update(array('is_pay' => 1, 'pay_time' => date("Y-m-d H:i:s")));
                if ($res) {
                    $uid = $order['uid'];
                    $money = $order['money'];
                    $ress = db('userinfo')->query("update userinfo set balance=balance+$money where id=$uid");
                }
            }
        }
    }

    public function refund($out_refund_no, $out_trade_no, $money)
    {
        $data['appid'] = $this->appid;
        $data['mch_id'] = $this->mchid;
        $data['total_fee'] = $money * 100;
        $data['refund_fee'] = $money * 100;
        $data['out_trade_no'] = $out_trade_no;
        $data['out_refund_no'] = $out_refund_no;
        $data['nonce_str'] = $this->rand_code();
        foreach ($data as $tmp) {
            if (empty($tmp)) {
                return false;
            }
        }
        $data['sign'] = $this->getSign($data);
        $xml = $this->ToXml($data);
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $res_tmp = $this->req_Posts($url, $xml);

        if ($res_tmp) {
            $resdata = $this->FromXml($res_tmp);
            return $resdata;
        } else {
            return false;
        }
    }

    //获取token
    public function getToken($appid = '', $secret = '')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;
        if (empty($appid) || empty($secret)) {
            return false;
        }
        $datas = '';
        $times = time();
        $res_info = db('tokeninfo')->find();
        $fp = fopen('lock.txt', 'r');
        if (flock($fp, LOCK_EX)) {
            if (!empty($res_info['id'])) {
                if ($res_info['expires_in'] < ($times - 60)) {
                    $updatas = $this->req_Get($url);
                    $updata = json_decode($updatas, true);
                    if (!empty($updata['access_token'])) {
                        $updata['id'] = $res_info['id'];
                        $updata['expires_in'] = $times + $updata['expires_in'];
                        $updata['add_time'] = date('Y-m-d H:i:s');
                        $ress = db('tokeninfo')->update($updata);
                        $datas = $updata['access_token'];
                    } else {
                        $datas = false;
                    }
                } else {
                    $datas = $res_info['access_token'];
                }
            } else {
                $updatas = $this->req_Get($url);
                $updata = json_decode($updatas, true);
                if (!empty($updata['access_token'])) {
                    $updata['expires_in'] = $times + $updata['expires_in'];
                    $updata['add_time'] = date('Y-m-d H:i:s');
                    $ress = db('tokeninfo')->insert($updata);
                    $datas = $updata['access_token'];
                } else {
                    $datas = false;
                }
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return $datas;
    }

    public function getOpenId($code)
    {
        $appid = $this->appid;
        $secret = $this->secret;
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $secret . "&js_code=" . $code . "&grant_type=authorization_code";
        $res_wxdata = $this->req_Get($url);
        $wxdata = json_decode($res_wxdata, true);
        if (!empty($wxdata['openid'])) {
            return $wxdata;
        } else {
            return false;
        }
    }

    //发送模板消息
    public function sendTmpMsg($appid = '', $secret = '', $userid = '', $template_id = '', $formid = '', $data)
    {
        $tokens = $this->getToken($appid, $secret);
        if (!$tokens) {
            return false;
        }
        if (empty($userid) || empty($template_id)) {
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $tokens;
        $udata['touser'] = $userid;
        $udata['template_id'] = $template_id;
        $udata['form_id'] = $formid;
        $udata['data'] = $data;
        $res = $this->req_Post($url, json_encode($udata));
        $ress = json_decode($res, true);
        if ($ress['errcode'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    //回调验证
    public function paynotifyurl()
    {
        $xml = file_get_contents('php://input');
        $wx_back = $this->FromXml($xml);
        if (empty($wx_back)) {
            return false;
        }
        $resdata = $wx_back;
        unset($resdata['sign']);
        $checkSign = $this->getSign($resdata);
        if ($checkSign == $wx_back['sign']) {
            if ($resdata['return_code'] == 'SUCCESS') {
                echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
                return $resdata;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function rand_code()
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = str_shuffle($str);
        $str = substr($str, 0, 32);
        return $str;
    }

    //php发送post请求
    public function req_Post($url, $udata)
    {
        if ($url == '' || $udata == '') {
            return false;
        }
        //$udata=json_encode($udata);
        $timeout = 5;
        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $udata);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
        $p_res = curl_exec($con);
        curl_close($con);
        if ($p_res) {
            return $p_res;
        } else {
            return false;
        }
    }

    //php发送get请求
    public function req_Get($curl)
    {
        if ($curl == "") {
            return false;
        }
        $timeout = 5;
        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $curl);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
        $c_res = curl_exec($con);
        curl_close($con);
        if ($c_res) {
            return $c_res;
        } else {
            return false;
        }
    }

    //php发送带证书的post请求
    public function req_Posts($url, $udata)
    {
        if (empty($url)) {
            return false;
        }
        if (empty($udata)) {
            return false;
        }
        $timeout = 5;
        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($con, CURLOPT_SSLCERTTYPE, 'pem');
        curl_setopt($con, CURLOPT_SSLCERT, $this->cert_path);
        curl_setopt($con, CURLOPT_SSLKEYTYPE, 'pem');
        curl_setopt($con, CURLOPT_SSLKEY, $this->key_path);

        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $udata);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
        $p_res = curl_exec($con);
        curl_close($con);
        return $p_res;
    }

    //MD5签名
    public function getSign($sig_data)
    {
        ksort($sig_data);
        foreach ($sig_data as $key => $s_tmp) {
            if (!empty($s_tmp)) {
                $newArr[] = $key . '=' . $s_tmp;
            }
        }
        $str_sign = implode("&", $newArr);
        $stringSignTemp = MD5($str_sign . "&key=" . $this->secretkey);
        return strtoupper($stringSignTemp);
    }

    //数组转xml
    public function ToXml($data = array())
    {
        if (!is_array($data) || count($data) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                //$xml.="<".$key.">".$val."</".$key.">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    //xml转数组
    public function FromXml($xml)
    {
        if (!$xml) {
            return false;
        }
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }


}