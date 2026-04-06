<?php
class ControllerExtensionModuleSmartOrdersBoughtTogether extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/smart_orders_bought_together');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_smart_orders_bought_together', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            if (isset($this->request->post['apply'])) {
                $this->response->redirect($this->url->link('extension/module/smart_orders_bought_together', 'user_token=' . $this->session->data['user_token'], true));
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
            'href' => $this->url->link('extension/module/smart_orders_bought_together', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/smart_orders_bought_together', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_smart_orders_bought_together_status'])) {
            $data['module_smart_orders_bought_together_status'] = $this->request->post['module_smart_orders_bought_together_status'];
        } else {
            $data['module_smart_orders_bought_together_status'] = $this->config->get('module_smart_orders_bought_together_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/smart_orders_bought_together', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/smart_orders_bought_together')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/module/smart_orders_bought_together');
        $this->model_extension_module_smart_orders_bought_together->install();
    }

    public function uninstall() {
        $this->load->model('extension/module/smart_orders_bought_together');
        $this->model_extension_module_smart_orders_bought_together->uninstall();
    }
}