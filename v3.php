<?php
/**
 * Plugin Name: api-tax
 * Description: Get data from tax government API and store it in modi_farzad table.
 * Plugin URI: https://tarrahiweb.com
 * Author: farzad beheshti
 * Version: 3.14.1
 * Elementor tested up to: 3.14.0
 * Author URI: https:/tarrahiweb.com
 *
 * Text Domain: elementor-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register the activation hook to create the table and fetch API data upon plugin activation
register_activation_hook(__FILE__, 'api_tax_plugin_activation');
require_once(plugin_dir_path(__FILE__) . 'libs/phpspreadsheet/autoload.php');
add_action('admin_menu', 'excel_upload_menu');

function excel_upload_menu() {
    add_menu_page('Excel Upload', 'آپلود فایل شناسه کالا خدمات  ', 'manage_options', 'excel_upload', 'excel_upload_admin_page');
}
function enqueue_jquery() {
    // Enqueue jQuery from Google CDN
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), '3.6.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

function excel_upload_admin_page() {
    echo '<div style="border: 1px solid #ccc; border-radius: 5px; width: 50%; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin: auto; margin-top:3rem; text-align:center;">
            <form action="" method="post" enctype="multipart/form-data" style="margin-top: 20px;">
                <p style="margin-bottom: 10px;">بارگذاری فایل اکسل :</p>
                 <input type="file" name="csv_file" id="csv_file" style="margin-bottom: 10px;">
				</br>
                <input type="submit" name="upload" value="بارگذاری" style="margin: auto;   background-color: red; border-radius:6px;box-shaddow:1px 1px black; width:35%; color: #fff; border: none; padding: 8px 16px; cursor: pointer;">
            </form>
          </div>';
          
    if(isset($_POST['upload'])) {
        // delete_existing_records(); // Delete existing records
        handle_excel_upload(); // Insert new records
    }
}

function handle_excel_upload() {
    $uploaded_file = $_FILES['csv_file'];
    $file_path = $uploaded_file['tmp_name'];
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    global $wpdb;
    $table_name = $wpdb->prefix . 'modi_farzad2';

    // Increase PHP memory limit if needed
    ini_set('memory_limit', '512M');

    // Disable autocommit and keys for better performance
    $wpdb->query('SET autocommit = 0');
    $wpdb->query("ALTER TABLE $table_name DISABLE KEYS");

    $batch_size = 1000; // Number of rows to process in each batch
    $total_rows = count($rows);

    for ($i = 0; $i < $total_rows; $i += $batch_size) {
        $batch_rows = array_slice($rows, $i, $batch_size);

        // Start a transaction for each batch
        $wpdb->query('START TRANSACTION');

        foreach ($batch_rows as $index => $row) {
            if ($index == 0) continue; // Skip header

            $normalized_onvan_kala = Normalizer::normalize($row[2], Normalizer::FORM_C);
            $cd = $row[0]; // Assuming the 'cd' value is in the first column

            // Check if a row with the same 'cd' value exists
            $existing_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE cd = %d", $cd), ARRAY_A);

            if ($existing_row) {
                // Row with the same 'cd' value exists, update the row
                $update_query = $wpdb->prepare("UPDATE $table_name SET tp = %s, dt = %s, tt = %s, rt = %s, ds = %s WHERE cd = %d", $row[1], $row[2], $row[6], $row[8], $row[9], $cd);
                $update_result = $wpdb->query($update_query);

                if ($update_result === false) {
                    // Log error if update fails
                    error_log('Error updating data: ' . $wpdb->last_error);
                }
            } else {
                // Row with the same 'cd' value doesn't exist, insert a new row
                $insert_query = $wpdb->prepare("INSERT INTO $table_name (cd, tp, dt, tt, rt, ds) VALUES (%d, %s, %s, %s, %s, %s)", $row[0], $row[1], $row[2], $row[6], $row[8], $row[9]);
                $insert_result = $wpdb->query($insert_query);

                if ($insert_result === false) {
                    // Log error if insert fails
                    error_log('Error inserting data: ' . $wpdb->last_error);
                }
            }
        }

        // Commit the transaction for the current batch
        $wpdb->query('COMMIT');

        // Flush buffers to free up memory
        $wpdb->flush();
    }

    // Enable keys after all batches are processed
    $wpdb->query("ALTER TABLE $table_name ENABLE KEYS");

    echo "Data Uploaded Successfully!";
}
// function handle_excel_upload() {
//     $uploaded_file = $_FILES['csv_file'];
//     $file_path = $uploaded_file['tmp_name'];

//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
//     $worksheet = $spreadsheet->getActiveSheet();
//     $rows = $worksheet->toArray();

//     global $wpdb;
//     $table_name = $wpdb->prefix . 'modi_farzad2';

//     foreach($rows as $index => $row) {
//         if($index == 0) continue; // Skip header
//         $normalized_onvan_kala = Normalizer::normalize($row[2], Normalizer::FORM_C);

//         $insert_result = $wpdb->insert($table_name, array(
//             'cd' => $row[0],
//             'tp' => $row[1],
//             'dt' => $row[2],
//             'tt' => $row[6],
//             'rt' => $row[8],
//             'ds' => $row[9],
//         ));

//         if ($insert_result === false) {
//             // Log error if insert fails
//             error_log('Error inserting data: ' . $wpdb->last_error);
//         }
//     }

//     // Check if there were any errors during insert
//     if ($wpdb->last_error) {
//         // Log any last errors after the loop
//         error_log('Last error after loop: ' . $wpdb->last_error);
//     }

//     echo "Data Uploaded Successfully!";
// }


// function handle_excel_upload() {
//     $uploaded_file = $_FILES['csv_file'];
//     $file_path = $uploaded_file['tmp_name'];

//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
//     $worksheet = $spreadsheet->getActiveSheet();
//     $rows = $worksheet->toArray();

//     global $wpdb;
//     $table_name = $wpdb->prefix . 'modi_farzad2';

//     foreach($rows as $index => $row) {
//         if($index == 0) continue; // Skip header
// $normalized_onvan_kala = Normalizer::normalize($row[2], Normalizer::FORM_C);

//         $wpdb->insert($table_name, array(
//             'cd' => $row[0],
//             'tp' => $row[1],
//             'dt' => $row[2],
//             'tt' => $row[6],
//             'rt ' => $row[8],
//             'ds' => $row[9],
//           ));
//     }

//     echo "Data Uploaded Successfully!";
// }


// excel updated
function api_tax_plugin_activation()
{
	    error_log('plugin tax activated');

    // Create the table if it doesn't exist
    global $wpdb;
    $table_name = $wpdb->prefix . 'modi_farzad2'; // Replace 'modi_farzad2' with your desired table name

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
	    error_log('after sql check');

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
	    error_log('after dbDelta  ');
    fetch_api_data();

    // // Schedule the daily API data fetch event
    if (!wp_next_scheduled('daily_api_data_fetch')) {
        wp_schedule_event(strtotime('02:00:00'), 'daily', 'daily_api_data_fetch');
    }
}

// Function to fetch and store the API data
function fetch_api_data()
{
		    error_log('comming inside fetch_api_data  ');

    // Set the timeout value for the API request
    $timeout = 4000; // Timeout value in seconds (adjust as needed)

    // Fetch data from the API
    //  $url = 'https://api.mega-pay.ir/api/stuffid/query?page=12&items_per_page=100000';
    // $url = 'https://api.mega-pay.ir/api/stuffid/query?page=22&items_per_page=100000';
    $url = 'https://api.mega-pay.ir/api/stuffid/query?page=9&items_per_page=1';
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
	        error_log('before table_name.');

    $table_name = $wpdb->prefix . 'modi_farzad2';
        error_log('after table_name.');

    foreach ($data as $item) {
        // Check if the item already exists in the database
        $existing_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ix = %d", $item['ix']), ARRAY_A);

        if (!$existing_item) {
			        error_log('not exist and try to import table_name.');

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
            // $wpdb->insert($table_name, $data_to_insert);
			// Insert the new item into the database
$result = $wpdb->insert($table_name, $data_to_insert);

// Check if the insertion was successful
if ($result === false) {
    // Log the MySQL error if the insertion failed
    error_log('MySQL Error: ' . $wpdb->last_error);
} else {
    // Log a success message if the insertion was successful
    error_log('Data inserted successfully.');
}}
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
     $table_name = $wpdb->prefix . 'modi_farzad2';

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
    $table_name = $wpdb->prefix . 'modi_farzad2';

    // Calculate the offset for pagination
    $offset = ($page - 1) * $itemsPerPage;

    // Prepare the SQL query with sorting, pagination, search, status filter, and percentage filter
    $sql = "SELECT * FROM $table_name WHERE 1=1";
  if (!empty($searchQuery)) {
    $words = explode(" ", $searchQuery);
    $likes = [];
    foreach ($words as $word) {
        $likes[] = $wpdb->prepare("CONCAT_WS(' ', cd, tp, dt, tt, rt, ds) LIKE %s", '%' . $wpdb->esc_like($word) . '%');
    }
    $likes_str = implode(" AND ", $likes);
    $sql .= " AND ($likes_str)";
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
  //  (!empty($searchQuery) ? $wpdb->prepare(" AND (cd LIKE %s OR tp LIKE %s OR dt LIKE %s OR tt LIKE %s OR rt LIKE %s OR ds LIKE %s)", '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%', '%' . $searchQuery . '%') : '') .
    (!empty($searchQuery) ? " AND ($likes_str)" : '') .
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

function enqueue_api_tax_scripts() {
    // Make sure jQuery is loaded first
    wp_enqueue_script('jquery');

    // Enqueue the plugin's custom styles
    wp_enqueue_style('api-tax-custom-style', plugins_url('api-tax-style.css', __FILE__));

    // Enqueue the plugin's data table script
    wp_enqueue_script('data-table-js', plugins_url('data-table.js', __FILE__), array('jquery'), '1.0.0', true);

    // Enqueue the new JavaScript file for handling AJAX requests
    wp_enqueue_script('api-tax-ajax-handler', plugins_url('ajax-handler.js', __FILE__), array('jquery'), '1.0.0', true);
error_log("Data Table JS URL: " . plugins_url('data-table.js', __FILE__));
error_log("Ajax Handler JS URL: " . plugins_url('ajax-handler.js', __FILE__));

    // Localize the ajaxurl
    wp_localize_script('data-table-js', 'api_tax_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'loading_img_url' => 'https://mandegaracc.com/wp-content/uploads/2023/07/Spinner-1s-200px.gif'
    ));
}


function enqueue_api_tax_style()
{
    echo '<style>';
    include plugin_dir_path(__FILE__) . 'api-tax-style.css';
    echo '</style>';
}
add_action('wp_head', 'enqueue_api_tax_style');
