<?php
/**
 * Class API_Request_Logger_Table
 *
 * Class that extends WP_List_Table to display the API requests in a table format, with pagination and sorting.
 *
 */
class API_Request_Logger_Table extends WP_List_Table
{
  public function __construct($args = [])
  {
    $args = wp_parse_args($args, [
        'singular' => 'api-request',
        'plural'   => 'api-requests',
        'ajax'     => false
    ]);

    parent::__construct($args);
  }

  /**
   * Define the columns that are going to be used in the table
   */
  public function get_columns(): array
  {
    return [
        'cb'       => '<input type="checkbox" />',
        'endpoint' => 'Endpoint',
        'method'   => 'Method',
        'response' => 'Response',
        'time'     => 'Time',
    ];
  }

  /**
   * Define which columns are sortable
   */
  public function get_sortable_columns(): array
  {
    return [
        'endpoint' => ['endpoint', true],
        'method'   => ['method', true],
        'response' => ['response', true],
        'time'     => ['time', true],
    ];
  }

  /**
   * Prepare the items for the table to display
   */
  public function prepare_items()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'api_request_logs';
    $per_page = 10; // show 10 items per page
    $current_page = $this->get_pagenum();

    // Sorting parameters
    $orderby = !empty($_GET['orderby']) ? esc_sql($_GET['orderby']) : 'time'; // Default sorting by time
    $order   = (!empty($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    // Column headers
    $this->_column_headers = [
        $this->get_columns(),
        [],
        $this->get_sortable_columns(),
        'ID'
    ];

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Fetch paginated and sorted results from the database
    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
        $per_page,
        ($current_page - 1) * $per_page
    ), ARRAY_A);

    // Set pagination arguments
    $this->set_pagination_args([
        'total_items' => $total_items,
        'per_page'    => $per_page,
        'total_pages' => ceil($total_items / $per_page)
    ]);

    $this->items = $requests;
  }

  /**
   * Render the checkbox column
   */
  public function column_cb($item): string
  {
    return sprintf(
        '<input type="checkbox" name="bulk-delete[]" value="%d" />',
        esc_attr($item['id'])
    );
  }

  /**
   * Render the column values
   */
  public function column_default($item, $column_name): string
  {
    switch ($column_name) {
      case 'endpoint':
      case 'method':
      case 'response':
      case 'time':
        return $item[$column_name];
      case 'actions':
        $delete_url = add_query_arg([
            'page'    => 'api-request-logger',
            'action'  => 'delete',
            'log_id'  => $item['id'],
            '_wpnonce' => wp_create_nonce('delete_log')
        ], admin_url('tools.php'));

        return sprintf('<a href="%s" onclick="return confirm(\'Are you sure you want to delete this log?\')">Delete</a>', esc_url($delete_url));
      default:
        return '';
    }
  }

  /**
   * Define the items in the bulk actions dropdown
   */
  public function get_bulk_actions(): array
  {
    return [
        'delete' => 'Delete selected'
    ];
  }

  /**
   * Process the triggered bulk action
   */
  public function process_bulk_action()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'api_request_logs';

    // Delete selected logs from the database
    if ($this->current_action() === 'delete' && !empty($_POST['bulk-delete'])) {
      $wpdb->query("DELETE FROM $table_name WHERE id IN (" . implode(',', $_POST['bulk-delete']) . ")");
    }
  }
}