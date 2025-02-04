<?php
/*
Plugin Name: Woo Category Widget for Elementor
Description: Custom Elementor widget to display WooCommerce category with an image and product count.
Version: 1.0
Author: HM Shahid
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if Elementor is installed and active
function check_elementor_active() {
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Elementor Shed Woo Category Widget</strong> requires Elementor to be installed and activated.</p></div>';
        });
        return false;
    }
    return true;
}


// Register the custom widget
function register_elementor_category_widget() {
    if (!check_elementor_active()) {
        return;
    }

    class Elementor_Category_Widget extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'category_widget';
        }

        public function get_title()
        {
            return __('Category Widget', 'plugin-name');
        }

        public function get_icon()
        {
            return 'eicon-posts-grid';
        }

        public function get_categories()
        {
            return ['general'];
        }

        protected function _register_controls()
        {
            // Content Tab
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'plugin-name'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            // Dropdown to select category
            $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
            $options = [];
            foreach ($categories as $category) {
                $options[$category->slug] = $category->name;
            }

            $this->add_control(
                'category',
                [
                    'label' => __('Category', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $options,
                    'default' => array_key_first($options),
                ]
            );

            // Option to use category image or upload a custom image
            $this->add_control(
                'use_category_image',
                [
                    'label' => __('Use Category Image', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __('Yes', 'plugin-name'),
                    'label_off' => __('No', 'plugin-name'),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'custom_image',
                [
                    'label' => __('Custom Image', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'default' => [
                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                    ],
                    'condition' => [
                        'use_category_image!' => 'yes',
                    ],
                ]
            );

            // Option to use category title or custom title
            $this->add_control(
                'custom_title',
                [
                    'label' => __('Custom Title', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                ]
            );

            $this->end_controls_section();

            // Style Tab
            $this->start_controls_section(
                'style_section',
                [
                    'label' => __('Style', 'plugin-name'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
                'background_color',
                [
                    'label' => __('Background Color', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .custom-category-widget' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'text_color',
                [
                    'label' => __('Text Color', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .custom-category-widget' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'border_radius',
                [
                    'label' => __('Border Radius', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        '%' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                    ],
                    'default' => [
                        'unit' => 'px',
                        'size' => 10,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .custom-category-widget' => 'border-radius: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'padding',
                [
                    'label' => __('Padding', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => 0,
                        'right' => 0,
                        'bottom' => 0,
                        'left' => 0,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .custom-category-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();

        }

        protected function render()
        {
            $settings = $this->get_settings_for_display();
            $category_slug = $settings['category'];

            // Get WooCommerce category object
            $category = get_term_by('slug', $category_slug, 'product_cat');
            if (!$category) {
                echo '<p>Invalid category selected.</p>';
                return;
            }

            // Get category details
            $category_name = $category->name;
            $category_link = get_term_link($category);
            $product_count = $category->count;

            // Get category thumbnail or custom image
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $category_image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
            
            $image_url = ($settings['use_category_image'] === 'yes') ? $category_image_url : $settings['custom_image']['url'];

            // Use custom title if provided
            $title = !empty($settings['custom_title']) ? $settings['custom_title'] : $category_name;

            // Ensure default values for color settings
            $background_color = isset($settings['background_color']) ? esc_attr($settings['background_color']) : '#000000';
            $text_color = isset($settings['text_color']) ? esc_attr($settings['text_color']) : '#ffffff';

            echo '<div class="custom-category-widget" style="display: flex; align-items: center; justify-content: space-between;' . $background_color . ';' . $text_color . '; border-radius: 10px; transition: transform 0.3s ease;">';
            echo '<div class="category-info">';
            echo '<h2>' . esc_html($title) . '</h2>';
            echo '<p>' . esc_html($product_count) . ' products</p>';
            echo '<a href="' . esc_url($category_link) . '" class="view-link" style="color: ' . $text_color . '; font-size: 1.5rem; text-decoration: none;">';
            echo '<span class="arrow-icon">→</span>';
            echo '</a>';
            echo '</div>';
            echo '<div class="category-image">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" style="max-width: 130px; border-radius: 10px;">';
            echo '</div>';
            echo '</div>';
        }
        


  
    }

    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_Category_Widget());
}
add_action('elementor/widgets/widgets_registered', 'register_elementor_category_widget');
