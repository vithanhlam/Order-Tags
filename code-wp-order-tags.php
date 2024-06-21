<?php
/**
 * @link              https://code-wp.com
 * @since             1.0.3
 *
 * @wordpress-plugin
 * Plugin Name:       Order Tags - CODE-WP
 * Plugin URI:        https://code-wp.com
 * Description:       Add tags in order
 * Version:           1.0.3
 * Author:            vithanhlam
 * Author URI:        https://fb.com/vithanhlam
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ORDER_TAG_WP_CODE_Handler {
    
    public function __construct() {
        add_filter('manage_edit-shop_order_columns', array($this, 'code_wp_order_tags_wc_order_extras_column_tags'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'code_order_tags_wc_order_extras'), 10, 2);
        add_action('init', array($this, 'code_wp_order_tag_custom_order_taxonomy'), 0);
        add_action('order_tag_add_form_fields', array($this, 'code_wp_order_tags_custom_order_tag_custom_fields'));
        add_action('order_tag_edit_form_fields', array($this, 'code_wp_order_tags_custom_order_tag_custom_fields'));
        add_action('created_order_tag', array($this, 'code_wp_order_tag_save_order_tag_custom_fields'));
        add_action('edited_order_tag', array($this, 'code_wp_order_tag_save_order_tag_custom_fields'));
        add_filter('manage_edit-order_tag_columns', array($this, 'code_wp_order_tag_add_color_column_to_order_tag'));
        add_filter('manage_order_tag_custom_column', array($this, 'code_wp_order_tag_display_order_tag_color'), 10, 3);
        add_action('admin_menu', array($this, 'code_wp_order_tag_add_tags_order_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'code_wp_theme_load_scripts'));
    }
    
    public function code_wp_order_tags_wc_order_extras_column_tags($columns) {
        $new_columns = array();
        $position = 0;

        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            $position++;
            if ($position === 3) {
                $new_columns['order_tags'] = __('Tags', 'code-wp-order-tags');
            }
        }
        return $new_columns;
    }

    public function code_order_tags_wc_order_extras($column, $post_id) {
        if ('order_tags' === $column) {
            $terms = wp_get_post_terms($post_id, 'order_tag');
            foreach ($terms as $terms_item) {
                $term_color = esc_attr(get_term_meta($terms_item->term_id, 'term_color', true));
                $escaped_term_name = esc_html($terms_item->name);
				if(!empty($term_color)) {
					$escaped_style = esc_attr("background:{$term_color}; padding: 3px 5px; border-radius: 3px; margin-right: 5px; color:#fff;");
				} else {
					$escaped_style = esc_attr("background:#28a745; padding: 3px 5px; border-radius: 3px; margin-right: 5px; color:#fff;");
				}
                
				
                $escaped_label_output = "<label style='{$escaped_style}'>{$escaped_term_name}</label>";
                echo $escaped_label_output;
            }
        }
    }

    public function code_wp_order_tag_custom_order_taxonomy() {
        $labels = array(
            'name'              => _x('Order Tags', 'taxonomy general name', 'code-wp-order-tags'),
            'singular_name'     => _x('Order Tag', 'taxonomy singular name', 'code-wp-order-tags'),
            'search_items'      => __('Search Order Tags', 'code-wp-order-tags'),
            'all_items'         => __('All Order Tags', 'code-wp-order-tags'),
            'edit_item'         => __('Edit Order Tag', 'code-wp-order-tags'),
            'update_item'       => __('Update Order Tag', 'code-wp-order-tags'),
            'add_new_item'      => __('Add New Order Tag', 'code-wp-order-tags'),
            'new_item_name'     => __('New Order Tag Name', 'code-wp-order-tags'),
            'menu_name'         => __('Order Tags', 'code-wp-order-tags'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'order_tag'),
        );

        register_taxonomy('order_tag', 'shop_order', $args);
    }

    public function code_wp_order_tags_custom_order_tag_custom_fields($term) {
        $color = '';
        if (is_object($term)) {
            $color = get_term_meta($term->term_id, 'term_color', true);
        }
        ?>
        <tr class="form-field term-color-wrap">
            <th scope="row"><label for="term-color"><?php esc_attr_e('Color', 'code-wp-order-tags'); ?></label></th>
            <td>
                <?php wp_nonce_field('save_term_color_nonce', 'save_term_color_nonce'); ?>
                <input name="term-color" class="color-picker" id="term-color" type="text" value="<?php echo esc_attr($color); ?>">
                <p class="description"><?php esc_attr_e('Choose a color for this tag.', 'code-wp-order-tags'); ?></p>
            </td>
        </tr>
        <script>
        jQuery(document).ready(function($){
            $('#term-color').wpColorPicker();
        });
        </script>
        <?php
    }

    public function code_wp_theme_load_scripts() {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
    }

    public function code_wp_order_tag_save_order_tag_custom_fields($term_id) {
        if (isset($_POST['save_term_color_nonce']) && wp_verify_nonce(sanitize_key(wp_unslash($_POST['save_term_color_nonce'])), 'save_term_color_nonce')) {
            if (isset($_POST['term-color'])) {
                $color = sanitize_hex_color($_POST['term-color']);
                update_term_meta($term_id, 'term_color', $color);
            }
        }
    }

    public function code_wp_order_tag_add_color_column_to_order_tag($columns) {
        $columns['term-color'] = __('Color', 'code-wp-order-tags');
        return $columns;
    }

    public function code_wp_order_tag_display_order_tag_color($content, $column_name, $term_id) {
        if ($column_name !== 'term-color') {
            return $content;
        }

        $color = get_term_meta($term_id, 'term_color', true);
        if (!empty($color)) {
            $content .= '<div style="width: 20px; height: 20px; border-radius: 50%; background-color: ' . esc_attr($color) . ';"></div>';
        } else {
            $content = '-';
        }

        return $content;
    }

    public function code_wp_order_tag_add_tags_order_submenu_page() {
        add_submenu_page(
            'woocommerce',
            'Tags Order',
            'Tags Order',
            'manage_options',
            'edit-tags.php?taxonomy=order_tag'
        );
    }
}

new ORDER_TAG_WP_CODE_Handler();
