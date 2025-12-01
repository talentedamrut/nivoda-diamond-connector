<?php
/**
 * Custom Post Type for Diamonds
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_CPT {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_ndc_diamond', [$this, 'save_meta_boxes']);
    }
    
    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = [
            'name' => __('Diamonds', 'nivoda-diamond-connector'),
            'singular_name' => __('Diamond', 'nivoda-diamond-connector'),
            'add_new' => __('Add New', 'nivoda-diamond-connector'),
            'add_new_item' => __('Add New Diamond', 'nivoda-diamond-connector'),
            'edit_item' => __('Edit Diamond', 'nivoda-diamond-connector'),
            'new_item' => __('New Diamond', 'nivoda-diamond-connector'),
            'view_item' => __('View Diamond', 'nivoda-diamond-connector'),
            'search_items' => __('Search Diamonds', 'nivoda-diamond-connector'),
            'not_found' => __('No diamonds found', 'nivoda-diamond-connector'),
            'not_found_in_trash' => __('No diamonds found in trash', 'nivoda-diamond-connector'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'diamonds'],
            'show_in_rest' => true,
            'capability_type' => 'post',
        ];
        
        register_post_type('ndc_diamond', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Shape taxonomy
        register_taxonomy('ndc_shape', 'ndc_diamond', [
            'label' => __('Shape', 'nivoda-diamond-connector'),
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
        ]);
        
        // Color taxonomy
        register_taxonomy('ndc_color', 'ndc_diamond', [
            'label' => __('Color', 'nivoda-diamond-connector'),
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
        ]);
        
        // Clarity taxonomy
        register_taxonomy('ndc_clarity', 'ndc_diamond', [
            'label' => __('Clarity', 'nivoda-diamond-connector'),
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
        ]);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ndc_diamond_details',
            __('Diamond Details', 'nivoda-diamond-connector'),
            [$this, 'render_details_meta_box'],
            'ndc_diamond',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ndc_diamond_pricing',
            __('Pricing', 'nivoda-diamond-connector'),
            [$this, 'render_pricing_meta_box'],
            'ndc_diamond',
            'side',
            'default'
        );
    }
    
    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('ndc_save_meta', 'ndc_meta_nonce');
        
        $diamond_id = get_post_meta($post->ID, '_ndc_diamond_id', true);
        $carat = get_post_meta($post->ID, '_ndc_carat', true);
        $cut = get_post_meta($post->ID, '_ndc_cut', true);
        $polish = get_post_meta($post->ID, '_ndc_polish', true);
        $symmetry = get_post_meta($post->ID, '_ndc_symmetry', true);
        $fluorescence = get_post_meta($post->ID, '_ndc_fluorescence', true);
        $cert_number = get_post_meta($post->ID, '_ndc_cert_number', true);
        $cert_lab = get_post_meta($post->ID, '_ndc_cert_lab', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ndc_diamond_id"><?php _e('Nivoda Diamond ID', 'nivoda-diamond-connector'); ?></label></th>
                <td><input type="text" id="ndc_diamond_id" name="ndc_diamond_id" value="<?php echo esc_attr($diamond_id); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="ndc_carat"><?php _e('Carat', 'nivoda-diamond-connector'); ?></label></th>
                <td><input type="number" id="ndc_carat" name="ndc_carat" value="<?php echo esc_attr($carat); ?>" step="0.01" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="ndc_cut"><?php _e('Cut', 'nivoda-diamond-connector'); ?></label></th>
                <td>
                    <select id="ndc_cut" name="ndc_cut">
                        <option value=""><?php _e('Select Cut', 'nivoda-diamond-connector'); ?></option>
                        <?php foreach (['Ideal', 'Excellent', 'Very Good', 'Good', 'Fair'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($cut, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ndc_polish"><?php _e('Polish', 'nivoda-diamond-connector'); ?></label></th>
                <td>
                    <select id="ndc_polish" name="ndc_polish">
                        <option value=""><?php _e('Select Polish', 'nivoda-diamond-connector'); ?></option>
                        <?php foreach (['Excellent', 'Very Good', 'Good', 'Fair', 'Poor'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($polish, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ndc_symmetry"><?php _e('Symmetry', 'nivoda-diamond-connector'); ?></label></th>
                <td>
                    <select id="ndc_symmetry" name="ndc_symmetry">
                        <option value=""><?php _e('Select Symmetry', 'nivoda-diamond-connector'); ?></option>
                        <?php foreach (['Excellent', 'Very Good', 'Good', 'Fair', 'Poor'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($symmetry, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ndc_fluorescence"><?php _e('Fluorescence', 'nivoda-diamond-connector'); ?></label></th>
                <td>
                    <select id="ndc_fluorescence" name="ndc_fluorescence">
                        <option value=""><?php _e('Select Fluorescence', 'nivoda-diamond-connector'); ?></option>
                        <?php foreach (['None', 'Faint', 'Medium', 'Strong', 'Very Strong'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($fluorescence, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ndc_cert_number"><?php _e('Certificate Number', 'nivoda-diamond-connector'); ?></label></th>
                <td><input type="text" id="ndc_cert_number" name="ndc_cert_number" value="<?php echo esc_attr($cert_number); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="ndc_cert_lab"><?php _e('Certificate Lab', 'nivoda-diamond-connector'); ?></label></th>
                <td>
                    <select id="ndc_cert_lab" name="ndc_cert_lab">
                        <option value=""><?php _e('Select Lab', 'nivoda-diamond-connector'); ?></option>
                        <?php foreach (['GIA', 'IGI', 'HRD', 'GCAL'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($cert_lab, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render pricing meta box
     */
    public function render_pricing_meta_box($post) {
        $price = get_post_meta($post->ID, '_ndc_price', true);
        $discount = get_post_meta($post->ID, '_ndc_discount', true);
        
        ?>
        <p>
            <label for="ndc_price"><strong><?php _e('Price', 'nivoda-diamond-connector'); ?></strong></label><br>
            <input type="number" id="ndc_price" name="ndc_price" value="<?php echo esc_attr($price); ?>" step="0.01" class="regular-text">
        </p>
        <p>
            <label for="ndc_discount"><strong><?php _e('Discount %', 'nivoda-diamond-connector'); ?></strong></label><br>
            <input type="number" id="ndc_discount" name="ndc_discount" value="<?php echo esc_attr($discount); ?>" step="0.01" class="small-text">
        </p>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['ndc_meta_nonce']) || !wp_verify_nonce($_POST['ndc_meta_nonce'], 'ndc_save_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = [
            '_ndc_diamond_id' => 'sanitize_text_field',
            '_ndc_carat' => 'floatval',
            '_ndc_cut' => 'sanitize_text_field',
            '_ndc_polish' => 'sanitize_text_field',
            '_ndc_symmetry' => 'sanitize_text_field',
            '_ndc_fluorescence' => 'sanitize_text_field',
            '_ndc_cert_number' => 'sanitize_text_field',
            '_ndc_cert_lab' => 'sanitize_text_field',
            '_ndc_price' => 'floatval',
            '_ndc_discount' => 'floatval',
        ];
        
        foreach ($fields as $key => $sanitizer) {
            $field_name = str_replace('_ndc_', 'ndc_', $key);
            if (isset($_POST[$field_name])) {
                $value = call_user_func($sanitizer, $_POST[$field_name]);
                update_post_meta($post_id, $key, $value);
            }
        }
    }
}
