 <div class="api-data-table-container table-container">
    <div class="search-and-filter">
        <input type="text" id="api-data-search" placeholder="جستجو بر اساس شناسه کالاو خدمات و توضیحات...">
        <button id="search-button" class="glass-morphism-button" style="background-color: #5FB3CA;">جستجو</button>
        <select id="api-data-filter">
            <option value="">نرخ ارزش افزوده</option>
            <option value="">مشمول و معاف </option>
            <option value="معاف">معاف</option>
            <option value="مشمول">مشمول</option>
            <option value="1">1 درصد</option>
            <option value="9">9 درصد</option>
            <option value="15">15 درصد</option>
            <option value="16">16 درصد</option>
            <option value="30">30 درصد</option>
            <option value="36">36 درصد</option>
        </select>
        <select id="api-data-filter-namayande">
            <option value="">فیلتر بر اساس نوع</option>
            <option value="شناسه اختصاصی تولید داخل(کالا)">شناسه اختصاصی تولید داخل(کالا)</option>
            <option value="شناسه اختصاصی وارداتی(کالا)">شناسه اختصاصی وارداتی(کالا)</option>
            <option value="شناسه اختصاصی(خدمت)">شناسه اختصاصی(خدمت)</option>
            <option value="شناسه عمومی تولید داخل(کالا)">شناسه عمومی تولید داخل(کالا)</option>
            <option value="شناسه عمومی وارداتی(کالا)">شناسه عمومی وارداتی(کالا)</option>
            <option value="شناسه عمومی(خدمت)">شناسه عمومی(خدمت)</option>
      </select>
    </div>
    <table id="api-data-table" class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th class="shenaseh">شناسه کالا/خدمت</th>
                <th>نوع</th>
    <th >تاریخ</th>
                <th>وضعیت</th>
                <th>درصد مالیات بر ارزش افزوده</th>
                <th>توضیحات</th>
            </tr>
        </thead>
        <tbody>
            <!-- Table rows will be populated using AJAX -->
            <tr id="loading-row" style="display: none;">
                <td colspan="7" style="text-align: center;"><img src="https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif" alt="Loading..." /> Loading...</td>
            </tr>
        </tbody>
    </table>
        <div id="no-results-text" style="display: none; text-align: center;">نتیجه‌ای یافت نشد</div>
   
</div> 
    <div class="api-data-pagination">
        <!-- Pagination links will be populated using AJAX -->
    </div>
<div id="total-records" style="text-align: center; margin-top: 10px;">Total Records: 0</div>
    

</div>

<!-- Load jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Load ajax-handler.js -->
<script src="ajax-handler.js" defer></script>
<script src="data-table.js" defer></script>

<script>
    jQuery(document).ready(function ($) {
        var ajaxurl = api_tax_ajax.ajax_url; // Use the correct variable name for ajax_url

        var currentPage = 1;
        var itemsPerPage = 50;
        var totalPages = 1;
        var statusFilter = "";
        var percentageFilter = "";
        var typeFilter = "";

        function fetchData(pageNum = 1, searchQuery = "", status = "", percentage = "", type = "") {
           var loadingRow = $("#loading-row");
    var tbody = $("#api-data-table tbody");
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "get_api_data_with_filter",
                    page: pageNum,
                    itemsPerPage: itemsPerPage,
                    search: searchQuery,
                    statusFilter: status,
                    percentageFilter: status === "معاف" || status === "مشمول" ? "" : percentage,
                    filter_namayande: type,
                },
                beforeSend: function () {
                    if (searchQuery === "") {
                        // Show the loading message only during pagination, not during search
                                  $('#api-data-table tbody').html('<tr id="loading-row"><td colspan="7" style="text-align: center;"><img src="https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif" alt="Loading..." /> Loading...</td></tr>');
                     //   $("#loading-row").show();
                    }
                },
                success: function (response) {
                      console.log("Response from server:", response); // Add this line

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

        function updateTable(response) {
            // Output the response to the browser console for debugging
               console.log("Response: ", response);
     var tbody = $("#api-data-table tbody");
    var paginationDiv = $(".api-data-pagination");
      var noResultsDiv = $("#no-results-text");
    var totalRecordsDiv = $("#total-records"); // Get the total-records div

    if (response.tableRows === '') {
                  console.log("nothing here babe!");
   paginationDiv.hide();
              tbody.empty();

        noResultsDiv.hide();
        // If no results found, show the "No results found" message
        totalRecordsDiv.text('تنتیجه ای یافت نشد!');


}
  else{
            console.log("Updating table with results");

            // Update table rows and pagination
            $("#api-data-table tbody").html(response.tableRows);
            $(".api-data-pagination").html(response.pagination);
            $('#record-count').text(response.totalRecords + ' records found');

            // Update current page and total pages
            currentPage = response.currentPage;
            totalPages = response.totalPages;

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

            // Event delegation for pagination links
            $(".api-data-pagination").on("click", "a", function (event) {
                event.preventDefault();
                const pageNum = $(this).data("page");
                fetchData(pageNum, $("#api-data-search").val(), statusFilter, percentageFilter, typeFilter);
            });
        if (typeof response.totalRecords !== 'undefined') {
            totalRecordsDiv.text('تعداد رکورد: ' + response.totalRecords);
            console.log("Total Records: " + response.totalRecords);
        } else {
            totalRecordsDiv.text('Total Records: N/A');
            console.log("Total Records not found in the response.");
        }

     }   }

        function performSearch() {
            var searchQuery = $("#api-data-search").val();
           // fetchData(1, searchQuery, statusFilter, percentageFilter, typeFilter);
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
        },
        success: function (response) {
            hideLoadingSpinner();
            updateTable(response);
           $("#search-button").html("جستجو");
        },
        error: function (xhr, ajaxOptions, thrownError) {
            hideLoadingSpinner();
            console.log('AJAX error:', xhr.status, thrownError);
           $("#search-button").html("جستجو");
        }
    });
        }

        $(".glass-morphism-button").on("click", function () {
            var selectedType = $("#api-data-filter").val();
            fetchData(1, "", statusFilter, percentageFilter, selectedType);
            performSearch();
        });

        // Search input enter key press event
        $("#api-data-search").on("keypress", function (event) {
            if (event.which === 13) {
                // 13 is the keycode for the Enter key
                performSearch();
            }
        });

        // Status and Percentage filter change event
        $("#api-data-filter").on("change", function () {
            var selectedValue = $(this).val();
            if (selectedValue === "معاف" || selectedValue === "مشمول") {
                statusFilter = selectedValue;
                percentageFilter = ""; // Reset the percentage filter
            } else {
                statusFilter = "";
                percentageFilter = selectedValue;
            }

            fetchData(
                1,
                $("#api-data-search").val(),
                statusFilter,
                percentageFilter,
                typeFilter
            );
        });

        // Type filter change event
        $("#api-data-filter-namayande").on("change", function () {
            var selectedValue = $(this).val();
            typeFilter = selectedValue;

            fetchData(
                1,
                $("#api-data-search").val(),
                statusFilter,
                percentageFilter,
                typeFilter
            );
        });

        // Initial data fetch on page load
        fetchData(1, "", statusFilter, percentageFilter, typeFilter);
      
      
 $("#search-button").on("click", function () {
            // Change button text to "Searching..."
            $(this).html('<img src="https://mandegaracc.ir/wp-content/uploads/2023/08/Dual-Ball-1.2s-37px-1.gif" style="width: 70%; padding:0px!important;   vertical-align: middle;" alt="Loading..." />');
   
            /*// Simulate searching for 3 seconds
            setTimeout(function () {
                // Revert button text to original
                $("#search-button").removeClass("loading").text("جستجو");
            }, 3000); // 3000 milliseconds = 3 seconds
*/
            // Call the performSearch function to initiate the actual search
            performSearch();
        });
      
      function showLoadingSpinner() {
    $('#api-data-table tbody').html('<tr id="loading-row"><td colspan="7" style="text-align: center;"><img src="https://mandegaracc.ir/wp-content/uploads/2023/07/Spinner-1s-200px.gif" alt="Loading..." /> Loading...</td></tr>');
}

 function hideLoadingSpinner() {
    $('#loading-row').remove();
}
        function handleFormSubmit(event) {
            event.preventDefault();

            // Get the search query from the input field
            var searchQuery = $("#api-data-search").val();

            // Make an AJAX request to the server to filter the table data
            fetchData(1, searchQuery, statusFilter, percentageFilter, typeFilter);
        }

        // Attach the form submit event handler to the form
        $("#api-data-filter-form").on("submit", handleFormSubmit);

        // Additional code here, if needed...
    });
</script>
