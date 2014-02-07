<?php

namespace Kontentblocks\Tables;

add_action('plugins_loaded', '\Kontentblocks\Tables\init');

function init()
{

    $dbVersion = get_option('kb_dbVersion');
    if ($dbVersion !== '1.0.12') {
        global $wpdb;

        $backups = $wpdb->prefix . "kb_backups";
        $meta = $wpdb->prefix . "kb_plugindata";
        $areas = $wpdb->prefix . "kb_areas";

        $sql = "CREATE TABLE $backups (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  post_id mediumint(9),
  value longtext NOT NULL,
  literal_id VARCHAR(64) DEFAULT '' NOT NULL,
  PRIMARY KEY  (id)
    );";

        $sql .= "CREATE TABLE $meta (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        data_key VARCHAR(128) NOT NULL,
        data_value longtext NOT NULL,
        data_group varchar(32) DEFAULT 'common' NOT NULL,
        data_lang CHAR(2) DEFAULT 'de' NOT NULL,
        PRIMARY KEY  (id)
        );";

        $sql .= "CREATE TABLE $areas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        area_id VARCHAR(128) NOT NULL,
        area_key VARCHAR(128) NOT NULL,
        area_value longtext DEFAULT '' NOT NULL,
        area_lang CHAR(2) DEFAULT 'de' NOT NULL
        PRIMARY KEY  (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option("kb_dbVersion", '1.0.12');
    }
}