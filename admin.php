<?php

namespace Crumbls\Plugins\Media;

defined('ABSPATH') or exit(1);

class Admin extends Plugin
{
    public function __construct()
    {
        parent::__construct();
        add_action('admin_init', [$this, 'adminInit']);
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    /**
     * Initialize the administrative area.
     */
    public function adminInit() {
        register_setting( 'pluginPage', 'amg_settings' );

        add_settings_section(
            'amg__pluginPage_section',
            __('Amazon Settings', __NAMESPACE__ ),
            [$this, '_labelLabelSection'],
            'pluginPage'
        );

        add_settings_field(
            's3_migrate',
            __('Migrate Images to S3', __NAMESPACE__ ),
            [$this, '_fieldMigrateEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_key',
            __( 'Key', __NAMESPACE__ ),
            [$this, '_fieldAwsKey'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_secret',
            __( 'Secret', __NAMESPACE__ ),
            [$this, '_fieldAwsSecret'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_region',
            __( 'Region', __NAMESPACE__ ),
            [$this, '_fieldAwsRegion'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_bucket',
            __( 'Bucket', __NAMESPACE__ ),
            [$this, '_fieldAwsBucket'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'label_enabled',
            __('Label to Tag', __NAMESPACE__ ),
            [$this, '_fieldAwsLabelEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );
        add_settings_field(
            'face_enabled',
            __('Facial recognition', __NAMESPACE__ ),
            [$this, '_fieldAwsFaceEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );
        add_settings_field(
            'face_collection',
            __('Facial Collection Name', __NAMESPACE__ ),
            [$this, '_fieldAwsFaceCollection'],
            'pluginPage',
            'amg__pluginPage_section'
        );
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldMigrateEnabled($a, $b = '', $c = '') {
        ?>
        <input type='checkbox' name='amg_settings[s3_migrate]' <?php checked( $this->options['s3_migrate'], 1 ); ?> value='1'>
        <?php
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldAwsLabelEnabled($a, $b = '', $c = '') {


        ?>
        <input type='checkbox' name='amg_settings[label_enabled]' <?php checked( $this->options['label_enabled'], 1 ); ?> value='1'>
        <?php
    }


    /**
     * Generate checkbox field.
     */
    public function _fieldAwsFaceEnabled($a, $b = '', $c = '') {
        ?>
        <input type='checkbox' name='amg_settings[face_enabled]' <?php checked( $this->options['face_enabled'], 1 ); ?> value='1'>
        <?php
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldAwsFaceCollection($a, $b = '', $c = '') {
        ?>
        <input type="text" name="amg_settings[face_collection]" value="<?php echo esc_attr($this->options['face_collection']); ?>" >
        <?php
    }



    /**
     * AWS Bucket name field.
     */
    public function _fieldAwsBucket(  ) {
        ?>
        <input type='text' name='amg_settings[aws_bucket]' value="<?php echo esc_attr($this->options['aws_bucket']); ?>" />
        <?php


    }




    function _fieldAwsKey(  ) {

        
        ?>
        <input type='text' name='amg_settings[aws_key]' value='<?php echo $this->options['aws_key']; ?>'>
        <?php

    }


    function _fieldAwsSecret(  ) {
        ?>
        <input type='text' name='amg_settings[aws_secret]' value='<?php echo $this->options['aws_secret']; ?>'>
        <?php

    }


    function _fieldAwsRegion(  ) {
        ?>
        <input type='text' name='amg_settings[aws_region]' value='<?php echo $this->options['aws_region']; ?>'>
        <?php
    }


    /**
     * Header for label area.
     */
    public function _labelLabelSection(  ) {
        _e('Settings from Amazon Rekognition.', __NAMESPACE__);
    }

    /**
     * Admin menu generation.
     */
    public function adminMenu() {
        add_options_page(
            __('Advanced Media Gallery', __NAMESPACE__),
            __('Advanced Media Gallery', __NAMESPACE__),
            'manage_options',
            'advanced-media-gallery',
            [$this, 'adminPageOptions']
        );
    }

    /**
     * Admin page: Options
     */
    public function adminPageOptions() {
        ?>
        <div class="wrap">
            <h1><?php _e('Advanced Media Gallery', __NAMESPACE__); ?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'pluginPage' );
                do_settings_sections( 'pluginPage' );
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

}
