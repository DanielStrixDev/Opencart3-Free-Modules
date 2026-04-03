<?php
class ModelExtensionModuleAdvancedCategoryStickers extends Model
{
    /*
     * Module install/uninstall
     */
    public function install()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "advanced_category_stickers` 
        (
            act_id int NOT NULL AUTO_INCREMENT,
            category_id int NOT NULL,
            sticker varchar(255),

            PRIMARY KEY (act_id)
        )
        ");
    }

    public function uninstall()
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE code = 'module_advanced_category_stickers'");

        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "advanced_category_stickers`");
    }

    /*
     * Category Data
     */
    public function applyCategorySticker($category_id, $sticker_text)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "advanced_category_stickers (category_id, sticker) VALUES (" . (int)$category_id . ", '" . $this->db->escape($sticker_text) . "')");
    }

    public function removeCategorySticker($category_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "advanced_category_stickers WHERE category_id = " . (int)$category_id);
    }

    public function hasCategorySticker($category_id)
    {
        $query = $this->db->query("SELECT 1 FROM " . DB_PREFIX . "advanced_category_stickers WHERE category_id = " . (int)$category_id);
        if ($query->rows > 0) {
            return true;
        }
    }

    public function getCategories($filter_name = '', $start = 0, $limit = 20)
    {
        $this->load->model('catalog/category');

        $categories = $this->model_catalog_category->getCategories();

        $category_data = array();

        // Филтриране по име
        if ($filter_name) {
            foreach ($categories as $category) {
                if (stripos($category['name'], $filter_name) !== false) {
                    $category_data[] = array(
                        'category_id' => $category['category_id'],
                        'name' => $category['name'],
                        'sticker' => $this->getCategorySticker($category['category_id'])
                    );
                }
            }
        } else {
            foreach ($categories as $category) {
                $category_data[] = array(
                    'category_id' => $category['category_id'],
                    'name' => $category['name'],
                    'sticker' => $this->getCategorySticker($category['category_id'])
                );
            }
        }

        // Пагинация
        if ($limit > 0) {
            return array_slice($category_data, $start, $limit);
        }

        return $category_data;
    }

    public function getTotalCategories($filter_name = '')
    {
        $this->load->model('catalog/category');

        $categories = $this->model_catalog_category->getCategories();

        if ($filter_name) {
            $count = 0;
            foreach ($categories as $category) {
                if (stripos($category['name'], $filter_name) !== false) {
                    $count++;
                }
            }
            return $count;
        }

        return count($categories);
    }
    public function getCategorySticker($category_id)
    {
        $query = $this->db->query("SELECT sticker FROM " . DB_PREFIX . "advanced_category_stickers WHERE category_id = '" . (int) $category_id . "'");

        if ($query->num_rows) {
            return $query->row['sticker'];
        }

        return '';
    }


    public function saveCategoryStickers($category_stickers)
    {
        // Обновяваме или добавяме стикерите само за текущите категории
        foreach ($category_stickers as $category_id => $sticker_text) {
            if (!empty($sticker_text)) {
                // Проверяваме дали вече съществува стикер за тази категория
                $query = $this->db->query("SELECT act_id FROM " . DB_PREFIX . "advanced_category_stickers WHERE category_id = '" . (int)$category_id . "'");
                
                if ($query->num_rows) {
                    // Обновяваме съществуващ стикер
                    $this->db->query("UPDATE " . DB_PREFIX . "advanced_category_stickers SET sticker = '" . $this->db->escape($sticker_text) . "' WHERE category_id = '" . (int)$category_id . "'");
                } else {
                    // Добавяме нов стикер
                    $this->db->query("INSERT INTO " . DB_PREFIX . "advanced_category_stickers SET category_id = '" . (int)$category_id . "', sticker = '" . $this->db->escape($sticker_text) . "'");
                }
            } else {
                // Изтриваме празни стикери
                $this->db->query("DELETE FROM " . DB_PREFIX . "advanced_category_stickers WHERE category_id = '" . (int)$category_id . "'");
            }
        }
    }

    /*
     * Settings
     */
    public function getData()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_advanced_category_stickers'");
        return $query->rows;
    }
}