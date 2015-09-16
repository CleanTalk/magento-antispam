<?php 
require_once 'Mage/Contacts/controllers/IndexController.php';

class Cleantalk_Antispam_Contacts_IndexController extends Mage_Contacts_IndexController
{
    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ($post) {

    	    $aMessage = array();
            $aMessage['type'] = 'comment';
            $aMessage['sender_email'] = isset($post['email']) ? $post['email'] : '';
            $aMessage['sender_nickname'] = isset($post['name']) ? $post['name'] : '';
            $aMessage['message_title'] = isset($post['telephone']) ? $post['telephone'] : '';
            $aMessage['message_body'] = isset($post['comment']) ? $post['comment'] : '';
            $aMessage['example_title'] = '';
            $aMessage['example_body'] = '';
            $aMessage['example_comments'] = '';

            $model = Mage::getModel('antispam/api');
            $aResult = $model->CheckSpam($aMessage, FALSE);

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
        		$this->_redirect('*/*/');
        		return;
                    }
                }
            }
	}
	parent::postAction();
    }
}
