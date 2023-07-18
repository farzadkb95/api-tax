 function fetch_api_data() {
    // Set the timeout value for the API request
    $timeout = 60; // Timeout value in seconds (adjust as needed)

    // Fetch data from the API
    $url = 'https://api.mega-pay.ir/api/stuffid/query?page=1&items_per_page=50';
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

    // Store the data in the WordPress database, checking for duplicates
    foreach ($data as $item) {
        // Check if the item already exists in the database
        $existing_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ix = %d", $item['ix']), ARRAY_A);

        if (!$existing_item) {
            // Prepare the data for insertion
            $data_to_insert = array(
                'ix' => isset($item['ix']) ? $item['ix'] : 0,
                'cd' => isset($item['cd']) ? $item['cd'] : '',
                'tp' => isset($item['tp']) ? $item['tp'] : '',
                'dt' => isset($item['dt']) ? $item['dt'] : '',
                'tt' => isset($item['tt']) ? $item['tt'] : '',
                'rt' => isset($item['rt']) ? $item['rt'] : '',
                'ds' => isset($item['ds']) ? $item['ds'] : '',
                'vp' => isset($item['vp']) ? $item['vp'] : '',
                'sg' => isset($item['sg']) ? $item['sg'] : '',
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

// Call the fetch_api_data() function to trigger immediate data retrieval and storage
fetch_api_data();
