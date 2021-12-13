<?php
/**
 * Author: Awais Ahmad
 * Last Modified: 3 February, 2021
 * Company: Cawoy Services
 * Website: www.cawoy.com
 */
namespace Inc;
use Inc\Packages;

class RestAPI{
    protected $post_name="license";
    protected $prefix="ca_license";

    public function init()
    {
        add_action("rest_api_init",array($this,'cfAPIMethod'));
    }
    public function cfAPIMethod()
    {
        register_rest_route(
            "cfLicenseActivation",
            'hook',
            array(
            "methods"=>"POST",
            "callback"=> array($this,'activateLicenseCF'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(
            "activate_license",
            'post',
            array(
            "methods"=>"POST",
            "callback"=> array($this,'activateLicense'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(
            "check_license_status",
            'post',
            array(
            "methods"=>"POST",
            "callback"=> array($this,'checkLicenseStatus'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(
            "deactivate_license",
            'post',
            array(
            "methods"=>"POST",
            "callback"=> array($this,'deActivateLicense'),
            'permission_callback' => '__return_true',
        ));

    }
    public function activateLicenseCF($request)
    {
        $webhookID = isset($_GET['whook']) ? $_GET['whook'] : "";
        $data = Packages :: getWebhook($webhookID);
        if(!isset($data))
        {
            die();
        }
        $data=$data[0];
        $tData["first_name"] = $request->get_param('first_name');
        $tData["last_name"] = $request->get_param('last_name');
        $tData["name"] = $tData["first_name"]." ".$tData["last_name"];
        $tData["email"] = $request->get_param('email');
        $tData["phone"] = $request->get_param('phone');
        if(isset($tData))
        {
            $contact['first_name'] = $tData['first_name'];
            $contact['last_name'] = $tData['last_name'];
            $contact['email'] = isset($tData['email']) ? $tData['email'] : " ";
            $contact['phone'] = $tData['phone'];
            $contact['name'] = $tData['name'];
            $licenseOBJ = new LicenseManager();
            if($licenseOBJ->insert($data->post_id,$contact))
            {
            echo json_encode(array("OK"));
            die();
            }
            echo json_encode(array("Error!"));
            die();
        }
    }
    public function activateLicense($request)
    {
        //$email = $request->get_param('email');
        $hash_key = $request->get_param('secret_key');
        if(isset($hash_key) && $hash_key=="ec22d5a0ed7ea51f3d0a974e6ffcd3a6")
        {

        }
        else
        {
            return [
                "error"=> "Unauthorized access denied"
            ];
        }
        $license = $request->get_param('license_key');
        $app_name = $request->get_param('app_name');
        $machine_id = $request->get_param('machine_id');
        global $wpdb;
        $table = $wpdb->prefix."license_machines";
        $response=[];

        $args = array(
            'meta_query' => array(
                array(
                    'key' => $this->prefix."_key",
                    'value' => $license
                )
            ),
            'post_type' => $this->post_name,
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        if(!is_array($posts) || count($posts)<=0)
        {

            return [
                "error"=>"Wrong License Key",
                "message"=>"License Key is not found!",
                "status"=>404
            ];
        }
        else
        {
            foreach($posts as $post)
            {
                $max_instances = get_post_meta( $post->ID,$this->prefix."_max_instances",true);
                if(empty($max_instances))
                    $max_instances=0;
                $start_date = get_post_meta( $post->ID,$this->prefix."_start_date",true);
                $expiry_date = get_post_meta( $post->ID,$this->prefix."_expiry_date",true);
                $license_type = get_post_meta( $post->ID,$this->prefix."_license_type",true);
                $activated_machines = $wpdb->get_var ( "SELECT COUNT(*) FROM $table WHERE `license_id` = $post->ID AND `machine_status`=1");
                $machine_code = $wpdb->get_var ( "SELECT machine_id FROM $table WHERE `machine_id` = '$machine_id'");
                if(!isset($machine_code))
                {
                    if($activated_machines>=$max_instances && $max_instances!=-1)
                {
                    return [
                        "error"=>"You have reached maximum numbers of machines."
                    ];
                }
                }
                if(isset($expiry_date) && $expiry_date!=-1)
                {
                    if(date('Y-m-d')>date("Y-m-d", strtotime($expiry_date)))
                    {
                        update_post_meta( $post_id = $post->ID, $key = $this->prefix."_license_status", $value = 'expired' );
                        return [
                            "error"=>"Key Expired",
                            "message"=>"License Key has been expired!",
                            "status"=>401
                        ];
                    }
                }
                $wpdb->insert(
                    $table,
                    array(
                        'app_name' => $app_name,
                        'machine_id' =>  $machine_id,
                        'machine_status' => 1,
                        'license_id' => $post->ID
                    )
                 );
                update_post_meta( $post_id = $post->ID, $key = $this->prefix."_license_status", $value = 'active' );
                $response["email"] =  get_post_meta( $post->ID,$this->prefix."_customer_email",true);
                $response["success"] = "Key has been activated";
                $response["machine_id"] = $machine_id;
                $response["activated_machines"] = $activated_machines+1;
                $response["allowed_machines"] = $max_instances;
                $response["created_at"] = $start_date;
                $response["expire_at"] = $expiry_date;
                $response['license_type'] = $license_type;
                $days=0;
                if(isset($start_date) && isset($expiry_date))
                {
                    $date1 = strtotime($expiry_date);
                    $date2 = time();
                    $days= floor(($date2-$date1)/(60*60*24));
                }
                if($days<0)
                {
                    $days = $days * -1 ;
                }
                $response["days_left"]=  $days;

                return $response;
            }
        }
        return [];
    }

    public function checkLicenseStatus($request)
    {
        $date1 = null; $date2=null;
        if(date('Y-m-d')>date("Y-m-d", strtotime($expiry_date)))
        {

        }
        //$email = $request->get_param('email');
        $hash_key = $request->get_param('secret_key');
        if(isset($hash_key) && $hash_key=="ec22d5a0ed7ea51f3d0a974e6ffcd3a6")
        {

        }
        else
        {
            return [
                "error"=> "Unauthorized access denied"
            ];
        }
        $license = $request->get_param('license_key');
        $machine_id = $request->get_param('machine_id');
        global $wpdb;
        $table = $wpdb->prefix."license_machines";
        $response=[];

        $args = array(
            'meta_query' => array(
                array(
                    'key' => $this->prefix."_key",
                    'value' => $license
                )
            ),
            'post_type' => $this->post_name,
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        if(!is_array($posts) || count($posts)<=0)
        {

            return [
                "error"=>"Wrong License Key",
                "message"=>"License Key is not found!",
                "status"=>404
            ];
        }
        else
        {
            foreach($posts as $post)
            {
                $max_instances = get_post_meta( $post->ID,$this->prefix."_max_instances",true);
                if(empty($max_instances))
                    $max_instances=0;
                $start_date = get_post_meta( $post->ID,$this->prefix."_start_date",true);
                $expiry_date = get_post_meta( $post->ID,$this->prefix."_expiry_date",true);
                $license_type = get_post_meta( $post->ID,$this->prefix."_license_type",true);
                $activated_machines = $wpdb->get_var ( "SELECT COUNT(*) FROM $table WHERE `license_id` = $post->ID AND `machine_status`=1");
                $machine_code = $wpdb->get_var ( "SELECT machine_id FROM $table WHERE `machine_id` = $machine_id");

                if(isset($expiry_date) && $expiry_date!=-1)
                {
                    if(date('Y-m-d')>date("Y-m-d", strtotime($expiry_date)))
                    {
                        update_post_meta( $post_id = $post->ID, $key = $this->prefix."_license_status", $value = 'expired' );
                        return [
                            "error"=>"Key Expired",
                            "message"=>"License Key has been expired!",
                            "status"=>401
                        ];
                    }
                }
                update_post_meta( $post_id = $post->ID, $key = $this->prefix."_license_status", $value = 'active' );
                $response["email"] =  get_post_meta( $post->ID,$this->prefix."_customer_email",true);
                $response["success"] = "Key has been activated";
                $response["machine_id"] = $machine_id;
                $response["activated_machines"] = $activated_machines;
                $response["allowed_machines"] = $max_instances;
                $response["created_at"] = $start_date;
                $response["expire_at"] = $expiry_date;
                $response['license_type'] = $license_type;
                $days=0;
                if(isset($start_date) && isset($expiry_date))
                {
                    $date1 = strtotime($expiry_date);
                    $date2 = time();
                    $days= floor(($date2-$date1)/(60*60*24));
                }
                if($days<0)
                {
                    $days = $days * -1 ;
                }
                $response["days_left"]=  $days;

                return $response;
            }
        }
        return [];
    }


    public function deActivateLicense($request)
    {
        //$email = $request->get_param('email');
        $license = $request->get_param('license_key');
        $machine_id = $request->get_param('machine_id');
        global $wpdb;
        $table = $wpdb->prefix."license_machines";
        $args = array(
            'meta_query' => array(
                array(
                    'key' => $this->prefix."_key",
                    'value' => $license
                )
            ),
            'post_type' => $this->post_name,
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        $machine_code = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE `machine_id` = '$machine_id' AND `machine_status`=1");
        if(!is_array($posts) || count($posts)<=0)
        {

            return [
                "error"=>"Wrong License Key!",
                "message"=>"License Key could not be found!",
                "status"=>404
            ];
        }
        else if(!isset($machine_code) && $machine_code<=0)
        {
            return [
                "error"=>"Wrong Machine ID",
                "message"=>"Machine ID could not be found!",
                "status"=>404
            ];
        }
        else
        {
            $machine_code = $wpdb->delete($table, array( "machine_id" => $machine_id, "machine_status" => "1"));
        }
        return [
            "success"=>"Success",
            "message"=>"Machine ID has been deactivated!",
            "status"=>200
        ];
    }
}
