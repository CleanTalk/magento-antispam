<?php

class Cleantalk_Antispam_Model_Observer
{
	public function interceptOutput(Varien_Event_Observer $observer)
	{
		$transport = $observer->getTransport();
		$html = $transport->getHtml();
		if(strpos($html,'<div class="middle"')!==false&&isset($_GET['cleantalk_message']))
		{
			$html=str_replace('<div class="middle"','<div class="notification-global  notification-global-error"><b>CleanTalk error: '.$_GET['cleantalk_message'].'</b></div><div class="middle"',$html);
			$transport->setHtml($html);
		}
		$show_notice=intval(Mage::getStoreConfig('general/cleantalk/show_notice'));
		if(strpos($html,'<div class="middle"')!==false&&$show_notice==1)
		{
			$message = "Like Anti-spam by CleanTalk? Help others learn about CleanTalk! <a  target='_blank' href='http://www.magentocommerce.com/magento-connect/antispam-by-cleantalk.html'>Leave a review at the Magento Connect</a> <a href='?close_notice=1' style='float:right;'>Close</a>";
			$html=str_replace('<div class="middle"','<div class="notification-global  notification-global-notice">'.$message.'</div><div class="middle"',$html);
			$transport->setHtml($html);
		}
		if(strpos($html,'%LINK_TEXT%')!==false)
		{
			$api_key = Mage::getStoreConfig('general/cleantalk/api_key');
			if(trim($api_key)=='')
			{
				Mage::app()->cleanCache();
				$admin_email=Mage::getStoreConfig('trans_email/ident_general/email');
				$button="<input type='button' style='margin-top:5px;-webkit-border-bottom-left-radius: 5px;-webkit-border-bottom-right-radius: 5px;-webkit-border-radius: 5px;-webkit-border-top-left-radius: 5px;-webkit-border-top-right-radius: 5px;background: #3399FF;border-radius: 5px;box-sizing: border-box;color: #FFFFFF;font: normal normal 400 14px/16.2px \"Open Sans\";padding:3px;border:0px none;cursor:pointer;' value='Get access key automatically' onclick='location.href=\"?get_auto_key=1\"'><br /><a target='_blank' href='https://cleantalk.org/register?platform=magento&email=".$admin_email."&website=".$_SERVER['HTTP_HOST']."'>Click here to get access key manually</a><br />Admin e-mail (".$admin_email.") will be used for registration<br /><a target='__blank' href='http://cleantalk.org/publicoffer' style='color:#e5e5e5'>License agreement</a>";
				$html=str_replace('%LINK_TEXT%',$button,$html);
			}
			else
			{
				$html=str_replace('%LINK_TEXT%',"<a target='__blank' href='http://cleantalk.org/my' >Click here to get anti-spam statistics</a>",$html);
			}
			$transport->setHtml($html);
		}
		
	}
	public function interceptQuery(Varien_Event_Observer $observer)
	{
		if(isset($_GET['close_notice']))
		{
			$config = new Mage_Core_Model_Config();
			$config->saveConfig('general/cleantalk/show_notice', 0, 'default', 0);
			Mage::app()->cleanCache();
			header('Location: .');
			die();
		}
		
		if(isset($_GET['get_auto_key']))
		{
			Mage::getSingleton('core/session', array('name'=>'adminhtml'));
			if(Mage::getSingleton('admin/session')->isLoggedIn())
			{
				require_once 'lib/cleantalk.class.php';
				$admin_email=Mage::getStoreConfig('trans_email/ident_general/email');
				$site=$_SERVER['HTTP_HOST'];
				$result = getAutoKey($admin_email,$site,'magento');
				if ($result)
				{
					$result = json_decode($result, true);
					if (isset($result['data']) && is_array($result['data']))
					{
						$result = $result['data'];
					}
					else if(isset($result['error_no']))
					{
						header('Location: ?cleantalk_message='.$result['error_message']);
						die();
					}
					if(isset($result['auth_key']))
					{
						Mage::app()->cleanCache();
						$config = new Mage_Core_Model_Config();
						$config->saveConfig('general/cleantalk/api_key', $result['auth_key'], 'default', 0);
						Cleantalk_Antispam_Model_Observer::CleantalkTestMessage($result['auth_key']);
					}
					header('Location: .');
					die();
				}
			}
		}
		
		if(isset($_POST['groups']['cleantalk']['fields']['api_key']['value']))
		{
			$new_key=$_POST['groups']['cleantalk']['fields']['api_key']['value'];
			$old_key = Mage::getStoreConfig('general/cleantalk/api_key');
			if($old_key!=$new_key&&$new_key!='')
		    {
		    	Cleantalk_Antispam_Model_Observer::CleantalkTestMessage($new_key);
		    }
		}
		
		//Mage::getSingleton('core/session', array('name'=>'adminhtml'));
		if(isset($_COOKIE['adminhtml']))
		{
			$key=Mage::getStoreConfig('general/cleantalk/api_key');
	        $last_checked=intval(Mage::getStoreConfig('general/cleantalk/last_checked'));
			$last_status=intval(Mage::getStoreConfig('general/cleantalk/is_paid'));
			$new_checked=time();
			
			if($key!='')
			{
				$new_status=$last_status;
	    		if($new_checked-$last_checked>3600)
	    		{
	    			require_once 'lib/cleantalk.class.php';
	    			$url = 'https://api.cleantalk.org';
		    		$dt=Array(
		    			'auth_key'=>$key,
		    			'method_name'=> 'get_account_status');
		    		$result=sendRawRequest($url,$dt,false);
		    		if($result!==null)
		    		{
		    			$result=json_decode($result);
		    			if(isset($result->data)&&isset($result->data->paid))
		    			{
		    				$new_status=intval($result->data->paid);
		    				if($last_status!=1&&$new_status==1)
		    				{
		    					$config = new Mage_Core_Model_Config();
								$config->saveConfig('general/cleantalk/is_paid', '1', 'default', 0);
		    					$config->saveConfig('general/cleantalk/show_notice', '1', 'default', 0);
		    					Mage::app()->cleanCache();
		    				}
		    			}
		    		}
		    		$config = new Mage_Core_Model_Config();
					$config->saveConfig('general/cleantalk/last_checked', $new_checked, 'default', 0);
	    		}
			}
		}
		if(!isset($_COOKIE['adminhtml'])&&sizeof($_POST)>0&&strpos($_SERVER['REQUEST_URI'],'login')===false&&strpos($_SERVER['REQUEST_URI'],'forgotpassword')===false)
		{
		    $isCustomForms = Mage::getStoreConfig('general/cleantalk/custom_forms');
		    if($isCustomForms==1)
		    {
			$sender_email = null;
			$message = '';
			Cleantalk_Antispam_Model_Observer::cleantalkGetFields($sender_email,$message,$_POST);
			if($sender_email!==null)
			{
				$aMessage = array();
				$aMessage['type'] = 'comment';
				$aMessage['sender_email'] = $sender_email;
				$aMessage['sender_nickname'] = '';
				$aMessage['message_title'] = '';
				$aMessage['message_body'] = $message;
				$aMessage['example_title'] = '';
				$aMessage['example_body'] = '';
				$aMessage['example_comments'] = '';
				
				$model = Mage::getModel('antispam/api');
				$aResult = $model->CheckSpam($aMessage, FALSE);
				
				if(isset($aResult) && is_array($aResult))
				{
					if($aResult['errno'] == 0)
					{
						if($aResult['allow'] == 0)
						{
							if (preg_match('//u', $aResult['ct_result_comment']))
							{
								$comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $aResult['ct_result_comment']);
								$comment_str = preg_replace('/<[^<>]*>/iu', '', $comment_str);
							}
							else
							{
								$comment_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/i', '', $aResult['ct_result_comment']);
								$comment_str = preg_replace('/<[^<>]*>/i', '', $comment_str);
							}
							Mage::getModel('antispam/api')->CleantalkDie($comment_str);
						}
					}
				}
			}
		    }
		}
	}
	
	
	/*
	* Sends test message when api key is changed
	*/
	public function CleantalkTestMessage($key)
	{
		require_once 'lib/cleantalk.class.php';
    		$url = 'http://moderate.cleantalk.org/api2.0';
    		$dt=Array(
		    'auth_key'=>$_POST['cleantalk_authkey'],
		    'method_name' => 'send_feedback',
		    'feedback' => 0 . ':' . 'magento-121');
		$result=sendRawRequest($url,$dt,true);
		return $result;
	}
	
	/**
     * Get all fields from array
     * @param string email variable
     * @param string message variable
     * @param array array, containing fields
     */
    
    static function cleantalkGetFields(&$email,&$message,$arr)
	{
		$is_continue=true;
		foreach($arr as $key=>$value)
		{
			if(strpos($key,'ct_checkjs')!==false)
			{
				$email=null;
				$message='';
				$is_continue=false;
			}
		}
		if($is_continue)
		{
			foreach($arr as $key=>$value)
			{
				if(!is_array($value))
				{
					if ($email === null && preg_match("/^\S+@\S+\.\S+$/", $value))
			    	{
			            $email = $value;
			        }
			        else
			        {
			        	$message.="$value\n";
			        }
				}
				else
				{
					Cleantalk_Antispam_Model_Observer::cleantalkGetFields($email,$message,$value);
				}
			}
		}
	}
}

?>