<?php
class ModelExtensionModuleAbandonedCarts extends Model {

	public function recoverEmail($customer_id) {
		// $order_info = $this->getOrder($order_id);
		$carts_info = $this->getCart($customer_id);

		$log = new Log('abandone_carts_email');
		$log->write("<-------<E-mails Initiated>------->".PHP_EOL);

		$this->_sendEmail($carts_info);

		$total_products = count($carts_info['products']);
		$log->write("Mail sent to {$carts_info['info']['firstname']} {$carts_info['info']['lastname']}($customer_id) for $total_products products.".PHP_EOL);
		$log->write("<-------<E-mails Ended>------->".PHP_EOL);
		unset($log);
	}

	private function _sendEmail($cart_and_customer_data){
		$carts_info = $cart_and_customer_data;
		$customer_info = $carts_info['info'];
		$cart_products = $carts_info['products'];
		$store_name = $this->config->get('config_name');
		$store_url = HTTPS_SERVER;

		$this->load->language('extension/module/abandoned_carts');

		$text  = sprintf($this->language->get('failed_cart_greeting'),ucfirst($customer_info['firstname']))."\n\n";
		$text .= $this->language->get('failed_cart_intro') . "\n\n";
		$text .= $this->language->get('failed_cart_contents') . "\n";

		foreach ($cart_products as $product) {
			$text .= $product['quantity'] . 'x ' . $product['name'] . "\n";
		}

		$text .= "\n".$this->language->get('failed_cart_body') . "\n\n";
		$text .= $this->language->get('failed_cart_footer') . "\n\n";
		$text .= $this->language->get('failed_cart_signoff') . "\n\n";
		$text .= $this->language->get('failed_cart_signature') . "\n\n";
		$text .= $store_name . "\n";
		$text .= $store_url. "\n";

		$mail = new Mail();
		$mail->protocol      = $this->config->get('config_mail_protocol');
		$mail->parameter     = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port     = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
		$mail->setTo($customer_info['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
		$mail->setSubject($this->language->get(html_entity_decode($this->language->get('subject_prefix')).' '.$store_name));
		$mail->setText($text);
		$mail->send();
	}

	public function sendEmails() {
		$all_abandoned_carts = $this->getCarts();
		$log = new Log('abandone_carts_email');
		$log->write("<-------<E-mails Initiated>------->".PHP_EOL);
		foreach ($all_abandoned_carts as $customer_id => $cart) {
			$this->_sendEmail($cart);
			$total_products = count($cart['products']);
			$log->write("Mail sent to {$cart['info']['firstname']} {$cart['info']['lastname']}($customer_id) for $total_products products.".PHP_EOL);
		}
		$log->write("<-------<E-mails Ended>------->".PHP_EOL);
		unset($log);		
	}

	public function getCarts($data = array()) {
		$sortable = [
			'date_added',
			'product_id',
			'customer_id',
		];

		$carts_query = "SELECT * FROM `".DB_PREFIX."cart` WHERE `customer_id` > 0 AND `date_added` <= DATE_SUB(NOW(), INTERVAL {$this->config->get('abandoned_carts_limit')} DAY)";

		if (isset($data['sort']) && in_array($data['sort'], $sortable)) {
			$carts_query .= " ORDER BY `{$data['sort']}`";
		} else {
			$carts_query .= " ORDER BY `cart_id` ";
		}

		$carts_query .= !empty($data['order']) ? $data['order'] : 'DESC';

		if (isset($data['start']) || isset($data['limit'])) {
			$start = (!empty($data['start']) && $data['start'] > 0) ? $data['start'] : 0;
			$limit = (!empty($data['limit']) && $data['limit'] > 0) ? $data['limit'] : 20;
			$carts_query .= " LIMIT " . (int)$start . "," . (int)$limit;
		}

		$abandoned_carts = $this->db->query($carts_query);
		$customer_with_carts = [];
		$temp_products = [];
		$temp_customers = [];
		if( !empty($abandoned_carts->row) ){
			foreach ($abandoned_carts->rows as $index => $row) {
				$cid = $row['customer_id'];
				$pid = $row['product_id'];
				$lid = $this->config->get('config_language_id');
				if( !isset($temp_customers[$cid]) ){
					$customer = $this->db->query("SELECT `firstname`, `lastname`, `email` FROM `".DB_PREFIX."customer` WHERE `customer_id` = '$cid' AND `status` = 1");
					if( !empty($customer->row) ){
						$temp_customers[$cid] = $customer->row;
					}
				}
				if( !empty($temp_customers[$cid]) ){
					$customer_with_carts[$cid]['info'] = $temp_customers[$cid];
					if( !isset($temp_products[$pid]) ){
						$product_1 = $this->db->query("SELECT `image`, `price` FROM `".DB_PREFIX."product` WHERE `product_id` = '{$pid}'");
						if( !empty($product_1->row) ){
							$product_2 = $this->db->query("SELECT `name` FROM `".DB_PREFIX."product_description` WHERE `product_id` = '$pid' AND `language_id` = '$lid'");
							$temp_products[$pid] = [
								'product_id' => $pid,
								'price' => $product_1->row['price'],
								'image' => $product_1->row['image'],
								'name' => !empty($product_2->row['name']) ? $product_2->row['name'] : 'N/A',
							];
						}
					}
					if( !empty($temp_products[$pid]) ){
						$customer_with_carts[$cid]['products'][$pid] = $temp_products[$pid];
						if( !isset($customer_with_carts[$cid]['products'][$pid]['quantity']) ){
							$customer_with_carts[$cid]['products'][$pid]['quantity'] = $row['quantity'];
						}
						else{
							$customer_with_carts[$cid]['products'][$pid]['quantity'] += $row['quantity'];
						}
						$customer_with_carts[$cid]['products'][$pid]['cart_id'] = $row['cart_id'];
						$customer_with_carts[$cid]['products'][$pid]['date_added'] = date('Y-m-d', strtotime($row['date_added']));
					}
				}				
			}
		}
		return $customer_with_carts;
	}

	public function getCart($customer_id) {

		$carts_query = "SELECT * FROM `".DB_PREFIX."cart` WHERE `customer_id` = '$customer_id' AND `date_added` <= DATE_SUB(NOW(), INTERVAL {$this->config->get('abandoned_carts_limit')} DAY)";

		$abandoned_cart = $this->db->query($carts_query);
		$customer_with_carts = [];
		$temp_products = [];
		$temp_customers = [];
		if( !empty($abandoned_carts->row) ){
			foreach ($abandoned_carts->rows as $index => $row) {
				$cid = $row['customer_id'];
				$pid = $row['product_id'];
				$lid = $this->config->get('config_language_id');
				if( !isset($temp_customers[$cid]) ){
					$customer = $this->db->query("SELECT `firstname`, `lastname`, `email` FROM `".DB_PREFIX."customer` WHERE `customer_id` = '$cid' AND `status` = 1");
					if( !empty($customer->row) ){
						$temp_customers[$cid] = $customer->row;
					}
				}
				if( !empty($temp_customers[$cid]) ){
					$customer_with_carts[$cid]['info'] = $temp_customers[$cid];
					if( !isset($temp_products[$pid]) ){
						$product_1 = $this->db->query("SELECT `image`, `price` FROM `".DB_PREFIX."product` WHERE `product_id` = '{$pid}'");
						if( !empty($product_1->row) ){
							$product_2 = $this->db->query("SELECT `name` FROM `".DB_PREFIX."product_description` WHERE `product_id` = '$pid' AND `language_id` = '$lid'");
							$temp_products[$pid] = [
								'product_id' => $pid,
								'price' => $product_1->row['price'],
								'image' => $product_1->row['image'],
								'name' => !empty($product_2->row['name']) ? $product_2->row['name'] : 'N/A',
							];
						}
					}
					if( !empty($temp_products[$pid]) ){
						$customer_with_carts[$cid]['products'][$pid] = $temp_products[$pid];
						if( !isset($customer_with_carts[$cid]['products'][$pid]['quantity']) ){
							$customer_with_carts[$cid]['products'][$pid]['quantity'] = $row['quantity'];
						}
						else{
							$customer_with_carts[$cid]['products'][$pid]['quantity'] += $row['quantity'];
						}
						$customer_with_carts[$cid]['products'][$pid]['cart_id'] = $row['cart_id'];
						$customer_with_carts[$cid]['products'][$pid]['date_added'] = $row['date_added'];
					}
				}				
			}
		}
		return $customer_with_carts;
	}
}
