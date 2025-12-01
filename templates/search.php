<!-- Diamond Search Template -->
<div class="ndc-search-wrapper">
    
    <!-- Filters -->
    <div class="ndc-filters">
        <h3><?php _e('Filter Diamonds', 'nivoda-diamond-connector'); ?></h3>
        
        <form id="ndc-filter-form" class="ndc-filter-form">
            
            <!-- Shape Filter -->
            <div class="ndc-filter-group">
                <label><?php _e('Shape', 'nivoda-diamond-connector'); ?></label>
                <div class="ndc-shape-grid">
                    <?php
                    $shapes = ['Round', 'Princess', 'Cushion', 'Emerald', 'Oval', 'Radiant', 'Asscher', 'Marquise', 'Heart', 'Pear'];
                    foreach ($shapes as $shape):
                    ?>
                        <label class="ndc-shape-option">
                            <input type="checkbox" name="shape[]" value="<?php echo esc_attr($shape); ?>">
                            <span class="ndc-shape-label"><?php echo esc_html($shape); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Carat Range -->
            <div class="ndc-filter-group">
                <label><?php _e('Carat Weight', 'nivoda-diamond-connector'); ?></label>
                <div id="ndc-carat-slider" class="ndc-slider"></div>
                <div class="ndc-slider-values">
                    <input type="number" id="ndc-carat-min" name="carat_min" value="0.3" step="0.01" readonly>
                    <span>-</span>
                    <input type="number" id="ndc-carat-max" name="carat_max" value="20" step="0.01" readonly>
                </div>
            </div>
            
            <!-- Color Filter -->
            <div class="ndc-filter-group">
                <label><?php _e('Color', 'nivoda-diamond-connector'); ?></label>
                <div class="ndc-checkbox-group">
                    <?php
                    $colors = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
                    foreach ($colors as $color):
                    ?>
                        <label>
                            <input type="checkbox" name="color[]" value="<?php echo esc_attr($color); ?>">
                            <?php echo esc_html($color); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Clarity Filter -->
            <div class="ndc-filter-group">
                <label><?php _e('Clarity', 'nivoda-diamond-connector'); ?></label>
                <div class="ndc-checkbox-group">
                    <?php
                    $clarities = ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2', 'I1', 'I2'];
                    foreach ($clarities as $clarity):
                    ?>
                        <label>
                            <input type="checkbox" name="clarity[]" value="<?php echo esc_attr($clarity); ?>">
                            <?php echo esc_html($clarity); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Cut Filter -->
            <div class="ndc-filter-group">
                <label><?php _e('Cut', 'nivoda-diamond-connector'); ?></label>
                <div class="ndc-checkbox-group">
                    <?php
                    $cuts = ['Ideal', 'Excellent', 'Very Good', 'Good'];
                    foreach ($cuts as $cut):
                    ?>
                        <label>
                            <input type="checkbox" name="cut[]" value="<?php echo esc_attr($cut); ?>">
                            <?php echo esc_html($cut); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Price Range -->
            <div class="ndc-filter-group">
                <label><?php _e('Price Range', 'nivoda-diamond-connector'); ?></label>
                <div id="ndc-price-slider" class="ndc-slider"></div>
                <div class="ndc-slider-values">
                    <input type="number" id="ndc-price-min" name="price_min" value="0" step="100" readonly>
                    <span>-</span>
                    <input type="number" id="ndc-price-max" name="price_max" value="1000000" step="100" readonly>
                </div>
            </div>
            
            <!-- Filter Actions -->
            <div class="ndc-filter-actions">
                <button type="submit" class="ndc-btn ndc-btn-primary">
                    <?php _e('Apply Filters', 'nivoda-diamond-connector'); ?>
                </button>
                <button type="button" id="ndc-reset-filters" class="ndc-btn ndc-btn-secondary">
                    <?php _e('Reset', 'nivoda-diamond-connector'); ?>
                </button>
            </div>
            
        </form>
    </div>
    
    <!-- Results -->
    <div class="ndc-results">
        
        <!-- Results Header -->
        <div class="ndc-results-header">
            <div class="ndc-results-count">
                <span id="ndc-total-count">0</span> <?php _e('diamonds found', 'nivoda-diamond-connector'); ?>
            </div>
            
            <div class="ndc-results-sort">
                <label><?php _e('Sort by:', 'nivoda-diamond-connector'); ?></label>
                <select id="ndc-sort">
                    <option value="price_asc"><?php _e('Price: Low to High', 'nivoda-diamond-connector'); ?></option>
                    <option value="price_desc"><?php _e('Price: High to Low', 'nivoda-diamond-connector'); ?></option>
                    <option value="carat_desc"><?php _e('Carat: High to Low', 'nivoda-diamond-connector'); ?></option>
                    <option value="carat_asc"><?php _e('Carat: Low to High', 'nivoda-diamond-connector'); ?></option>
                </select>
            </div>
        </div>
        
        <!-- Results Grid -->
        <div id="ndc-results-grid" class="ndc-results-grid">
            <div class="ndc-loading">
                <div class="ndc-spinner"></div>
                <p><?php _e('Loading diamonds...', 'nivoda-diamond-connector'); ?></p>
            </div>
        </div>
        
        <!-- Pagination -->
        <div id="ndc-pagination" class="ndc-pagination"></div>
        
    </div>
    
</div>
