var ajaxurl = api_tax_ajax.ajax_url; 
function fetchData(page, searchQuery, status, percentage, type) {
    $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            action: "get_api_data_with_filter",
            page: page,
            itemsPerPage: itemsPerPage,
            search: searchQuery,
            statusFilter: status,
            percentageFilter: percentage,
            filter_namayande: type, // Use the correct filter name for 'نوع' filter
        },
        beforeSend: function () {
            if (searchQuery === "") {
                // Show the loading message only during pagination, not during search
                $("#loading-row").show();
            }
        },
        success: function (response) {
            updateTable(response);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Handle error if necessary
        },
        complete: function () {
            // Hide the loading message when the AJAX call is complete
            $("#loading-row").hide();
        },
    });
}

jQuery(document).ready(function ($) {
    var ajaxurl = api_tax_ajax.ajax_url; // Use the correct variable name for ajax_url

    var currentPage = 1;
    var itemsPerPage = 50;
    var totalPages = 1;
    var statusFilter = "";
    var percentageFilter = "";
    var typeFilter = "";

    // Function to fetch data based on type filter
    function fetchDataByType(selectedType) {
          typeFilter = selectedType;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_api_data_with_filter',
                page: 1,
                itemsPerPage: itemsPerPage,
                search: $('#api-data-search').val(),
                statusFilter: statusFilter,
                percentageFilter: percentageFilter,
                filter_namayande: selectedType // Pass the selected type filter value to the server
            },
            beforeSend: function () {
                // Show the loading message
                $('#loading-row').show();
            },
            success: function (response) {
                // Output the response to the browser console for debugging
                console.log(response);

              // Update table rows and pagination
    $('#api-data-table tbody').html(response.tableRows);
    $('.api-data-pagination').html(response.pagination);
    $('#total-records').text(response.totalRecords + ' records found');

                // Add the class 'maaf' to rows with 'معاف' status
               
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Handle error if necessary
            },
            complete: function () {
                // Hide the loading message when the AJAX call is complete
                $('#loading-row').hide();
            }
        });
    }

    // Search by type button click event
    $('#search-by-type').on('click', function () {
        var selectedType = $('#api-data-filter-namayande').val();
        fetchDataByType(selectedType);
    });

    function performSearch() {
        var searchQuery = $('#api-data-search').val();
        fetchData(1, searchQuery, statusFilter, percentageFilter, typeFilter);
    }

     function updateTable(response) {
    // Output the response to the browser console for debugging
    console.log(response);

    // Update table rows and pagination
    $("#api-data-table tbody").html(response.tableRows);
    $(".api-data-pagination").html(response.pagination);
    $('#record-count').text(response.totalRecords + ' records found');
 // Set the "Total Records" count
    $("#total-records").text("Total: " + response.totalRecords + " records found");

    // Update current page and total pages
    currentPage = response.currentPage;
    totalPages = response.totalPages;

    // Add the class 'maaf' to rows with 'معاف' status
    $("#api-data-table tbody tr").each(function () {
        if ($(this).find("td:nth-child(5)").text() === "معاف") {
            $(this).addClass("maaf");
        }
    });

    // Event delegation for pagination links
    $(document).on("click", ".api-data-pagination a", function (event) {
        event.preventDefault(); // Prevent the default link behavior

        // Get the page number from the data attribute of the clicked link
        const pageNum = $(this).data("page");

        // Fetch data using AJAX, including the current status filter value
        fetchData(pageNum, $("#api-data-search").val(), statusFilter, percentageFilter, typeFilter);
    });

    // Event delegation for Type filter change event
    $("#api-data-filter-namayande").on("change", function () {
        var selectedValue = $(this).val();
        typeFilter = selectedValue;

        fetchData(1, $("#api-data-search").val(), statusFilter, percentageFilter, typeFilter);
    });
}

  

    // Search by type button click event
    $(".glass-morphism-button").on("click", function () {
        performSearch();
    });

    // Search input enter key press event
    $("#api-data-search").on("keypress", function (event) {
        if (event.which === 13) {
            // 13 is the keycode for the Enter key
            performSearch();
        }
    });

    // Function to handle filters
    function handleFilters() {
        // Search input change event
        $("#api-data-search").on("input", function () {
            var searchQuery = $(this).val();
            fetchData(currentPage, searchQuery, statusFilter, percentageFilter, typeFilter);
        });

        // Status and Percentage filter change event
        $("#api-data-filter").on("change", function () {
            var selectedValue = $(this).val();
            if (selectedValue === "معاف" || selectedValue === "مشمول") {
                statusFilter = selectedValue;
                percentageFilter = ""; // Reset the percentage filter
            } else if (selectedValue === "9" || selectedValue === "16" || selectedValue === "32") {
                statusFilter = "";
                percentageFilter = selectedValue;
            } else {
                statusFilter = "";
                percentageFilter = "";
            }

            fetchData(currentPage, $("#api-data-search").val(), statusFilter, percentageFilter, typeFilter);
        });
    }

    // Initialize the table
    handleFilters();
});
