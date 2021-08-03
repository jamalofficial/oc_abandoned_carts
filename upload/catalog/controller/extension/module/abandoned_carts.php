<?php
class ControllerExtensionModuleAbandonedCarts extends Controller {
	private $error = array();

	public function index() {
		if( !$this->_Status() ){
			return false;
		}
		// $this->load->language('report/abandoned_carts');

		// $this->document->setTitle($this->language->get('heading_title'));

		// $this->load->model('extension/module/abandoned_carts');

		// $this->getList();
	}

	public function sendMails(){
		if( !$this->_Status() ){
			return false;
		}
		$this->load->model('extension/module/abandoned_carts');
		$log = new Log('abandone_carts_email_errors');
		try{
			$this->model_extension_module_abandoned_carts->sendEmails();	
		}
		catch(\Exception $e){
			$log->write("<-------<E-mails Failed>------->".PHP_EOL);
			$log->write($e->getMessage().PHP_EOL);
			$log->write("<------------------------------>".PHP_EOL);
		}
		finally{
			unset($log);
		}	
	}

	private function _Status(){
		return $this->config->get('abandoned_carts_status');
	}
}
