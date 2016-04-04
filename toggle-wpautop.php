<?php
/**
Plugin Name: Toggle wpautop
Plugin URI: http://wordpress.org/extend/plugins/toggle-wpautop
Description: Allows the disabling of wpautop filter on a Post by Post basis. Toggle can also be enabled on a Post Type by Post Type basis or globally
Version: 1.2.2
Author: Linchpin
Author URI: http://linchpin.agency/wordpress-plugins/toggle-wpautop?utm_source=toggle-wpautop&utm_medium=plugin-admin-page&utm_campaign=wp-plugin
License: GPLv2
*/

// Make sure we don't expose any info if called directly.
if ( ! function_exists( 'add_action' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Toggle_wpautop' ) ) {

	/**
	 * LP_Toggle_wpautop class.
	 */
	class LP_Toggle_wpautop {

		/**
		 * LP_Toggle_wpautop constructor.
		 */
		function __construct() {
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			add_action( 'admin_init', array( $this, 'activation' ) ); // This will upgrade users who had version 1.0 since register_activation_hook does not fire on plugin upgrade.

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_post' ) );
			add_action( 'the_post', array( $this, 'the_post' ) );
			add_action( 'loop_end', array( $this, 'loop_end' ) );

			add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		}

		/**
		 * By default, add the ability to disable wpautop on all registered post types
		 *
		 * @access public
		 * @return void
		 */
		function activation() {
			if ( $settings = get_option( 'lp_toggle_wpautop_settings' ) ) {
				return;
			}

			$post_types = get_post_types();

			if ( empty( $post_types ) ) {
				return;
			}

			$default_post_types = array();

			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ) ) || ! $post_type_object->public ) {
					continue;
				}

				$default_post_types[] = $post_type;
			}

			if ( ! empty( $default_post_types ) ) {
				add_option( 'lp_toggle_wpautop_settings', $default_post_types );
			}
		}

		/**
		 * Add our settings fields to the writing page
		 *
		 * @access public
		 * @return void
		 */
		function admin_init() {
			register_setting( 'writing', 'lp_toggle_wpautop_settings', array( $this, 'sanitize_settings' ) );
			register_setting( 'writing', 'lp_toggle_wpautop_auto' );

			// Add a section for the plugin's settings on the writing page.
			add_settings_section( 'lp_toggle_wpautop_settings_section', __( 'Toggle wpautop', 'toggle-wpautop' ), array( $this, 'settings_section_text' ), 'writing' );

			// For each post type add a settings field, excluding revisions and nav menu items.
			if ( $post_types = get_post_types() ) {

				add_settings_field( 'lp_toggle_wpautop_auto', __( 'Auto Enable', 'toggle-wpautop' ), array( $this, 'toggle_wpautop_auto_field' ), 'writing', 'lp_toggle_wpautop_settings_section' );

				$show_private_post_types = apply_filters( 'lp_wpautop_show_private_pt', false );

				foreach ( $post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ) ) || ( ! $show_private_post_types && ! $post_type_object->public ) ) {
						continue;
					}

					add_settings_field( 'lp_toggle_wpautop_post_types' . $post_type, $post_type_object->labels->name, array( $this, 'toggle_wpautop_field' ), 'writing', 'lp_toggle_wpautop_settings_section', array( 'slug' => $post_type_object->name, 'name' => $post_type_object->labels->name ) );
				}
			}
		}

		/**
		 * Display our settings section
		 *
		 * @access public
		 * @return void
		 */
		function settings_section_text() {
			?>
			<p>
				<?php esc_html_e( 'Select which post types have the option to disable the wpautop filter.', 'toggle-wpautop' ); ?>
			</p>
			<?php
		}

		/**
		 * Add our settings checboxes
		 *
		 * @access public
		 * @return void
		 */
		function toggle_wpautop_auto_field() {
			?>
			<input type="checkbox" name="lp_toggle_wpautop_auto" id="lp_toggle_wpautop_auto" value="1" <?php checked( get_option( 'lp_toggle_wpautop_auto', 0 ) ); ?> />
			<span class="description"><?php esc_html_e( 'Disable wpautop on all new posts.', 'toggle-wpautop' ); ?></span>
			<?php
		}

		/**
		 * Display the actual settings field
		 *
		 * @access public
		 * @param mixed $args Customization Options.
		 * @return void
		 */
		function toggle_wpautop_field( $args ) {
			$settings = get_option( 'lp_toggle_wpautop_settings', array() );

			if ( $post_types = get_post_types() ) { ?>
				<input type="checkbox" name="lp_toggle_wpautop_post_types[]" id="lp_toggle_wpautop_post_types_<?php echo $args['slug']; ?>" value="<?php echo $args['slug']; ?>" <?php in_array( $args['slug'], $settings ) ? checked( true ) : checked( false ); ?>/>
				<?php
			}
		}

		/**
		 * Sanitize our settings fields
		 *
		 * @access public
		 * @param mixed $input Input Options
		 *
		 * @return array
		 */
		function sanitize_settings( $input ) {
			$input = wp_parse_args( $_POST['lp_toggle_wpautop_post_types'], array() );

			$new_input = array();

			foreach ( $input as $pt ) {
				if ( post_type_exists( sanitize_text_field( $pt ) ) ) {
					$new_input[] = sanitize_text_field( $pt );
				}
			}

			return $new_input;
		}

		/**
		 * Add meta boxes to the selected post types
		 *
		 * @access public
		 * @param  mixed $post_type
		 * @return void
		 */
		function add_meta_boxes( $post_type ) {
			$settings = get_option( 'lp_toggle_wpautop_settings', array() );

			if ( empty( $settings ) ) {
				return;
			}

			if ( in_array( $post_type, $settings ) ) {
				add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ), 5 );
			}
		}

		/**
		 * Display a checkbox to disable the wpautop filter
		 *
		 * @access public
		 * @return void
		 */
		function post_submitbox_misc_actions() {
			global $post;

			wp_nonce_field( '_lp_wpautop_nonce', '_lp_wpautop_noncename' );

			$screen = get_current_screen();
			if ( ! empty( $screen->action ) && 'add' === $screen->action && get_option( 'lp_toggle_wpautop_auto', 0 ) ) {
				$checked = true;
			} else {
				$checked = get_post_meta( $post->ID, '_lp_disable_wpautop', true );
			}

			?>
			<div class="misc-pub-section lp-wpautop">
				<span><?php esc_html_e( 'Disable wpautop:', 'toggle-wpautop' ); ?></span> <input type="checkbox" name="_lp_disable_wpautop" id="_lp_disable_wpautop" <?php checked( $checked ); ?> /> <span style="float:right; display: block;"><a href="http://codex.wordpress.org/Function_Reference/wpautop" target="_blank">?</a>
			</div>
			<?php
		}

		/**
		 * Process the wpautop checkbox
		 *
		 * @access public
		 * @param  mixed $post_id Post ID.
		 * @return void
		 */
		function save_post( $post_id ) {

			// Skip revisions and autosaves.
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}

			// Users should have the ability to edit listings.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( isset( $_POST['_lp_wpautop_noncename'] ) && wp_verify_nonce( $_POST['_lp_wpautop_noncename'], '_lp_wpautop_nonce' ) ) {

				if ( isset( $_POST['_lp_disable_wpautop'] ) && ! empty( $_POST['_lp_disable_wpautop'] ) ) {
					update_post_meta( $post_id, '_lp_disable_wpautop', 1 );
				} else {
					delete_post_meta( $post_id, '_lp_disable_wpautop' );
				}
			}
		}

		/**
		 * Add or remove the wpautop filter
		 *
		 * @access public
		 * @param  mixed $post Current Post Object.
		 * @return void
		 */
		function the_post( $post ) {
			if ( get_post_meta( $post->ID, '_lp_disable_wpautop', true ) ) {
				remove_filter( 'the_content', 'wpautop' );
				remove_filter( 'the_excerpt', 'wpautop' );
			} else {
				if ( ! has_filter( 'the_content', 'wpautop' ) ) {
					add_filter( 'the_content', 'wpautop' );
				}

				if ( ! has_filter( 'the_excerpt', 'wpautop' ) ) {
					add_filter( 'the_excerpt', 'wpautop' );
				}
			}
		}

		/**
		 * After we run our loop, everything should be set back to normal
		 *
		 * @access public
		 * @return void
		 */
		function loop_end() {
			if ( ! has_filter( 'the_content', 'wpautop' ) ) {
				add_filter( 'the_content', 'wpautop' );
			}

			if ( ! has_filter( 'the_excerpt', 'wpautop' ) ) {
				add_filter( 'the_excerpt', 'wpautop' );
			}
		}

		/**
		 * Add a class to posts noting whether they were passed through the wpautop filter
		 *
		 * @param mixed $classes Array of Post Classes.
		 * @param mixed $class   Current Class.
		 * @param int   $post_id Post ID.
		 *
		 * @return array
		 */
		function post_class( $classes, $class, $post_id ) {
			if ( get_post_meta( $post_id, '_lp_disable_wpautop', true ) ) {
				$classes[] = 'no-wpautop';
			} else {
				$classes[] = 'wpautop';
			}

			return $classes;
		}
	}
}

$lp_toggle_wpautop = new LP_Toggle_wpautop();

/**
 * Delete everything created by the plugin
 *
 * @access public
 * @return void
 */
function toggle_wpautop_uninstall() {
	// Delete post meta entries.
	delete_post_meta_by_key( '_lp_disable_wpautop' );

	// Delete settings.
	delete_option( 'lp_toggle_wpautop_settings' );
}

register_uninstall_hook( __FILE__, 'toggle_wpautop_uninstall' );
