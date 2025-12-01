<!-- Single Diamond Template -->
<?php
$diamond_id = $atts['id'];

// You can fetch diamond data here via AJAX or directly
?>

<div class="ndc-single-diamond" data-diamond-id="<?php echo esc_attr($diamond_id); ?>">
    
    <div class="ndc-loading">
        <div class="ndc-spinner"></div>
        <p><?php _e('Loading diamond details...', 'nivoda-diamond-connector'); ?></p>
    </div>
    
    <!-- Content will be loaded via AJAX -->
    <div id="ndc-diamond-content"></div>
    
</div>

<script>
jQuery(document).ready(function($) {
    var diamondId = '<?php echo esc_js($diamond_id); ?>';
    
    $.ajax({
        url: ndcData.ajaxUrl,
        type: 'POST',
        data: {
            action: 'ndc_get_diamond',
            nonce: ndcData.nonce,
            diamond_id: diamondId
        },
        success: function(response) {
            if (response.success) {
                renderDiamond(response.data);
            } else {
                $('#ndc-diamond-content').html('<p class="ndc-error">' + response.data.message + '</p>');
            }
        },
        error: function() {
            $('#ndc-diamond-content').html('<p class="ndc-error">' + ndcData.i18n.error + '</p>');
        },
        complete: function() {
            $('.ndc-loading').hide();
        }
    });
    
    function renderDiamond(diamond) {
        var cert = diamond.certificate || {};
        
        var html = '<div class="ndc-diamond-layout">';
        
        // Images Column
        html += '<div class="ndc-diamond-images">';
        
        if (diamond.image || diamond.video) {
            html += '<div class="ndc-diamond-gallery">';
            
            if (diamond.image) {
                html += '<div class="ndc-gallery-item"><img src="' + diamond.image + '" alt="Diamond"></div>';
            }
            
            if (diamond.video) {
                html += '<div class="ndc-gallery-item"><video src="' + diamond.video + '" controls></video></div>';
            }
            
            html += '</div>';
        }
        
        // Certificate
        if (cert.certNumber) {
            html += '<div class="ndc-certificate">';
            html += '<h3><?php _e('Certificate', 'nivoda-diamond-connector'); ?></h3>';
            html += '<p><strong>' + (cert.lab || '') + ' ' + cert.certNumber + '</strong></p>';
            html += '</div>';
        }
        
        html += '</div>';
        
        // Details Column
        html += '<div class="ndc-diamond-details">';
        
        html += '<h2>' + (cert.carats || '') + ' ct ' + (cert.shape || '') + ' ' + (cert.color || '') + ' ' + (cert.clarity || '') + ' Diamond</h2>';
        
        html += '<div class="ndc-diamond-price">';
        html += '<span class="ndc-price">$' + (diamond.price || 0).toLocaleString() + '</span>';
        html += '</div>';
        
        html += '<table class="ndc-specs-table">';
        html += '<tr><th><?php _e('Shape', 'nivoda-diamond-connector'); ?></th><td>' + (cert.shape || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Carat', 'nivoda-diamond-connector'); ?></th><td>' + (cert.carats || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Color', 'nivoda-diamond-connector'); ?></th><td>' + (cert.color || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Clarity', 'nivoda-diamond-connector'); ?></th><td>' + (cert.clarity || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Cut', 'nivoda-diamond-connector'); ?></th><td>' + (cert.cut || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Polish', 'nivoda-diamond-connector'); ?></th><td>' + (cert.polish || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Symmetry', 'nivoda-diamond-connector'); ?></th><td>' + (cert.symmetry || '-') + '</td></tr>';
        html += '<tr><th><?php _e('Fluorescence', 'nivoda-diamond-connector'); ?></th><td>' + (cert.fluorescence || '-') + '</td></tr>';
        
        if (cert.measurements) {
            html += '<tr><th><?php _e('Measurements', 'nivoda-diamond-connector'); ?></th><td>' + cert.measurements + '</td></tr>';
        }
        
        html += '</table>';
        
        html += '<div class="ndc-diamond-actions">';
        html += '<button class="ndc-btn ndc-btn-primary ndc-add-to-cart" data-diamond-id="' + diamond.id + '">';
        html += '<?php _e('Add to Cart', 'nivoda-diamond-connector'); ?>';
        html += '</button>';
        html += '</div>';
        
        html += '</div>';
        html += '</div>';
        
        $('#ndc-diamond-content').html(html);
        
        // Initialize gallery
        if ($('.ndc-diamond-gallery').length) {
            $('.ndc-diamond-gallery').slick({
                dots: true,
                arrows: true,
                infinite: false,
                speed: 300,
                slidesToShow: 1,
                adaptiveHeight: true
            });
        }
    }
});
</script>
