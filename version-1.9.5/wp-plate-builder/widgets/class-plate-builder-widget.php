<?php
/**
 * Elementor widget that renders the number plate builder. The builder allows
 * visitors to configure their own registration plates by selecting
 * plate type, entering a registration, picking sizes, text styles,
 * colours, borders and more. A live preview updates in real time.
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WP_Plate_Builder_Widget
 */
class WP_Plate_Builder_Widget extends Widget_Base {

    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_plate_builder';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __( 'Number Plate Builder', 'wp-plate-builder' );
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon class.
     */
    public function get_icon() {
        return 'eicon-editor-code';
    }

    /**
     * Get categories this widget belongs to.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return [ 'general' ];
    }

    /**
     * Get CSS dependencies for this widget.
     *
     * @return array List of style handles.
     */
    public function get_style_depends() {
        return [ 'wp-plate-builder-style' ];
    }

    /**
     * Get JS dependencies for this widget.
     *
     * @return array List of script handles.
     */
    public function get_script_depends() {
        return [ 'wp-plate-builder-script' ];
    }

    /**
     * Registers widget controls. Elementor allows controls in the editor pane,
     * but this simple widget does not expose any options to the site editor.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'wp-plate-builder' ),
            ]
        );

        $this->add_control(
            'heading_text',
            [
                'label'       => __( 'Heading Text', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Number plate builder!', 'wp-plate-builder' ),
                'description' => __( 'Heading text displayed above the builder.', 'wp-plate-builder' ),
            ]
        );

        $this->add_control(
            'subheading_text',
            [
                'label'       => __( 'Subheading Text', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Customise your registration plate in seconds using our simple builder.', 'wp-plate-builder' ),
                'rows'        => 3,
                'description' => __( 'Subheading text displayed beneath the heading.', 'wp-plate-builder' ),
            ]
        );

        $this->end_controls_section();

        /**
         * Design & Colour options.
         */
        $this->start_controls_section(
            'section_design',
            [
                'label' => __( 'Design & Colours', 'wp-plate-builder' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Primary accent colour used for headings, selections and buttons.
        $this->add_control(
            'primary_color',
            [
                'label'   => __( 'Primary Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#c62828',
            ]
        );

        // Secondary colour used for hover states.
        $this->add_control(
            'secondary_color',
            [
                'label'   => __( 'Secondary Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#b71c1c',
            ]
        );

        // Background colour for the options panel.
        $this->add_control(
            'options_background',
            [
                'label'   => __( 'Options Background Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#ffffff',
            ]
        );

        // Background colour for the preview panel.
        $this->add_control(
            'preview_background',
            [
                'label'   => __( 'Preview Background Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#fafafa',
            ]
        );

        // Text colour for the purchase button.
        $this->add_control(
            'button_text_color',
            [
                'label'   => __( 'Button Text Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#ffffff',
            ]
        );

        // Text colour for the purchase button on hover.
        $this->add_control(
            'button_hover_text_color',
            [
                'label'   => __( 'Button Hover Text Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '#ffffff',
            ]
        );

        $this->end_controls_section(); // end section_design

        /**
         * Appearance section: typography and layout tweaks.
         */
        $this->start_controls_section(
            'section_appearance',
            [
                'label' => __( 'Appearance', 'wp-plate-builder' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Registration font size (em units)
        $this->add_control(
            'reg_font_size',
            [
                'label'       => __( 'Registration Font Size (em)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'em' ],
                'range'       => [
                    'em' => [ 'min' => 1, 'max' => 4, 'step' => 0.1 ],
                ],
                // Increase the default font size to produce more realistic lettering
                'default'     => [ 'unit' => 'em', 'size' => 2.5 ],
            ]
        );

        // Plate border radius
        $this->add_control(
            'plate_border_radius',
            [
                'label'       => __( 'Plate Border Radius (px)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [ 'min' => 0, 'max' => 20, 'step' => 1 ],
                ],
                'default'     => [ 'unit' => 'px', 'size' => 6 ],
            ]
        );

        // Panel border radius (options and preview panels)
        $this->add_control(
            'panel_border_radius',
            [
                'label'       => __( 'Panel Border Radius (px)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
                ],
                'default'     => [ 'unit' => 'px', 'size' => 4 ],
            ]
        );

        // Button padding (vertical padding only)
        $this->add_control(
            'button_padding',
            [
                'label'       => __( 'Button Padding (vertical, px)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [ 'min' => 4, 'max' => 30, 'step' => 1 ],
                ],
                'default'     => [ 'unit' => 'px', 'size' => 12 ],
                'description' => __( 'Horizontal padding is fixed at 25px.', 'wp-plate-builder' ),
            ]
        );

        // Button border radius
        $this->add_control(
            'button_border_radius',
            [
                'label'       => __( 'Button Border Radius (px)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'px' ],
                'range'       => [
                    'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
                ],
                'default'     => [ 'unit' => 'px', 'size' => 4 ],
            ]
        );

        // Letter spacing for registration characters
        $this->add_control(
            'letter_spacing',
            [
                'label'       => __( 'Letter Spacing (em)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 'em' ],
                'range'       => [
                    'em' => [ 'min' => 0, 'max' => 1, 'step' => 0.01 ],
                ],
                'default'     => [ 'unit' => 'em', 'size' => 0.1 ],
                'description' => __( 'Adjust the spacing between registration characters. Typical values are 0.07–0.12 em.', 'wp-plate-builder' ),
            ]
        );

        $this->end_controls_section(); // end section_appearance

        /**
         * Configuration for selectable options. Using repeaters allows site admins
         * to tailor the builder to their own needs: sizes, styles, colours, etc.
         */
        // Start the options section. All selectable configuration lists (sizes,
        // styles, colours, etc.) are grouped here in the Content tab.
        $this->start_controls_section(
            'section_options',
            [
                'label' => __( 'Plate Options', 'wp-plate-builder' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Plate sizes repeater: label, value, width, height.
        $size_repeater = new Repeater();
        $size_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'Standard (520mm x 111mm)',
                'label_block' => true,
            ]
        );
        $size_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'std',
            ]
        );
        $size_repeater->add_control(
            'width',
            [
                'label'   => __( 'Width (px)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 280,
            ]
        );
        $size_repeater->add_control(
            'height',
            [
                'label'   => __( 'Height (px)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 60,
            ]
        );
        
        $size_repeater->add_control(
            'max_chars',
            [
                'label'   => __( 'Max characters (legal)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 7,
                'min'     => 1,
                'max'     => 10,
                'step'    => 1,
            ]
        );
$this->add_control(
            'plate_sizes',
            [
                'label'       => __( 'Plate Sizes', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $size_repeater->get_controls(),
                // Removed motorcycle size by default. Editors can add it back if needed.
                'default'     => [
                    // Standard + Short + HEX (from user's list)
                    [
                        'label' => 'Standard (520mm x 111mm)',
                        'value' => 'std',
                        'width' => 520,
                        'height' => 111,
                        'max_chars' => 7
                    ],
                    [
                        'label' => 'Standard 4x4 (280mm x 203mm)',
                        'value' => 'std_4x4',
                        'width' => 280,
                        'height' => 203,
                        'max_chars' => 7
                    ],
                    [
                        'label' => 'Large Rear (533mm x 152mm)',
                        'value' => 'large_rear',
                        'width' => 533,
                        'height' => 152,
                        'max_chars' => 7
                    ],
                    [
                        'label' => 'Short Plate (Max 6 digits) 408mm x 111mm',
                        'value' => 'short6',
                        'width' => 408,
                        'height' => 111,
                        'max_chars' => 6
                    ],
                    [
                        'label' => 'Short Plate (Max 5 digits) 345mm x 111mm',
                        'value' => 'short5',
                        'width' => 345,
                        'height' => 111,
                        'max_chars' => 5
                    ],
                    [
                        'label' => 'Short Plate (Max 4 digits) 285mm x 111mm',
                        'value' => 'short4',
                        'width' => 285,
                        'height' => 111,
                        'max_chars' => 4
                    ],
                    [
                        'label' => 'HEX (Lambo) 522mm x 111mm',
                        'value' => 'hex7',
                        'width' => 522,
                        'height' => 140,
                        'max_chars' => 7
                    ],
                    [
                        'label' => 'HEX 6 Digit (Lambo) 490mm x 111mm',
                        'value' => 'hex6',
                        'width' => 490,
                        'height' => 140,
                        'max_chars' => 6
                    ],
                    [
                        'label' => 'HEX 5 Digit (Lambo) 390mm x 111mm',
                        'value' => 'hex5',
                        'width' => 390,
                        'height' => 140,
                        'max_chars' => 5
                    ],
                    [
                        'label' => 'HEX 4 Digit (Lambo) 311mm x 111mm',
                        'value' => 'hex4',
                        'width' => 311,
                        'height' => 140,
                        'max_chars' => 4
                    ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        // Text styles repeater: label, value, price per plate.
        $style_repeater = new Repeater();
        $style_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'Standard',
                'label_block' => true,
            ]
        );
        $style_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'standard',
            ]
        );
        $style_repeater->add_control(
            'price',
            [
                'label'   => __( 'Price per plate', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 14.95,
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        
        // New: front/back pricing (user will fill these; defaults empty)
        $style_repeater->add_control(
            'price_front',
            [
                'label'   => __( 'Front price', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => '',
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        $style_repeater->add_control(
            'price_rear',
            [
                'label'   => __( 'Rear price', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => '',
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        // New: static preview image per style
        $style_repeater->add_control(
            'preview_image',
            [
                'label' => __( 'Preview image', 'wp-plate-builder' ),
                'type'  => Controls_Manager::MEDIA,
                'default' => [ 'url' => '' ],
            ]
        );
// Road legal toggle: determines whether this style is available when
        // the user selects "Road legal". Standard is road-legal by default,
        // all others default to non-road-legal.
        $style_repeater->add_control(
            'road_legal',
            [
                'label'        => __( 'Road Legal?', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'wp-plate-builder' ),
                'label_off'    => __( 'No', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );
        $this->add_control(
            'text_styles',
            [
                'label'       => __( 'Text Styles', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $style_repeater->get_controls(),
                'default'     => [
                    [ 'label' => 'Standard',           'value' => 'standard',     'price' => 14.95, 'road_legal' => 'yes' ],
                    [ 'label' => '3D Gel Domed',       'value' => '3d',           'price' => 26.00, 'road_legal' => ''],
                    [ 'label' => '4D Perspex',         'value' => '4d',           'price' => 26.00, 'road_legal' => ''],
                    [ 'label' => '5D Gel Perspex',     'value' => '5d',           'price' => 35.50, 'road_legal' => ''],
                    // Additional styles inspired by the uploaded sample plate designs. Site editors can adjust pricing.
                    [ 'label' => '5mm 3mm 4D',         'value' => '5mm3mm4d',     'price' => 26.00, 'road_legal' => '' ],
                    [ 'label' => '4D Gel',             'value' => '4dgel',        'price' => 26.00, 'road_legal' => '' ],
                    [ 'label' => 'Ghost',              'value' => 'ghost',        'price' => 30.00, 'road_legal' => '' ],
                    [ 'label' => 'Piano',              'value' => 'piano',        'price' => 30.00, 'road_legal' => '' ],
                    [ 'label' => 'Matte',              'value' => 'matte',        'price' => 28.00, 'road_legal' => '' ],
                    [ 'label' => 'Short (style)',      'value' => 'shortstyle',   'price' => 20.00, 'road_legal' => '' ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        // Badge colours repeater: label and colour.
        $colour_repeater = new Repeater();
        $colour_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'None',
                'label_block' => true,
            ]
        );
        $colour_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'none',
            ]
        );
        $colour_repeater->add_control(
            'colour',
            [
                'label'   => __( 'Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '',
            ]
        );
        $this->add_control(
            'badge_colours',
            [
                'label'       => __( 'Badge Colours', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $colour_repeater->get_controls(),
                'default'     => [
                    [ 'label' => 'None',  'value' => 'none',  'colour' => '' ],
                    [ 'label' => 'Blue',  'value' => 'blue',  'colour' => '#0055a5' ],
                    [ 'label' => 'Red',   'value' => 'red',   'colour' => '#cf142b' ],
                    [ 'label' => 'Yellow','value' => 'yellow','colour' => '#fcd116' ],
                    [ 'label' => 'Green', 'value' => 'green', 'colour' => '#00a650' ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        // Border colours repeater.
        $border_repeater = new Repeater();
        $border_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'None',
                'label_block' => true,
            ]
        );
        $border_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'transparent',
            ]
        );
        $border_repeater->add_control(
            'colour',
            [
                'label'   => __( 'Colour', 'wp-plate-builder' ),
                'type'    => Controls_Manager::COLOR,
                'default' => '',
            ]
        );
        $this->add_control(
            'border_colours',
            [
                'label'       => __( 'Border Colours', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $border_repeater->get_controls(),
                'default'     => [
                    [ 'label' => 'None',    'value' => 'transparent', 'colour' => '' ],
                    [ 'label' => 'Black',   'value' => 'black',       'colour' => '#000000' ],
                    [ 'label' => 'White',   'value' => 'white',       'colour' => '#ffffff' ],
                    [ 'label' => 'Red',     'value' => 'red',         'colour' => '#cf142b' ],
                    [ 'label' => 'Blue',    'value' => 'blue',        'colour' => '#0055a5' ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        // Plate surround options repeater.
        $surround_repeater = new Repeater();
        $surround_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'None',
                'label_block' => true,
            ]
        );
        $surround_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'none',
            ]
        );
        $surround_repeater->add_control(
            'cost_single',
            [
                'label'   => __( 'Cost (single)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 0,
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        $surround_repeater->add_control(
            'cost_pair',
            [
                'label'   => __( 'Cost (both)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 0,
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        $this->add_control(
            'surround_options',
            [
                'label'       => __( 'Plate Surround Options', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $surround_repeater->get_controls(),
                'default'     => [
                    [ 'label' => 'None',        'value' => 'none',  'cost_single' => 0,  'cost_pair' => 0 ],
                    [ 'label' => 'Plain black', 'value' => 'plain', 'cost_single' => 28, 'cost_pair' => 50 ],
                    [ 'label' => 'Marque',      'value' => 'marque','cost_single' => 28, 'cost_pair' => 50 ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        
        // Extra options (checkboxes on the front-end)
        $extra_repeater = new Repeater();
        $extra_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'Example Extra',
                'label_block' => true,
            ]
        );
        $extra_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value (slug)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'extra_1',
            ]
        );
        $extra_repeater->add_control(
            'price',
            [
                'label'   => __( 'Price (£)', 'wp-plate-builder' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 0,
                'min'     => 0,
                'step'    => 0.01,
            ]
        );
        $this->add_control(
            'extras_options',
            [
                'label'       => __( 'Extras', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $extra_repeater->get_controls(),
                'default'     => [],
                'title_field' => '{{{ label }}}',
            ]
        );
// Electric options repeater.
        $electric_repeater = new Repeater();
        $electric_repeater->add_control(
            'label',
            [
                'label'       => __( 'Label', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'None',
                'label_block' => true,
            ]
        );
        $electric_repeater->add_control(
            'value',
            [
                'label'   => __( 'Value', 'wp-plate-builder' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'none',
            ]
        );
        $this->add_control(
            'electric_options',
            [
                'label'       => __( 'Electric Plate Options', 'wp-plate-builder' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $electric_repeater->get_controls(),
                'default'     => [
                    [ 'label' => 'None',       'value' => 'none' ],
                    [ 'label' => 'Green flash','value' => 'green' ],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        // Discount applied when both plates are ordered. Subtracted from total.
        $this->add_control(
            'pair_discount',
            [
                'label'       => __( 'Pair Discount', 'wp-plate-builder' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 1.00,
                'min'         => 0,
                'step'        => 0.01,
                'description' => __( 'Discount applied when both plates are ordered.', 'wp-plate-builder' ),
            ]
        );

        // Toggles for showing or hiding specific option sections in the builder.
        // These controls allow site editors to enable/disable groups of options.
        // Option toggles: allow site editors to selectively enable or disable
        // each section of the builder. When disabled, the corresponding
        // markup will not be rendered and no controls will appear in the
        // front‑end. All toggles default to "yes" so by default the full
        // builder is shown.
        $this->add_control(
            'show_plate_use',
            [
                'label'        => __( 'Show Plate Type Selection', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_plate_count',
            [
                'label'        => __( 'Show Plate Count Selection', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_registration',
            [
                'label'        => __( 'Show Registration Input', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_plate_size',
            [
                'label'        => __( 'Show Plate Size Selection', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_text_style',
            [
                'label'        => __( 'Show Text Style Selection', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_badge_picker',
            [
                'label'        => __( 'Show Badge Colour Picker', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_border_picker',
            [
                'label'        => __( 'Show Border Colour Picker', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_surround_option',
            [
                'label'        => __( 'Show Plate Surround Option', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        $this->add_control(
            'show_electric_option',
            [
                'label'        => __( 'Show Electric Plate Option', 'wp-plate-builder' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'wp-plate-builder' ),
                'label_off'    => __( 'Hide', 'wp-plate-builder' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // Optional form selector. When provided, the builder will create
        // hidden fields in the specified Elementor form and keep them
        // synchronised with the user’s selections. Use a CSS selector
        // (e.g. #my-form or .elementor-form) to target the form.
        $this->add_control(
            'form_selector',
            [
                'label'       => __( 'Form Selector (optional)', 'wp-plate-builder' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => '#my-form',
                'description' => __( 'CSS selector for an Elementor Form to which selected options will be sent via hidden fields.', 'wp-plate-builder' ),
            ]
        );

        // End the options section
        $this->end_controls_section();
    }

    /**
     * Renders the widget output on the front‑end.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Enqueue the scripts and styles for the front‑end output.
        wp_enqueue_style( 'wp-plate-builder-style' );
        wp_enqueue_script( 'wp-plate-builder-script' );

        // Prepare runtime configuration for JavaScript based on widget settings.
        
        // Build front/back style pricing map
        $style_prices_fb = [];
        $style_images = [];
$style_prices = [];
        if ( ! empty( $settings['text_styles'] ) && is_arrayy( $settings['text_styles'] ) ) {
            foreach ( $settings['text_styles'] as $style ) {
                if ( isset( $style['value'] ) && isset( $style['price'] ) ) {
                    $style_prices[ $style['value'] ] = floatval( $style['price'] );
                }
                // New FB prices (empty defaults to 0)
                $front = isset($style['price_front']) && $style['price_front'] !== '' ? floatval($style['price_front']) : 0;
                $rear  = isset($style['price_rear'])  && $style['price_rear']  !== '' ? floatval($style['price_rear'])  : 0;
                $style_prices_fb[ $style['value'] ] = [ 'front' => $front, 'rear' => $rear ];
                // Style preview image
                 $url = '';
                if ( isset( $style['preview_image'] ) && is_arrayy( $style['preview_image'] ) && ! empty( $style['preview_image']['url'] ) ) {
                    $url = $style['preview_image']['url'];
                }
                if ( $url ) {
                    $style_images[ $style['value'] ] = esc_url( $url );
                }
            }
        }
        $size_prices_fr = [];
        $plate_sizes_map = [];
        if ( ! empty( $settings['plate_sizes'] ) && is_arrayy( $settings['plate_sizes'] ) ) {
            foreach ( $settings['plate_sizes'] as $size ) {
                if ( isset( $size['value'] ) && isset( $size['width'] ) && isset( $size['height'] ) ) {
                    $plate_sizes_map[ $size['value'] ] = [ 'width' => intval( $size['width'] ), 'height' => intval( $size['height'] ), 'max' => isset($size['max_chars']) ? intval($size['max_chars']) : 7 ];
                    $size_prices_fr[ $size['value'] ] = [
                        'front' => isset($size['price_front']) ? floatval($size['price_front']) : 0,
                        'rear'  => isset($size['price_rear'])  ? floatval($size['price_rear'])  : 0,
                    ];
                }
            }
            }
        }
        $surround_costs = [];
        if ( ! empty( $settings['surround_options'] ) && is_arrayy( $settings['surround_options'] ) ) {
            foreach ( $settings['surround_options'] as $sur ) {
                if ( isset( $sur['value'] ) ) {
                    $surround_costs[ $sur['value'] ] = [
                        'single' => isset( $sur['cost_single'] ) ? floatval( $sur['cost_single'] ) : 0,
                        'pair'   => isset( $sur['cost_pair'] ) ? floatval( $sur['cost_pair'] ) : 0,
                    ];
                }
            }
        }
        
        // Build extras map
        $extras_map = [];
        if ( ! empty( $settings['extras_options'] ) && is_arrayy( $settings['extras_options'] ) ) {
            foreach ( $settings['extras_options'] as $ex ) {
                if ( isset( $ex['value'] ) ) {
                    $extras_map[ $ex['value'] ] = [
                        'label' => isset($ex['label']) ? $ex['label'] : '',
                        'price' => isset($ex['price']) ? floatval($ex['price']) : 0,
                    ];
                }
            }
        }
$config = [
            'stylePrices'   => $style_prices,
            'stylePricesFB' => $style_prices_fb,
            'styleImages'   => $style_images,
            'extras'        => $extras_map,
            'plateSizes'    => $plate_sizes_map,
            'sizePricesFR' => $size_prices_fr,
            'surroundCosts' => $surround_costs,
            // Pass the pair discount to the front‑end. Cast to float for safety.
            'pairDiscount'  => isset( $settings['pair_discount'] ) ? floatval( $settings['pair_discount'] ) : 1.0,
        ];
        $config_json = wp_json_encode( $config );

        // Determine which option sections to show. Defaults to 'yes'. Each value
        // corresponds to a switcher defined in register_controls().
        $show_plate_use   = isset( $settings['show_plate_use'] ) ? $settings['show_plate_use'] : 'yes';
        $show_plate_count = isset( $settings['show_plate_count'] ) ? $settings['show_plate_count'] : 'yes';
        $show_registration = isset( $settings['show_registration'] ) ? $settings['show_registration'] : 'yes';
        $show_plate_size = isset( $settings['show_plate_size'] ) ? $settings['show_plate_size'] : 'yes';
        $show_text_style = isset( $settings['show_text_style'] ) ? $settings['show_text_style'] : 'yes';
        $show_badges   = isset( $settings['show_badge_picker'] ) ? $settings['show_badge_picker'] : 'yes';
        $show_borders  = isset( $settings['show_border_picker'] ) ? $settings['show_border_picker'] : 'yes';
        $show_surround = isset( $settings['show_surround_option'] ) ? $settings['show_surround_option'] : 'yes';
        $show_electric = isset( $settings['show_electric_option'] ) ? $settings['show_electric_option'] : 'yes';
        $form_selector = ! empty( $settings['form_selector'] ) ? $settings['form_selector'] : '';

        // Determine accent colours.
        $primary   = $settings['primary_color'] ? $settings['primary_color'] : '#c62828';
        $secondary = $settings['secondary_color'] ? $settings['secondary_color'] : '#b71c1c';
        $options_bg = ! empty( $settings['options_background'] ) ? $settings['options_background'] : '#ffffff';
        $preview_bg = ! empty( $settings['preview_background'] ) ? $settings['preview_background'] : '#fafafa';
        $button_text = ! empty( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff';
        $button_hover_text = ! empty( $settings['button_hover_text_color'] ) ? $settings['button_hover_text_color'] : '#ffffff';
        // Typography and layout values
        $reg_font_size = ! empty( $settings['reg_font_size']['size'] ) ? floatval( $settings['reg_font_size']['size'] ) : 1.8;
        $reg_font_unit = ! empty( $settings['reg_font_size']['unit'] ) ? $settings['reg_font_size']['unit'] : 'em';
        $plate_radius  = ! empty( $settings['plate_border_radius']['size'] ) ? intval( $settings['plate_border_radius']['size'] ) : 6;
        $panel_radius  = ! empty( $settings['panel_border_radius']['size'] ) ? intval( $settings['panel_border_radius']['size'] ) : 4;
        $button_pad    = ! empty( $settings['button_padding']['size'] ) ? intval( $settings['button_padding']['size'] ) : 12;
        $button_radius = ! empty( $settings['button_border_radius']['size'] ) ? intval( $settings['button_border_radius']['size'] ) : 4;

        // Letter spacing value for characters
        $letter_spacing = ! empty( $settings['letter_spacing']['size'] ) ? floatval( $settings['letter_spacing']['size'] ) : 0.1;
        $letter_spacing_unit = ! empty( $settings['letter_spacing']['unit'] ) ? $settings['letter_spacing']['unit'] : 'em';

        // Build dynamic markup for select lists and pickers.
        ob_start();
        ?>
        <div class="wp-plate-builder-container"
            data-config='<?php echo esc_attr( $config_json ); ?>'
            data-show-use="<?php echo esc_attr( $show_plate_use ); ?>"
            data-show-count="<?php echo esc_attr( $show_plate_count ); ?>"
            data-show-registration="<?php echo esc_attr( $show_registration ); ?>"
            data-show-size="<?php echo esc_attr( $show_plate_size ); ?>"
            data-show-style="<?php echo esc_attr( $show_text_style ); ?>"
            data-show-badges="<?php echo esc_attr( $show_badges ); ?>"
            data-show-borders="<?php echo esc_attr( $show_borders ); ?>"
            data-show-surround="<?php echo esc_attr( $show_surround ); ?>"
            data-show-electric="<?php echo esc_attr( $show_electric ); ?>"
            data-form-selector="<?php echo esc_attr( $form_selector ); ?>"
            style="--wp-plate-primary-color: <?php echo esc_attr( $primary ); ?>; --wp-plate-secondary-color: <?php echo esc_attr( $secondary ); ?>; --wp-plate-options-bg: <?php echo esc_attr( $options_bg ); ?>; --wp-plate-preview-bg: <?php echo esc_attr( $preview_bg ); ?>; --wp-plate-button-text-color: <?php echo esc_attr( $button_text ); ?>; --wp-plate-button-hover-text-color: <?php echo esc_attr( $button_hover_text ); ?>; --wp-plate-reg-font-size: <?php echo esc_attr( $reg_font_size . $reg_font_unit ); ?>; --wp-plate-plate-radius: <?php echo esc_attr( $plate_radius . 'px' ); ?>; --wp-plate-panel-radius: <?php echo esc_attr( $panel_radius . 'px' ); ?>; --wp-plate-button-padding: <?php echo esc_attr( $button_pad . 'px 25px' ); ?>; --wp-plate-button-radius: <?php echo esc_attr( $button_radius . 'px' ); ?>; --wp-plate-letter-spacing: <?php echo esc_attr( $letter_spacing . $letter_spacing_unit ); ?>;">
            <h2 class="wp-plate-builder-heading"><?php echo esc_html( $settings['heading_text'] ); ?></h2>
            <p class="wp-plate-builder-subheading"><?php echo esc_html( $settings['subheading_text'] ); ?></p>
            <div class="wp-plate-builder-wrapper">
                <div class="plate-options">
                    <?php if ( $show_plate_use === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate Type', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <label><input type="radio" name="plate_use_type" value="road" checked> <?php esc_html_e( 'Road legal', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_use_type" value="show"> <?php esc_html_e( 'Show plate', 'wp-plate-builder' ); ?></label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_plate_count === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plates required', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <label><input type="radio" name="plate_count" value="both" checked> <?php esc_html_e( 'Both', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_count" value="front"> <?php esc_html_e( 'Front only', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_count" value="rear"> <?php esc_html_e( 'Rear only', 'wp-plate-builder' ); ?></label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_registration === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Registration', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <input type="text" name="registration" class="plate-registration" maxlength="10" placeholder="YOUR REG">
                            <!-- Error message for invalid registration format -->
                            <div class="reg-error" style="display:none;color:<?php echo esc_attr( $primary ); ?>;font-size:0.8em; margin-top:4px;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_plate_size === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate size', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="plate_size" class="plate-size">
                                <?php foreach ( $settings['plate_sizes'] as $size ) : ?>
                                    <?php
                                    // Skip the square size entirely.  Site editors can still add
                                    // custom sizes, but the built‑in square option is removed.
                                    if ( isset( $size['value'] ) && $size['value'] === 'square' ) {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr( $size['value'] ); ?>"><?php echo esc_html( $size['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_text_style === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Text style', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="text_style" class="text-style">
                                <?php foreach ( $settings['text_styles'] as $style ) : ?>
                                    <?php
                                    $legal = ( isset( $style['road_legal'] ) && $style['road_legal'] === 'yes' ) ? 'yes' : 'no';
                                    ?>
                                    <option value="<?php echo esc_attr( $style['value'] ); ?>" data-legal="<?php echo esc_attr( $legal ); ?>">
                                        <?php echo esc_html( $style['label'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_badges === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Badge & colour', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content badge-picker">
                            <?php foreach ( $settings['badge_colours'] as $badge ) : ?>
                                <?php
                                $colour = $badge['colour'] ? $badge['colour'] : 'transparent';
                                ?>
                                <div class="badge-colour" data-colour="<?php echo esc_attr( $colour ); ?>" style="background: <?php echo esc_attr( $colour ); ?>;<?php echo $colour === 'transparent' ? ' border:1px solid #ccc;' : ''; ?>">
                                    <?php echo $colour === 'transparent' ? esc_html__( 'None', 'wp-plate-builder' ) : ''; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_borders === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Border colour', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content border-picker">
                            <?php foreach ( $settings['border_colours'] as $border ) : ?>
                                <?php
                                $col = $border['colour'] ? $border['colour'] : 'transparent';
                                ?>
                                <div class="border-colour" data-colour="<?php echo esc_attr( $col ); ?>" style="background: <?php echo esc_attr( $col ); ?>;<?php echo $col === 'transparent' ? ' border:1px solid #ccc;' : ''; ?>">
                                    <?php echo $col === 'transparent' ? esc_html__( 'None', 'wp-plate-builder' ) : ''; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_surround === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate surround', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="plate_surround" class="plate-surround">
                                <?php foreach ( $settings['surround_options'] as $sur ) : ?>
                                    <option value="<?php echo esc_attr( $sur['value'] ); ?>"><?php echo esc_html( $sur['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
<?php if ( $show_electric === 'yes' ) : ?>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Electric car plate', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="electric" class="electric-option">
                                <?php foreach ( $settings['electric_options'] as $el ) : ?>
                                    <option value="<?php echo esc_attr( $el['value'] ); ?>"><?php echo esc_html( $el['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Extras', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content extras">
                            <?php if ( ! empty( $settings['extras_options'] ) ) : ?>
                                <?php foreach ( $settings['extras_options'] as $ex ) : ?>
                                    <label><input type="checkbox" class="extra-option" value="<?php echo esc_attr( $ex['value'] ); ?>"> <?php echo esc_html( $ex['label'] ); ?></label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <em><?php esc_html_e( 'No extras configured yet.', 'wp-plate-builder' ); ?></em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="plate-preview">
                    <!-- Wrapper for the plate canvases.  This container stacks
                         the front and rear plates vertically using CSS. -->
                    <div class="preview-plates">
                        <!--
                          Each plate is rendered using an inline SVG.  The
                          JavaScript associated with the builder populates
                          these empty SVGs with a background shape, badge
                          strip and registration text based on the current
                          options.  Using <svg> instead of <div> allows
                          precise control over shapes, gradients and fonts.
                        -->
                        <svg class="plate front-plate" xmlns="http://www.w3.org/2000/svg"></svg>
                        <svg class="plate rear-plate" xmlns="http://www.w3.org/2000/svg"></svg>
                    </div>
                    
                    <div class="design-image-section" style="margin-top:10px; text-align:center;">
                        <img class="design-image" src="" alt="<?php esc_attr_e( 'Design preview', 'wp-plate-builder' ); ?>" style="max-width:100%; height:auto; display:none;" />
                    </div>
<div class="plate-price">
                        <?php esc_html_e( 'Price:', 'wp-plate-builder' ); ?> £<span class="price-value">0.00</span>
                    </div>
                    <button class="plate-buy-button">
                        <?php esc_html_e( 'Buy Now', 'wp-plate-builder' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }