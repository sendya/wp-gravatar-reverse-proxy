<?php
/*
Plugin Name: Gravatar反向代理
Plugin URI: https://sendya.me/
Description: Gravatar 反向代理插件可以让 wordpress 将整站的 Gravatar 头像换到不受 gfw 影响的国内镜像站
Author: Sendya
Version: 1.0.4
Author URI: https://sendya.me/
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

Gravatar::get_instance();
// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Gravatar', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Gravatar', 'plugin_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Gravatar', 'plugin_uninstall' ) );

class Gravatar
{
    private $gravatar_setting_default;
    private $gravatar_setting_local;
    private $gravatar_setting_localurl;

    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @since	 1.0.0
     *
     * @return	object						A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    /**
     * 插件激活
     */
    public static function plugin_activation()
    {
        $gravatar_setting_default = esc_attr(get_option('gravatar_setting_default'));
        $gravatar_setting_local = esc_attr(get_option('gravatar_setting_local'));
        $gravatar_setting_localurl = esc_attr(get_option('gravatar_setting_localurl'));

        if ($gravatar_setting_default == null || $gravatar_setting_default == "") {
            update_option('gravatar_setting_default', '1');
        }
        if ($gravatar_setting_local == null || $gravatar_setting_local == "") {
            update_option('gravatar_setting_local', '0');
        }
        if ($gravatar_setting_localurl == null || $gravatar_setting_localurl == "") {
            update_option('gravatar_setting_localurl', stripslashes('/wp-content/avatar/'));
        }
    }

    /**
     * 插件卸载
     */
    public static function plugin_uninstall()
    {
        delete_option('gravatar_setting_default');
        delete_option('gravatar_setting_local');
        delete_option('gravatar_setting_localurl');
    }

    /**
     * 插件禁止
     */
    public static function plugin_deactivation()
    {
        // delete_option('gravatar_setting_default');
        // delete_option('gravatar_setting_local');
        // delete_option('gravatar_setting_localurl');
    }

    /**
     * add admin options menu
     */
    public function create_gravatar_admin_page()
    {
        add_options_page('头像代理设置', 'Gravatar', 'manage_options', 'gravatar_reverse_setting', array( $this, 'gravatar_view'));
    }

    /**
     * construct
     */
    private function __construct()
    {
        add_action('admin_menu', array( $this, 'create_gravatar_admin_page'));
        add_filter('get_avatar', array( $this, 'gravatar_setting'));


        $this->gravatar_setting_default = esc_attr(get_option('gravatar_setting_default'));
        $this->gravatar_setting_local = esc_attr(get_option('gravatar_setting_local'));
        $this->gravatar_setting_localurl = esc_attr(get_option('gravatar_setting_localurl'));
        // dirname(dirname(__FILE__))
        // add_filter( 'pre_option_link_manager_enabled', '__return_true' ); //完全不知道什么时候把启用 友情链接 的代码插入进来的..
    }

    private static function createDir($dir, $permission = 0777)
    {
        if (is_dir($dir)) {
            return;
        }
        self::createDir(dirname($dir), $permission);
        @mkdir($dir, $permission);
    }

    function gravatar_setting($avatar)
    {
        $gravatar_url = '';

        switch ($this->gravatar_setting_default) {
            case '1':
                $gravatar_url = 'ruby-china.org';
                break;
            case '2':
                $gravatar_url = 'gravatar.duoshuo.com';
                break;
            case '3':
                $gravatar_url = "cdn.v2ex.co/gravatar";//该链接为 HTTPS
                break;
            case '0':
            case 'null':
            default:
                $gravatar_url = 'cn.gravatar.com';
                break;
        }

        $avatar = str_replace(array("www.gravatar.com", "0.gravatar.com", "1.gravatar.com", "2.gravatar.com", "secure.gravatar.com"), $gravatar_url, $avatar);
        if ($this->gravatar_setting_local == "1") {
            $tmp = strpos($avatar, 'https');
            $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
            $tmp = strpos($g, '/avatar/') + 7;
            $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
            $g = substr($g, 0, $tmp) . $f;
            $w = home_url();
            $e = ABSPATH . $this->gravatar_setting_localurl . $f . '.jpg';
            self::createDir(dirname($e));
            $t = 604800; //设定 7 天, 单位:秒
            if (empty($default)) $default = $w . $this->gravatar_setting_localurl .'default.jpg';
            if (!is_file($e) || (time() - filemtime($e)) > $t) // 当头像不存在或者文件超过 7 天才更新
                copy(htmlspecialchars_decode($g), $e);
            else
                $avatar = strtr($avatar, array($g => $w . $this->gravatar_setting_localurl . $f . '.jpg'));
            if (filesize($e) < 500) copy($default, $e);
        }
        return $avatar;

    }


    /**
     * 插件设置页面html
     */
    function gravatar_view()
    {
        if (isset($_POST['submit'])) {

            if (wp_verify_nonce($_POST['_wpnonce'], 'gravatar_setting_token')) {
                update_option('gravatar_setting_default', stripslashes($_POST['gravatar_setting_default']));
                update_option('gravatar_setting_local', stripslashes($_POST['gravatar_setting_local']));
                update_option('gravatar_setting_localurl', stripslashes($_POST['gravatar_setting_localurl']));
                echo '<div class="updated"><p>保存成功</p></div>';
            } else {
                echo '<div class="error"><p>保存失败</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
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
                        if ($gravatar_setting_default == "0") {
                            echo "cn.gavatar.com";
                        } elseif ($gravatar_setting_default == "1") {
                            echo "ruby-china.org";
                        } elseif ($gravatar_setting_default == "2") {
                            echo "gravatar.duoshuo.com";
                        } elseif ($gravatar_setting_default == "3") {
                            echo "cdn.v2ex.co/gravatar";
                        } ?></span></p>
                <p><span>重新设定服务器：</span><br/>
                    <input type="radio"
                           name="gravatar_setting_default" <?php if ($gravatar_setting_default == "0") echo "checked"; ?>
                           value="0"/>gravatar官方默认
                    <input type="radio"
                           name="gravatar_setting_default" <?php if ($gravatar_setting_default == "1") echo "checked"; ?>
                           value="1"/>ruby-china镜像站
                    <input type="radio"
                           name="gravatar_setting_default" <?php if ($gravatar_setting_default == "2") echo "checked"; ?>
                           value="2"/>duoshuo多说镜像站
                    <input type="radio"
                           name="gravatar_setting_default" <?php if ($gravatar_setting_default == "3") echo "checked"; ?>
                           value="3"/><a href="https://cdn.v2ex.co/gravatar" target="_blank">cdn.v2ex.co</a>镜像站
                </p>
                <h3><label>本地头像缓存</label></h3>
                <p>本地缓存路径：<input type="text" style="width:400px;" name="gravatar_setting_localurl" value="<?= $gravatar_setting_localurl ?>"/>(* 必须以 "/" 结尾)<br/>
                    是否启用：<input type="radio"
                                name="gravatar_setting_local" <?php if ($gravatar_setting_local == "0") echo "checked"; ?>
                                value="0"/>禁用
                    <input type="radio"
                           name="gravatar_setting_local" <?php if ($gravatar_setting_local == "1") echo "checked"; ?>
                           value="1"/>启用
                </p>
                <p><input type="submit" name="submit" value="保存设置" class="button button-primary" /></p>
                <?php wp_nonce_field('gravatar_setting_token') ?>
            </form>
        </div>
        <?php
    }

}