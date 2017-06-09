<?php
//@package WordPress
//@subpackage Bunny WordPress Custom;

//后台设置主菜单


add_action('admin_menu','yzk_ziti_menu_setting');
function yzk_ziti_menu_setting(){
	$sub_page=add_submenu_page('youziku',__('初始化设置','yzk'),__('初始化设置','yzk'),'manage_options','yzk_init','yzk_ziti_setting_option');
}

function yzk_ziti_setting_option()	{
	$error=array();
	$yzk_arr=array(
		array(
			'id'=>YZK_APIKEY,
			'title'=>__('有字库','yzk').' ApiKey：',
			'desc'=>__('有字库ApiKey获取地址：'.'http://www.youziku.com/apiKey/index','yzk'),
			'type'=>'text',
		),
		array(
			'id'=>YZK_USERKEY,
			'title'=>__('有字库','yzk').' UserKey：',
			'desc'=>__('有字库UserKey获取地址：'.'http://www.youziku.com/userKey/index','yzk'),
			'type'=>'text',
		),
		array(
			'id'=>YZK_PREFIX.'ziti_list',
			'title'=>__('可设置的字体列表：','yzk'),
			'desc'=>__('用于设置用的字体列表，请保持一行一个字体，使用【字体名称/ID】的方式，如:魏碑简粗体/46979 其中ID为对应字体介绍页URL的最后一段。 具体帮助地址：'.'http://www.youziku.com/','yzk'),
			'type'=>'textarea',
			'disabled'=>'disabled',
		),
	);
	$yzk_arr_updown=array(
		array(
			'id'=>YZK_PREFIX.'updown',
			'title'=>__('导入/导出：','yzk'),
			'desc'=>__('将字体设置内容粘贴在此，然后点导入即可完成导入。点击导出按钮，即可在此获得设置数据，可保存在任何地方。','yzk'),
			'type'=>'textarea',
		),
	);

	if (!empty($_POST) && $_POST['submit']=='down') {//导出数据
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		if ($checked) {
			$arr=array(
				'ziti_list'=>get_option(YZK_PREFIX.'ziti_list'),
				'title_ziti'=>keyToFont(get_option(YZK_PREFIX.'title_ziti')),
				'title_cls'=>get_option(YZK_PREFIX.'title_cls'),
				'content_ziti'=>keyToFont(get_option(YZK_PREFIX.'content_ziti')),
				'content_cls'=>get_option(YZK_PREFIX.'content_cls'),
				'list_title_ziti'=>keyToFont(get_option(YZK_PREFIX.'list_title_ziti')),
				'list_title_cls'=>get_option(YZK_PREFIX.'list_title_cls'),
				'list_content_ziti'=>keyToFont(get_option(YZK_PREFIX.'list_content_ziti')),
				'list_content_cls'=>get_option(YZK_PREFIX.'list_content_cls'),
			);
			$max_custom=5;
			for ($i=0; $i < $max_custom; $i++) {
				$arr['custom_ziti_'.$i]=keyToFont(get_option(YZK_PREFIX.'custom_ziti_'.$i));
				$arr['custom_cls_'.$i]=get_option(YZK_PREFIX.'custom_cls_'.$i);
				$arr['custom_zi_'.$i]=get_option(YZK_PREFIX.'custom_zi_'.$i);
			}
			$arr=json_encode($arr);
			$yzk_arr_updown[0]['value']=$arr;
			$error['type']='updated';
			$error['text']=__('导出成功，请复制【导入/导出】文本框中内容另行保存。','yzk');
		}
	}
	if (!empty($_POST) && $_POST['submit']=='up') {//导入数据
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		$api=get_option(YZK_PREFIX.'api');
		if ($checked && !empty($api)) {
			$data=stripslashes($_POST[YZK_PREFIX.'updown']);
			$data=json_decode($data);
			if (!empty($data)) {
				if (array_key_exists('ziti_list',$data)) {
					update_option(YZK_PREFIX.'ziti_list',$data->ziti_list);
					if (yzk_list_init()) {
						foreach ($data as $key => $value) {
							if (preg_match('/_ziti/',$key)) {
								$value=fontToKey($value);
							}
							update_option(YZK_PREFIX.$key,$value);
						}
						$error['type']='updated';
						$error['text']=__('导入成功，请尽情享用。','yzk');
					}
				}
			}
		}else{
			$error['type']='error';
			$error['text']=__('请先设置好ApiKey并保存修改再导入。','yzk');
		}
	}
	if (!empty($_POST) && $_POST['submit']=='fav') {//导入收藏字体
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		if ($checked) {
			$url='http://service.youziku.com/batchFont/getStores';
			$client=new HttpClient();
			$api=get_option(YZK_PREFIX.'api');
			if (!empty($api)) {
				$postData='ApiKey='.$api;
				$jsonResult =$client->Request($url,"Post", $postData);
				if (!empty($jsonResult)) {
					$j=json_decode($jsonResult);
					$j=$j->Stores;
					//$list=get_option(YZK_PREFIX.'ziti_list');
					foreach ($j as $font) {
						$list.="\n".$font->FontFamily.'/'.$font->FontId;
					}
					$str=array();
					$list=explode("\n",$list);
					foreach ($list as $l) {
						$str[]=preg_replace('/\s/','',$l);
					}
					$str=array_filter($str);
					$str=array_unique($str);
					$str=implode("\n",$str);
					update_option(YZK_PREFIX.'ziti_list',$str);
					if (yzk_list_init()) {
						$error['type']='updated';
						$error['text']=__('收藏字体已导入。','yzk');
					}else{
						$error['type']='error';
						$error['text']=__('有错误发生，请正确填写并重新保存。','yzk');
					}
				}else{
					$error['type']='error';
					$error['text']=__('无法导入收藏字体，请重试。','yzk');
				}
			}else{
				$error['type']='error';
				$error['text']=__('请先设置好ApiKey并保存修改再导入收藏字体。','yzk');
			}
		}
	}
	if (!empty($_POST) && $_POST['submit']=='yzk') {//保存更改
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		if ($checked) {
			foreach ($yzk_arr as $arr) {
				if ($arr['disabled']=='disabled') continue;
				update_option($arr['id'],$_POST[$arr['id']]);
			}
			$error['type']='updated';
			$error['text']=__('设置已保存，<a href="'.admin_url('admin.php?page=youziku').'">点击此处设置具体字体方案。</a>','yzk');
		}
	}
?>
<div class='wrap yzk_admin'>
	<form class="yzk_form" action="" method="post" novalidate="novalidate">
		<h1><?php _e('初始化设置','yzk') ?></h1>
		<?php echo yzk_notice($error); ?>
		<?php wp_nonce_field(YZK_PREFIX.'admin'); ?>
		<table class="form-table">
			<?php
			yzk_form_echo($yzk_arr);
			?>
		</table>
		<p>
			<button type="submit" name="submit" value="yzk" class="button button-primary">保存更改</button>
			<button type="submit" name="submit" value="fav" class="button button-default">导入收藏字体</button>
		</p>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<h1><?php _e('设置导入/导出','yzk'); ?></h1>
		<p><?php _e('导入字体设置前，请先设置好ApiKey。','yzk'); ?></p>
		<table class="form-table">
			<?php
			yzk_form_echo($yzk_arr_updown);
			?>
		</table>
		<p>
			<button type="submit" name="submit" value="up" class="button button-primary">导入字体设置</button>
			<button type="submit" name="submit" value="down" class="button button-default">导出字体设置</button>
		</p>
	</form>
</div>
<?php
}
