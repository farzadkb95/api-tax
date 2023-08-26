 <?php
/**
 * Plugin Name: api-tax
 * Description: Get data from tax government API and store it in modi_farzad table.
 * Plugin URI: https://tarrahiweb.com
 * Author: farzad beheshti
 * Version: 3.14.1
 * Elementor tested up to: 3.14.0
 * Author URI: https://go.elementor.com/wp-dash-wp-plugins-author-uri/
 *
 * Text Domain: elementor-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register the activation hook to create the table and fetch API data upon plugin activation
register_activation_hook(__FILE__, 'api_tax_plugin_activation');

function api_tax_plugin_activation()
{
    // Create the table if it doesn't exist
    global $wpdb;
    $table_name = $wpdb->prefix . 'modi_farzad'; // Replace 'modi_farzad' with your desired table name

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        ix INT(11) NOT NULL,
        cd VARCHAR(255) NOT NULL,
        tp VARCHAR(255) NOT NULL,
        dt VARCHAR(255) NOT NULL,
        tt VARCHAR(255) NOT NULL,
        rt VARCHAR(255) NOT NULL,
        ds TEXT,
        vp TEXT,
        sg TEXT,
        PRIMARY KEY (id),
        UNIQUE KEY ix (ix)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Schedule the daily API data fetch event
    if (!wp_next_scheduled('daily_api_data_fetch')) {
        wp_schedule_event(strtotime('09:55:00'), 'daily', 'daily_api_data_fetch');
    }
}

// Function to fetch and store the API data
function fetch_api_data()
{
    // Set the timeout value for the API request
    $timeout = 2500; // Timeout value in seconds (adjust as needed)

    // Fetch data from the API
  //  $url = 'https://api.mega-pay.ir/api/stuffid/query?page=86&items_per_page=10000';
      $url = 'https://api.mega-pay.ir/api/stuffid/query?page=29&items_per_page=50000';

   //   $url = 'https://mandegaracc.ir/new/formatted_output2.json';

    $response = wp_remote_get($url, array('timeout' => $timeout));

    if (is_wp_error($response)) {
        // Handle error if the API request fails
        error_log($response->get_error_message());
        return;
    }

    $api_data = json_decode(wp_remote_retrieve_body($response), true);

    if (!$api_data) {
        // Handle error if JSON parsing fails
        error_log('Failed to parse JSON data.');
        return;
    }

    $data = isset($api_data['data']) ? $api_data['data'] : array();

    if (empty($data)) {
        // Handle error if data array is empty
        error_log('No data found in the API response.');
        return;
    }

    // Store the data in the WordPress database, checking for duplicates
    global $wpdb;
    $table_name = $wpdb->prefix . 'modi_farzad';

    foreach ($data as $item) {
        // Check if the item already exists in the database
        $existing_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ix = %d", $item['ix']), ARRAY_A);

        if (!$existing_item) {
            // Prepare the data for insertion and sanitize it for security
            $data_to_insert = array(
                'ix' => isset($item['ix']) ? absint($item['ix']) : 0,
                'cd' => isset($item['cd']) ? sanitize_text_field($item['cd']) : '',
                'tp' => isset($item['tp']) ? sanitize_text_field($item['tp']) : '',
                'dt' => isset($item['dt']) ? sanitize_text_field($item['dt']) : '',
                'tt' => isset($item['tt']) ? sanitize_text_field($item['tt']) : '',
                'rt' => isset($item['rt']) ? sanitize_text_field($item['rt']) : '',
                'ds' => isset($item['ds']) ? wp_kses_post($item['ds']) : '',
                'vp' => isset($item['vp']) ? wp_kses_post($item['vp']) : '',
                'sg' => isset($item['sg']) ? wp_kses_post($item['sg']) : '',
            );

            // Insert the new item into the database
            $wpdb->insert($table_name, $data_to_insert);
        }
    }

    // Log a success message
    error_log('Data fetched from the API and stored in the database successfully.');

    // Log the retrieved data for debugging purposes
    error_log('Retrieved data:');
    error_log(print_r($data, true));
}

// Function to fetch API data daily
function fetch_api_data_daily()
{
    // Call the existing fetch_api_data function
    fetch_api_data();
}
add_action('daily_api_data_fetch', 'fetch_api_data_daily');

// Add shortcode to display the table
add_shortcode('api_data_table', 'api_data_table_shortcode');

function api_data_table_shortcode($atts)
{
    // Fetch the API data and store it in the database
    fetch_api_data();

    ob_start();
    include plugin_dir_path(__FILE__) . 'data-table.php';
    return ob_get_clean();
}

// AJAX endpoint for getting API data
add_action('wp_ajax_get_api_data', 'get_api_data_ajax_handler');
add_action('wp_ajax_nopriv_get_api_data', 'get_api_data_ajax_handler');
 function get_api_data_ajax_handler()
{
    global $wpdb;

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $itemsPerPage = isset($_POST['itemsPerPage']) ? intval($_POST['itemsPerPage']) : 50;
    $searchQuery = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $table_name = $wpdb->prefix . 'modi_farzad';

    // Calculate the offset for pagination
    $offset = ($page - 1) * $itemsPerPage;

    // Prepare the SQL query with sorting, pagination, and search
    $sql = "SELECT * FROM $table_name";
   
    if (!empty($searchQuery)) {
        $sql .= " WHERE cd LIKE %s OR tp LIKE %s OR dt LIKE %s OR tt LIKE %s OR rt LIKE %s OR ds LIKE %s";
        $sql = $wpdb->prepare($sql, '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%');
    }
   
    if ($wpdb->last_error) {
        // Log database query errors
        error_log('Database query error: ' . $wpdb->last_error);
        wp_send_json_error('Database query error occurred.');
    }
    $sql .= " ORDER BY id DESC LIMIT %d OFFSET %d";
    $sql = $wpdb->prepare($sql, $itemsPerPage, $offset);

    // Fetch data from the database
    $results = $wpdb->get_results($sql, ARRAY_A);

    // Calculate the total number of pages for pagination
    $totalItems = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Prepare table rows HTML
    $tableRows = '';
    foreach ($results as $row) {
        $tableRows .= '<tr>';
        $tableRows .= '<td>' . $row['id'] . '</td>';
        $tableRows .= '<td>' . $row['cd'] . '</td>';
        $tableRows .= '<td>' . $row['tp'] . '</td>';
        $tableRows .= '<td>' . $row['dt'] . '</td>'; // Add "تاریخ" field
        $tableRows .= '<td>' . $row['tt'] . '</td>'; // Add "وضعیت" field
        $tableRows .= '<td>' . $row['rt'] . '</td>'; // Add "درصد مالیات بر ارزش افزوده" field
        $tableRows .= '<td>' . $row['ds'] . '</td>'; // Add "توضیحات" field
        $tableRows .= '</tr>';
    }

    // Prepare pagination HTML
    $pagination = '';
    if ($totalPages > 1) {
        $pagination .= '<ul class="api-data-pagination">';
        // "First" page link
        $first_text = 'صفحه اول';
        $pagination .= '<li><a href="#" data-page="1" class="pagination-link">' . $first_text . '</a></li>';
        for ($i = 1; $i <= $totalPages; $i++) {
            // Show up to 5 pages before and after the current page
            if ($i === 1 || $i === $totalPages || ($i >= $page - 5 && $i <= $page + 5)) {
                $active = $i === $page ? 'class="active"' : '';
                $pagination .= '<li><a href="#" data-page="' . $i . '"' . $active . ' class="pagination-link">' . $i . '</a></li>';
            } elseif ($i === $page - 6 || $i === $page + 6) {
                // Add an ellipsis (...) before/after the skipped pages
                $pagination .= '<li><span class="ellipsis">...</span></li>';
            }
        }
        // "Last" page link
        $last_text = 'صفحه آخر';
        $pagination .= '<li><a href="#" data-page="' . $totalPages . '" class="pagination-link">' . $last_text . '</a></li>';
        $pagination .= '</ul>';
    }

    // Return the response as JSON
    $response = array(
        'tableRows' => $tableRows,
        'pagination' => $pagination,
        'currentPage' => $page,
        'totalPages' => $totalPages
    );

    wp_send_json($response);
}

// AJAX endpoint for getting API data with status and percentage filter
add_action('wp_ajax_get_api_data_with_filter', 'get_api_data_with_filter_ajax_handler');
add_action('wp_ajax_nopriv_get_api_data_with_filter', 'get_api_data_with_filter_ajax_handler');

function get_api_data_with_filter_ajax_handler()
{
    global $wpdb;

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $itemsPerPage = isset($_POST['itemsPerPage']) ? intval($_POST['itemsPerPage']) : 50;
    $searchQuery = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $statusFilter = isset($_POST['statusFilter']) ? sanitize_text_field($_POST['statusFilter']) : '';
    $percentageFilter = isset($_POST['percentageFilter']) ? sanitize_text_field($_POST['percentageFilter']) : '';
    $namayandeFilter = isset($_POST['filter_namayande']) ? sanitize_text_field($_POST['filter_namayande']) : '';
    $table_name = $wpdb->prefix . 'modi_farzad';

    // Calculate the offset for pagination
    $offset = ($page - 1) * $itemsPerPage;

    // Prepare the SQL query with sorting, pagination, search, status filter, and percentage filter
    $sql = "SELECT * FROM $table_name WHERE 1=1";
    if (!empty($searchQuery)) {
        $sql .= " AND (cd LIKE %s OR tp LIKE %s OR dt LIKE %s OR tt LIKE %s OR rt LIKE %s OR ds LIKE %s)";
        $sql = $wpdb->prepare($sql, '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%');
    }
    if (!empty($statusFilter)) {
        $sql .= " AND tt = %s";
        $sql = $wpdb->prepare($sql, $statusFilter);
    }
    if (!empty($percentageFilter)) {
        $sql .= " AND rt = %s";
        $sql = $wpdb->prepare($sql, $percentageFilter);
    }
   if (!empty($namayandeFilter)) {
        $sql .= " AND tp = %s";
           $sql = $wpdb->prepare($sql, $namayandeFilter);
    }
    $sql .= " ORDER BY id DESC LIMIT %d OFFSET %d";
    $sql = $wpdb->prepare($sql, $itemsPerPage, $offset);

    // Fetch data from the database
    $results = $wpdb->get_results($sql, ARRAY_A);

    // Calculate the total number of pages for pagination
 $totalItems = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE 1=1" .
    (!empty($searchQuery) ? $wpdb->prepare(" AND (cd LIKE %s OR tp LIKE %s OR dt LIKE %s OR tt LIKE %s OR rt LIKE %s OR ds LIKE %s)", '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%') : '') .
    (!empty($statusFilter) ? $wpdb->prepare(" AND tt = %s", $statusFilter) : '') .
    (!empty($percentageFilter) ? $wpdb->prepare(" AND rt = %s", $percentageFilter) : '') .
    (!empty($namayandeFilter) ? $wpdb->prepare(" AND tp = %s", $namayandeFilter) : '')
);
  $totalPages = ceil($totalItems / $itemsPerPage);

    // Prepare table rows HTML
    $tableRows = '';
    foreach ($results as $row) {
        $tableRows .= '<tr>';
        $tableRows .= '<td>' . $row['id'] . '</td>';
        $tableRows .= '<td>' . $row['cd'] . '</td>';
        $tableRows .= '<td>' . $row['tp'] . '</td>';
        $tableRows .= '<td>' . $row['dt'] . '</td>'; // Add "تاریخ" field
        $tableRows .= '<td>' . $row['tt'] . '</td>'; // Add "وضعیت" field
        $tableRows .= '<td>' . $row['rt'] . '</td>'; // Add "درصد مالیات بر ارزش افزوده" field
        $tableRows .= '<td>' . $row['ds'] . '</td>'; // Add "توضیحات" field
        $tableRows .= '</tr>';
    }

    // Prepare pagination HTML
    $pagination = '';
  

    if ($totalPages > 1) {
        $pagination .= '<ul class="api-data-pagination">';
        // "First" page link
        $first_text = 'صفحه اول';
        $pagination .= '<li><a href="#" data-page="1" class="pagination-link">' . $first_text . '</a></li>';
        for ($i = 1; $i <= $totalPages; $i++) {
            // Show up to 5 pages before and after the current page
            if ($i === 1 || $i === $totalPages || ($i >= $page - 5 && $i <= $page + 5)) {
                $active = $i === $page ? 'class="active"' : '';
                $pagination .= '<li><a href="#" data-page="' . $i . '"' . $active . ' class="pagination-link">' . $i . '</a></li>';
            } elseif ($i === $page - 6 || $i === $page + 6) {
                // Add an ellipsis (...) before/after the skipped pages
                $pagination .= '<li><span class="ellipsis">...</span></li>';
            }
        }
        // "Last" page link
        $last_text = 'صفحه آخر';
        $pagination .= '<li><a href="#" data-page="' . $totalPages . '" class="pagination-link">' . $last_text . '</a></li>';
        $pagination .= '</ul>';
    }
    $totalRecords = $totalItems;

    // Return the response as JSON
    $response = array(
        'tableRows' => $tableRows,
        'pagination' => $pagination,
        'currentPage' => $page,
        'totalPages' => $totalPages,
              'totalRecords' => $totalRecords, // Include the totalRecords field

    );

    wp_send_json($response);
}

// Enqueue the JavaScript file and pass the ajaxurl to it
add_action('wp_enqueue_scripts', 'enqueue_api_tax_scripts');
function enqueue_api_tax_scripts()
{
        wp_enqueue_script('jquery'); // Make sure jQuery is loaded first

    // Enqueue the plugin's custom styles
    wp_enqueue_style('api-tax-custom-style', plugins_url('api-tax-style.css', __FILE__));

    // Enqueue the plugin's data table script
    wp_enqueue_script('api-tax-data-table', plugins_url('data-table.js', __FILE__), array('jquery'), '1.0.0', true);

    // Enqueue the new JavaScript file for handling AJAX requests
    wp_enqueue_script('api-tax-ajax-handler', plugins_url('ajax-handler.js', __FILE__), array('jquery'), '1.0.0', true);

    // Localize the ajaxurl and the URL of the loading circle image
    wp_localize_script('api-tax-ajax-handler', 'api_tax_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'), // Define the ajaxurl variable
        'loading_img_url' => 'https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif', // Use the provided GIF URL
    ));
}

function enqueue_api_tax_style()
{
    echo '<style>';
    include plugin_dir_path(__FILE__) . 'api-tax-style.css';
    echo '</style>';
}
add_action('wp_head', 'enqueue_api_tax_style');


add_shortcode('fetch_api_data_button', 'fetch_api_data_button_shortcode');
function fetch_api_data_button_shortcode()
{
    return '<a href="' . admin_url('admin-ajax.php?action=trigger_api_data_fetch') . '">Fetch API Data</a>';
}
function enqueue_custom_scriptss() {
    wp_enqueue_script('jquery'); // Enqueue jQuery
    wp_enqueue_script('ajax-handler', get_template_directory_uri() . '/ajax-handler.js', array('jquery'), '1.0', true); // Enqueue ajax-handler.js
    wp_enqueue_script('data-table', get_template_directory_uri() . '/data-table.js', array('jquery'), '1.0', true); // Enqueue data-table.js
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scriptss');

function custom_page_redirectss() {
    // Check if the user is logged in and visiting the specific page with ID 12723
    if (is_user_logged_in() && is_page(12723)) {
        wp_redirect('https://mandegaracc.ir/kalas/');
        exit;
    }
}
add_action('template_redirect', 'custom_page_redirectss');





// Step 1: Form Submission Handling
add_action('gform_after_submission_36', 'custom_process_form_submission', 10, 2);
function custom_process_form_submission($entry, $form) {
    $user_id = get_current_user_id();
    if ($user_id) {
        // Update user's data (e.g., user_meta) to mark the form as submitted.
        update_user_meta($user_id, 'submitted_form_36', true);
    }
}

// Step 2: Form Display Handling
add_action('template_redirect', 'custom_check_form_access');
function custom_check_form_access() {
    if (is_page(12688)) { // Replace with the actual page ID
        $user_id = get_current_user_id();
        if ($user_id) {
            $has_submitted = get_user_meta($user_id, 'submitted_form_36', true);
            if ($has_submitted) {
                // Redirect if the user has submitted the form.
                wp_redirect('https://mandegaracc.ir/stuffid');
                exit();
            }
        } else {
            // Redirect non-logged-in users to the login page.
        wp_redirect('https://mandegaracc.ir/custom-login/');
            exit();
        }
    }
}
function populate_phone_number($field, $entry, $form) {
    // Check if the form ID is 36
    if ($form['id'] == 36) {
        // Check if the field ID is input_36_3
        if ($field['id'] == 'input_36_3') { // Replace with your actual field ID
            // Get the current user's ID
            $user_id = get_current_user_id();
            
            // Check if the user is logged in and has a phone number
            if ($user_id && $user_phone = get_user_meta($user_id, 'phone_number', true)) {
                $field['defaultValue'] = $user_phone;
            }
        }
    }
    return $field;
}
add_filter('gform_field_value', 'populate_phone_number', 10, 3);

/*
  function disable_right_click_on_specific_page() {
    if (is_page('12571')) { // Replace 12571 with your actual page ID
        echo '<script>
            document.addEventListener("contextmenu", function(e) {
                e.preventDefault();
            });
            document.addEventListener("keydown", function(e) {
                if (e.ctrlKey && (e.keyCode === 67 || e.keyCode === 88)) { // Disable Ctrl+C and Ctrl+X
                    e.preventDefault();
                }
            });
        </script>';
    }
}
add_action('wp_footer', 'disable_right_click_on_specific_page');