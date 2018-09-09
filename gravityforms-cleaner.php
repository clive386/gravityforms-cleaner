<?php
/*
 * Plugin Name: Gravity Forms Submission Cleaner
 * Description: Easily clean old or expired submissions from Gravity Forms
 *
 * Plugin URI: https://github.com/clive386/gravityforms-cleaner
 * Version: 0.0.1
 *
 * Author: Clive Martin
 */

// Add the initialisation sequence to WordPress
add_action( 'plugins_loaded', array( 'GFForms_Cleaner', 'plugins_loaded' ) );

/**
 * Class GFForms_Cleaner
 *
 * This handles all functions used for the admin configuration and initialisation of the Gravity Forms cleaner
 *
 * @version 0.0.1
 * @since   0.0.1
 */
class GFForms_Cleaner {

	/**
	 * Set up the initial environment for our plugin
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return void
	 */
	public function plugins_loaded() {

		// Get the details for the currently logged-in user
		$user_info = wp_get_current_user();

		// If the user has the 'administrator' role, then activate the features!
		if( in_array( 'administrator', $user_info->roles ) ) {

			// Queue our main initialisation function
			add_action( 'init', array( 'GFForms_Cleaner', 'init' ) );
		}
	}

	/**
	 * Once WordPress has finished loading everything, we can configure out plugin features!
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return void
	 */
	public function init() {

		// Check that we're in the admin panel; WordPress sometimes reports this incorrectly, so we have to include additional checks!
		if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

			// Add the menu updater to the admin menu handler
			add_action( 'admin_menu', array( 'GFForms_Cleaner', 'create_menu_entry' ) );
		}
	}

	/**
	 * Create a menu entry for the cleaning view
	 *
	 * @since  0.0.1
	 * @access public
	 *
	 * @return void	 *
	 */
	public function create_menu_entry() {

		// Determine whether the user has full access or not
		$has_full_access = current_user_can( 'gform_full_access' );

		// Now we need the parent menu ID. We'll have to ask Gravity Forms for the menu configuration
		$parent_menu = GFForms::get_parent_menu( array() );

		// Add the menu item to the bottom of the Gravity Forms menu
		add_submenu_page(
			$parent_menu['name'],
			'Entry Cleaner',
			'Entry Cleaner',
			$has_full_access ? 'gform_full_access' : 'manage_options',
			'gfcleaner',
			array( 'GFForms_Cleaner', 'list_forms' )
		);
	}

	/**
	 * Build and display the list form view
	 *
	 * @since  0.0.1
	 * @access public
	 *
	 * @return void	 *
	 */
	public function list_forms() {

		// Include the list form class
		require_once( 'includes/class-list-forms.php' );

		// Instantiate our modified version of the form list view
		$list_forms = new GFForms_Cleaner_List_Forms;

		// Include our custom table header
		$list_forms->display_table_header();

		// Process any current actions that need to be run
		$list_forms->process_action();

		// Prepare the list of forms to be displayed
		$list_forms->prepare_items();

		// Output the form
		$list_forms->display();

		// Include our custom table header
		$list_forms->display_table_footer();
	}
}