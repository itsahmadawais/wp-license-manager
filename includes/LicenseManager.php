<?php
namespace Inc;
use Setup;
use Inc\Packages;
class LicenseManager{

    protected $post_name="license";
    protected $prefix="ca_license";

    public function init()
    {
        add_action( 'init', array($this,'registerPostType') );
        add_filter( 'rwmb_meta_boxes', array($this,'addCustomFields') );
        add_filter( 'manage_'.$this->post_name.'_posts_columns', array($this,'setCustomColumnHead'));
        add_action( 'manage_'.$this->post_name.'_posts_custom_column' , array($this,'setColumnData'), 10, 2 );
    }

    public function registerPostType()
    {
        $labels = array(
            'name'               => _x( 'License', 'post type general name' ),
            'singular_name'      => _x( 'License', 'post type singular name' ),
            'add_new'            => _x( 'Add New', 'book' ),
            'add_new_item'       => __( 'Add New License' ),
            'edit_item'          => __( 'Edit License' ),
            'new_item'           => __( 'New license' ),
            'all_items'          => __( 'All license' ),
            'view_item'          => __( 'View license' ),
            'search_items'       => __( 'Search license' ),
            'not_found'          => __( 'No license found' ),
            'not_found_in_trash' => __( 'No license found in the Trash' ),
            'parent_item_colon'  => ',',
            'menu_name'          => 'License'
          );
          $args = array(
            'labels'        => $labels,
            'description'   => 'Holds our products and product specific data',
            'public'        => true,
            'menu_position' => 5,
            'supports'      => array( 'title', 'editor','taxonomoies' ),
            'has_archive'   => true,
          );
           register_post_type( $this->post_name, $args );
    }

    public function addCustomFields($meta_boxes)
    {
        $meta_boxes[] = array(
          'title'      => 'License Key',
          'post_types' => $this->post_name,

          'fields' => array(
              array(
                  'name'  => 'License Key',
                  'id'    => $this->prefix."_key",
                  'placeholder' => 'License Key',
                  'type'  => 'text',
                  'std' => $this->generateLicense(),
                  'sanitize_callback' => array($this,'licenseExist')
              ),
          )
        );
        $meta_boxes[] = array(
          'title'      => 'Customer Details',
          'post_types' => $this->post_name,

          'fields' => array(
              array(
                  'name'  => 'First Name',
                  'id'    => $this->prefix."_customer_first_name",
                  'placeholder' => 'First Name',
                  'type'  => 'text'
              ),
              array(
                  'name'  => 'Last Name',
                  'id'    => $this->prefix."_customer_last_name",
                  'placeholder' => 'Last Name',
                  'type'  => 'text'
              ),
              array(
                'name'  => 'Email',
                'id'    => $this->prefix."_customer_email",
                'placeholder' => 'example@email.com',
                'type'  => 'email'
            ),
            array(
                'name'  => 'Phone',
                'id'    => $this->prefix."_customer_phone",
                'placeholder' => '92_xxxxxxxxxxxx',
                'type'  => 'tel'
            ),
          )
        );
        $meta_boxes[] = array(
          'title'      => 'Package Info',
          'post_types' => $this->post_name,

          'fields' => array(
              array(
                'name'            => 'Product',
                'id'              => $this->prefix . 'package_type',
                'type'            => 'post',
                'post_type'       => 'packages',
                'placeholder'     => 'Select Pacakge',
              ),
              array(
                  'name'  => 'Start Date',
                  'id'    => $this->prefix."_start_date",
                  'placeholder' => "Select Start Date",
                  'type'  => 'date'
              ),
              array(
                'name'  => 'Expiry Date',
                'id'    => $this->prefix."_expiry_date",
                'placeholder' => "Select Expiry Date",
                'type'  => 'date'
            ),
            array(
              'name'            => 'License Status',
              'id'              => $this->prefix . '_license_status',
              'type'            => 'select',
              'options'         => array(
                  'active'       => 'Active',
                  'pending'       => 'Pending',
                  'expired'       => 'Expired',
              ),
              'placeholder'     => 'Select Status',
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
                  'name'  => 'Allowed Instances',
                  'desc'  => 'Number of machines that can use the same license.',
                  'id'    => $this->prefix."_max_instances",
                  'placeholder' => 'Type -1 for unlimited',
                  'type'  => 'number',
                  'min' => '-1'
              ),
              array(
                'name'  => 'Activated Mahcines',
                'desc'  => $this->getAcivatedCount(),
                'id'    => $this->prefix."_activated_machines",
                'type'  => 'heading'
              ),
          )
      );
      return $meta_boxes;
    }

    public function licenseExist($temp_key)
    {
      do{
        global $wpdb;
        $table = $wpdb->prefix."license_machines";
        $posts = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = '".$this->prefix."_key' AND  meta_value = '$temp_key' LIMIT 1", ARRAY_A);
        if($posts==null)
        {
          return $temp_key;
        }
      }
      while(true);
    }
    public static function generateLicense($suffix = null) {
        // Default tokens contain no "ambiguous" characters: 1,i,0,o
        if(isset($suffix)){
            // Fewer segments if appending suffix
            $num_segments = 3;
            $segment_chars = 6;
        }else{
            $num_segments = 4;
            $segment_chars = 5;
        }
        $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $license_string = '';
        // Build Default License String
        for ($i = 0; $i < $num_segments; $i++) {
            $segment = '';
            for ($j = 0; $j < $segment_chars; $j++) {
                $segment .= $tokens[rand(0, strlen($tokens)-1)];
            }
            $license_string .= $segment;
            if ($i < ($num_segments - 1)) {
                $license_string .= '-';
            }
        }
        // If provided, convert Suffix
        if(isset($suffix)){
            if(is_numeric($suffix)) {   // Userid provided
                $license_string .= '-'.strtoupper(base_convert($suffix,10,36));
            }else{
                $long = sprintf("%u\n", ip2long($suffix),true);
                if($suffix === long2ip($long) ) {
                    $license_string .= '-'.strtoupper(base_convert($long,10,36));
                }else{
                    $license_string .= '-'.strtoupper(str_ireplace(' ','-',$suffix));
                }
            }
        }
        return $license_string;
    }
    public static function getAcivatedCount()
    {
        $license = isset($_GET['post']) ? $_GET['post'] : 0;
        global $wpdb;
        $table = $wpdb->prefix."license_machines";
        $counter = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE `license_id` = $license AND `machine_status`=1");
        return $counter." Machines Activated.";
    }
    public  function expireLicense()
    {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => $this->prefix."_key"
                )
            ),
            'post_type' => $this->post_name,
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        foreach($posts as $post)
        {
            $start_date = get_post_meta( $post->ID,$this->prefix."_start_date",true);
            $expiry_date = get_post_meta( $post->ID,$this->prefix."_expiry_date",true);
            if(isset($expiry_date) && $expiry_date!=-1)
            {
                if(date('Y-m-d')>date("Y-m-d", strtotime($expiry_date)))
                {
                    update_post_meta($post->ID, $this->prefix."_license_status", $value = 'expired' );
                    $license_key="";
                    $contact['first_name'] = get_post_meta($post->ID,$this->prefix."_customer_first_name",true);
                    $contact['email'] = get_post_meta($post->ID, $this->prefix."_customer_email",true);
                    $product_id = get_post_meta($post->ID, $this->prefix . 'package_type',true);
                    $product_name="";
                    if($product_id!=null && isset($product_id))
                    {
                      $product_name = get_the_title($product_id);
                    }
                    $body = " Hi ". $contact['first_name'].', <br/>'.
                    "Product: ".$product_name."</br>".
                    "Your license key has been expired.<br/>".
                    "License key : ".$license_key."<br/>".
                    $dateText;
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail( $contact['email'], 'License Key', $body, $headers );
                }
            }
        }
    }

    public function insert($post_id,$contact)
    {
        $package = new Packages();
        $days = get_post_meta($post_id,$package->getPrefix().'days',true);
        $max_instances = get_post_meta($post_id,$package->getPrefix().'max_instances',true);
        $license_type = get_post_meta($post_id,$package->getPrefix().'_license_type',true);
        $start_date = " ";
        $expiry_date = " ";
        if($days==="-1")
        {
          $start_date="-1";
          $expiry_date="-1";
        }
        else
        {
          $current_date =  date("Y-m-d");
          $start_date=$current_date;
          $expiry_date= date('Y-m-d', strtotime($Date. ' + '.$days.' days'));
        }
        $license_key = $this->generateLicense();
        $license_key = $this->licenseExist($license_key);
        $my_post = array(
            'post_title'    => $contact['name']." - ". $contact['email'],
            'post_content'  => ' ',
            'post_status'   => 'publish',
            'post_type'     =>  $this->post_name,
            'meta_input'   => array(
            $this->prefix."_customer_email" => $contact['email'],
            $this->prefix."_customer_first_name" => $contact['first_name'],
            $this->prefix."_customer_last_name" => $contact['last_name'],
            $this->prefix."_customer_phone" => $contact['phone'],


            $this->prefix."_max_instances" => $max_instances,
            $this->prefix . 'package_type' => $post_id,
            $this->prefix . '_license_type' => $license_type,

            $this->prefix."_start_date" => $start_date,
            $this->prefix."_expiry_date" => $expiry_date,

            $this->prefix."_key" => $license_key,

            $this->prefix . '_license_status' => "active"
          ),
        );
        wp_insert_post( $my_post );

        $dateText =" ";
        if($expiry_date==-1)
        {
          $dateText="Lifetime access!";
        }
        else
        {
          $dateText="Start Date : ".$start_date."<br>".
          "Expiry Date : ".$expiry_date."<br>";
        }
        /**
         * Product Name Should be written in the email
         */
        $product_name = get_the_title($post_id);
        $body = " Hi ". $contact['first_name'].', <br>'.
        "Here is your license key for the product. <br>".
        "Product :".$product_name."<br>".
        "License key : ".$license_key."<br>".
        $dateText;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail( $contact['email'], 'Your license key', $body, $headers );
        return true;
    }
    function setCustomColumnHead($data)
    {
      $data['customer'] = 'Customer Name';
      $data['email'] = 'Email';
      $data['current_status'] = 'Current Status';
      $data['start_date'] = 'Start Date';
      $data['expiry_date'] = 'Expiry Date';
      unset($data['date']);
      return $data;
    }
    function setColumnData($column , $post_id)
    {
      switch($column){
        case 'customer':
          $f_name = get_post_meta($post_id , $this->prefix."_customer_first_name" , true);
          $l_name = get_post_meta($post_id , $this->prefix."_customer_last_name" , true);
          $name = $f_name." ".$l_name;
          echo $name;
          break;
        case 'email':
          $email = get_post_meta($post_id , $this->prefix."_customer_email" , true);
          if(isset($email))
          {
            echo $email;
          }
          break;
        case 'current_status':
          $status = get_post_meta($post_id , $this->prefix . '_license_status' , true);
          echo ucwords($status);
          break;
        case 'start_date';
          $start_date = get_post_meta($post_id , $this->prefix."_start_date" , true);
          echo $start_date;
          break;
        case 'expiry_date':
          $expiry_date = get_post_meta($post_id , $this->prefix."_expiry_date" , true);
          if($expiry_date=='-1')
          {
            echo "Lifetime";
          }
          else
          {
            echo $expiry_date;
          }
          break;
      }
    }
}
