<?php
class ModelExtensionModuleSmartOrdersBoughtTogether extends Model {

    public function install() {
        // Create table
        // $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "smart_orders_bought_together` ...");
    }

    public function uninstall() {
        // Delete settings
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE code = 'module_smart_orders_bought_together'");
        
        // Delete table
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "smart_orders_bought_together`");
    }

    public function getData() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_smart_orders_bought_together'");
        return $query->rows;
    }
}