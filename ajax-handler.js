  // Initial variables
    var currentPage = 1;
    var itemsPerPage = 50;
    var totalPages = 1;
    var statusFilter = "";
    var percentageFilter = "";
    var typeFilter = "";

    fetchData();
function showLoadingSpinner() {
    $('#api-data-table tbody').html('<tr id="loading-row"><td colspan="7" style="text-align: center;"><img src="https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif" alt="Loading..." /> Loading...</td></tr>');}
function hideLoadingSpinner() {
    $('#loading-row').remove();
}

 function handleFormSubmit(event) {
    event.preventDefault();

    // Get the search query from the input field
    var searchQuery = $("#api-data-search").val();
// Show loading spinner while the request is being processed
    //showLoadingSpinner();
    // Make an AJAX request to the server to filter the table data
    $.ajax({
        url: api_tax_ajax.ajax_url,
        type: 'post',
        data: {
            action: 'get_api_data_with_filter',
            page: 1,
            itemsPerPage: itemsPerPage,
            search: searchQuery,
            statusFilter: statusFilter,
            percentageFilter: statusFilter === "معاف" || statusFilter === "مشمول" ? "" : percentageFilter,
            filter_namayande: typeFilter,
        },
        beforeSend: function () {
            // Show loading spinner while the request is being processed
     showLoadingSpinner();                  
          console.log("Before sending AJAX request...");
        },
        success: function (response) {
              hideLoadingSpinner();
                       console.log("AJAX success response:", response);
            updateTable(response);
        },
        error: function (xhr, ajaxOptions, thrownError) {
                      hideLoadingSpinner(); // Hide loading spinner on error
            console.log('AJAX error:', xhr.status, thrownError);
        }
    });
}
$('#api-data-filter-form').on('submit', function (event) {
    event.preventDefault();
    const searchQuery = $("#api-data-search").val();
  
    fetchData(1, searchQuery, statusFilter, percentageFilter, typeFilter);
});
    // Attach the form submit event handler to the form
 $(".api-data-pagination").on("click", "a", function (event) {
    event.preventDefault();
       console.log("Pagination link clicked!"); // Add this line

    const pageNum = $(this).data("page");
    const searchQuery = $("#api-data-search").val();
       console.log("Clicked page:", pageNum); // Add this line

    fetchData(pageNum, searchQuery, statusFilter, percentageFilter, typeFilter);
});

    // Function to fetch the data for a specific page
    function fetchData(pageNum = 1, searchQuery, status, percentage, typeFilter) {
        $.ajax({
            url: api_tax_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'get_api_data',
                page: pageNum, // Send the page number to the server
                itemsPerPage: itemsPerPage, // Send the items per page value to the server
                statusFilter: statusFilter, // Send the status filter value to the server
                percentageFilter: percentageFilter, // Send the percentage filter value to the server
                filter_namayande: typeFilter // Send the type filter value to the server
            }, success: function (response) {
            updateTable(response, pageNum); // Call updateTable with response and pageNum
        },
            beforeSend: function () {
                // Show loading spinner while the request is being processed
                $('#api-data-table tbody').html('<tr id="loading-row"><td colspan="7" style="text-align: center;"><img src="https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif" alt="Loading..." /> Loading...</td></tr>');
            },
            success: function (response) {

                // Update the current page and total pages
                currentPage = response.currentPage;
                totalPages = response.totalPages;

                 $('#total-records').text('Total: ' + response.totalRecords + ' records');

            // Call updateTable with response
                              updateTable(response);

                // Add the class 'maaf' to rows with 'معاف' status
                $("#api-data-table tbody tr").each(function () {
                    if ($(this).find("td:nth-child(5)").text() === "معاف") {
                        $(this).addClass("maaf");
                    }
                });

                // Add the accordion functionality to the table rows
                $(".table-row").click(function () {
                    const detailsContent = $(this).find(".details-content");
                    $(this).toggleClass("expanded");
                    detailsContent.slideToggle();
                });
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log('AJAX error:', xhr.status, thrownError);
            }
        });
    }

    function updateTable(response, pageNum) {
        // Output the response to the browser console for debugging
        console.log(response);

        // Update table rows
        $('#api-data-table tbody').html(response.tableRows);

        // Update current page and total pages
        currentPage = response.currentPage;
        totalPages = response.totalPages;

        // Remove previous click event handlers from pagination links
        $(".api-data-pagination").off("click", "a");
       
    // Set total records
    $('#total-records').text('Total: ' + response.totalRecords + ' records');
 
// Remove previous click event handlers from pagination links
    $(".api-data-pagination").off("click", "a");
        // Event delegation for pagination links
        $(".api-data-pagination").on("click", "a", function (event) {
            event.preventDefault();
            const pageNum = $(this).data("page");
            fetchData(pageNum);
        });
    }

    // Additional code here, if needed...

    // Event delegation