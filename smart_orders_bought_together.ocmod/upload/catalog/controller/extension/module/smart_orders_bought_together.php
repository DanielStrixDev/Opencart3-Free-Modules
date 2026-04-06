<?php
class ControllerExtensionModuleSmartOrdersBoughtTogether extends Controller
{

    public function index($product_id)
    {
        // Check if module is enabled
        if (!$this->config->get('module_smart_orders_bought_together_status')) {
            return '';
        }

        $this->load->model('extension/module/smart_orders_bought_together');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $this->load->language('extension/module/smart_orders_bought_together');

        $this->document->addStyle('catalog/view/theme/default/stylesheet/smart_orders_bought_together.css');
        $this->document->addScript('catalog/view/javascript/smart_orders_bought_together.js');

        $data['text_recommended'] = $this->language->get('text_recommended');
        $data['text_people_bought_too'] = $this->language->get('text_people_bought_too');
        $data['recommend_products'] = array();
        $results = $this->model_extension_module_smart_orders_bought_together->getOrderedTogetherProduct($product_id, 5);

        foreach ($results as $result) {
            $link = $this->url->link('product/product', 'product_id=' . $result['product_id']);
            $title = $this->model_catalog_product->getProduct($result['product_id'])['name'];
            $image = $this->model_tool_image->resize($result['image'], 120, 120);

            $data['recommend_products'][] = array(
                'title' => $title,
                'link' => $link,
                'image' => $image,
            );
        }

        if (empty($data['recommend_products'])) {
            return '';
        }

        return $this->load->view('extension/module/smart_orders_bought_together', $data);
    }

}