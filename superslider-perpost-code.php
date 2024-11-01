<?php
/*
Plugin Name: SuperSlider-Perpost-Code
Plugin URI: http://wordpress.org/extend/plugins/superslider-perpost-code/
Description: Write css and javascript code directly on your post edit screen on a per post basis. Meta boxes provide a quick and easy way to enter custom code to each post. It then loads the code into your frontend theme header and or footer if the post has custom code.
Version: 1.1
Author: Daiv Mowbray
Author URI: http://www.daivmowbray.com
Licence: GPLZ
Text  Domain: superslider-perpost-code
Domain Path: /languages

Donate URI: http://superslider.daivmowbray.com/support-me/donate/
FAQ URI: http://wordpress.org/extend/plugins/superslider-perpost-code/faq/

*/

/*  Copyright 2012  Daiv Mowbray  (email : daiv.mowbray@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*

Changelog:
*1.1 (2012/11/17) php notices fixed
				added Spanish language file
*1.0 (2012/03/19) Initial release

*/

if (!class_exists('perpost_code')) {
	class perpost_code {

		/**
		* @var string $localDomain Domain used for localization
		*/
		var $localDomain = "superslider-perpost-code";
		
		

		/**
		* @var The plugin version
		*/ 
		var $version ='1.1';
		/**
		* @var string $urlpath The path to this plugin
		*/
		var $urlpath = '';
		var $plugin_url = '';

		//Class Functions
		/**
		* PHP 4 Compatible Constructor
		*/
		function perpost_code(){$this->__construct();}

		/**
		* PHP 5 Constructor
		*/		
		function __construct(){
		
		    self::superslider_perpost_code();
			
			//"Constants" setup
			$this->plugin_url = plugins_url(basename(__FILE__), __FILE__);
			$this->urlpath = plugins_url('', __FILE__);	
			
			//Language Setup
			$locale = get_locale();
			$mo = plugins_url("/languages/" . $this->localDomain . "-".$locale.".mo", __FILE__);	
			load_plugin_textdomain($this->localDomain, false, $mo);
			
	  	
		}
        function superslider_perpost_code() {
            register_activation_hook(__FILE__, array(&$this,'perpost_code_init') );
		    register_deactivation_hook( __FILE__, array(&$this,'options_deactivation') );
		    
		    add_action("init", array(&$this,'perpost_code_init'));
		    add_action('init', array(&$this,'custom_css_add_shortcode'));
		    add_action('init', array(&$this,'custom_js_add_shortcode'));
		    
		    add_action('add_meta_boxes', array(&$this,'custom_css_hooks'));
		    add_action('add_meta_boxes', array(&$this,'custom_js_hooks'));
		    
        }
        
        function options_deactivation() {
            delete_option('perpost_code_user');
            delete_option('perpost_css');
            delete_option('perpost_js');
        }
		function perpost_code_init() {		            
            add_action('save_post', array(&$this,'save_custom_css'));
            add_action('wp_head', array(&$this,'insert_custom_css'));

            add_action('save_post', array(&$this,'save_custom_js'));
            add_action('wp_footer', array(&$this,'insert_custom_js'));
              
            add_action('admin_init', array(&$this,'perpost_writing_options_page'));
            add_action('admin_menu', array(&$this, 'perpost_admin_menu'));
            $this->set_default_options();
		}
	   function perpost_writing_options_page() { 
	      
	       register_setting( 'writing', 'perpost_code_user');
           register_setting( 'writing', 'perpost_css');
           register_setting( 'writing', 'perpost_js');
        
           add_settings_section( 'perpost', 'SuperSlider Perpost Code Boxes', array(&$this,'perpost_code_section'), 'writing');
            
           add_settings_field( 'perpost_code_user', 'Minimum user ', array(&$this, 'pp_code_user'), 'writing', 'perpost');
           add_settings_field( 'perpost_css', 'Css metabox ', array(&$this, 'pp_css_box'), 'writing', 'perpost');
           add_settings_field( 'perpost_js', 'JavaScript metabox ', array(&$this, 'pp_js_box'), 'writing', 'perpost');

	    }
	    function perpost_admin_menu() {
	       add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_base'), 'manage_options', 2 );
	       add_filter( 'plugin_row_meta', array(&$this, 'plugin_links'), 10, 2);
	    }
	   function set_default_options() {
            $defaultOption = get_option('perpost_code_user');
            if (empty($defaultOption)) {
                    update_option( 'perpost_code_user' , 'editor' );
                    update_option( 'perpost_css' , 1 );
                    update_option( 'perpost_js' , 1 );
            }		
		}
        /**
        * Add link to options page from plugin list WP 2.6.
        */
        function filter_plugin_base($links, $file) {
             static $this_plugin;
                if (  ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
    
            if (  $file == $this_plugin )
                $settings_link = sprintf( '<a href="options-writing.php#perpage">%s</a>', __('Settings') );
                array_unshift( $links, $settings_link ); //  before other links
                return $links;
        }
		
		/**
		* This sets up the section on the writting options page
		*/
	   function perpost_code_section() {	   
	        echo '<a id="perpage" name="perpage"></a><span class="description"> '.__('Set default options for SuperSlider-PerPost-Code meta boxes viewed on the post edit screen.', $this->localDomain).'</span>';        

	    }
	   function pp_code_user() {	   
	       $code_user = get_option ('perpost_code_user');	       
	       echo '<fieldset><legend class="screen-reader-text"><span>Per post code user level</span></legend><label for="perpost_code_user"><select name="perpost_code_user" id="perpost_code_user">';          
           wp_dropdown_roles($code_user);
           echo '</select>  and above will have use of the perpost meta boxes.</label></fieldset>'; 
           
	    }
	   function pp_css_box() {	   
	       $css_box = get_option ('perpost_css');	       
            if( $css_box == 1)$check = 'checked = "checked"';          
           echo '<fieldset><legend class="screen-reader-text"><span>Css meta box</span></legend><label for="css_box">
                 <input name="perpost_css" id="perpost_css" type="checkbox" value="1" class="checkbox" '.$check.' />'.__(' Css meta box is visible on post - page edit screen', $this->localDomain).'</label></fieldset>'; 
	    }
	   function pp_js_box() {
	       $js_box = get_option ('perpost_js');	       
	       if( $js_box == 1)$check = 'checked = "checked"';  
           echo '<fieldset><legend class="screen-reader-text"><span>Javascript meta box</span></legend><label for="js_box">
                 <input name="perpost_js" id="perpost_js" type="checkbox" value="1" class="checkbox" '.$check.' />'.__(' JavaScript meta box is visible on post - page edit screen', $this->localDomain).'</label></fieldset>'; 

	    }
	    /**
		* This sets up the user level access
		*/
		function pp_user_level() {
		  $user = wp_get_current_user();
		  $id = $user->ID;
		  $min_code_user = get_option ('perpost_code_user');
            switch ($min_code_user) {
                case 'administrator':               
                    $i = user_can($id, 'delete_plugins');
                    return $i;
                    break;
                case 'editor':                
                    $i = user_can($id, 'edit_others_posts');
                    return $i;
                    break;
                case 'author':
                     $i = user_can($id, 'publish_posts');
                     return $i;
                    break;
                case 'contributor':
                    $i = user_can($id, 'edit_post');
                    return $i;
                    break;
                }
            return 0;
		}
	    /**
		* This sets up the meta boxes on the writting pages
		*/
       function custom_css_hooks() {
            $css_box = get_option ('perpost_css'); 
            $user_can = $this->pp_user_level();

            if( ($css_box == 1) && ($user_can == 1)) {
                add_meta_box('custom_css', 'Custom CSS', array(&$this,'custom_css_input'), 'post', 'normal', 'high');
                add_meta_box('custom_css', 'Custom CSS', array(&$this,'custom_css_input'), 'page', 'normal', 'high');
                }
         }        
        function custom_js_hooks() {
            $js_box = get_option ('perpost_js');
            $user_can = $this->pp_user_level();

            if( ($js_box == 1) && ($user_can == 1)) {
                add_meta_box('custom_js', 'Custom JS', array(&$this,'custom_js_input'), 'post', 'normal', 'high');
                add_meta_box('custom_js', 'Custom JS', array(&$this,'custom_js_input'), 'page', 'normal', 'high');
             
             }
         }  
		/**
		* This sets up the meta box contents
		*/
        function custom_js_input() {            
        	global $post;
        	$langyage = 'javascript';
        	
        	echo '<input type="hidden" name="custom_js_noncename" id="custom_js_noncename" value="'.wp_create_nonce('custom-js').'" />';
        	echo '<textarea name="custom_js" id="custom_js" rows="8" cols="30" style="width:100%;">'.get_post_meta($post->ID,'_custom_js',true).'</textarea>';
            echo '<p>'.__('Use shortcode to display your code in the post, example: [custom_js line="6" linenums="true" highlight="7-12"]',$this->localDomain).'</p>';
         }
         
        function custom_css_input() {
        	global $post;
        	$langyage = 'css';
        	
        	echo '<input type="hidden" name="custom_css_noncename" id="custom_css_noncename" value="'.wp_create_nonce('custom-css').'" />';
        	echo '<textarea name="custom_css" id="custom_css" rows="8" cols="30" style="width:100%;">'.get_post_meta($post->ID,'_custom_css',true).'</textarea>';
            echo '<p>'.__('Use shortcode to display your code in the post, example: [custom_css line="6" linenums="false" highlight="7,9,12"]',$this->localDomain).'</p>';
         
         }
        /**
		* This saves the meta box contents
		*/
         function save_custom_js($post_id) {
        	//if (!wp_verify_nonce($_POST['custom_js_noncename'], 'custom-js')) return $post_id;
        	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
        	if(isset($_POST['custom_js'])) { $custom_js = $_POST['custom_js'];
        	update_post_meta($post_id, '_custom_js', $custom_js); }
        } 
        
        function save_custom_css($post_id) {
        	//if (!wp_verify_nonce($_POST['custom_css_noncename'], 'custom-css')) return $post_id;
        	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
        	if(isset($_POST['custom_css'])) { $custom_css = $_POST['custom_css'];
        	update_post_meta($post_id, '_custom_css', $custom_css); }
        }
        
        /**
		* This inserts the css and javascript into the header and footer repectively
		*/
        function insert_custom_js() {
        	if (is_page() || is_single()) {
                if (have_posts()) : while (have_posts()) : the_post();
                $data = get_post_meta(get_the_ID(), '_custom_js', true);
                if ($data) {
                     echo "\n<!-- superslider-perpost-code v:".$this->version." -->\n<script type=\"text/javascript\">\n".$data."\n</script>\n";
                 }
    		endwhile; endif;
    		rewind_posts();
        	}
        }
        
        function insert_custom_css() {
        	if (is_page() || is_single()) {
                if (have_posts()) : while (have_posts()) : the_post();
                $data = get_post_meta(get_the_ID(), '_custom_css', true);
                if($data){
                     echo "\n<!-- superslider-perpost-code v:".$this->version." -->\n<style type=\"text/css\">\n".$data."\n</style>\n";
                }
    		endwhile; endif;
    		rewind_posts();
        	}
        }
        
        function custom_css_add_shortcode() {		
    	   add_shortcode ( 'custom_css' , array(&$this, 'custom_css_shortcode_out') );

	    }
	    function custom_js_add_shortcode() {		
    	   add_shortcode ( 'custom_js' , array(&$this, 'custom_js_shortcode_out') );

	    }
	    function get_syntax_plugin($code_type, $line, $linenums, $highlight) {
	       
	       if ($linenums == 'false') {$linenums ='';}else{$linenums ='linenums:';}
	       $this->pre_close = '</pre>';

	       // load the plugin file to be able to check for other plugins while on the front side
	       include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	       
	       // check if plugin is active
	       if (is_plugin_active('google-syntax-highlighter/google_syntax_highlighter.php')) {
                $i = 'google_syntax';
             }elseif (is_plugin_active('prettify-gc-syntax-highlighter/prettify-gc-syntax-highlighter.php')) {
                $i = 'prettify';
             }elseif (is_plugin_active('wp-syntax/wp-syntax.php')) {
                $i = 'wp-syntax';
             }else {
                $i = '';
             }
	       
	       switch ($i) {
            case 'google_syntax':               
                return '<pre name="code" class="custom_'.$code_type.' '.$code_type.'">';
                break;
            case 'prettify':                
                return '<pre class="custom_'.$code_type.' prettyprint '.$linenums.$line.' lang-'.$code_type.' highlight:'.$highlight.'">';
                break;
            case 'wp-syntax':
                return '<pre lang="'.$code_type.'" class="custom_'.$code_type.'" line="'.$line.'">';
                break;
            case '':
                return '<pre lang="'.$code_type.'" class="custom_'.$code_type.'" >';
                break;
            }
	    }
	    
        function custom_css_shortcode_out ( $atts ) {
		      global $post;
		      $atts = shortcode_atts(array(
			'line'      => '', 
			'linenums' => '',
			'highlight' =>''), $atts);
			extract($atts);
			
		  $pre_open = $this->get_syntax_plugin('css',$line, $linenums, $highlight);

		  $output = $pre_open;
		  $output .= get_post_meta(get_the_ID(), '_custom_css', true);
		  $output .=  $this->pre_close;
		  
		  return do_shortcode($output);
		
		}
		function custom_js_shortcode_out ( $atts ) {
		      global $post;
		      $atts = shortcode_atts(array(
			'line'      => '', 
			'linenums' => '',
			'highlight' =>''), $atts);
			extract($atts);
		
		  $pre_open = $this->get_syntax_plugin('javascript',$line, $linenums, $highlight);
		  
		  $output = $pre_open;
		  $output .= get_post_meta(get_the_ID(), '_custom_js', true);
		  $output .=  $this->pre_close;
		  
		  return do_shortcode($output);		
		
		}
		
		/**
        Adds a links directly to the settings page from the plugin page
        */
        function plugin_links($links, $file) {
            $plugin = plugin_basename(__FILE__);
            if ( $file == $plugin) {
                $links[] = "<a href='options-writing.php#perpage'>" . __('Settings') . "</a>";
                $links[] = "<a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=5RB5FATK5JWXW'><b>" . __('Donate') . "</b></a>";
                
            }
            return $links;
        } 
	
	} //End Class
} //End if class exists statement
/**
*instantiate the class
*/	
$perpost_code = new perpost_code();
?>