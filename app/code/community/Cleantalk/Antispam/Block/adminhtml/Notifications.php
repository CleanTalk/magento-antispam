<?php
class Cleantalk_Antispam_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    public function getMessage()
    {
        /*
          * Here you have check if there's a message to be displayed or not
          */
        $show_notice=intval(Mage::getStoreConfig('general/cleantalk/show_notice'));
        $message='';
        if($show_notice==1)
        {
        	$message = "Like Anti-spam by CleanTalk? Help others learn about CleanTalk! <a  target='_blank' href='http://www.magentocommerce.com/magento-connect/antispam-by-cleantalk.html'>Leave a review at the Magento Connect</a> <a href='?close_notice=1' style='float:right;'>Close</a>";
        }        
        return $message;
    }
}
?>