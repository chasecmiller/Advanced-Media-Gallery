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
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueue'], 10, 1);
        add_filter('get_terms', [$this, 'adminTermLimit'], 10, 4);
        add_action('add_meta_boxes', [$this, 'adminMetaBox']);
        add_action('save_post', [$this, 'adminSave']);

        add_action('wp_ajax_amgtag', [$this, 'adminAjax']);
//        add_action( 'wp_ajax_nopriv_amgtag', 'my_search' );

        add_action('admin_menu', function () {
            global $menu;

            unregister_post_type('post');
            unregister_post_type('page');

            $i = array_filter($menu, function ($e) {
                return $e[1] == 'edit_posts' || $e[1] == 'edit_pages';
            });
            if ($i) {
                foreach ($i as $x => $ig) {
                    unset($menu[$x]);
                }
            }
        }, PHP_INT_MAX);
        add_action('init', function () {
            // BizWest settings.
            unregister_post_type('post');
        }, PHP_INT_MAX);

    }

    /**
     * Initialize the administrative area.
     */
    public function adminInit()
    {

        register_setting('pluginPage', 'amg_settings');

        add_settings_section(
            'amg__pluginPage_section',
            __('Amazon Settings', __NAMESPACE__),
            [$this, '_labelLabelSection'],
            'pluginPage'
        );

        add_settings_field(
            's3_migrate',
            __('Migrate Images to S3', __NAMESPACE__),
            [$this, '_fieldMigrateEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_key',
            __('Key', __NAMESPACE__),
            [$this, '_fieldAwsKey'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_secret',
            __('Secret', __NAMESPACE__),
            [$this, '_fieldAwsSecret'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_region',
            __('Region', __NAMESPACE__),
            [$this, '_fieldAwsRegion'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'aws_bucket',
            __('Bucket', __NAMESPACE__),
            [$this, '_fieldAwsBucket'],
            'pluginPage',
            'amg__pluginPage_section'
        );

        add_settings_field(
            'label_enabled',
            __('Label to Tag', __NAMESPACE__),
            [$this, '_fieldAwsLabelEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );
        add_settings_field(
            'face_enabled',
            __('Facial recognition', __NAMESPACE__),
            [$this, '_fieldAwsFaceEnabled'],
            'pluginPage',
            'amg__pluginPage_section'
        );
        add_settings_field(
            'face_collection',
            __('Facial Collection Name', __NAMESPACE__),
            [$this, '_fieldAwsFaceCollection'],
            'pluginPage',
            'amg__pluginPage_section'
        );
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldMigrateEnabled($a, $b = '', $c = '')
    {
        ?>
        <input type='checkbox' name='amg_settings[s3_migrate]' <?php checked($this->options['s3_migrate'], 1); ?>
               value='1'>
        <?php
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldAwsLabelEnabled($a, $b = '', $c = '')
    {


        ?>
        <input type='checkbox' name='amg_settings[label_enabled]' <?php checked($this->options['label_enabled'], 1); ?>
               value='1'>
        <?php
    }


    /**
     * Generate checkbox field.
     */
    public function _fieldAwsFaceEnabled($a, $b = '', $c = '')
    {
        ?>
        <input type='checkbox' name='amg_settings[face_enabled]' <?php checked($this->options['face_enabled'], 1); ?>
               value='1'>
        <?php
    }

    /**
     * Generate checkbox field.
     */
    public function _fieldAwsFaceCollection($a, $b = '', $c = '')
    {
        ?>
        <input type="text" name="amg_settings[face_collection]"
               value="<?php echo esc_attr($this->options['face_collection']); ?>">
        <?php
    }


    /**
     * AWS Bucket name field.
     */
    public function _fieldAwsBucket()
    {
        ?>
        <input type='text' name='amg_settings[aws_bucket]'
               value="<?php echo esc_attr($this->options['aws_bucket']); ?>"/>
        <?php


    }


    function _fieldAwsKey()
    {


        ?>
        <input type='text' name='amg_settings[aws_key]' value='<?php echo $this->options['aws_key']; ?>'>
        <?php

    }


    function _fieldAwsSecret()
    {
        ?>
        <input type='text' name='amg_settings[aws_secret]' value='<?php echo $this->options['aws_secret']; ?>'>
        <?php

    }


    function _fieldAwsRegion()
    {
        ?>
        <input type='text' name='amg_settings[aws_region]' value='<?php echo $this->options['aws_region']; ?>'>
        <?php
    }


    /**
     * Header for label area.
     */
    public function _labelLabelSection()
    {
        _e('Settings from Amazon Rekognition.', __NAMESPACE__);
    }

    /**
     * Admin menu generation.
     */
    public function adminMenu()
    {
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
    public function adminPageOptions()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Advanced Media Gallery', __NAMESPACE__); ?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('pluginPage');
                do_settings_sections('pluginPage');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Enqueue scripts
     * @param $hook
     */
    public function adminEnqueue($hook)
    {
        global $post;

//        wp_register_style('jquery-ui-styles', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
        wp_register_style('amg', plugins_url('css/plugin.css', __FILE__), [], '1.0.0');
//        wp_register_script('facedetection', plugins_url('js/jquery.facedetection.min.js', __FILE__), false, '1.0.0');

        wp_register_script('tracking', plugins_url('js/tracking-min.js', __FILE__), [], '1.0.0');
        wp_register_script('tracking-face', plugins_url('js/face-min.js', __FILE__), ['tracking'], '1.0.0');
        wp_register_script('amg', plugins_url('js/plugin.js', __FILE__), ['tracking-face','tracking'], '1.0.0');
        if (
            $hook != 'upload.php'
            &&
            $hook != 'post.php'
        ) {
            return;
        }
        /*
        if (!$post || !$post instanceof \WP_Post) {
            return;
        }
        if (get_post_type($post) != 'attachment') {
            return;
        }
        if (
            strpos($post->post_mime_type, 'image/') !== 0
            &&
            !in_array($post->post_mime_type, [

            ])
        ) {
            return;
        }

        $meta = preg_grep('#^a:.*"Width";.*?"Height";.*?"Left";.*?"Top";.*}$#', array_map(function ($e) {
            return $e[0];
        }, array_filter(get_post_meta($post->ID), function ($e) {
            return sizeof($e) == 1;
        })));

        foreach ($meta as $k => &$e) {
//        array_walk($meta, function (&$e) {
            $e = unserialize($e);
            $e['Name'] = $k;
            $term = get_term_by('slug', $k, 'person');
            if ($term->parent > 0) {
                while ($term->parent > 0) {
                    $term = get_term_by('id', $term->parent, 'person');
                }
                $e['Name'] = $term->name;
            }
        };
        */
        $meta = [];

        wp_localize_script('amg', 'amg', [
            'faces' => $meta,
            'url' => admin_url('admin-ajax.php?action=amgtag')
        ]);

        wp_enqueue_style('amg');
        wp_enqueue_script('amg');

        add_thickbox();
    }

    public function adminTermLimit($terms, $taxonomy, $args, $query)
    {
        if (!function_exists('get_current_screen')) {
            return $terms;
        }
        $screen = get_current_screen();

        if (
            (is_array($taxonomy) && !in_array('person', $taxonomy))
            ||
            (is_string($taxonomy) && $taxonomy != 'person')
        ) {
            return $terms;
        }

        if ($screen->base != 'edit-tags') {
            return $terms;
        }

        // We should end up adding our count here first.
        // Not yet needed since we do not keep an accurate count.

        $terms = array_filter($terms, function ($e) {
            return $e->parent == 0;
        });

        return $terms;
    }

    /**
     * Create meta boxes
     */
    public function adminMetaBox()
    {
        global $post, $wp_meta_boxes;
        $taxonomies = get_taxonomies([], 'objects');
        if (!array_key_exists('person', $taxonomies)) {
            return;
        }


        if (!$post || !$post instanceof \WP_Post) {
            return;
        }
        if (get_post_type($post) != 'attachment') {
            return;
        }

        if (!wp_attachment_is_image($post->ID)) {
            return;
        }

        add_meta_box(
            'amg',
            __('Extended', __NAMESPACE__),
            [$this, 'adminMetaExtended'],
            'attachment',
            'side',
            'core'
        );

        // Re-arrange.
        unset($wp_meta_boxes['attachment']['side']['core']['persondiv']);
        if (array_key_exists('tagsdiv-post_tag', $wp_meta_boxes['attachment']['side']['core'])) {
            $temp = $wp_meta_boxes['attachment']['side']['core']['tagsdiv-post_tag'];
            unset($wp_meta_boxes['attachment']['side']['core']['tagsdiv-post_tag']);
            $wp_meta_boxes['attachment']['side']['core']['tagsdiv-post_tag'] = $temp;
        }
    }

    public function adminMetaExtended($post)
    {
        global $wpdb;

        $terms = wp_get_object_terms(get_the_ID(), 'person');

        printf('<ul class="tag-list %s">',
            sizeof($terms) > 0 ? '' : 'hidden'
        );

        printf('<li class="prefix">%s</li>', __('With', __NAMESPACE__));

        $x = sizeof($terms);

        // Rebuild this.  We group unknown.
        array_walk($terms, function (&$t) {
            $id = $t->slug;
            while ($t->parent > 0) {
                $t = get_term($t->parent, 'person');
            }
            $temp = substr_count($t->name, '-');
            if (
                substr_count($t->name, '-') == 4
                &&
                strtolower($t->name) === $t->name
            ) {
                $t->name = __('Unknown', __NAMESPACE__);
                $t->score = PHP_INT_MAX;
            } else {
                $t->score = 0;
            }
            $t->real = $id;
        });
        usort($terms, function ($a, $b) {
            if ($a->score == $b->score && $a->score == PHP_INT_MAX) {
                return false;
            }
            return strcmp($a->score, $b->score);
        });
        // Known
        $known = array_filter($terms, function ($e) {
            return !$e->score;
        });
        $unknown = array_filter($terms, function ($e) {
            return $e->score > 0;
        });

        foreach ($known as $term) {
            printf('<li><a href="" data-id="%s">%s</a></li>', esc_attr($term->real), $term->name);
        }

        $x = sizeof($unknown);

        if ($x == 1) {
            printf('<li class="toggle-menu closed"><a href="#">%s %d %s</a>',//;//</li>',
                __('and', __NAMESPACE__),
                $x,
                __('unknown person', __NAMESPACE__)
            );
            echo '<ul>';
            foreach ($unknown as $term) {
                printf('<li><a href="#" data-id="%s">%s</a></li>', esc_attr($term->real), $term->name);
            }
            echo '</ul></li>';
        } else if ($x && sizeof($known) > 0) {
            printf('<li class="toggle-menu closed"><a href="#">%s %d %s</a>',//;//</li>',
                __('and', __NAMESPACE__),
                $x,
                __('unknown people', __NAMESPACE__)
            );
            echo '<ul>';
            foreach ($unknown as $term) {
                printf('<li><a href="#" data-id="%s">%s</a></li>', esc_attr($term->real), $term->name);
            }
            echo '</ul></li>';
        }
        ?>
        <div id="edit_person" style="display:none;">
            <p>
                <?php _e('Update this person\'s name', __NAMESPACE__); ?>
                <input type="text" class="widefat" name="edit_person" value=""/>
            </p>
            <a href="#" class="button button-secondary button-large"
               id="cancel-edit_person"><?php _e('Cancel', __NAMESPACE__); ?></a>
            <a href="#" class="button button-primary button-large"
               id="save-edit_person"><?php _e('Update', __NAMESPACE__); ?></a>
        </div>
        <?php
        return;
        // Unknown
        echo '<pre>';
        echo $known . '-' . $unknown;
        print_r($terms);
        echo '</pre>';
        return;
        exit;
        foreach ($terms as $t) {
            $x--;

            printf('<li><a href="#" data-id="%s">%s</a>%s</li>',
                esc_attr($id),
//            var_export($t,true)
                $t->name,
                $x > 0 ? ',' : ''
            );
        }
        echo '</ul>';


        echo '<br />';
        printf('<a href="#" class="button button-small">%s</a>',
            __('Tag Photo', __NAMESPACE__)
        );
        printf('<a href="#" class="button button-small">%s</a>',
            __('Add Location', __NAMESPACE__)
        );
//        echo '<br />';
//        .button.button-small
        return;
        $taxonomies = get_taxonomies([], 'objects');
        if (!array_key_exists('person', $taxonomies)) {
            return;
        }
        $terms = wp_get_object_terms(get_the_ID(), 'person');


        foreach ($terms as $t) {
            $path = [];
            while ($t->parent > 0) {
                $path[] = $t->term_id;
                $t = get_term($t->parent, 'person');
            }
            $path[] = $t->term_id;
            $path = array_reverse($path);
            $path = implode(',', $path);
            print_r($path);
        }

        // Real ids are masked by their parent, if present.
        print_r($terms);
        echo 'Add New Button';

        ?>
        This is the next version of the people list.
        <br/>
        It is not yet complete.
        <?php
    }


    public function adminSave($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        return;
//        echo 'a';
//        exit;
        if (!isset($_POST['a_nonce']) || !wp_verify_nonce($_POST['a_nonce'], '_a_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['a_a']))
            update_post_meta($post_id, 'a_a', esc_attr($_POST['a_a']));
        else
            update_post_meta($post_id, 'a_a', null);
    }

    /**
     * Handle ajax
     */
    public function adminAjax()
    {
        if (
            array_key_exists('method', $_REQUEST)
            &&
            method_exists($this, 'adminAjax' . ucwords(strtolower($_REQUEST['method'])))
        ) {
            $sMethod = 'adminAjax' . ucwords(strtolower($_REQUEST['method']));
            $response = call_user_func([$this, $sMethod]);
            if ($response) {
                $response = json_encode($response);
                echo $response;
                exit;
            }
        } else if (
            array_key_exists('term', $_REQUEST)
            &&
            is_string($_REQUEST['term'])
            &&
            strlen($_REQUEST['term']) > 3
        ) {
            $term = $_REQUEST['term'];
            // Get a list
            $terms = get_terms([
                'taxonomy' => 'person',
                'hide_empty' => false
            ]);
//            echo $term;
            $terms = array_filter($terms, function ($e) use ($term) {
                if (strpos($e->name, $term) !== false) {
                    return true;
                }
                return similar_text($term, $e->name) > 10;
            });
            array_walk($terms, function (&$e) {
                $t = new \stdClass();
                $t->label = $e->name;
                $t->value = $e->term_id;
                $e = $t;
            });

            $response = json_encode($terms);
            echo $response;
            exit;
        } else if (
            array_key_exists('k', $_REQUEST) && array_key_exists('v', $_REQUEST)
            &&
            current_user_can('edit_posts')
            &&
            is_string($_REQUEST['k'])
            &&
            is_string($_REQUEST['v'])
        ) {
            /**
             * Most of the time, there will be modifications made
             * If we have a new insertion, we insert it as a parent.
             * Those should be rare.
             * If we have a modification, we move it to a parent under that name.
             * IF we only have one parent, then we can rename it.
             */

            echo 'a';
            exit;

        } else {

        }
        print_r($_REQUEST);
        exit;
    }

    private function adminAjaxProcess()
    {
        if (!array_key_exists('id', $_REQUEST) || !is_numeric($_REQUEST['id'])) {
            return false;
        }
        $post = get_post($_REQUEST['id']);
        if (!$post || $post->post_type != 'attachment' || !wp_attachment_is_image($post->ID)) {
            return false;
        }

        // Check lock.
        if (wp_check_post_lock($post->ID)) {
            return ['status' => 'success','message' => __('already being edited.', __NAMESPACE__)];
        }

        // Set lock.
        wp_set_post_lock($post->ID);

        // Generate post tags.
        $this->_generatePostTag($post);

        return var_export($_REQUEST, true);
    }
}
