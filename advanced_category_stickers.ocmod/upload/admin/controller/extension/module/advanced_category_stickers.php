<?php
class ControllerExtensionModuleAdvancedCategoryStickers extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/advanced_category_stickers');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('extension/module/advanced_category_stickers');
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        $data['categories'] = $this->model_extension_module_advanced_category_stickers->getCategories($filter_name, ($page - 1) * $this->config->get('config_limit_admin'), $this->config->get('config_limit_admin'));
        $category_total = $this->model_extension_module_advanced_category_stickers->getTotalCategories($filter_name);

        $pagination = new Pagination();
        $pagination->total = $category_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/advanced_category_stickers', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));
        $data['filter_name'] = $filter_name;
        $data['user_token'] = $this->session->data['user_token'];

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (isset($this->request->post['category_stickers'])) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "advanced_category_stickers");

                foreach ($this->request->post['category_stickers'] as $category_id => $sticker_text) {
                    if (!empty($sticker_text)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "advanced_category_stickers SET category_id = '" . (int) $category_id . "', sticker = '" . $this->db->escape($sticker_text) . "'");
                    }
                }
            }

            $this->model_setting_setting->editSetting('module_advanced_category_stickers', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->post['apply'])) {
                $this->response->redirect($this->url->link('extension/module/advanced_category_stickers', 'user_token=' . $this->session->data['user_token'] . $url, true));
            } else {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/advanced_category_stickers', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/advanced_category_stickers', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_advanced_category_stickers_status'])) {
            $data['module_advanced_category_stickers_status'] = $this->request->post['module_advanced_category_stickers_status'];
        } else {
            $data['module_advanced_category_stickers_status'] = $this->config->get('module_advanced_category_stickers_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/advanced_category_stickers', $data));
    }

    public function save()
    {
        $this->load->language('extension/module/advanced_category_stickers');

        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/module/advanced_category_stickers')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            // Save module status
            if (isset($this->request->post['module_advanced_category_stickers_status'])) {
                $this->load->model('setting/setting');
                $this->model_setting_setting->editSetting('module_advanced_category_stickers', $this->request->post);
            }

            // Save category stickers
            if (isset($this->request->post['category_stickers'])) {
                $this->load->model('extension/module/advanced_category_stickers');
                $this->model_extension_module_advanced_category_stickers->saveCategoryStickers($this->request->post['category_stickers']);
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/advanced_category_stickers')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/module/advanced_category_stickers');
        $this->model_extension_module_advanced_category_stickers->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/module/advanced_category_stickers');
        $this->model_extension_module_advanced_category_stickers->uninstall();
    }
}