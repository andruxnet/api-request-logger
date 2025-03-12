<?php
/**
 * Plugin Name: API Request Logger
 * Description: Logs all API requests from WordPress.
 * Version: 1.0.0
 * Author: Andres Olvera
 */

if (!defined('ABSPATH')) {
  exit;
}

// Create the table in the database when activating the plugin
function api_request_logger_activate() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'api_request_logs';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        endpoint TEXT NOT NULL,
        method VARCHAR(10) NOT NULL,
        response SMALLINT NOT NULL,
        time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);

  // Also, flush rewrite rules to register the REST API endpoint for json data
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'api_request_logger_activate');

// Delete the table when deactivating the plugin
function api_request_logger_delete_table() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'api_request_logs';
  $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}
register_deactivation_hook(__FILE__, 'api_request_logger_delete_table');

// Function to log the API request to the database
function api_request_logger_log($response, $context, $class, $parsed_args, $url) {
  global $wpdb;

  $table_name = $wpdb->prefix . 'api_request_logs';
  $method = isset($parsed_args['method']) ? strtoupper($parsed_args['method']) : 'GET';
  $status_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);

  $wpdb->insert($table_name, [
      'endpoint' => esc_url_raw($url),
      'method' => $method,
      'response' => $status_code,
      'time' => current_time('mysql')
  ]);
}
add_action('http_api_debug', 'api_request_logger_log', 10, 5);

// Add to the admin menu
function api_request_logger_menu() {
  add_submenu_page(
      'tools.php',
      'Monitor API Logs',
      'Monitor API Logs',
      'manage_options',
      'api-request-logger',
      'api_request_logger_render_page'
  );
}
add_action('admin_menu', 'api_request_logger_menu');

// Render the main page
function api_request_logger_render_page() {
  // Include the custom WP_List_Table which will display the API requests
  require_once __DIR__ . '/class-api-request-logger-table.php';

  echo '<div class="wrap">';
  echo '<h2>API Request Logger</h2>';

  // Adding a test button to send an API request for demonstration purposes
  echo '<form method="post">';
  echo '<input type="hidden" name="test-api-request" value="1">';
  submit_button('Send Test API Request');
  echo '</form>';

  if (!empty($_POST['test-api-request'])) {
    $random_id = rand(1, 100);
    wp_remote_get("https://jsonplaceholder.typicode.com/todos/{$random_id}");
    echo '<div class="updated notice"><p>Test API request sent!</p></div>';
  }

  $log_table = new API_Request_Logger_Table();

  // Process any bulk actions that were triggered
  $log_table->process_bulk_action();

  // Fetch entries from the database
  $log_table->prepare_items();

  // Render the form with search box and table
  echo '<form method="post">';
  echo '<input type="hidden" name="page" value="api-request-logger">';
  $log_table->search_box('Search Logs', 'log-search');
  $log_table->display();
  echo '</form>';

  echo '</div>';
}

// Export logs to CSV
function api_request_logger_export_csv() {
  if (!isset($_GET['export_csv']) || $_GET['page'] !== 'api-request-logger') {
    return;
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'api_request_logs';

  // Fetch all entries
  $logs = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

  if (empty($logs)) {
    wp_die('No logs available to export.');
  }

  // Set headers for CSV download
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=api-request-logs.csv');

  $output = fopen('php://output', 'w');

  // Add CSV column headers
  fputcsv($output, array_keys($logs[0]));

  // Add rows
  foreach ($logs as $log) {
    fputcsv($output, $log);
  }

  // Close the stream
  fclose($output);
  exit;
}
add_action('admin_init', 'api_request_logger_export_csv');

// Callback function to fetch logs in json format
function api_request_logger_get_logs(WP_REST_Request $request) {
  global $wpdb;
  $table_name = $wpdb->prefix . 'api_request_logs';

  $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC", ARRAY_A);

  return rest_ensure_response($logs);
}

// Register the REST API endpoint to fetch logs in json format
function api_request_logger_register_rest_routes() {
  register_rest_route('api-request-logger/v1', '/logs/', [
      'methods'  => 'GET',
      'callback' => 'api_request_logger_get_logs',
      'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'api_request_logger_register_rest_routes');
