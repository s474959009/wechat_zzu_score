<?php
/**
 * 微信公众平台 PHP SDK 示例文件
 *
 * @author NetPuter <netputer@gmail.com> 
 */

  require('src/Wechat.php');

  /**
   * 微信公众平台演示类
   */
  class MyWechat extends Wechat {

    /**
     * 用户关注时触发，回复「欢迎关注」
     *
     * @return void
     */
    protected function onSubscribe() {
          $items[]=new NewsResponseItem(
          	$title = "热烈欢迎新童鞋~~",
            $description = "新生报到流程",
            $picUrl = "http://zdj-shop.u.qiniudn.com/welcome1.jpg",
            $url = "http://mp.weixin.qq.com/s?__biz=MjM5MDk5NjYyMQ==&mid=201318306&idx=1&sn=6791f1e81800ecf0114b2cf0b3855b6a#rd"
          );
          $this->responseNews($items);
    }

    /**
     * 用户已关注时,扫描带参数二维码时触发，回复二维码的EventKey (测试帐号似乎不能触发)
     *
     * @return void
     */
    protected function onScan() {
      $this->responseText("欢迎你关注我们\n好消息，八点一刻可以查成绩啦\n编辑发送：\n".
    "学号 密码 \n".
    "201177x0xxx woshimima \n".
    "(以上查询的就是本学期成绩~)\n".
    "PS. 查询成绩仅需输入学号及密码。空格为英文空格");
    }
    /**
     * 用户取消关注时触发
     *
     * @return void
     */
    protected function onUnsubscribe() {
      // 「悄悄的我走了，正如我悄悄的来；我挥一挥衣袖，不带走一片云彩。」
    }

    /**
     * 上报地理位置时触发,回复收到的地理位置
     *
     * @return void
     */
    protected function onEventLocation() {
      $this->responseText('收到了位置推送：' . $this->getRequest('Latitude') . ',' . $this->getRequest('Longitude'));
    }

    /**
     * 收到文本消息时触发，回复收到的文本消息内容
     *
     * @return void
     */
    protected function onText() {
      include('src/HtmlParserModel.php');
	
      $data = trim($this->getRequest('content'));
      $rule = "/\ /";
      if(strlen($data)>12 && preg_match($rule, $data)){
      	$arr = explode(' ', $data);
      } 
      else if(preg_match("/成绩|help|帮助/", $data)) {
      	$this->responseText("八点一刻可以查成绩啦\n编辑发送：\n".
   							"学号 密码 \n".
   							"201177x0xxx woshimima \n".
   							"(以上查询的就是本学期成绩~)\n".
   							"PS. 查询成绩仅需输入学号及密码。空格为英文空格。\n".
                           "童鞋们需要什么帮助，可以留言，我们会及时回复的~");
      	return;
      } else if(preg_match("/四级|四六级|六级/", $data)){
          $items[]=new NewsResponseItem(
          	$title = "四六级成绩查询",
            $description = " ",
            $picUrl = "http://zdj-shop.u.qiniudn.com/getimgdata.jpg",
            $url = "http://apix.sinaapp.com/cet/?appkey=trialuser"
          );
          $this->responseNews($items);
      } else if(preg_match("/课表|课程表/", $data)){
			$this->responseText('课表查询功能近期上线...');
      }else {
      	return true;
      }
      $nianji = substr($arr[0], 0, 4);
      $xuehao = $arr[0];
        if(!preg_match("/^\d*$/", $xuehao)){
            return true;
        }
      $mima = $arr[1];
      $formdata = 'nianji='.$nianji.'&xuehao='.$xuehao.'&mima='.$mima;
      $url = 'http://jw.zzu.edu.cn/scripts/qscore.dll/search';
            if(strlen($xuehao)==12){
                $url = 'http://202.197.190.20/scripts/qscore.dll/search';
            }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);
      curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1200);
      $output = curl_exec($ch);
      $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if($httpCode!=200||$output===false){
        	$this->responseText("服务器查询压力过大，已经崩溃，改天再试吧[再见]");
        }
      curl_close($ch);
      $html_dom = str_get_html($output);
      $rule = "/密码|不正确/";
      $rule1 = "/当前学期已注册/";
        $rule2 = "/对不起/";
      if(preg_match($rule,$html_dom)){
           $this->responseText("请检查学号和密码，更换输入法。");
           return;
      }
      if(preg_match($rule1,$html_dom)){
      	$this->responseText("该账号无法查询成绩，请在教务网上核实。");
          return;
      }
      if(preg_match($rule2,$html_dom)){
      	$this->responseText("请稍后再试");
          return;
      }
      foreach ($html_dom->find('tr') as $tr) {
        $item['课程：'] = $tr->find('td',0)->plaintext."\n";
        $item['成绩：'] = $tr->find('td',3)->plaintext."\n";
        $item['学分：'] = $tr->find('td',2)->plaintext."\n";
        $item['绩点：'] = $tr->find('td',4)->plaintext."\n----\n";
        $trs[] = $item;
      }

      array_splice($trs, 0, 1);

      function arrToStr ($array)
      {
          // 定义存储所有字符串的数组
          static $r_arr = array();
          
          if (is_array($array)) {
              foreach ($array as $key => $value) {
                  if (is_array($value)) {
                      // 递归遍历
                      arrToStr($value);
                  } else {
                      $r_arr[] = $key.$value;
                  }
              }
          } else if (is_string($array)) {
                  $r_arr[] = $array;
          }
              
          //数组去重
          $string = implode('', $r_arr);
          
          return $string;
      }
      $zongji = str_replace("&nbsp;&nbsp;&nbsp;","；",$html_dom->find('p',2)->plaintext)."\n----\n";
      $text = "(目前只能查询部分已出成绩，成绩信息来源于郑州大学教务在线，如有疑问可登陆核实，欢迎大家反馈意见。)";
      $this->responseText(arrToStr($trs).$zongji.$text);
    }

    /**
     * 收到图片消息时触发，回复由收到的图片组成的图文消息
     *
     * @return void
     */
    protected function onImage() {
      $this->responseText("童鞋，现在只可以查成绩");
    }

    /**
     * 收到地理位置消息时触发，回复收到的地理位置
     *
     * @return void
     */
    protected function onLocation() {
      // 故意触发错误，用于演示调试功能
      $this->responseText("童鞋，现在只可以查成绩");
    }

    /**
     * 收到链接消息时触发，回复收到的链接地址
     *
     * @return void
     */
    protected function onLink() {
      $this->responseText("童鞋，现在只可以查成绩");
    }

    /**
     * 收到语音消息时触发，回复语音识别结果(需要开通语音识别功能)
     *
     * @return void
     */
    protected function onVoice() {
      $this->responseText("童鞋，现在只可以查成绩");
    }

    /**
     * 收到未知类型消息时触发，回复收到的消息类型
     *
     * @return void
     */
    protected function onUnknown() {
      $this->responseText("童鞋，现在只可以查成绩");
    }

  }

  $wechat = new MyWechat('weixin', TRUE);
  $wechat->run();
