<?php
class ModelExtensionModulePersonalization extends Model
{

    public function install()
    {
        // Create tables (separate queries for better error handling)
        $this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "personalization_user` (
        `user_id` VARCHAR(36) NOT NULL,
        `first_seen` DATETIME NOT NULL,
        `last_seen` DATETIME NOT NULL,
        `visits` INT DEFAULT 1,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
    ");

        $this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "personalization_events` (
        `event_id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` VARCHAR(36) NOT NULL,
        `event_type` VARCHAR(20) NOT NULL,
        `product_id` INT(11),
        `timestamp` DATETIME NOT NULL,
        PRIMARY KEY (`event_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
    ");

        $this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "personalization_recommendations` (
        `user_id` VARCHAR(36) NOT NULL,
        `products` TEXT,
        `last_updated` DATETIME NOT NULL,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
    ");

        // Insert default settings (clean up first to prevent duplicates on reinstall)
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_personalization'");
        $this->db->query("
    INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
    (0, 'module_personalization', 'module_personalization_status', '1', 0)
    ");

        $layout_query = $this->db->query("SELECT layout_id FROM `" . DB_PREFIX . "layout` WHERE `name` = 'Home' LIMIT 1");

        if ($layout_query->num_rows > 0) {
            $home_layout_id = $layout_query->row['layout_id'];

            $check_query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "layout_module` 
            WHERE `layout_id` = '" . (int) $home_layout_id . "' 
            AND `code` = 'personalization' 
            AND `position` = 'content_top'
        ");

            if ($check_query->num_rows == 0) {
                $sort_order_query = $this->db->query("
                SELECT MAX(sort_order) as max_sort 
                FROM `" . DB_PREFIX . "layout_module` 
                WHERE `layout_id` = '" . (int) $home_layout_id . "' 
                AND `position` = 'content_top'
            ");

                $sort_order = ($sort_order_query->row['max_sort'] !== null) ? $sort_order_query->row['max_sort'] + 1 : 0;

                // Insert module into layout
                $this->db->query("
                INSERT INTO `" . DB_PREFIX . "layout_module` 
                (`layout_id`, `code`, `position`, `sort_order`) 
                VALUES (
                    '" . (int) $home_layout_id . "', 
                    'personalization', 
                    'content_top', 
                    '" . (int) $sort_order . "'
                )
            ");
            }
        }
    }

    public function uninstall()
    {
        // Delete settings
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_personalization'");

        // Delete tables (separate queries for each table)
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "personalization_user`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "personalization_events`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "personalization_recommendations`");

        // Remove module from ALL layouts (not just home)
        $this->db->query("
        DELETE FROM `" . DB_PREFIX . "layout_module` 
        WHERE `code` = 'personalization'
    ");
    }

    public function getData()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_personalization'");
        return $query->rows;
    }
}