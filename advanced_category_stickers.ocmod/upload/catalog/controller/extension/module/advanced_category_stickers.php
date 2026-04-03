<?php
class ControllerExtensionModuleAdvancedCategoryStickers extends Controller
{

    public function index()
    {
        $this->load->language('extension/module/advanced_category_stickers');

        $this->document->addStyle('catalog/view/theme/default/stylesheet/advanced_category_stickers.css');
        $this->document->addScript('catalog/view/javascript/advanced_category_stickers.js');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_content'] = $this->language->get('text_content');

        return $this->load->view('extension/module/advanced_category_stickers', $data);
    }

}