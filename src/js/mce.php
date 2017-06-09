<?php
header('Content-Type: application/x-javascript; charset=UTF-8');
if (basename(dirname(dirname(__FILE__)))=='plugins') {
	require( dirname(__FILE__) . '/../../../wp-load.php' ); //
}elseif (basename(dirname(dirname(dirname(__FILE__))))=='plugins') {
	require( dirname(__FILE__) . '/../../../../wp-load.php' ); //
}else{
	die();
}
$list=get_option(YZK_LIST);
$arr=array();
foreach ($list as $l) {
	$arr[]='{text:"'.$l['name'].'",value:"'.$l['accessKey'].'"}';
}
?>
(function() {
  tinymce.create('tinymce.plugins.youziku', {
    init : function(ed, url) {
      ed.addButton('youziku', {
        title : '有字库字体设置',
        image : url+'/youziku.png', //注意图片的路径 url是当前js的路径
        onclick : function(req) {
          //ed.selection.setContent('[]'); //这里是你要插入到编辑器的内容，你可以直接写上广告代码
          ed.windowManager.open({
            title: '有字库字体设置',
            body: [
              {
								type: 'listbox',
								name: 'youziku',
								label: '字体选择',
								values:[<?php echo implode(',',$arr); ?>],
							}
            ],
            onsubmit: function(e) {
              // Insert content when the window form is submitted
              //ed.insertContent('[youziku id='+e.data.youziku+']');
							console.dir(this.value());
							var html='';
							if(!html) html=ed.getContent();
							html=html.replace(/^(<p>)?\[youziku\s+id=[^\]]+\](<\/p>)?/,'');
							html=html.replace(/(<p>)?\[\/youziku\](<\/p>)?$/,'');
							ed.setContent('[youziku id='+e.data.youziku+']'+html+'[/youziku]');
            }
          });
        }
      });
    },
    createControl : function(n, cm) {
      return null;
    },
  });
  tinymce.PluginManager.add('youziku', tinymce.plugins.youziku);
})();
