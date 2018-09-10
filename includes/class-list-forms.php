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
            <form id="form_list_form" method="post" action="admin.php?page=gfcleaner">
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
            </form>
        </div>
		<?php
	}

	/**
	 * Process any submitted data
	 *
	 * @since  0.0.1
	 * @access public
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function process_action() {

	    // We'll need the WordPress database handler!
	    global $wpdb;

	    // Get the current action
	    $current_action = $this->current_action();

	    // If data was posted and the action matches, then try to grab the form identifier list
	    if( isset( $_POST['action'] ) && $_POST['action'] == $current_action ) {

	        // Note that if the form was posted with confirmation, then the list of forms will be comma-separated, rather than an array
	        if( is_array( $_POST['form'] ) ) {
	            $form = $_POST['form'];
            } else {
	            $form = explode( ',', $_POST['form']);
            }

        // If data was sent via the quick actions, then try to grab the form identifier
        } elseif( isset( $_GET['action'] ) && $_GET['action'] == $current_action ) {
	        if( !is_array( $_GET['form'] ) ) {
	            $form = array( $_GET['form'] );
            }
        }

        // If we don't have any form identifiers, then we've got nothing to do!
        if( ! isset( $form ) ) {
	        return true;
        }

        // Set the current date so that we've got a benchmark starting point
		$current_date = new DateTime;

	    // Find the selected action so that we can set the interval for the SQL query
	    switch( $_REQUEST['action'] ) {

            case 'delete_day_old':
				$query_date = $current_date->sub( new DateInterval( 'P1D' ) );
                break;

			case 'delete_week_old':
				$query_date = $current_date->sub( new DateInterval( 'P7D' ) );
				break;

			case 'delete_month_old':
				$query_date = $current_date->sub( new DateInterval( 'P1M' ) );
				break;

			case 'delete_year_old':
				$query_date = $current_date->sub( new DateInterval( 'P1Y' ) );
				break;

			// If we're deleting everything, just don't set a date!
			case 'delete_all_entries':
				break;

			// If nothing matches, then leave; there's nothing to do!
            default:
                return true;
                break;
        }

        // Convert all form values to integers. This should remove invalid items as a side-effect
		$forms = array_filter( array_map( 'intval', $form ) );

	    // Build the SQL query base for retrieving the list of events to be processed
        $get_forms_sql = "SELECT id FROM {$wpdb->prefix}gf_entry where form_id IN (" . implode( ', ', $forms ) . ')';

        // If the query date has been set, then add it to the query
        if( isset( $query_date ) ) {
            $get_forms_sql .= ' AND date_created < "' . $current_date->format( 'Y-m-d H:i:s' ) . '"';
        }

        // Retrieve the list of events to be deleted
        $results = $wpdb->get_results( $get_forms_sql, OBJECT );

        // If nothing matches the criteria, then advise the user and continue on our merry way!
        if( empty( $results ) ) {
            ?>
			<div class="updated notice">
                <p>Nothing to delete!</p>
            </div>
            <?php

            return true;
        }

        // If the request has been confirmed, then process it
        if ( $_POST['confirm'] == 'Yes' ) {

            // Build the 'where' clause
            $query = "DELETE from {$wpdb->prefix}gf_entry WHERE form_id IN (" . implode( ', ', $forms ) . ')';
            if (isset ( $query_date ) ) {
				$query .= 'AND date_created < "' . $current_date->format( 'Y-m-d H:i:s' ) . '"';
            }

            // Run the deletion and make a note of the number of items removed
            $deleted_entries = $wpdb->query( $query );

			// Let the user know what happened here!
            if( $deleted_entries ) {
				?>
                <div class="updated notice">
                    <p>The requests form <?php echo($deleted_entries == 1 ? 'entry has' : 'entries have'); ?> been
                        cleared!</p>
                </div>
				<?php
			} else {
				?>
                <div class="updated notice">
                    <p>No entries have been deleted!</p>
                </div>
				<?php
            }

            // Exit and allow the displaying of the table!
            return true;

        // If the 'no' button was pressed, cancel the operation!
        } elseif( isset( $_POST['confirm'] ) ) {
			?>
            <div class="updated notice">
                <p>No entries have been deleted!</p>
            </div>
			<?php
            return true;
        }

        // Display the confirmation form
        $count = count($results);
        ?>
            <h3>Confirm Deletion</h3>
            <p><?php echo $count . ( $count == 1 ? ' entry was' : ' entries were'); ?> found matching the specified criteria. Are you sure you wish to delete <?php echo ( $count == 1 ? 'it' : 'them' ); ?>?</p>
            <input type="hidden" name="form" value="<?php echo implode( ',', $forms ); ?>">
            <input type="hidden" name="action" value="<?php echo $_REQUEST['action']; ?>">
            <input type="submit" name="confirm" value="No"> <input type="submit" name="confirm" value="Yes">
        <?php

        // Since we're awaiting confirmation, we don't want the rest of the form to be displayed!
		return false;
    }

	/**
	 * Build the per-row hover actions in the view table
	 *
	 * @since 0.0.1
	 * @access public
     *
     * @param object $form        The form object for the current row
	 * @param string $column_name The current column name
	 * @param string $primary     The primary column name
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
                'url'      => "admin.php?page=gfcleaner&form={$form->id}&action=delete_day_old",
                'priority' => 100
            ),
            'delete_week_old' => array(
                'label'    => '1 week',
                'title'    => 'Delete entries older than 1 week',
				'url'      => "admin.php?page=gfcleaner&form={$form->id}&action=delete_week_old",
                'priority' => 200
            ),
            'delete_month_old' => array(
                'label'    => '1 month',
                'title'    => 'Delete entries older than 1 month',
				'url'      => "admin.php?page=gfcleaner&form={$form->id}&action=delete_month_old",
                'priority' => 300
            ),
            'delete_year_old' => array(
                'label'    => '1 year',
                'title'    => 'Delete entries older than 1 year',
				'url'      => "admin.php?page=gfcleaner&form={$form->id}&action=delete_year_old",
                'priority' => 400
            ),
            'delete_all_entries' => array(
                'label'    => 'All',
                'title'    => 'Delete all entries',
				'url'      => "admin.php?page=gfcleaner&form={$form->id}&action=delete_all_entries",
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