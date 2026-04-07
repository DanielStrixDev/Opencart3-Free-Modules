<?php
class ModelExtensionModulePersonalization extends Model
{

    public function addEvent($data)
    {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "personalization_events 
            SET user_id = '" . $this->db->escape($data['user_id']) . "',
                event_type = '" . $this->db->escape($data['event_type']) . "',
                product_id = '" . (int) ($data['product_id'] ?? 0) . "',
                timestamp = NOW()
        ");
    }

    // Обновяване на потребител
    public function updateUser($user_id)
    {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "personalization_user 
            SET user_id = '" . $this->db->escape($user_id) . "',
                first_seen = NOW(),
                last_seen = NOW()
            ON DUPLICATE KEY UPDATE
                last_seen = NOW(),
                visits = visits + 1
        ");
    }

    // Последни прегледи
    public function getRecentViews($user_id, $limit)
    {
        $query = $this->db->query("
            SELECT DISTINCT product_id 
            FROM " . DB_PREFIX . "personalization_events 
            WHERE user_id = '" . $this->db->escape($user_id) . "'
                AND event_type = 'view'
                AND product_id > 0
            ORDER BY timestamp DESC
            LIMIT " . (int) $limit
        );

        return array_column($query->rows, 'product_id');
    }

    // Related products - намира продукти, гледани от същите потребители
    public function getRelatedProducts($product_ids, $limit)
    {
        if (empty($product_ids)) {
            return [];
        }

        $ids_string = implode(',', array_map('intval', $product_ids));

        $query = $this->db->query("
            SELECT pe2.product_id, COUNT(DISTINCT pe1.user_id) as relevance
            FROM " . DB_PREFIX . "personalization_events pe1
            JOIN " . DB_PREFIX . "personalization_events pe2 
                ON pe1.user_id = pe2.user_id
            WHERE pe1.product_id IN (" . $ids_string . ")
                AND pe2.product_id NOT IN (" . $ids_string . ")
                AND pe2.event_type = 'view'
                AND pe2.product_id > 0
            GROUP BY pe2.product_id
            ORDER BY relevance DESC
            LIMIT " . (int) $limit
        );

        return array_column($query->rows, 'product_id');
    }

    // Популярни продукти в сайта
    public function getPopularProducts($limit)
    {
        $query = $this->db->query("
            SELECT p.product_id, COUNT(pe.event_id) as views
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "personalization_events pe 
                ON p.product_id = pe.product_id AND pe.event_type = 'view'
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY views DESC, p.viewed DESC
            LIMIT " . (int) $limit
        );

        return array_column($query->rows, 'product_id');
    }

    // Кеширане на препоръки
    public function cacheRecommendations($user_id, $products)
    {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "personalization_recommendations 
            SET user_id = '" . $this->db->escape($user_id) . "',
                products = '" . $this->db->escape(json_encode($products)) . "',
                last_updated = NOW()
            ON DUPLICATE KEY UPDATE
                products = '" . $this->db->escape(json_encode($products)) . "',
                last_updated = NOW()
        ");
    }

    // Вземане на кеширани препоръки
    public function getRecommendations($user_id)
    {
        $query = $this->db->query("
            SELECT products 
            FROM " . DB_PREFIX . "personalization_recommendations 
            WHERE user_id = '" . $this->db->escape($user_id) . "'
        ");

        if ($query->num_rows) {
            return json_decode($query->row['products'], true);
        }

        return null;
    }


    public function getData()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_personalization'");
        return $query->rows;
    }

}