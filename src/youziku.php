<?php
/*
Plugin Name: 有字库
Plugin URI: http://www.youziku.com/
Description: 中文webfont插件，完美替代google font 更兼容中文，为wordpress应用字体提供完美的解决方案。
Author: Lizus
Version: 1.0.1
Author URI: http://www.youziku.com/
Text Domain: yzk
License: GNU/GPL Version 2 or later. http://www.gnu.org/licenses/gpl.html
*/
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
define ('YZK_DIR',dirname(__FILE__));
define ('YZK_DIR_URI',plugins_url('',__FILE__));
define ('YZK_PREFIX','yzk_');
define ('YZK_APIKEY',YZK_PREFIX.'api');
define ('YZK_USERKEY',YZK_PREFIX.'userkey');
define ('YZK_LIST',YZK_PREFIX.'ziti_keys');

add_action('admin_init','yzk_init');
function yzk_init(){
  load_plugin_textdomain('yzk',false,YZK_DIR.'/lang');
}

require_once YZK_DIR.'/lib/YouzikuServiceClient.php';
require_once YZK_DIR.'/functions.php';
require_once YZK_DIR.'/admin-panel.php';
require_once YZK_DIR.'/admin-panel-ziti.php';


add_action('wp_head','spark_yzk_css');
function spark_yzk_css(){
  $api=get_option(YZK_APIKEY);
  if (empty($api)) {
    return;
  }
  $css=array();
  $title_key=get_option(YZK_PREFIX.'title_ziti');
  $content_key=get_option(YZK_PREFIX.'content_ziti');
  //文章页字体设置
  if (is_singular()) {
    global $post;
    $title_tag=$titles;
    $arr=array(
      'accessKey'=>$title_key,
      'cls'=>'.yzk_title_'.get_the_ID(),
      'id'=>get_the_ID(),
      'type'=>'post',
      'pos'=>'title',
    );
    $css[]=yzk_creat_css($arr);
    $arr=array(
      'accessKey'=>$content_key,
      'cls'=>'.yzk_content_'.get_the_ID(),
      'id'=>get_the_ID(),
      'type'=>'post',
      'pos'=>'content',
    );
    $css[]=yzk_creat_css($arr);
  }
  //列表页字体设置
  if (is_archive() || is_home()) {
    global $wp_query;
    $posts=$wp_query->posts;
    foreach ($posts as $p) {
      $arr=array(
        'accessKey'=>$title_key,
        'cls'=>'.yzk_title_'.$p->ID,
        'id'=>$p->ID,
        'type'=>'post',
        'pos'=>'title',
      );
      $css[]=yzk_creat_css($arr);
      $arr=array(
        'accessKey'=>$content_key,
        'cls'=>'.yzk_content_'.$p->ID,
        'id'=>$p->ID,
        'type'=>'post',
        'pos'=>'content',
      );
      $css[]=yzk_creat_css($arr);
    }
  }
  //自定义字体设置
  for ($j=0; $j < 5; $j++) {
    $custom_tag=get_option(YZK_PREFIX.'custom_cls_'.$j);
    $custom_key=get_option(YZK_PREFIX.'custom_ziti_'.$j);
    $arr=array(
      'accessKey'=>$custom_key,
      'cls'=>$custom_tag,
      'id'=>$j,
      'type'=>'custom',
    );
    $css[]=yzk_creat_css($arr);
  }
  if (!empty($css)) {
    echo '<style type="text/css" media="screen">';
    echo implode('',$css);
    echo '</style>';
    ?>
    <script type="text/javascript">
    (function (win){
      function load(func){
    	  var old=window.onload;
    	  if (typeof old !='function') {
    	    window.onload=func;
    	  }else{
    	    window.onload=function (){
    	      old();
    	      func();
    	    }
    	  }
    	}
      load(function (){
        var a=document.getElementsByTagName('a');
        var l=a.length;
        for (var i = 0; i < l; ++i) {
          var t=a[i];
          var title=t.title;
          title=title.replace(/<[^>]*>/g, "");
          t.title=title;
        }
      });
    })(window);
    </script>
    <?php
  }
}

add_filter('the_title','spark_title_yzk');
function spark_title_yzk($title)
{
  if (is_admin()) return $title;
  global $post;
  return '<span class=\'yzk_title_'.$post->ID.'\'>'.$title.'</span>';
}
add_filter('the_content','spark_yzk_content',999);
function spark_yzk_content($content)
{
  if (is_admin()) return $content;
  global $post;
  return '<div class="yzk_content_'.$post->ID.'">'.$content.'</div>';
}

add_action('save_post','spark_yzk_save_post');
function spark_yzk_save_post($pid){
  $ppid=wp_is_post_revision($pid);
  if ($ppid) $pid=$ppid;
  $post=get_post($pid);
  $content=$post->post_content;
  $accessKeys=array();
  if (preg_match_all('/youziku\s+id=([^\]]+)/',$content,$m)) {
    $accessKeys=$m[1];
  }
  $arr=array(
    array(
			'content'=>yzk_content($content,true),//文字内容
			'accessKey'=>get_option(YZK_PREFIX.'content_ziti'),//字体accessKey
			'id'=>$pid,//文章ID或custom id
			'type'=>'post',//post,archive,custom
      'pos'=>'content',
    ),
    array(
			'content'=>yzk_content($post->post_title,true),//文字内容
			'accessKey'=>get_option(YZK_PREFIX.'title_ziti'),//字体accessKey
			'id'=>$pid,//文章ID或custom id
			'type'=>'post',//post,archive,custom
      'pos'=>'title',
    ),
  );
  foreach ($accessKeys as $key) {
    $arr[]=array(
			'content'=>yzk_content($content,true),//文字内容
			'accessKey'=>$key,//字体accessKey
			'id'=>$pid.$key,//文章ID或custom id
			'type'=>'post',//post,archive,custom
      'pos'=>'content',
    );
  }
  yzk_creat_font($arr);
}


add_action('the_post','spark_yzk_loop_start');
function spark_yzk_loop_start(){
  if (is_admin()) return;
  global $post;
  $css=array();
  $title_key=get_option(YZK_PREFIX.'title_ziti');
  $title_tag=array('.yzk_title_'.$post->ID);
  $arr=array(
    'accessKey'=>$title_key,
    'cls'=>implode(',',$title_tag),
    'id'=>$post->ID,
    'type'=>'post',
    'pos'=>'title',
  );
  $css[]=yzk_creat_css($arr);
  if (!empty($css)) {
    echo '<style type="text/css" media="screen">';
    echo implode('',$css);
    echo '</style>';
  }
}

//编辑器添加按钮
add_action('init', 'youziku_button');
function youziku_button() {
    //判断用户是否有编辑文章和页面的权限
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
        return;
    }
    //判断用户是否使用可视化编辑器
    if ( get_user_option('rich_editing') == 'true' ) {

        add_filter( 'mce_external_plugins', 'add_plugin' );
        add_filter( 'mce_buttons', 'register_button' );
    }
}
function register_button( $buttons ) {
    array_push( $buttons, "youziku" ); //添加 一个myadvert按钮
    return $buttons;
}
function add_plugin( $plugin_array ) {
   $plugin_array['youziku'] = YZK_DIR_URI.'/js/mce.php'; //myadvert按钮的js路径
   return $plugin_array;
}

add_shortcode('youziku','spark_shortcode_youziku');
function spark_shortcode_youziku($atts,$content=null, $name=''){
  global $post;
	extract(shortcode_atts(array(
		"id" => '',
	), $atts));
  $cls='post_content_'.$id;
  $arr=array(
    'accessKey'=>$id,
    'cls'=>'.'.$cls,
    'id'=>$post->ID.$id,
    'type'=>'post',
    'pos'=>'content',
  );
  $css=array();
  $css[]=yzk_creat_css($arr);
  $opt='';
  $opt.='<style type="text/css" media="screen">';
  $opt.=implode('',$css);
  $opt.='</style>';
  $opt.='<span class="'.$cls.'">';
  $opt.=$content;
  $opt.='</span>';
	return $opt;
}
