<?php
//@package WordPress
//@subpackage Bunny WordPress Custom;

//后台设置主菜单


add_action('admin_menu','yzk_admin_menu_setting');
function yzk_admin_menu_setting(){
	$yzk_page=add_menu_page(__('有字库设置','yzk'),__('有字库设置','yzk'),'manage_options','youziku','yzk_admin_setting_option','');
}

function yzk_admin_setting_option()	{
	$yzk_list=get_option(YZK_LIST);
  $api=get_option(YZK_APIKEY);
  $userkey=get_option(YZK_USERKEY);
	if (!$yzk_list || empty($yzk_list) || empty($api) || empty($userkey)) {
		echo '<script>location.href="'.admin_url('admin.php?page=yzk_init').'";</script>';
		return;
	}
	$ziti_options=array();
	foreach ($yzk_list as $y) {
		$ziti_options[]=array(
			'key'=>$y['accessKey'],
			'name'=>$y['name'],
		);
	}
	$error=array();
	$max_custom=5;
	$yzk_arr_custom=array();
	for ($i=0; $i < $max_custom; $i++) {
		$yzk_arr_custom[$i]=array(
			array(
				'id'=>YZK_PREFIX.'custom_ziti_'.$i,
				'title'=>__('字体选择','yzk'),
				'desc'=>__('字体列表请在初始化设置中填写。如无法正确显示，请确认您有相应字体的权限，并检查您输入的字体名称及ID是否与官网一致。','yzk'),
				'type'=>'select',
				'options'=>$ziti_options,
			),
			array(
				'id'=>YZK_PREFIX.'custom_cls_'.$i,
				'title'=>__('字体应用Class','yzk'),
				'desc'=>__('使用CSS选择器语法设定，多个选择器间使用英文逗号分隔，如不会填写请联系有字库客服。示例：.post .title,.post .meta','yzk'),
				'type'=>'text',
			),
			array(
				'id'=>YZK_PREFIX.'custom_zi_'.$i,
				'title'=>__('字体应用以下文字','yzk'),
				'desc'=>__('贴入文字保存后会自动格式化。可使用[0-9][a-z][A-Z]三组写法分别表示数字，小写字母和大写字母，[tags]表示所有标签，[cats]表示所有分类。','yzk'),
				'type'=>'textarea',
			),
		);
	}
	$yzk_arr_post=array(
		array(
			'id'=>YZK_PREFIX.'title_ziti',
			'title'=>__('文章标题字体选择','yzk'),
			'desc'=>__('字体列表请在初始化设置中填写。如无法正确显示，请确认您有相应字体的权限，并检查您输入的字体名称及ID是否与官网一致。','yzk'),
			'type'=>'select',
			'options'=>$ziti_options,
		),
		array(
			'id'=>YZK_PREFIX.'content_ziti',
			'title'=>__('文章内容字体选择','yzk'),
			'desc'=>__('字体列表请在初始化设置中填写。如无法正确显示，请确认您有相应字体的权限，并检查您输入的字体名称及ID是否与官网一致。','yzk'),
			'type'=>'select',
			'options'=>$ziti_options,
		),
	);
	if (!empty($_POST) && $_POST['submit']=='yzk') {//保存更改
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		if ($checked) {
			foreach ($yzk_arr_post as $arr) {
				update_option($arr['id'],$_POST[$arr['id']]);
			}
			$arrs=array();
			for ($i=0; $i < $max_custom; $i++) {
				foreach ($yzk_arr_custom[$i] as $arr) {
					$id=$arr['id'];
					$cont=$_POST[$id];
					if (preg_match('/'.YZK_PREFIX.'custom_zi_/',$id)) {
						if(!empty($cont)) {
							$cont=yzk_content($cont);
						}
					}
					update_option($id,$cont);
				}
				$arr=array(
					'content'=>yzk_content(get_option(YZK_PREFIX.'custom_zi_'.$i),true),//文字内容
					'accessKey'=>get_option(YZK_PREFIX.'custom_ziti_'.$i),//字体accessKey
					'id'=>$i,//文章ID或custom id
					'type'=>'custom',//post,archive,custom
				);
				$arrs[]=$arr;
			}
			yzk_creat_font($arrs);
			$error['type']='updated';
			$error['text']=__('设置已保存。','yzk');
		}
	}
?>
<div class='wrap yzk_admin'>
	<h1><?php _e('有字库设置','yzk'); ?></h1>
	<?php
	if (!empty($_POST) && $_POST['submit']=='yzk_action') {//应用到所有文章
		set_time_limit(0);
		if (ob_get_level() == 0) ob_start();
		$checked=check_admin_referer(YZK_PREFIX.'admin');
		if ($checked) {
			$rs=new WP_Query();//docs:http://codex.wordpress.org/Function_Reference/WP_Query
			$args=array(
				'posts_per_page'=>-1,
				'post_type'=>'any',//post,page,revision,attachment,any,custom_post_type,(can array)
				'post_status'=>'publish',//publish,pending,draft,auto-draft,future,private,inherit,trash,any,(can array)
				'orderby'=>'date',//date,modified,ID,author,title,name,parent,rand,comment_count,menu_order,meta_value,meta_value_num,post__in
				'order'=>'DESC',//ASC,DESC
				//'offset'=>0,//偏移量,表示前几个结果不要
				//'ignore_sticky_posts'=>0,//1为排除置顶
				//'perm'=>'readable',//Show posts if user has the appropriate capability
			);
			$rs->query($args);
			if ($rs->have_posts()){
				while ($rs->have_posts()){
					$rs->the_post();
					spark_yzk_save_post(get_the_ID());
					echo '<div>'.get_the_ID().' : 【'.get_the_title().'】 已更新。</div>';
					ob_flush();
					flush();
				}
			}
			wp_reset_postdata();
		}
	}
	?>
	<?php echo yzk_notice($error); ?>
	<form class="yzk_form" action="" method="post" novalidate="novalidate">
		<?php wp_nonce_field(YZK_PREFIX.'admin'); ?>
		<p>
			<button type="submit" name="submit" value="yzk" class="button button-primary">保存更改</button>
			<button type="submit" name="submit" value="yzk_action" class="button button-default">应用到所有文章</button>
		</p>
		<div class="postbox" style="padding:20px;">
			<h3><?php _e('文章字体设置','yzk'); ?></h3>
			<table class="form-table">
				<?php
				yzk_form_echo($yzk_arr_post);
				?>
			</table>
		</div>
		<?php
		for ($i=0; $i < $max_custom; $i++) {
			echo '<div class="postbox" style="padding:20px;">';
			echo '<h3>'.__('新增自定义字体设置','yzk').ch_num($i+1).'</h3>';
			echo '<table class="form-table">';
			yzk_form_echo($yzk_arr_custom[$i]);
			echo '</table>';
			echo '</div>';
		}
		?>
		<p>
			<button type="submit" name="submit" value="yzk" class="button button-primary">保存更改</button>
		</p>
	</form>
</div>
<?php
}
