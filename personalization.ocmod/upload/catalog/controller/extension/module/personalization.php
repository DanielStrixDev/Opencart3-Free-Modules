<?php
class ControllerExtensionModulePersonalization extends Controller
{
    public function index($setting)
    {
        $this->load->language('extension/module/personalization');
        $this->load->model('setting/setting');
        $this->load->model('extension/module/personalization');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['module_personalization_status'] = $this->config->get('module_personalization_status');

        $data['products'] = [];

        if ($data['module_personalization_status']) {
            $data['style'] = 'catalog/view/theme/default/stylesheet/personalization.css';
            $data['script'] = 'catalog/view/javascript/personalization.js';

            // Get user ID
            if ($this->customer->isLogged()) {
                $user_id = 'c_' . $this->customer->getId();
            } else {
                $user_id = isset($this->session->data['personalization_id']) ? $this->session->data['personalization_id'] : null;
            }

            // Fetch recommendations
            $recommended_ids = [];

            if ($user_id) {
                $recommended_ids = $this->model_extension_module_personalization->getRecommendations($user_id);
            }

            // Fallback: show popular products if no personalized recommendations or no user_id
            if (empty($recommended_ids)) {
                $recommended_ids = $this->model_extension_module_personalization->getPopularProducts(10);
            }

            foreach ($recommended_ids as $product_id) {
                $product = $this->model_catalog_product->getProduct($product_id);
                if ($product) {
                    $data['products'][] = [
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'price' => $this->currency->format($product['price'], $this->session->data['currency']),
                        'thumb' => $this->model_tool_image->resize($product['image'] ?: 'placeholder.png', 200, 200),
                        'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                    ];
                }
            }
        }

        return $this->load->view('extension/module/personalization', $data);
    }

    public function track()
    {
        if (!isset($this->request->server['HTTP_X_REQUESTED_WITH'])) {
            return;
        }

        $this->load->model('extension/module/personalization');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['user_id']) || !isset($input['event_type'])) {
            return;
        }

        $user_id = $input['user_id'];

        // Ако е логнат - използваме customer_id
        if ($this->customer->isLogged()) {
            $user_id = 'c_' . $this->customer->getId();
        }

        // Запазваме събитието
        $this->model_extension_module_personalization->addEvent([
            'user_id' => $user_id,
            'event_type' => $input['event_type'],
            'product_id' => $input['data']['product_id'] ?? 0
        ]);

        // Обновяваме потребителя
        $this->model_extension_module_personalization->updateUser($user_id);

        // Запазваме user_id в сесията за гости
        if (!$this->customer->isLogged()) {
            $this->session->data['personalization_id'] = $user_id;
        }

        // Генерираме нови препоръки
        if ($input['event_type'] == 'view') {
            $this->generateRecommendations($user_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true]));
    }

    // Вземане на препоръки
    public function getRecommendations()
    {
        if (!isset($this->request->get['user_id']) && !$this->customer->isLogged()) {
            return;
        }

        $this->load->model('extension/module/personalization');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        // Определяне на user_id
        if ($this->customer->isLogged()) {
            $user_id = 'c_' . $this->customer->getId();
        } else {
            $user_id = $this->request->get['user_id'];

            if (!preg_match('/^(g|c)_/', $user_id)) {
                return;
            }
        }

        // Вземаме от кеша
        $recommended_ids = $this->model_extension_module_personalization->getRecommendations($user_id);

        $products = [];
        if ($recommended_ids) {
            foreach ($recommended_ids as $product_id) {
                $product = $this->model_catalog_product->getProduct($product_id);
                if ($product) {
                    $products[] = [
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'price' => $this->currency->format($product['price'], $this->session->data['currency']),
                        'thumb' => $this->model_tool_image->resize($product['image'] ?: 'placeholder.png', 200, 200),
                        'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                    ];
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['products' => $products]));
    }

    // Генериране на препоръки (ядрото)
    private function generateRecommendations($user_id)
    {
        $this->load->model('extension/module/personalization');

        // Вземаме последните 5 прегледа
        $recent = $this->model_extension_module_personalization->getRecentViews($user_id, 5);

        if (empty($recent)) {
            return;
        }

        // Алгоритъм: "Хората, които гледаха това, гледаха и..."
        $recommended = $this->model_extension_module_personalization->getRelatedProducts($recent, 10);

        // Fallback: ако няма, взимаме популярни
        if (empty($recommended)) {
            $recommended = $this->model_extension_module_personalization->getPopularProducts(10);
        }

        // Кешираме
        $this->model_extension_module_personalization->cacheRecommendations($user_id, $recommended);
    }
}