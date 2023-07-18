// Schedule the task to run daily at 11:00 PM
add_action('init', 'schedule_api_fetch_task');
function schedule_api_fetch_task() {
    if (!wp_next_scheduled('fetch_api_data')) {
        wp_schedule_event(strtotime('23:00:00'), 'daily', 'fetch_api_data');
    }
}

// Hook the fetch_api_data function to the scheduled task
add_action('fetch_api_data', 'fetch_api_data');

function fetch_api_data() {
    // Fetch data from the API
    $url = 'https://api.mega-pay.ir/api/stuffid/query?page=1&items_per_page=5000';
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        // Handle error if the API request fails
        error_log($response->get_error_message());
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!$data) {
        // Handle error if JSON parsing fails
        error_log('Failed to parse JSON data.');
        return;
    }

    // Store the data in the WordPress database, checking for duplicates
    global $wpdb;
    $modi_farzad = $wpdb->prefix . 'modi_farzad'; // Replace 'modi_farzad' with your desired table name

    foreach ($data as $item) {
        // Check if the item already exists in the database
        $existing_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $modi_farzad WHERE ix = %d", $item['ix']), ARRAY_A);

        if ($existing_item) {
            // Item already exists, skip storing it
            continue;
        }

        // Insert the new item into the database
        $wpdb->insert($modi_farzad, $item);
    }
}
