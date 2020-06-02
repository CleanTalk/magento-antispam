<?php

class Cleantalk_Antispam_Model_Observer
{
	public function interceptOutput(Varien_Event_Observer $observer)
	{
		$transport = $observer->getTransport();
		$html = $transport->getHtml();
		if(strpos($html,'<div class="middle"')!==false && Mage::app()->getRequest()->getParam('cleantalk_message')!== null)
		{
			$html=str_replace('<div class="middle"','<div class="notification-global  notification-global-error"><b>CleanTalk error: '.Mage::app()->getRequest()->getParam('cleantalk_message').'</b></div><div class="middle"',$html);
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
				$user = Mage::getSingleton('admin/session'); 
				$admin_email = $user->getUser()->getEmail();
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
		if (strpos($_SERVER['PHP_SELF'],'/downloader/') === false)
		{
			Mage::getSingleton('core/session', array('name'=>'adminhtml'));
			$key=Mage::getStoreConfig('general/cleantalk/api_key');	
			if ($key !== '')
			{
				Cleantalk_Antispam_Model_Observer::apbct_cookie();				
			}	

			if(Mage::getSingleton('admin/session')->isLoggedIn() && strpos($_SERVER['PHP_SELF'],'system_config') !== false)
			{				
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
				if(Mage::app()->getRequest()->getParam('close_notice'))
				{
					$config = new Mage_Core_Model_Config();
					$config->saveConfig('general/cleantalk/show_notice', 0, 'default', 0);
					Mage::app()->cleanCache();
					header('Location: .');
					return false;
				}
				if(Mage::app()->getRequest()->getParam('get_auto_key'))
				{
					require_once 'lib/cleantalk.class.php';
					$user = Mage::getSingleton('admin/session'); 
					$admin_email = $user->getUser()->getEmail();
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
							return false;
						}
						if(isset($result['auth_key']))
						{
							Mage::app()->cleanCache();
							$config = new Mage_Core_Model_Config();
							$config->saveConfig('general/cleantalk/api_key', $result['auth_key'], 'default', 0);
							Cleantalk_Antispam_Model_Observer::CleantalkTestMessage($result['auth_key']);
						}
						header('Location: .');
						return false;
						
					}
				}
				if(Mage::app()->getRequest()->getPost()['groups']['cleantalk']['fields']['api_key']['value'])
				{
					$new_key=Mage::app()->getRequest()->getPost()['groups']['cleantalk']['fields']['api_key']['value'];
					if($key!=$new_key&&$new_key!='')
				    {
				    	Cleantalk_Antispam_Model_Observer::CleantalkTestMessage($new_key);
				    }
				}								
			}	
		
			
			if(!Mage::getSingleton('admin/session')->isLoggedIn() && sizeof(Mage::app()->getRequest()->getPost())>0 && (strpos($_SERVER['PHP_SELF'],'/account/create') === false || strpos($_SERVER['REQUEST_URI'],'/account/forgotpassword') === false || strpos($_SERVER['PHP_SELF'],'/account/login') === false || strpos($_SERVER['REQUEST_URI'],'/account/login') === false || strpos($_SERVER['REQUEST_URI'],'/account/create') === false))
			{

			    $isCustomForms = Mage::getStoreConfig('general/cleantalk/custom_forms');
			    if($isCustomForms==1)
			    {
				$ct_fields = Cleantalk_Antispam_Model_Observer::cleantalkGetFields($_POST);
				if($ct_fields)
				{
					$aMessage = array();
					$aMessage['type'] = 'comment';
					$aMessage['sender_email'] = ($ct_fields['email'] ? $ct_fields['email'] : '');
					$aMessage['sender_nickname'] = ($ct_fields['nickname'] ? $ct_fields['nickname'] : '');
					$aMessage['message_title'] = '';
					$aMessage['message_body'] =($ct_fields['message'] ? $ct_fields['message'] : '');
					$aMessage['example_title'] = '';
					$aMessage['example_body'] = '';
					$aMessage['example_comments'] = '';
					$aMessage['send_request'] = ($ct_fields['message'] || $ct_fields['email']) ? true: false;
					$model = Mage::getModel('antispam/api');
					if ($aMessage['send_request'])
					{
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
		    'auth_key'=>Mage::app()->getRequest()->getPost()['cleantalk_authkey'],
		    'method_name' => 'send_feedback',
		    'feedback' => 0 . ':' . 'magento-127');
		$result=sendRawRequest($url,$dt,true);
		return $result;
	}
	
	/**
     * Get all fields from array
     * @param string email variable
     * @param string message variable
     * @param array array, containing fields
     */
    
    static function cleantalkGetFields($arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = '')
	{
        //Skip request if fields exists
        $skip_params = array(
            'ipn_track_id',     // PayPal IPN #
            'txn_type',         // PayPal transaction type
            'payment_status',   // PayPal payment status
            'ccbill_ipn',       // CCBill IPN 
            'ct_checkjs',       // skip ct_checkjs field
            'api_mode',         // DigiStore-API
            'loadLastCommentId' // Plugin: WP Discuz. ticket_id=5571
        );
        
        // Fields to replace with ****
        $obfuscate_params = array(
            'password',
            'password_confirmation',
            'pass',
            'pwd',
            'pswd'
        );
        
        // Skip feilds with these strings and known service fields
        $skip_fields_with_strings = array( 
            // Common
            'ct_checkjs', //Do not send ct_checkjs
            'nonce', //nonce for strings such as 'rsvp_nonce_name'
            'security',
            // 'action',
            'http_referer',
            'timestamp',
            'captcha',
            // Formidable Form
            'form_key',
            'submit_entry',
            // Custom Contact Forms
            'form_id',
            'ccf_form',
            'form_page',
            // Qu Forms
            'iphorm_uid',
            'form_url',
            'post_id',
            'iphorm_ajax',
            'iphorm_id',
            // Fast SecureContact Froms
            'fs_postonce_1',
            'fscf_submitted',
            'mailto_id',
            'si_contact_action',
            // Ninja Forms
            'formData_id',
            'formData_settings',
            'formData_fields_\d+_id',
            'formData_fields_\d+_files.*',      
            // E_signature
            'recipient_signature',
            'output_\d+_\w{0,2}',
            // Contact Form by Web-Settler protection
            '_formId',
            '_returnLink',
            // Social login and more
            '_save',
            '_facebook',
            '_social',
            'user_login-',
            'submit',
            'form_token',
            'creation_time',
            'uenc',
            'product',

        );
                
        foreach($skip_params as $value){
            if(array_key_exists($value,$_POST))
            {
                $contact = false;
            }
        } unset($value);
            
        if(count($arr)){
            foreach($arr as $key => $value){
                
                if(gettype($value)=='string'){
                    $decoded_json_value = json_decode($value, true);
                    if($decoded_json_value !== null)
                    {
                        $value = $decoded_json_value;
                    }
                }
                
                if(!is_array($value) && !is_object($value)){
                    
                    if (in_array($key, $skip_params, true) && $key != 0 && $key != '' || preg_match("/^ct_checkjs/", $key))
                    {
                        $contact = false;
                    }
                    
                    if($value === '')
                    {
                        continue;
                    }
                    
                    // Skipping fields names with strings from (array)skip_fields_with_strings
                    foreach($skip_fields_with_strings as $needle){
                        if (preg_match("/".$needle."/", $prev_name.$key) == 1){
                            continue(2);
                        }
                    }unset($needle);
                    // Obfuscating params
                    foreach($obfuscate_params as $needle){
                        if (strpos($key, $needle) !== false){
                            $value = Cleantalk_Antispam_Model_Observer::obfuscate_param($value);
                        }
                    }unset($needle);
                    

                    // Decodes URL-encoded data to string.
                    $value = urldecode($value); 

                    // Email
                    if (!$email && preg_match("/^\S+@\S+\.\S+$/", $value)){
                        $email = $value;
                        
                    // Names
                    }elseif (preg_match("/name/i", $key)){
                        
                        preg_match("/(first.?name)?(name.?first)?(forename)?/", $key, $match_forename);
                        preg_match("/(last.?name)?(family.?name)?(second.?name)?(surname)?/", $key, $match_surname);
                        preg_match("/(nick.?name)?(user.?name)?(nick)?/", $key, $match_nickname);
                        
                        if(count($match_forename) > 1)
                        {
                            $nickname['first'] = $value;
                        }
                        elseif(count($match_surname) > 1)
                        {
                            $nickname['last'] = $value;
                        }
                        elseif(count($match_nickname) > 1)
                        {
                            $nickname['nick'] = $value;
                        }
                        else
                        {
                            $message[$prev_name.$key] = $value;
                        }
                    
                    // Subject
                    }elseif ($subject === null && preg_match("/subject/i", $key)){
                        $subject = $value;
                    
                    // Message
                    }else{
                        $message[$prev_name.$key] = $value;                 
                    }
                    
                }elseif(!is_object($value)){
                    
                    $prev_name_original = $prev_name;
                    $prev_name = ($prev_name === '' ? $key.'_' : $prev_name.$key.'_');
                    
                    $temp = Cleantalk_Antispam_Model_Observer::cleantalkGetFields($value, $message, $email, $nickname, $subject, $contact, $prev_name);
                    
                    $message    = $temp['message'];
                    $email      = ($temp['email']       ? $temp['email'] : null);
                    $nickname   = ($temp['nickname']    ? $temp['nickname'] : null);                
                    $subject    = ($temp['subject']     ? $temp['subject'] : null);
                    if($contact === true)
                    {
                        $contact = ($temp['contact'] === false ? false : true);
                    }
                    $prev_name  = $prev_name_original;
                }
            } unset($key, $value);
        }
                
        //If top iteration, returns compiled name field. Example: "Nickname Firtsname Lastname".
        if($prev_name === ''){
            if(!empty($nickname)){
                $nickname_str = '';
                foreach($nickname as $value){
                    $nickname_str .= ($value ? $value." " : "");
                }unset($value);
            }
            $nickname = $nickname_str;
        }
        
        $return_param = array(
            'email'     => $email,
            'nickname'  => $nickname,
            'subject'   => $subject,
            'contact'   => $contact,
            'message'   => $message
        );  
        return $return_param;
	}
	    /**
    * Masks a value with asterisks (*)
    * @return string
    */
    static function obfuscate_param($value = null) {
        if ($value && (!is_object($value) || !is_array($value))) {
            $length = strlen($value);
            $value = str_repeat('*', $length);
        }
        return $value;
    } 
	public function apbct_cookie()
	{
	      
	  // Cookie names to validate
	  $cookie_test_value = array(
	      'cookies_names' => array(),
	      'check_value' => Mage::getStoreConfig('general/cleantalk/api_key'),
	  );  

	  // Submit time
	  $apbct_timestamp = time();
	  setcookie('apbct_timestamp', $apbct_timestamp, 0, '/');
	  $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
	  $cookie_test_value['check_value'] .= $apbct_timestamp;

	  //Previous referer
	  if(!empty($_SERVER['HTTP_REFERER'])){
	      setcookie('apbct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
	      $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
	      $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
	  }

	   // Cookies test
	  $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
	  setcookie('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');

	}
}
