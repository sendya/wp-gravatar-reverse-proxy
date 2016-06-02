<?php
/**
 * @package Plugin.Gravatar
 * @version 1.0.3
 * @Author Sendya(言肆)
 * @Memo 自定义缓存路径已经可以使用
 */
/*
Plugin Name: Gravatar反向代理
Plugin URI: https://sendya.me/
Description: Gravatar 插件可以让wordpress将整站的 Gravatar 头像换到不受gfw影响的国内镜像站
Author: Sendya
Version: 1.0.3
Author URI: https://sendya.me/
*/



class gravatar {
    /**
     * 插件激活
     */ 
    function Activation(){
        $gravatar_setting_default = esc_attr(get_option('gravatar_setting_default'));
        $gravatar_setting_local = esc_attr(get_option('gravatar_setting_local'));
        $gravatar_setting_localurl = esc_attr(get_option('gravatar_setting_localurl'));
        
        if($gravatar_setting_default == null or $gravatar_setting_default == ""){
            update_option('gravatar_setting_default', '1');
        }
        if($gravatar_setting_local == null or $gravatar_setting_local == ""){
            update_option('gravatar_setting_local', '0');
        }
        if($gravatar_setting_localurl == null or $gravatar_setting_localurl == ""){
            update_option('gravatar_setting_localurl', stripslashes('/wp-content/avatar/'));
        }
    }
    /**
     * 插件卸载 
     */ 
    function Uninstall(){
        delete_option('gravatar_setting_default');
        delete_option('gravatar_setting_local');
        delete_option('gravatar_setting_localurl');
    }
    /**
     * 插件禁止
     */ 
    function Deactivation(){
        // delete_option('gravatar_setting_default');
        // delete_option('gravatar_setting_local');
        // delete_option('gravatar_setting_localurl');
    }
    /**
     * 插件注册菜单
     */ 
    function create_gravatar_admin_page(){
    	add_options_page('Gravatar插件设置','Gravatar','manage_options','gravatar_setting','GravatarView');
    }
    
    /**
     * Init
     */ 
    function Gravatar() {
        add_action('admin_menu', 'create_gravatar_admin_page');
        add_filter('get_avatar', 'gavatar_setting');
        // add_filter( 'pre_option_link_manager_enabled', '__return_true' ); //完全不知道什么时候把启用 友情链接 的代码插入进来的..
        
        // 注册插件安装卸载禁用处理函数
        register_activation_hook( __FILE__, 'Activation');
        register_uninstall_hook(__FILE__, 'Uninstall');
        register_deactivation_hook( __FILE__, 'Deactivation' );
    }
    
    function Setting($avatar) {
        global $gravatar_setting_localurl;
        $gravatarUrl = esc_attr(get_option('gravatar_setting_default'));
        
        switch ($gravatarUrl) {
            case '1':
                $gravatarUrl = 'ruby-china.org';
                break;
            case '2':
                $gravatarUrl = 'gravatar.duoshuo.com';
                break;
            case '3':
                $gravatarUrl = "gravatar.css.network";//该链接为 HTTPS
                break;
            case '0':
            case 'null':
            default:
               $gravatarUrl = 'www.gravatar.com';
            break;
        }
        $avatar = str_replace(array("www.gravatar.com", "0.gravatar.com", "1.gravatar.com", "2.gravatar.com", "3.gravatar.com", "secure.gravatar.com"), 'gravatar.css.network', $avatar);
        // 缓存为本地资源
        if( esc_attr(get_option('gravatar_setting_local')) == "0"){
            $tmp = strpos($avatar, 'https');
            $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
            $tmp = strpos($g, 'avatar/') + 7;
            $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
            $g = substr($g,0,$tmp ) . $f;
            $w = home_url(); // $w = get_bloginfo('url');
            $e = WP_CONTENT_DIR .'/avatar/'. $f .'.jpg';
            $t = 604800; //设定头像本地缓存7天, 单位:秒
            if ( empty($default) ) $default = $w. $gravatar_setting_localurl .'default.jpg'; //拼接默认头像地址
            if ( !is_file($e) || (time() - filemtime($e)) > $t ) copy(htmlspecialchars_decode($g), $e);//当头像不存在或者文件超过7天才更新
            else
             $avatar = strtr($avatar, array($g => $w. $gravatar_setting_localurl .$f.'.jpg')); //拼接头像地址
            if (filesize($e) < 500) copy($default, $e);
        }
        return $avatar;
        
    }
    
    
    /**
     * 插件设置页面html
     */ 
    function GravatarView(){
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
    		                            echo "www.gavatar.com";
    		                          } elseif ($gravatar_setting_default == "1") {
    		                            echo "ruby-china.org";
    		                          } elseif ($gravatar_setting_default == "2") {
    		                            echo "gravatar.duoshuo.com";
    		                          } elseif ($gravatar_setting_default == "3") {
    		                            echo "cdn.css.net";
    		                          } ?></span></p>
    		<p><span>重新设定服务器：</span><br/>
    		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "0") echo "checked";?> value="0"/>gavatar官方默认
    		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "1") echo "checked";?> value="1"/>ruby-china镜像站
    		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "2") echo "checked";?> value="2"/>duoshuo多说镜像站
    		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "3") echo "checked";?> value="2"/><a href="//css.net" target="_blank">css.net</a>镜像站
    		</p>
    		<h3><label>本地头像缓存</label></h3>
    		<p>本地缓存路径：<input type="text" style="width:400px;" name="gravatar_setting_localurl" value="<?=$gravatar_setting_localurl ?>"/><br/>
    		是否启用：<input type="radio" name="gravatar_setting_local" <?php if($gravatar_setting_local == "0") echo "checked";?> value="0"/>禁用 
    		<input type="radio" name="gravatar_setting_local" <?php if($gravatar_setting_local == "1") echo "checked";?> value="1"/>启用
    		</p>
    		<p><input type="submit" name="submit" value="保存设置" class="button button-primary"></input></p>
    		<?php wp_nonce_field('gravatar_setting_token')?>	
    	</form>
    	</div>
    <?php
    }
    
    
}
/*
function gravatar_hook() {
    $gravatar = new Gravatar();
}

add_action( 'plugins_loaded', 'gravatar_hook' );
*/
/*
 * 找回上传设置
 */
if(get_option('upload_path')=='wp-content/uploads' || get_option('upload_path')==null) {
	update_option('upload_path',WP_CONTENT_DIR.'/uploads');
}

add_action('admin_menu', 'create_gravatar_admin_page');
add_filter('get_avatar', 'gavatar_setting');
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

register_activation_hook( __FILE__, 'gavatar_plugin_activation');
register_uninstall_hook(__FILE__, 'gavatar_plugin_uninstall');
register_deactivation_hook( __FILE__, 'gavatar_plugin_deactivation' );

function gavatar_plugin_activation(){
    $gravatar_setting_default = esc_attr(get_option('gravatar_setting_default'));
    $gravatar_setting_local = esc_attr(get_option('gravatar_setting_local'));
    $gravatar_setting_localurl = esc_attr(get_option('gravatar_setting_localurl'));
    
    if($gravatar_setting_default == null or $gravatar_setting_default == ""){
        update_option('gravatar_setting_default', '1');
    }
    if($gravatar_setting_local == null or $gravatar_setting_local == ""){
        update_option('gravatar_setting_local', '0');
    }
    if($gravatar_setting_localurl == null or $gravatar_setting_localurl == ""){
        update_option('gravatar_setting_localurl', stripslashes('/wp-content/avatar/'));
    }
}
function gavatar_plugin_uninstall(){
    delete_option('gravatar_setting_default');
    delete_option('gravatar_setting_local');
    delete_option('gravatar_setting_localurl');
}
function gavatar_plugin_deactivation(){
    
    delete_option('gravatar_setting_default');
    delete_option('gravatar_setting_local');
    delete_option('gravatar_setting_localurl');
    
}
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
         case '3':
             $gravatarUrl = "gravatar.css.network";//该链接为 HTTPS
             break;
         case '0':
             $gravatarUrl = 'www.gravatar.com';
         case 'null':
         default:
             $gravatarUrl = 'www.gravatar.com';
             break;
     }
     
     $avatar = str_replace(array("www.gravatar.com", "0.gravatar.com", "1.gravatar.com", "2.gravatar.com", "secure.gravatar.com"), $gravatarUrl, $avatar);
     if( esc_attr(get_option('gravatar_setting_local')) == "1"){
        $tmp = strpos($avatar, 'https');
        $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
        $tmp = strpos($g, 'avatar/') + 7;
        $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
        $g = substr($g,0,$tmp ) . $f;
        $w = home_url(); // $w = get_bloginfo('url');
        $e = WP_CONTENT_DIR .'/avatar/'. $f .'.jpg';
        $t = 604800; //设定7天, 单位:秒
        if ( empty($default) ) $default = $w. '/wp-content/avatar/default.jpg';
        if ( !is_file($e) || (time() - filemtime($e)) > $t ) //当头像不存在或者文件超过7天才更新
         copy(htmlspecialchars_decode($g), $e);
        else
         $avatar = strtr($avatar, array($g => $w.'/wp-content/avatar/'.$f.'.jpg'));
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
		                          } elseif ($gravatar_setting_default == "3") {
		                            echo "gravatar.css.network";
		                          } ?></span></p>
		<p><span>重新设定服务器：</span><br/>
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "0") echo "checked";?> value="0"/>gavatar官方默认
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "1") echo "checked";?> value="1"/>ruby-china镜像站
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "2") echo "checked";?> value="2"/>duoshuo多说镜像站
		<input type="radio" name="gravatar_setting_default" <?php if($gravatar_setting_default == "3") echo "checked";?> value="3"/><a href="//css.net" target="_blank">gravatar.css.network</a>镜像站
		</p>
		<h3><label>本地头像缓存</label></h3>
		<p>本地缓存路径：<input type="text" style="width:400px;" name="gravatar_setting_localurl" value="<?=$gravatar_setting_localurl ?>"/><br/>
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