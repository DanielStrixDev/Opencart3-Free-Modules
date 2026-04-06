<?php
class ModelExtensionModuleSmartOrdersBoughtTogether extends Model
{

    public function getData()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_smart_orders_bought_together'");
        return $query->rows;
    }

    public function getOrderedTogetherProduct($product_id, $limit = 5)
    {
        $sql = "SELECT op2.product_id, p.image,
                COUNT(*) as order_count,
                COUNT(*) * 100.0 / (
                    SELECT COUNT(*) FROM " . DB_PREFIX . "order_product 
                    WHERE product_id = " . (int) $product_id . "
                ) as confidence
            FROM " . DB_PREFIX . "order_product op1
            JOIN " . DB_PREFIX . "order_product op2 ON op1.order_id = op2.order_id 
                AND op1.product_id != op2.product_id
            JOIN " . DB_PREFIX . "product p ON op2.product_id = p.product_id
            WHERE op1.product_id = " . (int) $product_id . "
            GROUP BY op2.product_id, p.image
            HAVING order_count >= 1
            ORDER BY confidence DESC, order_count DESC
            LIMIT " . (int) $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }
}