<?php
/*
Plugin Name: TWD TinyMCE Example
Plugin URI: https://www.thewebsitedev.com/
Description: An example plugin to load dynamic content in tinymce popup.
Version: 0.2
Author: Gautam Thapar
Author URI: https://www.thewebsitedev.com/
License: GPLv2 or later
Text Domain: twd
Domain Path: /languages
*/

// Make sure we don't expose any info if called directly
if ( !defined( 'ABSPATH' ) ){
	exit;
}

define( 'TWD__VERSION', '0.1' );
define( 'TWD__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register post type to use in example
 * @return
 */
function codex_custom_init() {
    $args = array(
      	'public' => true,
      	'label'  => 'Books'
    );
    register_post_type( 'book', $args );
}
add_action( 'init', 'codex_custom_init' );

function book_title( $atts ) {
	$atts = shortcode_atts( array(
		'id' => ''
	), $atts, 'twd_post_title' );

	$title = get_the_title($atts['id']);
	return $title;
}
add_shortcode( 'twd_post_title', 'book_title' );

if( !class_exists( 'TWDTinymceExample' ) ) {
	class TWDTinymceExample {

		private static $instance;

		/**
		 * Initiator
		 * @since 0.1
		 */
		public static function init() {
			return self::$instance;
		}

		/**
		 * Constructor
		 * @since 0.1
		 */
		public function __construct() {
			add_action( 'wp_ajax_cpt_list', array( $this, 'list_ajax' ) );
			add_action( 'admin_footer', array( $this, 'cpt_list' ) );
			add_action( 'admin_head', array( $this, 'mce_button' ) );
		}

		// Hooks your functions into the correct filters
		function mce_button() {
			// check user permissions
			if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
				return;
			}
			// check if WYSIWYG is enabled
			if ( 'true' == get_user_option( 'rich_editing' ) ) {
				add_filter( 'mce_external_plugins', array( $this, 'add_mce_plugin' ) );
				add_filter( 'mce_buttons', array( $this, 'register_mce_button' ) );
			}
		}

		// Script for our mce button
		function add_mce_plugin( $plugin_array ) {
			$plugin_array['twd_mce_button'] = TWD__PLUGIN_URL . 'mce.js';
			return $plugin_array;
		}

		// Register our button in the editor
		function register_mce_button( $buttons ) {
			array_push( $buttons, 'twd_mce_button' );
			return $buttons;
		}

		/**
		 * Function to fetch cpt posts list
		 * @since  1.7
		 * @return string
		 */
		public function posts( $post_type ) {

			global $wpdb;
		   	$cpt_type = $post_type;
			$cpt_post_status = 'publish';
	        $cpt = $wpdb->get_results( $wpdb->prepare(
	            "SELECT ID, post_title
	                FROM $wpdb->posts 
	                WHERE $wpdb->posts.post_type = %s
	                AND $wpdb->posts.post_status = %s
	                ORDER BY ID DESC",
	            $cpt_type,
	            $cpt_post_status
	        ) );

	        $list = array();

	        foreach ( $cpt as $post ) {
				$selected = '';
				$post_id = $post->ID;
				$post_name = $post->post_title;
				$list[] = array(
					'text' =>	$post_name,
					'value'	=>	$post_id
				);
			}

			wp_send_json( $list );
		}

		/**
		 * Function to fetch buttons
		 * @since  1.6
		 * @return string
		 */
		public function list_ajax() {
			// check for nonce
			check_ajax_referer( 'twd-nonce', 'security' );
			$posts = $this->posts( 'book' ); // change 'book' to 'post' if you need posts list
			return $posts;
		}
 		
		/**
		 * Function to output button list ajax script
		 * @since  1.6
		 * @return string
		 */
		public function cpt_list() {
			// create nonce
			global $pagenow;
			var_dump($pagenow);
			if( $pagenow != 'admin.php' ){
				$nonce = wp_create_nonce( 'twd-nonce' );
				?>
			    <script type="text/javascript">
					jQuery( document ).ready( function( $ ) {
						var data = {
							'action'	: 'cpt_list',							// wp ajax action
							'security'	: '<?php echo $nonce; ?>'		// nonce value created earlier
						};
						// fire ajax
					  	jQuery.post( ajaxurl, data, function( response ) {
					  		// if nonce fails then not authorized else settings saved
					  		if( response === '-1' ){
						  		// do nothing
						  		console.log('error');
					  		} else {
					  			if (typeof(tinyMCE) != 'undefined') {
					  				if (tinyMCE.activeEditor != null) {
										tinyMCE.activeEditor.settings.cptPostsList = response;
									}
								}
					  		}
					  	});
					});
				</script>
				<?php
			}
		}

		
	} // Mce Class
}

/**
 *  Kicking this off
 */

$twd_mce = new TWDTinymceExample();
$twd_mce->init();
