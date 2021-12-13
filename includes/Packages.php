<?php
/**
 * Author: Awais Ahmad
 * Last Modified: 3 February, 2021
 * Company: Cawoy Services
 * Website: www.cawoy.com
 */
namespace Inc;

class Packages{
    protected $post_name="packages";
    protected $prefix="ca_package_";
    public static $webHook_Prefix="ca_package_";

    public function init()
    {
        add_action( 'init', array($this,'registerPostType') );
        add_filter( 'rwmb_meta_boxes', array($this,'addCustomFields') );
        add_filter( 'manage_'.$this->post_name.'_posts_columns', array($this,'setCustomColumnHead'));
        add_action( 'manage_'.$this->post_name.'_posts_custom_column' , array($this,'setColumnData'), 10, 2 );
    }
    public  function getPrefix()
    {
      return $this->prefix;
    }
    public function registerPostType()
    {
        $labels = array(
            'name'               => _x( 'Packages', 'post type general name' ),
            'singular_name'      => _x( 'Product', 'post type singular name' ),
            'add_new'            => _x( 'Add Product', 'book' ),
            'add_new_item'       => __( 'Add New Product' ),
            'edit_item'          => __( 'Edit Product' ),
            'new_item'           => __( 'New Product' ),
            'all_items'          => __( 'All Products' ),
            'view_item'          => __( 'View Product' ),
            'search_items'       => __( 'Search Products' ),
            'not_found'          => __( 'No Product found' ),
            'not_found_in_trash' => __( 'No Products found in the Trash' ),
            'parent_item_colon'  => ',',
            'menu_name'          => 'Products'
          );
          $args = array(
            'labels'        => $labels,
            'description'   => 'Holds our products and product specific data',
            'public'        => true,
            'menu_position' => 5,
            'supports'      => array( 'title', 'editor' ),
            'has_archive'   => true,
          );
           register_post_type( $this->post_name, $args );
    }
    public function addCustomFields($meta_boxes)
    {
        $meta_boxes[] = array(
          'title'      => 'Package Info',
          'post_types' => $this->post_name,

          'fields' => array(
              array(
                  'name'  => 'Allowed Instances',
                  'desc'  => 'Number of machines that can use the same license.',
                  'id'    => $this->prefix."max_instances",
                  'placeholder' => 'Type -1 for unlimited',
                  'type'  => 'number',
                  'min' => '-1'
              ),
              array(
                'name'  => 'Days',
                'desc'  => 'Number of days.',
                'id'    => $this->prefix."days",
                'placeholder' => 'Type -1 for unlimited',
                'type'  => 'number',
                'min' => '-1'
            ),
            array(
                'name'            => 'License Type',
                'id'              => $this->prefix . '_license_type',
                'type'            => 'select',
                'options'         => array(
                    'basic'       => 'Basic',
                    'premium'       => 'Premium',
                ),
                'placeholder'     => 'Select License Type',
              ),
              array(
                'name'  => 'Webhook',
                'desc'  => site_url()."/wp-json/cfLicenseActivation/hook?_method=post&whook=",
                'id'    => $this->prefix."webhook",
                'type'  => 'text',
                'sanitize_callback' => array($this,'webhookValidator'),
            ),
          )
      );
      return $meta_boxes;
    }

    public function webhookValidator($webhook)
    {
      $webhook =  sanitize_title($webhook);
      $tempWebhook = $webhook;
      $id = get_the_ID();
      $count = 1;
      global $wpdb;
      do{
        $meta = $wpdb->get_row(
          $wpdb->prepare("SELECT * FROM $wpdb->postmeta where meta_key = %s AND meta_value=%s", $this->prefix."webhook", $tempWebhook)
        );
        if($meta!=null)
        {
          if($meta->meta_value==$tempWebhook && $id!=$meta->post_id)
          {
            $tempWebhook = $tempWebhook .'-'. $count++;
          }
          else
          {
            break;
          }
        }
        else
        {
          break;
        }
      }
        while(1);
      $webhook = $tempWebhook;
      return $webhook;
    }

    public static function getAllwebHooks()
    {
      global $wpdb;
      $metas = $wpdb->get_results(
        $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta where meta_key = %s", Packages::$webHook_Prefix."webhook")
      );
      return $metas;
    }

    public static function getWebhook($data)
    {
      global $wpdb;
      $metas = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $wpdb->postmeta where meta_key = %s && meta_value= %s", Packages::$webHook_Prefix."webhook",$data)
      );
      return $metas;
    }
    function setCustomColumnHead($data)
    {
      $data['access_days'] = 'Access Duration (Days)';
      $data['max_instances'] = 'Max Machines';
      $data['webhook'] = 'Webhook';
      unset($data['date']);
      return $data;
    }
    function setColumnData($column , $post_id)
    {
      switch($column){
        case 'access_days':
          $access_days = get_post_meta($post_id , $this->prefix."days" , true);
          if($access_days=='-1')
          {
            echo 'Lifetime';
          }
          else
          {
            echo $access_days;
          }
          break;
        case 'max_instances':
          $max_instances = get_post_meta($post_id , $this->prefix."max_instances" , true);
          echo $max_instances;
          break;
        case 'webhook':
          $webook = get_post_meta($post_id , $this->prefix."webhook" , true);
          echo site_url()."/wp-json/cfLicenseActivation/hook?_method=post&whook=".$webook;
          break;
      }
    }
}
