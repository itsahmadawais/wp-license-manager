<?php
/**
 * Author: Awais Ahmad
 * Last Modified: 3 February, 2021
 * Company: Cawoy Services
 * Website: www.cawoy.com
 */
namespace Inc;

use Inc\LicenseManager;

class Setup{
    public static function CronScheduler()
    {
        add_action( 'my_hourly_event', Setup::expirelicense());
    }
    public static function activate()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'license_machines';
    
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            license_id mediumint(9) NOT NULL,
            app_name varchar(100),
            machine_id varchar(100),
            machine_status int,
            PRIMARY KEY  (id),
            UNIQUE KEY (machine_id),
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        Setup:: CronScheduler();
        wp_schedule_event( time(), 'daily', 'my_hourly_event' );
    }
    public static function expirelicense()
    {
        $license_manager_object = new LicenseManager();
        $license_manager_object->expireLicense();
    }
    static public function clearCronJob()
    {
        wp_clear_scheduled_hook( 'my_hourly_event' );
    }
}