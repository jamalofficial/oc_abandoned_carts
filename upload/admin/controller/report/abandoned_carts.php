<?php
class ControllerReportAbandonedCarts extends Controller {
	private $error = array();

	public function index() {
		if( !$this->_Status() ){
			return false;
		}
		$this->load->language('report/abandoned_carts');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/abandoned_carts');

		$this->getList();
	}

	public function recover() {
		if( !$this->_Status() ){
			return false;
		}
		if (isset($this->request->post['selected']) && $this->validate()) {
			$this->load->language('report/abandoned_carts');
			$this->load->model('extension/module/abandoned_carts');
			foreach ($this->request->post['selected'] as $customer_id) {
				$this->model_extension_module_abandoned_carts->recoverEmail($customer_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');
		}
		$this->response->redirect($this->url->link('report/abandoned_carts', 'token='.$this->session->data['token'], true));
	}

	protected function getList() {

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('report/abandoned_carts', 'token=' . $this->session->data['token'] . $url, true)
		);

		$data['recover'] = $this->url->link('report/abandoned_carts/recover', 'token=' . $this->session->data['token'], true);
		$data['delete']  = $this->url->link('report/abandoned_carts/delete', 'token=' . $this->session->data['token'], true);
		$data['carts']  = array();

		$filter_data = array(
			'days'  => $this->config->get('abandoned_carts_limit'),
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$cart_total = $this->model_extension_module_abandoned_carts->getTotalCarts($filter_data);

		$data['carts'] = $this->model_extension_module_abandoned_carts->getCarts($filter_data);

		$data['heading_title']          = $this->language->get('heading_title');

		$data['text_list']              = $this->language->get('text_list');
		$data['text_no_results']        = $this->language->get('text_no_results');
		$data['text_confirm']           = $this->language->get('text_confirm');
		$data['text_success']           = $this->language->get('text_success');

		$data['column_order_id']        = $this->language->get('column_order_id');
		$data['column_customer']        = $this->language->get('column_customer');
		$data['column_status']          = $this->language->get('column_status');
		$data['column_total']           = $this->language->get('column_total');
		$data['column_date_added']      = $this->language->get('column_date_added');
		$data['column_date_modified']   = $this->language->get('column_date_modified');
		$data['column_abandoned']       = $this->language->get('column_abandoned');
		$data['column_action']          = $this->language->get('column_action');

		$data['button_recover']         = $this->language->get('button_recover');
		$data['button_delete']          = $this->language->get('button_delete');
		$data['button_view']            = $this->language->get('button_view');

		$data['token']                  = $this->session->data['token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$pagination         = new Pagination();
		$pagination->total  = $cart_total;
		$pagination->page   = $page;
		$pagination->limit  = $this->config->get('config_limit_admin');
		$pagination->url    = $this->url->link('report/abandoned_carts', 'token=' . $this->session->data['token']  . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results']    = sprintf($this->language->get('text_pagination'), ($cart_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($cart_total - $this->config->get('config_limit_admin'))) ? $cart_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $cart_total, ceil($cart_total / $this->config->get('config_limit_admin')));
		$data['header']         = $this->load->controller('common/header');
		$data['column_left']    = $this->load->controller('common/column_left');
		$data['footer']         = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('report/abandoned_carts', $data));
	}

	public function delete() {
		if( !$this->_Status() ){
			return false;
		}
		$data_delete = [];
		if( !empty($this->request->get['customer_id']) ){
			$data_delete[] = $this->request->get['customer_id'];
		}
		elseif( !empty($this->request->post['selected']) ){
			$data_delete = $this->request->post['selected'];
			dd($data_delete);
		}
		if( !empty($data_delete) && $this->validate() ){
			$this->load->model('extension/module/abandoned_carts');
			$this->load->language('report/abandoned_carts');
			foreach ($data_delete as $customer_id) {
				$this->model_extension_module_abandoned_carts->deleteCart($customer_id);
			}
			$this->session->data['success'] = $this->language->get('text_deleted');
		}
		$this->response->redirect($this->url->link('report/abandoned_carts', 'token='.$this->session->data['token'], true));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'report/abandoned_carts')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function _Status(){
		return $this->config->get('abandoned_carts_status');
	}
}
