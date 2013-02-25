<?php
/*
Plugin Name: Toggle wpautop
Plugin URI: http://wordpress.org/extend/plugins/toggle-wpautop
Description: Allows the disabling of wpautop filter on a post by post basis.
Version: 1.0
Author: Linchpin
Author URI: http://linchpinagency.com
License: GPLv2
*/

if ( ! class_exists( 'LP_Toggle_wpautop' ) ) {

	class LP_Toggle_wpautop {

		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_post' ) );
			add_action( 'the_post', array( $this, 'the_post' ) );

			add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		}

		/**
		 * add_meta_boxes function.
		 *
		 * @access public
		 * @param mixed $post_type
		 * @return void
		 */
		function add_meta_boxes( $post_type ) {
			//Exclude certain core post types
			if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ) ) )
				return;

			add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ), 5 );
		}

		/**
		 * post_submitbox_misc_actions function.
		 *
		 * @access public
		 * @return void
		 */
		function post_submitbox_misc_actions() {
			global $post;

			wp_nonce_field( '_lp_wpautop_nonce', '_lp_wpautop_noncename' );
			?>
			<div class="misc-pub-section lp-wpautop">
				<span>Disable wpautop:</span> <input type="checkbox" name="_lp_disable_wpautop" id="_lp_disable_wpautop" <?php checked( get_post_meta( $post->ID, '_lp_disable_wpautop', true ) ); ?> /> <span style="float:right; display: block;"><a href="http://codex.wordpress.org/Function_Reference/wpautop" target="_blank">?</a>
			</div>
			<?php
		}

		/**
		 * save_post function.
		 *
		 * @access public
		 * @param mixed $post_id
		 * @return void
		 */
		function save_post( $post_id ) {
			//Skip revisions and autosaves
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
				return;

			//Users should have the ability to edit listings.
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;

			if ( isset( $_POST['_lp_wpautop_noncename'] ) && wp_verify_nonce( $_POST['_lp_wpautop_noncename'], '_lp_wpautop_nonce' ) ) {

				if ( isset( $_POST['_lp_disable_wpautop'] ) && ! empty( $_POST['_lp_disable_wpautop'] ) )
					update_post_meta( $post_id, '_lp_disable_wpautop', 1 );
				else
					delete_post_meta( $post_id, '_lp_disable_wpautop' );
			}
		}

		/**
		 * the_post function.
		 *
		 * @access public
		 * @param mixed $post
		 * @return void
		 */
		function the_post( $post ) {
			if ( get_post_meta( $post->ID, '_lp_disable_wpautop', true ) ) {
				remove_filter( 'the_content', 'wpautop' );
				remove_filter( 'the_excerpt', 'wpautop' );
			} else {
				add_filter( 'the_content', 'wpautop' );
				add_filter( 'the_excerpt', 'wpautop' );
			}
		}

		/**
		 * post_class function.
		 *
		 * @access public
		 * @param mixed $classes
		 * @param mixed $class
		 * @param mixed $post_id
		 * @return void
		 */
		function post_class( $classes, $class, $post_id ) {
			if ( get_post_meta( $post_id, '_lp_disable_wpautop', true ) )
				$classes[] = 'no-wpautop';
			else
				$classes[] = 'wpautop';

			return $classes;
		}
	}
}

$lp_toggle_wpautop = new LP_Toggle_wpautop();