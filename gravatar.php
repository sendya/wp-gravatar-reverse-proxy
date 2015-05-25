<?php
/**
 * @package Plugin.Gravatar
 * @version 1.0.2
 * @Author Sendya(言肆)
 * @Memo 当前未修复本地缓存，目前启用本地缓存将强制使用绝对路径 wordpress安装目录/avatar/
 */
/*
Plugin Name: Gravatar反向代理
Plugin URI: http://blog.loacg.com/plugins/gravatar
Description: Gravatar 插件可以让wordpress将整站的 Gravatar 头像换到不受gfw影响的国内镜像站
Author: Sendya
Version: 1.0.2
Author URI: http://blog.loacg.com/
*/

add_action('admin_menu', 'create_gravatar_admin_page');
add_filter('get_avatar', 'gavatar_setting');

function create_gravatar_admin_page(){
	add_options_page('Gravatar插件设置','Gravatar设置','manage_options','gravatar_setting','gravatar_view');
}

/**
 * ($avatar)
 * return $avatar
 */
function gavatar_setting($avatar) {
     $gravatarUrl = esc_attr(get_option('gravatar_setting_default'));
     
     switch ($gravatarUrl) {
         case '1':
             $gravatarUrl = 'ruby-china.org';
             break;
         case '2':
             $gravatarUrl = 'gravatar.duoshuo.com';
             break;
         case '0':
             $gravatarUrl = 'www.gravatar.com';
         case 'null':
         default:
             $gravatarUrl = 'www.gravatar.com';
             break;
     }
     
     $avatar = str_replace(array("www.gravatar.com", "0.gravatar.com", "1.gravatar.com", "2.gravatar.com"), $gravatarUrl, $avatar);
     
     if( get_option('gravatar_setting_local') == "1"){
         $tmp = strpos($avatar, 'http');
         $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
         $tmp = strpos($g, 'avatar/') + 7;
         $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
         $w = home_url(); // $w = get_bloginfo('url');
         $e = preg_replace('/wordpress//', '', ABSPATH) .'avatar/'. $f .'.jpg';
         $t = 604800; //设定7天, 单位:秒
         if ( empty($default) ) $default = $w. '/avatar/default.jpg';
         if ( !is_file($e) || (time() - filemtime($e)) > $t ) //当头像不存在或者文件超过7天才更新
             copy(htmlspecialchars_decode($g), $e);
         else
             $avatar = strtr($avatar, array($g => $w.'/avatar/'.$f.'.jpg'));
         if (filesize($e) < 500) copy($default, $e);
     }
    
     return $avatar;
}

/**
 * 插件设置页面html
 */ 
function gravatar_view(){
    if(isset($_POST['submit'])) {
    
    	if(wp_verify_nonce($_POST['_wpnonce'],'gravatar_setting_token')) {
    		update_option('gravatar_setting_default',stripslashes($_POST['gravatar_setting_default']));
    		update_option('gravatar_setting_local',stripslashes($_POST['gravatar_setting_local']));
    		update_option('gravatar_setting_localurl',stripslashes($_POST['gravatar_setting_localurl']));
    		echo '<div class="updated"><p>保存成功</p></div>';
    	} else {
    		echo '<div class="error"><p>保存失败</p></div>';
    	}
    }
?>
    <div class="wrap">
    <?php screen_icon();?>
    <h2>Gravatar 设置</h2>
    <hr/>
	<form action="" method="post" id="gavatar-form">
	    <?php
	        $gravatar_setting_localurl = esc_attr(get_option('gravatar_setting_localurl'));
	        $gravatar_setting_local = esc_attr(get_option('gravatar_setting_local'));
	    ?>
		<h3><label>服务器地址设置</label></h3>
		<p>当前服务器：<span style="font-weight:bold;"><?php 
		                          $gravatar_setting_default = esc_attr(get_option('gravatar_setting_default'));
		                          if($gravatar_setting_default == "0"){
		                            echo "cn.gavatar.com";
		                          } elseif ($gravatar_setting_default == "1") {
		                            echo "ruby-china.org";
		                          } elseif ($gravatar_setting_default == "2") {
		                            echo "gravatar.duoshuo.com";
		                          } ?></span></p>
		<p><span>重新设定服务器：</span><br/>
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "0") echo "checked";?> value="0"/>gavatar官方默认
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "1") echo "checked";?> value="1"/>ruby-china镜像站
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "2") echo "checked";?> value="2"/>duoshuo多说镜像站</p>
		<h3><label>本地头像缓存</label></h3>
		<p>本地缓存路径：<input type="text" style="width:400px;" name="gravatar_setting_localurl" value="<?=$gravatar_setting_localurl == '' ?  '/gravatar/' :  $gravatar_setting_localurl; ?>"/><br/>
		是否启用：<input type="radio" name="gravatar_setting_local" <?php if($gravatar_setting_local == "0") echo "checked";?> value="0"/>禁用 
		<input type="radio" name="gravatar_setting_local" <?php if($gravatar_setting_local == "1") echo "checked";?> value="1"/>启用
		</p>
		<p><input type="submit" name="submit" value="保存设置" class="button button-primary"></input></p>
		<?php wp_nonce_field('gravatar_setting_token')?>	
	</form>
	</div>
<?php
}



// -- END ----------------------------------------
?>
