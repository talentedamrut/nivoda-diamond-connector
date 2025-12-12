(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Admin search functionality
        $('#nivoda-search-btn').on('click', function(e) {
            e.preventDefault();
            performSearch();
        });

        function performSearch() {
            const params = {
                labgrown: $('#filter-labgrown').val(),
                shapes: $('#filter-shape').val(),
                size_from: $('#filter-size-from').val(),
                size_to: $('#filter-size-to').val(),
                price_from: $('#filter-price-from').val(),
                price_to: $('#filter-price-to').val(),
                limit: 50
            };

            // Remove empty parameters
            Object.keys(params).forEach(key => {
                if (!params[key] || (Array.isArray(params[key]) && params[key].length === 0)) {
                    delete params[key];
                }
            });

            // Show loading
            $('#nivoda-search-results').html('<div class="nivoda-spinner"></div><p>Searching diamonds...</p>');

            // Make API request
            $.ajax({
                url: '/wp-json/nivoda/v1/search',
                method: 'GET',
                data: params,
                success: function(response) {
                    displayResults(response);
                },
                error: function(xhr, status, error) {
                    $('#nivoda-search-results').html(
                        '<div class="notice notice-error"><p>Error: ' + error + '</p></div>'
                    );
                }
            });
        }

        function displayResults(response) {
            if (!response.data || !response.data.diamonds_by_query) {
                $('#nivoda-search-results').html('<p>No results found.</p>');
                return;
            }

            const data = response.data.diamonds_by_query;
            const items = data.items || [];
            const totalCount = data.total_count || 0;

            let html = '<h3>Results: ' + totalCount + ' diamonds found</h3>';
            html += '<div class="nivoda-results-grid">';

            items.forEach(function(item) {
                const diamond = item.diamond;
                const cert = diamond.certificate;

                html += '<div class="nivoda-diamond-card">';
                
                if (diamond.image) {
                    html += '<img src="' + diamond.image + '" alt="Diamond" class="nivoda-diamond-image">';
                } else {
                    html += '<div class="nivoda-diamond-image" style="display:flex;align-items:center;justify-content:center;background:#f0f0f0;">No Image</div>';
                }

                html += '<div class="nivoda-diamond-details">';
                html += '<p><strong>Shape:</strong> ' + (cert.shape || 'N/A') + '</p>';
                html += '<p><strong>Carat:</strong> ' + (cert.carats || 'N/A') + '</p>';
                html += '<p><strong>Color:</strong> ' + (cert.color || 'N/A') + '</p>';
                html += '<p><strong>Clarity:</strong> ' + (cert.clarity || 'N/A') + '</p>';
                html += '<p><strong>Cut:</strong> ' + (cert.cut || 'N/A') + '</p>';
                html += '<p><strong>Cert #:</strong> ' + (cert.certNumber || 'N/A') + '</p>';
                html += '<p class="nivoda-price">$' + (item.price || 'N/A').toLocaleString() + '</p>';
                html += '</div>';
                
                html += '</div>';
            });

            html += '</div>';

            $('#nivoda-search-results').html(html);
        }
    });

})(jQuery);
