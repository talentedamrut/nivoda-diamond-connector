/**
 * Nivoda Diamond Connector - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    var NDC = {
        currentPage: 1,
        currentFilters: {},
        isLoading: false,
        
        init: function() {
            this.initSliders();
            this.bindEvents();
            this.loadDiamonds();
        },
        
        initSliders: function() {
            // Carat slider
            if ($('#ndc-carat-slider').length) {
                var caratSlider = document.getElementById('ndc-carat-slider');
                noUiSlider.create(caratSlider, {
                    start: [0.3, 20],
                    connect: true,
                    range: {
                        'min': 0.3,
                        'max': 20
                    },
                    step: 0.01,
                    format: {
                        to: function(value) {
                            return value.toFixed(2);
                        },
                        from: function(value) {
                            return parseFloat(value);
                        }
                    }
                });
                
                caratSlider.noUiSlider.on('update', function(values) {
                    $('#ndc-carat-min').val(values[0]);
                    $('#ndc-carat-max').val(values[1]);
                });
            }
            
            // Price slider
            if ($('#ndc-price-slider').length) {
                var priceSlider = document.getElementById('ndc-price-slider');
                noUiSlider.create(priceSlider, {
                    start: [0, 100000],
                    connect: true,
                    range: {
                        'min': 0,
                        'max': 1000000
                    },
                    step: 100,
                    format: {
                        to: function(value) {
                            return Math.round(value);
                        },
                        from: function(value) {
                            return parseInt(value);
                        }
                    }
                });
                
                priceSlider.noUiSlider.on('update', function(values) {
                    $('#ndc-price-min').val(values[0]);
                    $('#ndc-price-max').val(values[1]);
                });
            }
        },
        
        bindEvents: function() {
            var self = this;
            
            // Filter form submit
            $('#ndc-filter-form').on('submit', function(e) {
                e.preventDefault();
                self.currentPage = 1;
                self.loadDiamonds();
            });
            
            // Reset filters
            $('#ndc-reset-filters').on('click', function(e) {
                e.preventDefault();
                self.resetFilters();
            });
            
            // Sort change
            $('#ndc-sort').on('change', function() {
                self.loadDiamonds();
            });
            
            // Pagination
            $(document).on('click', '.ndc-page-link', function(e) {
                e.preventDefault();
                self.currentPage = $(this).data('page');
                self.loadDiamonds();
                $('html, body').animate({
                    scrollTop: $('.ndc-results').offset().top - 100
                }, 500);
            });
            
            // Add to cart
            $(document).on('click', '.ndc-add-to-cart', function(e) {
                e.preventDefault();
                var diamondId = $(this).data('diamond-id');
                self.addToCart(diamondId, $(this));
            });
        },
        
        getFilters: function() {
            var filters = {};
            
            // Shape
            var shapes = [];
            $('input[name="shape[]"]:checked').each(function() {
                shapes.push($(this).val());
            });
            if (shapes.length) filters.shape = shapes;
            
            // Carat range
            filters.carat_min = $('#ndc-carat-min').val();
            filters.carat_max = $('#ndc-carat-max').val();
            
            // Color
            var colors = [];
            $('input[name="color[]"]:checked').each(function() {
                colors.push($(this).val());
            });
            if (colors.length) filters.color = colors;
            
            // Clarity
            var clarities = [];
            $('input[name="clarity[]"]:checked').each(function() {
                clarities.push($(this).val());
            });
            if (clarities.length) filters.clarity = clarities;
            
            // Cut
            var cuts = [];
            $('input[name="cut[]"]:checked').each(function() {
                cuts.push($(this).val());
            });
            if (cuts.length) filters.cut = cuts;
            
            // Price range
            filters.price_min = $('#ndc-price-min').val();
            filters.price_max = $('#ndc-price-max').val();
            
            return filters;
        },
        
        loadDiamonds: function() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.currentFilters = this.getFilters();
            
            $('#ndc-results-grid').html('<div class="ndc-loading"><div class="ndc-spinner"></div><p>' + ndcData.i18n.loading + '</p></div>');
            
            var self = this;
            
            $.ajax({
                url: ndcData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ndc_search_diamonds',
                    nonce: ndcData.nonce,
                    filters: this.currentFilters,
                    page: this.currentPage,
                    limit: 20
                },
                success: function(response) {
                    if (response.success) {
                        self.renderDiamonds(response.data);
                    } else {
                        $('#ndc-results-grid').html('<p class="ndc-error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('#ndc-results-grid').html('<p class="ndc-error">' + ndcData.i18n.error + '</p>');
                },
                complete: function() {
                    self.isLoading = false;
                }
            });
        },
        
        renderDiamonds: function(data) {
            var html = '';
            
            $('#ndc-total-count').text(data.total.toLocaleString());
            
            if (!data.diamonds || data.diamonds.length === 0) {
                html = '<p class="ndc-no-results">' + ndcData.i18n.noResults + '</p>';
                $('#ndc-results-grid').html(html);
                $('#ndc-pagination').html('');
                return;
            }
            
            data.diamonds.forEach(function(diamond) {
                html += this.renderDiamondCard(diamond);
            }.bind(this));
            
            $('#ndc-results-grid').html(html);
            
            // Render pagination
            this.renderPagination(data.total, data.current_page);
        },
        
        renderDiamondCard: function(diamond) {
            var cert = diamond.certificate || {};
            var price = diamond.price || 0;
            var image = diamond.image || '';
            
            var html = '<div class="ndc-diamond-card">';
            
            // Image
            if (image) {
                html += '<div class="ndc-diamond-image" style="background-image: url(\'' + image + '\')"></div>';
            } else {
                html += '<div class="ndc-diamond-image ndc-no-image"><span>No Image</span></div>';
            }
            
            // Info
            html += '<div class="ndc-diamond-info">';
            
            html += '<h3 class="ndc-diamond-title">';
            html += (cert.carats || '') + ' ct ' + (cert.shape || '') + ' ' + (cert.color || '') + ' ' + (cert.clarity || '');
            html += '</h3>';
            
            html += '<div class="ndc-diamond-specs">';
            html += '<span>Cut: ' + (cert.cut || '-') + '</span>';
            html += '<span>Polish: ' + (cert.polish || '-') + '</span>';
            html += '</div>';
            
            html += '<div class="ndc-diamond-price">$' + price.toLocaleString() + '</div>';
            
            html += '<div class="ndc-diamond-actions">';
            html += '<button class="ndc-btn ndc-btn-view" data-diamond-id="' + diamond.id + '">View Details</button>';
            html += '<button class="ndc-btn ndc-btn-primary ndc-add-to-cart" data-diamond-id="' + diamond.id + '">Add to Cart</button>';
            html += '</div>';
            
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        renderPagination: function(total, currentPage) {
            var perPage = 20;
            var totalPages = Math.ceil(total / perPage);
            
            if (totalPages <= 1) {
                $('#ndc-pagination').html('');
                return;
            }
            
            var html = '<div class="ndc-pagination-inner">';
            
            // Previous
            if (currentPage > 1) {
                html += '<a href="#" class="ndc-page-link ndc-page-prev" data-page="' + (currentPage - 1) + '">« Previous</a>';
            }
            
            // Page numbers
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            for (var i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<span class="ndc-page-current">' + i + '</span>';
                } else {
                    html += '<a href="#" class="ndc-page-link" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            // Next
            if (currentPage < totalPages) {
                html += '<a href="#" class="ndc-page-link ndc-page-next" data-page="' + (currentPage + 1) + '">Next »</a>';
            }
            
            html += '</div>';
            
            $('#ndc-pagination').html(html);
        },
        
        resetFilters: function() {
            $('#ndc-filter-form')[0].reset();
            
            // Reset sliders
            if ($('#ndc-carat-slider')[0] && $('#ndc-carat-slider')[0].noUiSlider) {
                $('#ndc-carat-slider')[0].noUiSlider.set([0.3, 20]);
            }
            
            if ($('#ndc-price-slider')[0] && $('#ndc-price-slider')[0].noUiSlider) {
                $('#ndc-price-slider')[0].noUiSlider.set([0, 100000]);
            }
            
            this.currentPage = 1;
            this.loadDiamonds();
        },
        
        addToCart: function(diamondId, $button) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Adding...');
            
            $.ajax({
                url: ndcData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ndc_add_to_cart',
                    nonce: ndcData.nonce,
                    diamond_id: diamondId
                },
                success: function(response) {
                    if (response.success) {
                        $button.text('Added!').addClass('ndc-success');
                        
                        // Show success message
                        alert(response.data.message + '\n\nWould you like to view your cart?');
                        
                        // Optionally redirect to cart
                        if (confirm('View cart now?')) {
                            window.location.href = response.data.cart_url;
                        }
                        
                        setTimeout(function() {
                            $button.text(originalText).removeClass('ndc-success').prop('disabled', false);
                        }, 2000);
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert(ndcData.i18n.error);
                    $button.text(originalText).prop('disabled', false);
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.ndc-search-wrapper').length) {
            NDC.init();
        }
    });
    
})(jQuery);
