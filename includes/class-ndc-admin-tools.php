<?php
/**
 * Admin Tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDC_Admin_Tools {
    
    /**
     * API service
     */
    private $api;
    
    /**
     * Cache service
     */
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct($api, $cache) {
        $this->api = $api;
        $this->cache = $cache;
        
        add_action('admin_menu', [$this, 'add_tools_page']);
    }
    
    /**
     * Add tools submenu page
     */
    public function add_tools_page() {
        add_submenu_page(
            'nivoda-diamond-connector',
            __('Tools & Diagnostics', 'nivoda-diamond-connector'),
            __('Tools', 'nivoda-diamond-connector'),
            'manage_options',
            'nivoda-diamond-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Render tools page
     */
    public function render_tools_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle actions
        if (isset($_POST['clear_cache']) && check_admin_referer('ndc_clear_cache')) {
            $this->cache->clear_all();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Cache cleared successfully.', 'nivoda-diamond-connector') . '</p></div>';
        }
        
        if (isset($_POST['sync_diamonds']) && check_admin_referer('ndc_sync_diamonds')) {
            $count = $this->sync_diamonds();
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Synced %d diamonds.', 'nivoda-diamond-connector'), $count) . '</p></div>';
        }
        
        // Get cache stats
        $cache_stats = $this->cache->get_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tools & Diagnostics', 'nivoda-diamond-connector'); ?></h1>
            
            <!-- Cache Management -->
            <div class="card">
                <h2><?php _e('Cache Management', 'nivoda-diamond-connector'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Cache Status', 'nivoda-diamond-connector'); ?></th>
                        <td>
                            <?php if ($cache_stats['enabled']): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php _e('Enabled', 'nivoda-diamond-connector'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                                <?php _e('Disabled', 'nivoda-diamond-connector'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Cached Items', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html($cache_stats['count']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Cache Size', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html($cache_stats['size_formatted']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Cache TTL', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html($cache_stats['ttl']); ?> <?php _e('seconds', 'nivoda-diamond-connector'); ?></td>
                    </tr>
                </table>
                
                <form method="post">
                    <?php wp_nonce_field('ndc_clear_cache'); ?>
                    <p>
                        <button type="submit" name="clear_cache" class="button button-secondary">
                            <?php _e('Clear All Cache', 'nivoda-diamond-connector'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Diamond Sync -->
            <div class="card">
                <h2><?php _e('Diamond Sync', 'nivoda-diamond-connector'); ?></h2>
                
                <p><?php _e('Sync the latest diamonds from Nivoda API to local custom post types.', 'nivoda-diamond-connector'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('ndc_sync_diamonds'); ?>
                    <p>
                        <button type="submit" name="sync_diamonds" class="button button-primary">
                            <?php _e('Sync Diamonds', 'nivoda-diamond-connector'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- API Diagnostics -->
            <div class="card">
                <h2><?php _e('API Diagnostics', 'nivoda-diamond-connector'); ?></h2>
                
                <div id="ndc-diagnostics-result"></div>
                
                <p>
                    <button type="button" id="ndc-run-diagnostics" class="button button-secondary">
                        <?php _e('Run Diagnostics', 'nivoda-diamond-connector'); ?>
                    </button>
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#ndc-run-diagnostics').on('click', function() {
                        var $btn = $(this);
                        var $result = $('#ndc-diagnostics-result');
                        
                        $btn.prop('disabled', true).text('<?php _e('Running...', 'nivoda-diamond-connector'); ?>');
                        $result.html('<p><?php _e('Testing API connection...', 'nivoda-diamond-connector'); ?></p>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'ndc_test_api',
                                nonce: '<?php echo wp_create_nonce('ndc_admin_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                                } else {
                                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                                }
                            },
                            error: function() {
                                $result.html('<div class="notice notice-error inline"><p><?php _e('An error occurred.', 'nivoda-diamond-connector'); ?></p></div>');
                            },
                            complete: function() {
                                $btn.prop('disabled', false).text('<?php _e('Run Diagnostics', 'nivoda-diamond-connector'); ?>');
                            }
                        });
                    });
                });
                </script>
            </div>
            
            <!-- System Info -->
            <div class="card">
                <h2><?php _e('System Information', 'nivoda-diamond-connector'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Plugin Version', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html(NDC_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('WordPress Version', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('PHP Version', 'nivoda-diamond-connector'); ?></th>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('WooCommerce Version', 'nivoda-diamond-connector'); ?></th>
                        <td>
                            <?php
                            if (defined('WC_VERSION')) {
                                echo esc_html(WC_VERSION);
                            } else {
                                echo '<span style="color: red;">' . __('Not Installed', 'nivoda-diamond-connector') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sync diamonds from API
     */
    private function sync_diamonds() {
        $result = $this->api->search_diamonds([], 1, 100);
        
        if (is_wp_error($result)) {
            return 0;
        }
        
        $diamonds = $result['data']['diamonds_by_query']['items'] ?? [];
        $count = 0;
        
        foreach ($diamonds as $diamond) {
            if ($this->create_diamond_post($diamond)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Create diamond post
     */
    private function create_diamond_post($diamond) {
        $diamond_id = $diamond['id'];
        
        // Check if exists
        $existing = get_posts([
            'post_type' => 'ndc_diamond',
            'meta_key' => '_ndc_diamond_id',
            'meta_value' => $diamond_id,
            'posts_per_page' => 1,
        ]);
        
        $cert = $diamond['certificate'] ?? [];
        
        $post_data = [
            'post_title' => sprintf(
                '%s ct %s %s %s',
                $cert['carats'] ?? '',
                $cert['shape'] ?? '',
                $cert['color'] ?? '',
                $cert['clarity'] ?? ''
            ),
            'post_type' => 'ndc_diamond',
            'post_status' => 'publish',
        ];
        
        if (!empty($existing)) {
            $post_data['ID'] = $existing[0]->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if ($post_id) {
            update_post_meta($post_id, '_ndc_diamond_id', $diamond_id);
            update_post_meta($post_id, '_ndc_carat', $cert['carats'] ?? '');
            update_post_meta($post_id, '_ndc_cut', $cert['cut'] ?? '');
            update_post_meta($post_id, '_ndc_price', $diamond['price'] ?? 0);
            
            return true;
        }
        
        return false;
    }
}
