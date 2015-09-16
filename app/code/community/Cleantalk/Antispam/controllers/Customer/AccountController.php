<?php 
require_once 'Mage/Customer/controllers/AccountController.php';

class Cleantalk_Antispam_Customer_AccountController extends Mage_Customer_AccountController
{
    public function createPostAction()
    {
        $post = $this->getRequest()->getPost();
        if ($post) {
    	    $aUser = array();
    	    $aUser['type'] = 'register';
            $aUser['sender_email'] = isset($post['email']) ? $post['email'] : '';
            $aUser['sender_nickname'] = isset($post['firstname']) ? $post['firstname'] : '';
            $aUser['sender_nickname'] .= isset($post['lastname']) ? ' ' . $post['lastname'] : '';

            $model = Mage::getModel('antispam/api');
            $aResult = $model->CheckSpam($aUser, FALSE);

            if(isset($aResult) && is_array($aResult)){
                if($aResult['errno'] == 0){
                    if($aResult['allow'] == 0){
                        if (preg_match('//u', $aResult['ct_result_comment'])){
                                $comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $aResult['ct_result_comment']);
                                $comment_str = preg_replace('/<[^<>]*>/iu', '', $comment_str);
                        }else{
                                $comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/i', '', $aResult['ct_result_comment']);
                                $comment_str = preg_replace('/<[^<>]*>/i', '', $comment_str);
                        }
			Mage::getSingleton('customer/session')->addError($comment_str);
        		//$this->_redirect('*/*/');
                        $this->_redirectError(Mage::getUrl('*/*/create', array('_secure' => true)));
        		return;
                    }
                }
            }
	}
	parent::createPostAction();
    }
}
