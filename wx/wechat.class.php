<?php
/**
 * wechat php test
 */

//define your token
require './wechat.cfg.php';


class wechat
{
	private $token;

	public function __construct()
	{
		$this->token = TOKEN;
		$this->appid = APPID;
		$this->appsecret = APPSECRET;
		$this->textTpl = "<xml>
		  <ToUserName><![CDATA[%s]]></ToUserName>
		  <FromUserName><![CDATA[%s]]></FromUserName>
		  <CreateTime>%s</CreateTime>
		  <MsgType><![CDATA[%s]]></MsgType>
		  <Content><![CDATA[%s]]></Content>
		  <FuncFlag>0</FuncFlag></xml>";
		$this->newsTpl =
		$this->items =
		$this->imageTpl="<xml>
			<ToUserName><![CDATA[toUser]]></ToUserName>
			<FromUserName><![CDATA[fromUser]]></FromUserName>
			<CreateTime>12345678</CreateTime>
			<MsgType><![CDATA[image]]></MsgType>
			<Image>
			<MediaId><![CDATA[media_id]]></MediaId>
			</Image>
			</xml>";
	}

	//校验方法
	public function valid()
	{
		$echoStr = $_GET["echostr"];

		//valid signature , option
		if ($this->checkSignature()) {
			echo $echoStr;
			exit;
		}
	}

	//消息管理
	public function responseMsg()
	{
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

		//extract post data
		if (!empty($postStr)) {
			/* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
			   the best way is to check the validity of xml by yourself */
			libxml_disable_entity_loader(true);
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			//对于不同的消息类型,进行不同的方法处理
			switch ($postObj->MsgType) {
				case 'text':
					$this->doText($postObj);
					break;
				case 'image':
					$this->doImage($postObj);
					break;
				case 'voice':
					$this->doVoice($postObj);
					break;
				case 'location':
					$this->doLocation($postObj);
					break;
				case 'event':
					$this->doEvent($postObj);
					break;
				default:
					break;
			}
		}
	}

	//检查签名
	private function checkSignature()
	{
		// you must define TOKEN by yourself
		if (!defined("TOKEN")) {
			throw new Exception('TOKEN is not defined!');
		}

		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		// use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}

	//文本消息处理方法
	private function doText($postObj)
	{
		$keyword = trim($postObj->Content);
		//xml模板
		if (!empty($keyword)) {
			// $contentStr = "Welcome to wechat world!";
			//$contentStr = "你好!我是php59的微信公众号";
			//请求机器人接口
			$url = 'http://api.qingyunke.com/api.php?key=free&appid=0&msg='.$keyword;
			$contentStr = str_replace("{br}","\r",json_decode($this->request($url, false))->content);
			//sprintf 拼接模板
			$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), 'text', $contentStr);
			// file_put_contents('./data1.xml',$resultStr);
			echo $resultStr;
		}
	}

	//图片处理消息方法
	private function doImage($postObj)
	{
		$MediaId = $postObj->MediaId;
		$resultStr =sprintf($this->imageTpl, $postObj->FromUserName, $this->ToUserName, time(), $MediaId);
		file_put_contents('return.xml',$resultStr);
		echo $resultStr;
		/*//拼接返回数据模板
		//返回接收到图片url地址
		$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $this->ToUserName, time(), 'text', $postObj->PicUrl);
		//file_get_contents('./data1.xml',$resultStr);
		echo $resultStr;*/
	}

	//接收语音消息
	public function doVoice($postObj)
	{
		$mediaID = $postObj->MediaID;
		$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $this->ToUserName, time(), 'text', '语音接收到,MediaID:' . $mediaID);
		echo $resultStr;
	}

	//地图经纬度
	public function doLocation($postObj)
	{
		$contentStr = '您所在的纬度为:' . $postObj->Location_X . '经度为:' . $postObj->Location_Y;
		$resultStr = sprintf($this->textTpl, $postObj->FromUserName, $this->ToUserName, time(), 'text', $contentStr);
		echo $resultStr;
	}

	//请求方法
	public function request($url,$https=true,$method='get',$data=null)
	{
		//1.初始化
		$ch = curl_init($url);
		//2.设置参数
		//返回数据不直接输出，保存起来
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//判断请求协议
		if($https === true){
			//绕过ssl证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		//判断请求方式
		if($method === 'post'){
			//post设置
			curl_setopt($ch, CURLOPT_POST, true);
			//post数据传输
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		//3.发送请求
		$content = curl_exec($ch);
		//4.关闭链接
		curl_close($ch);
		//返回数据
		return $content;
	}

	//获取ticket
	public function getTicket($tmp=true){
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessToken();
		if($tmp === true){
			$data ='{"expire_seconds":604800,"action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
		}else{
			$data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
		}
		$content = $this->request($url,true,'post',$data);
		$ticket = json_decode($content)->ticket;
		return $ticket;
	}

	//通过ticket换取二维码
	public function getQRCode(){
		$ticket = $this->getTicket();
		$url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
		$content = $this->request($url);
		echo file_put_contents(time().'.jpg',$content);
	}

	//获取accesstoken
	public function getAccessToken()
	{
		$mem = new Memcache();
		$mem->connect('127.0.0.1',11211);
		$access_token = $mem->get('access_token');
		if ($access_token === false) {
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
			$content = $this->request($url);
			$access_token = json_decode($content)->access_token;
			$mem->set('accsess_token',$access_token,0,7100);
		}
		return $access_token;
	}

	//获取事件
	public function doEvent($postObj){
		switch ($postObj->Event){
			case 'subscribe':
				$this->doSubscribe($postObj);
				break;
			case 'unsubscribe':
				$this->doUnSubscribe($postObj);
				break;
			case 'SCAN':
				$this->doScan($postObj);//已关注扫描二维码事件
				break;
			case 'CLICK':
				$this->doClick($postObj);//已关注扫描二维码事件
				break;
			default:
				break;
		}
	}

	//未关注,扫描二维码事件
	private function doSubscribe($postObj){
		if(!isset($postObj->EventKey)){
			$content = '参加的活动代号是:'.$postObj->EventKey;

		}else{
			$content = '感谢关注';
		}
		$resultStr = sprintf($this->textTpl,$postObj->FromUserName,$this->ToUserName,time(),'text',$content);
		echo $resultStr;
	}

	//已关注,扫描二维码事件
	private function doScan($postObj){
		$scene_id = '已关注扫描二维码:'.$postObj->EventKey;
		$resultStr = sprintf($this->textTpl,$postObj->FromUserName,$this->toUserName,time(),'text',$scene_id);
		echo $resultStr;
	}

	//取消关注事件
	private function doUnSubscribe($postObj){
		$data = $postObj->FromUserName.'在'.date('Y-m-d H:i:s',time()).'取消了关注';
		file_put_contents('list.txt',$data,FILE_APPEND);
	}

	//获取用户列表
	public function getUserList(){
		$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessToken();
		$content = $this->request($url);
		$content = json_decode($content);
		echo '关注用户数:'.$content->total.'<br />';
		echo '本次拉取数:'.$content->total.'<br />';
		foreach ($content->data->openid as $key=>$value){
			echo ($key+1).'###'.$value.'<br />';
		}
	}

	//获取用户信息
	public function getUserInfo(){
		$openid="o53Qh00kJ2wdUpZdpWQnPjSK5tjc";
		$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
		$content = $this->request($url);
		$content =json_decode($content);
		switch ($content->sex) {
			case '1':
				$sex = '男';
				break;
			case '2':
				$sex = '女';
				break;
			default:
				$sex = '未知';
				break;
		}
		//var_dump($content);die;
		header("Content-type: text/html; charset=utf-8");

		echo '昵称为:'.$content->nickname.'<br />';
		echo '性别为:'.$sex.'<br />';
		echo '省份为:'.$content->province.'<br />';
		echo '关注时间:'.date('Y-m-d H:i:s',$content->subscribe_time).'<br />';
		echo '<img src="'.$content->headimgurl.'" style="width:100px; />"<br />';
	}

	//通过素材接口上传临时素材
	public function uploadFile(){
		$url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type=image';
		$data = array(
			'media'=>'@H:\bbb\wx\a.jpg',
		);
		$content = $this->request($url,true,'post',$data);
		$content = json_decode($content);
		//var_dump($content);die;
	}

	//通过素材接口下载素材
	public function getFile(){
		$media_id = 'On3fuIDNSIXJfxno3U0kN1oSNsnUjNYG2qSgCTU8l6ixYaBbxr1CSxSgj5nMpiCv';
		$url ='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$media_id;
		$content = $this->request($url);
		echo file_put_contents(time().'.jpg',$content);
	}

	//创建自定义菜单
	public function createmenu(){
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken();
		$data = '{
			"button":[
			{
				 "type":"click",
				 "name":"资讯信息",
				 "key":"news"
			 },
			 {
				  "name":"php59",
				  "sub_button":[
				  {
					  "type":"view",
					  "name":"百度",
					  "url":"http://www.baidu.com/"
				   },
				   {
						"type":"view",
						"name":"京东",
						"url":"http://m.jd.com/",
					},
				   {
						"name": "发送位置",
						"type": "location_select",
						"key": "rselfmenu_2_0"
				  }]
			  }]
		}';

		$content =$this->request($url,true,'post',$data);
		$content = json_decode($content);
		//var_dump($content);die;
		if($content->errcode == '0'){
			echo '创建菜单成功';
		}else{
			echo '错误代码为:'.$content->errcode.'<br />';
			echo '错误信息为:'.$content->errmsg.'<br />';
		}
	}
//查看菜单信息
	public function showMenu()
	{
		//1.url
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$this->getAccessToken();
		//2.请求方式
		//3.发送请求
		$content = $this->request($url);
		//4.处理返回值
		//var_dump($content);
	}
	//删除菜单
	public function delMenu()
	{
		//1.url
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken();
		//2.请求方式
		//3.发送请求
		$content = $this->request($url);
		//4.处理返回值
		// var_dump($content);
		$content = json_decode($content);
		if($content->errcode == '0'){
			echo '删除菜单成功！';
		}else{
			echo '错误代码为:'.$content->errcode.'<br />';
			echo '错误信息为:'.$content->errmsg.'<br />';
		}
	}

	//自定义菜单点击事件
	public function doClick($postObj){
		switch ($postObj->){

		}
	}

	//
	public function items(){
		$itemsArray=array(
			array()
		);
	}
	//publ
	public function sendNews(){
		$itemsArray=array();
		$Articles = '';
		foreach ($itemsArray as $key =>$value){
			$Articles .= sprintf($this->item,$value['Title'],$value['Description'],$value['PicUrl'],$value['Url']);
		}
		$resultStr = sprintf($this->newsTpl,$postObj->FromUserName,$this->ToUserName,time(),count($itemsArray),$Articles);
		echo $resultStr;
	}

	public function sendMusic(){
		$itemsArray=array(
			array()
		);
	}




















}


?>