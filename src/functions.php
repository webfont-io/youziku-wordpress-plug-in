<?php

//用于生成设置项
function yzk_form_echo($arr){
	if (!empty($arr)) {
		foreach ($arr as $a) {
			echo '<tr>';
			echo '<th scope="row"><label for="'.$a['id'].'">'.$a['title'];
			echo '<span style="display:inline-block;margin:0 10px;border:1px solid #ddd;border-radius:4px;padding:0 6px 0 5px;color:#ddd;" title="'.$a['desc'].'">?</span>';
			echo '</label></th>';
			echo '<td>';
			$value=$a['value'];
			if(empty($value)) $value=get_option($a['id']);
			switch ($a['type']) {
				case 'select':
					echo '<select name="'.$a['id'].'" id="'.$a['id'].'" class="regular-text">';
					$options=$a['options'];
					echo '<option value="0">'.__('无....','yzk').'</option>';
					foreach ($options as $o) {
						$chk='';
						if($value==$o['key']) $chk='selected="selected"';
						echo '<option '.$chk.' value="'.$o['key'].'">'.$o['name'].'</option>';
					}
					echo '</select>';
					break;
				case 'textarea':
					echo '<textarea name="'.$a['id'].'" id="'.$a['id'].'" class="large-text code" rows="10" cols="50" '.$a['disabled'].'>'.$value.'</textarea>';
					break;
				case 'text':
				default:
					echo '<input type="text" name="'.$a['id'].'" id="'.$a['id'].'" class="large-text" value="'.$value.'">';
					break;
			}
			echo '</td>';
			echo '</tr>';
		}
	}
}

//错误信息显示
function yzk_notice($error){
  $opt='<div id="setting-error-settings_updated" class="'.$error['type'].' settings-error notice is-dismissible">
      		<p><strong>'.$error['text'].'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('忽略此通知。','yzk').'</span></button>
      	</div>';
  if (empty($error) || !is_array($error)) {
    $opt='';
  }
  return $opt;
}

//获取当前页面URL
if (!function_exists('get_current_page_url')) {
function get_current_page_url(){
    $current_page_url = 'http';
    if (in_array('HTTPS',$_SERVER) && $_SERVER["HTTPS"] == "on") {
        $current_page_url .= "s";
    }
     $current_page_url .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
    $current_page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $current_page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $current_page_url;
}
}

function ch_num($num){
	$re='一';
	switch ($num) {
		case '2':
			$re='二';
			break;
		case '3':
			$re='三';
			break;
		case '4':
			$re='四';
			break;
		case '5':
			$re='五';
			break;
		case '6':
			$re='六';
			break;
		case '7':
			$re='七';
			break;
		case '8':
			$re='八';
			break;
		case '9':
			$re='九';
			break;
		default:
			break;
	}
	return $re;
}

//字体accessKey读取字体名称
function keyToFont($key){
	$arr=get_option(YZK_LIST);
	foreach ($arr as $font) {
		if ($font['accessKey']==$key) {
			return $font['name'];
		}
	}
	return '';
}

//字体名称读取字体accessKey
function fontToKey($name){
	$arr=get_option(YZK_LIST);
	foreach ($arr as $font) {
		if ($font['name']==$name) {
			return $font['accessKey'];
		}
	}
	return '';
}

//当字体列表设置项发生改变时需要执行一次以便生成正确的字体列表供选择
function yzk_list_init(){
	$api=get_option(YZK_PREFIX.'api');
	$ziti=get_option(YZK_PREFIX.'ziti_list');
	$accessKeys=array();
	if (!empty($ziti) && !empty($api)) {
		$ziti=explode("\n",$ziti);
		$names=array();
		$fonts=array();
		foreach ($ziti as $z) {
			$z=trim($z);
			$z=explode('/',$z);
			$fonts[]=$z[1];
			$names[]=$z[0];
		}
		$url='http://service.youziku.com/batchFont/getAccessKeys';
		$postData='ApiKey='.$api;
		$n=0;
		foreach ($fonts as $font) {
			$postData.='&Font['.$n.']='.$font;
			$n++;
		}
    $client=new HttpClient();
   	$jsonResult =$client->Request($url,"Post", $postData);
		$accessKeys=json_decode($jsonResult);
	}
	$result=array();
	$count=count($accessKeys);
	for ($i=0; $i < $count; $i++) {
		$result[]=array(
			'id'=>$fonts[$i],
			'name'=>$names[$i],
			'accessKey'=>$accessKeys[$i],
		);
	}
	if (!empty($result)) {
		update_option(YZK_LIST,$result);
		return true;
	}
	return false;
}

//用于生成字体，保存文章时要使用到
/*
$arrs=array(
	array(
		'content'=>,//文字内容
		'accessKey'=>,//字体accessKey
		'id'=>,//文章ID或custom id
		'type'=>,//post,archive,custom
		'pos'=>,//title,content,或者留空
	),
);
*/
function yzk_creat_font($arrs){
  $api=get_option(YZK_APIKEY);
	if (empty($api)) return false;
  $yzk=new YouzikuServiceClient($api);
	$param=array();
	foreach ($arrs as $arr) {
		$param[]=array(
			'content'=>$arr['content'],
			'accessKey'=>$arr['accessKey'],
			'url'=>get_yzk_font_url($arr['id'],$arr['type'],$arr['accessKey'],$arr['pos']),
		);
	}
	$yzk->CreateBatchWebFontAsync($param);
}

//获取字体唯一URL
function get_yzk_font_url($id,$type,$accessKey,$pos=''){
	$url=get_bloginfo('url');
	if ($type=='custom') {
		$id='cus_'.$id;
	}
	$url=$url.'/'.$type.'/'.$id.'/'.$accessKey;
	if (!empty($pos)) {
		$url=$url.'/'.$pos;
	}
	$url=preg_replace('/https?:\/\//','',$url);
	$url=preg_replace('/\./','_',$url);
	return $url;
}

//对要提交生成字体的内容进行一些处理
function yzk_content($cont,$explain=false){
	$cont=preg_replace('/\[0-9\]/','0123456789',$cont);
	$cont=preg_replace('/\[a-z\]/','abcdefghijklmnopqrstuvwxyz',$cont);
	$cont=preg_replace('/\[A-Z\]/','ABCDEFGHIJKLMNOPQRSTUVWXYZ',$cont);
	$after='';
	if (preg_match('/\[tags\]/',$cont)) {
		$cont=preg_replace('/\[tags\]/','',$cont);
		$after.='[tags]';
	}
	if (preg_match('/\[cats\]/',$cont)) {
		$cont=preg_replace('/\[cats\]/','',$cont);
		$after.='[cats]';
	}
	if(preg_match_all('/./u',$cont,$r)){
		$r=$r[0];
		$r=array_unique($r);
		sort($r);
		$r=trim(implode('',$r));
		$cont=$r;
	}
	$cont.=$after;
	if ($explain) {
		$custom_content=$cont;
		if (preg_match('/\[cats\]/',$custom_content)) {
			$cont=preg_replace('/\[cats\]/','',$custom_content);
      $tags=get_categories(array('hide_empty'=>0));
      foreach ($tags as $tag) {
        $cont.=$tag->name;
      }
      $custom_content=$cont;
    }
		if (preg_match('/\[tags\]/',$custom_content)) {
			$cont=preg_replace('/\[tags\]/','',$custom_content);
      $tags=get_tags();
      foreach ($tags as $tag) {
        $cont.=$tag->name;
      }
      $custom_content=$cont;
    }
		$cont=apply_filters('the_content',$custom_content);
		$cont=trim(strip_tags($cont));
	}
	$cont=preg_replace('/&/','',$cont);
	return $cont;
}

//生成字体相应的CSS
/*
$arr=array(
	'accessKey'=>,//字体accessKey
	'id'=>,//文章ID或custom id
	'type'=>,//post,archive,custom
	'cls'=>,//设定字体相应的class
);
*/
function yzk_creat_css($arr){
	$opt='';
	$userkey=get_option(YZK_USERKEY);
	if (empty($userkey)) return $opt;
	$accessKey=$arr['accessKey'];
	if (empty($accessKey)) return $opt;
	$api_url='https://cdn.webfont.youziku.com/webfonts/custompath';
	$font_url=get_yzk_font_url($arr['id'],$arr['type'],$arr['accessKey'],$arr['pos']);
	$fontFamily='yzk'.$arr['type'].$arr['pos'].$arr['id'];
	$opt.='
	@font-face{
		font-family:"'.$fontFamily.'";
		src:url("'.$api_url.'/'.$userkey.'/'.$font_url.'.gif");
		src:url("'.$api_url.'/'.$userkey.'/'.$font_url.'.gif?#iefix")format("embedded-opentype"), url("'.$api_url.'/'.$userkey.'/'.$font_url.'.bmp")format("woff"), url("'.$api_url.'/'.$userkey.'/'.$font_url.'.jpg")format("truetype");
	}'.$arr['cls'].'{font-family:"'.$fontFamily.'" !important;}';
	return $opt;
}
