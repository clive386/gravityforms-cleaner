<?php

// If the main class hasn't been loaded, then this shouldn't be being run; don't allow it!
if ( ! class_exists( 'GFForms_Cleaner' ) ) {
	die();
}

// We're going to use the existing Gravity Forms list table as our base since it already does the lookups we need!
if( ! class_exists( 'GF_Form_List_Table' ) ) {
	require_once(GFCommon::get_base_path() . '/form_list.php');
}

/**
 * Class GFForms_Cleaner_List_Forms
 *
 * This extends the Gravity Forms form list table to provide a list of all forms for processing
 *
 * @version 0.0.1
 * @since   0.0.1
 */
class GFForms_Cleaner_List_Forms extends GF_Form_List_Table {

	/**
	 * Get the list of columns for the view table
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return array
	 */
	public function get_columns() {

	    // Build the list of columns needed for the view
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => esc_html__( 'Title', 'gravityforms' ),
			'id'         => esc_html__( 'ID', 'gravityforms' ),
			'entry_count' => esc_html__( 'Entries', 'gravityforms' ),
		);

		// Allow the list to be filtered
		$columns = apply_filters( 'gfforms_cleaner_list_forms_columns', $columns );

		// Return the list of columns
		return $columns;
	}

	/**
	 * Get the list of bulk actions for the view table
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return array
	 */
	function get_bulk_actions() {

	    // Build the list of bulk actions for the drop-down
		$actions = array(
			'delete_day_old'     => 'Delete entries older than 1 day',
			'delete_week_old'    => 'Delete entries older than 1 week',
			'delete_month_old'   => 'Delete entries older than 1 month',
			'delete_year_old'    => 'Delete entries older than 1 year',
			'delete_all_entries' => 'Delete all entries'
		);

		// Allow the list to be filtered
		$actions = apply_filters( 'gfforms_cleaner_list_forms_actions', $actions );

		// Return the list of bulk actions
		return $actions;
	}

	/**
	 * Build and display the header at the top of the list page
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return void
	 */
	public function display_table_header() {

	    // Check whether Gravity Forms is in debug mode or not; if not, then use the minified CSS file
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		// Output the header!
		?>
		<link rel="stylesheet" href="<?php echo esc_url( GFCommon::get_base_url() ); ?>/css/admin<?php echo $min; ?>.css?ver=<?php echo GFForms::$version ?>"/>
		<div class="wrap <?php echo sanitize_html_class( GFCommon::get_browser_class() ); ?>">

		<h2><?php esc_html_e( 'Entry Cleaner' ); ?></h2>
		<?php
	}

	/**
	 * Build and display the footer below the list table
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return void
	 */
	public function display_table_footer() {

		// Output the table footer! Note that currently this only consists of closing the div we opened in the header!
		?>
        </div>
		<?php
	}

	/**
	 * Build the per-row hover actions in the view table
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return string
	 */
	protected function handle_row_actions( $form, $column_name, $primary ) {

	    // Only add the actions to the primary column
		if ( $primary !== $column_name ) {
			return '';
		}

		// Output the row actions section header
		?>
		<div class="row-actions"><span>Delete entries older than:</span>
        <?php

        // Build the actions list
        $form_actions = array(
            'delete_day_old' => array(
                'label'    => '1 day',
                'title'    => 'Delete entries older than 1 day',
                'url'      => '',
                'priority' => 100
            ),
            'delete_week_old' => array(
                'label'    => '1 week',
                'title'    => 'Delete entries older than 1 week',
                'url'      => '',
                'priority' => 200
            ),
            'delete_month_old' => array(
                'label'    => '1 month',
                'title'    => 'Delete entries older than 1 month',
                'url'      => '',
                'priority' => 300
            ),
            'delete_year_old' => array(
                'label'    => '1 year',
                'title'    => 'Delete entries older than 1 year',
                'url'      => '',
                'priority' => 400
            ),
            'delete_all_entries' => array(
                'label'    => 'All',
                'title'    => 'Delete all entries',
                'url'      => '',
                'priority' => 500
            ),
        );

        // Allow the actions list to be filtered
        $form_actions = apply_filters( 'gform_form_actions', $form_actions, $form->id );

        // Use the Gravity Forms handler to build the output!
        echo GFForms::format_toolbar_menu_items( $form_actions, true );

        // Don't forget to close the actions div!
        ?>
		</div>
		<?php

        // Output the accessibility button if needed, otherwise we're done here!
		return $column_name === $primary ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>' : '';
	}
}