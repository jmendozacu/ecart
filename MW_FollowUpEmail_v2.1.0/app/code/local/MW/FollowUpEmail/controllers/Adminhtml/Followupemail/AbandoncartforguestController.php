<?php



class MW_FollowUpEmail_Adminhtml_Followupemail_AbandoncartforguestController extends Mage_Adminhtml_Controller_Action

{



	protected function _initAction() {

		$this->loadLayout()

			->_setActiveMenu('report/shopcart/abandoncartforguest')

			->_addBreadcrumb(Mage::helper('adminhtml')->__('Abandoned Carts for guest'), Mage::helper('adminhtml')->__('Abandoned Carts for guest'));

		

		return $this;

	}	
 

	public function indexAction() {		

		$this->_initAction()

			->renderLayout();

	}



    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')

    {

        $response = $this->getResponse();

        $response->setHeader('HTTP/1.1 200 OK','');

        $response->setHeader('Pragma', 'public', true);

        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);

        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);

        $response->setHeader('Last-Modified', date('r'));

        $response->setHeader('Accept-Ranges', 'bytes');

        $response->setHeader('Content-Length', strlen($content));

        $response->setHeader('Content-type', $contentType);

        $response->setBody($content);

        $response->sendResponse();

        die;

    }

}