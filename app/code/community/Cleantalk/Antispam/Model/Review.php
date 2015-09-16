<?php

class Cleantalk_Antispam_Model_Review extends Mage_Review_Model_Review
{
    public function validate()
    {
        $errors = parent::validate();

        if (is_array($errors)) {
            return $errors;
        }

        $errors = array();

        $aMessage = array();
        $aMessage['type'] = 'comment';
        $aMessage['sender_email'] = '';
        $aMessage['sender_nickname'] = $this->getNickname();
        $aMessage['message_title'] = $this->getTitle();
        $aMessage['message_body'] = $this->getDetail();
        $aMessage['example_title'] = '';
        $aMessage['example_body'] = '';
        $aMessage['example_comments'] = '';

        $model = Mage::getModel('antispam/api');
        $aResult = $model->CheckSpam($aMessage, FALSE);

        if(isset($aResult) && is_array($aResult)){
            if($aResult['errno'] == 0){
                if($aResult['allow'] == 0){
                    // Spammer - fill errors
                    // Note: 'stop_queue' is ignored in user checking
                    if (preg_match('//u', $aResult['ct_result_comment'])){
                                $comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $aResult['ct_result_comment']);
                                $comment_str = preg_replace('/<[^<>]*>/iu', '', $comment_str);
                    }else{
                                $comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/i', '', $aResult['ct_result_comment']);
                                $comment_str = preg_replace('/<[^<>]*>/i', '', $comment_str);
                    }
                    $errors[] = $comment_str;
                }
            }
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }
}
