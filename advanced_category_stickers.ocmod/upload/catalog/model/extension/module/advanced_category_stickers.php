<?php
class ModelExtensionModuleAdvancedCategoryStickers extends Model {

    public function getData() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_advanced_category_stickers'");
        return $query->rows;
    }

    public function productHasStickerCategory($product_id)
    {
        $query = $this->db->query("SELECT acs.sticker FROM " . DB_PREFIX . "advanced_category_stickers acs 
                                   LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON acs.category_id = p2c.category_id 
                                   WHERE p2c.product_id = '" . (int)$product_id . "' 
                                   AND acs.sticker != '' 
                                   LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['sticker'];
        }
        
        return false;
    }

}