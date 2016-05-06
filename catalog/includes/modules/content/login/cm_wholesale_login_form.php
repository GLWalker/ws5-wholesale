<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
  Just a duplicate of login_form with check for wholesale customer, and install for database
  Created June 2014 for addition of 
  WS5 Wholesale Addon
  webmaster@wsfive.com
  http://wsfive.com
*/

  class cm_wholesale_login_form {
    var $code;
    var $group;
    var $title;
    var $description;
    var $sort_order;
    var $enabled = false;

    function cm_wholesale_login_form() {
      $this->code = get_class($this);
      $this->group = basename(dirname(__FILE__));

      $this->title = MODULE_CONTENT_WHOLESALE_LOGIN_FORM_TITLE;
      $this->description = MODULE_CONTENT_WHOLESALE_LOGIN_FORM_DESCRIPTION;

      if ( defined('MODULE_CONTENT_WHOLESALE_LOGIN_FORM_STATUS') ) {
        $this->sort_order = MODULE_CONTENT_WHOLESALE_LOGIN_FORM_SORT_ORDER;
        $this->enabled = (MODULE_CONTENT_WHOLESALE_LOGIN_FORM_STATUS == 'True');
      }
    }
	

    function execute() {
      global $HTTP_GET_VARS, $HTTP_POST_VARS, $sessiontoken, $login_customer_id, $wholesale_customers_id, $messageStack, $oscTemplate;
	  

      $error = false;

      if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
        $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
        $password = tep_db_prepare_input($HTTP_POST_VARS['password']);

// Check if email exists
        $customer_query = tep_db_query("select customers_id, customers_password from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "' limit 1");
        if (!tep_db_num_rows($customer_query)) {
          $error = true;
        } else {
          $customer = tep_db_fetch_array($customer_query);

// Check that password is good
          if (!tep_validate_password($password, $customer['customers_password'])) {
            $error = true;
          } else {
// set $login_customer_id globally and perform post login code in catalog/login.php
            $login_customer_id = (int)$customer['customers_id'];

// migrate old hashed password to new phpass password
            if (tep_password_type($customer['customers_password']) != 'phpass') {
              tep_db_query("update " . TABLE_CUSTOMERS . " set customers_password = '" . tep_encrypt_password($password) . "' where customers_id = '" . (int)$login_customer_id . "'");
            }
          }  
        }
		
	    // Check if we have a wholesale customer and set session if true
		$wholesale_query = tep_db_query("select wholesale_customers_id from " . TABLE_CUSTOMERS_TO_WHOLESALE . " where customers_id = '" . (int)$login_customer_id . "'");
		
        if (tep_db_num_rows($wholesale_query)) {
          $wholesale_check = tep_db_fetch_array($wholesale_query);
          $wholesale_customers_id = (int)$wholesale_check['wholesale_customers_id'];
          tep_session_register('wholesale_customers_id');
        }
		
      }

      if ($error == true) {
        $messageStack->add('login', MODULE_CONTENT_LOGIN_TEXT_LOGIN_ERROR);
      }

      ob_start();
      include(DIR_WS_MODULES . 'content/' . $this->group . '/templates/login_form.php');
      $template = ob_get_clean();

      $oscTemplate->addContent($template, $this->group);
    }

    function isEnabled() {
      return $this->enabled;
    }

    function check() {
      return defined('MODULE_CONTENT_WHOLESALE_LOGIN_FORM_STATUS');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Wholesale Module', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_STATUS', 'True', 'Do you want to enable the wholesale pricing?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Content Width', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_CONTENT_WIDTH', 'Half', 'Should the content be shown in a full or half width container?', '6', '1', 'tep_cfg_select_option(array(\'Full\', \'Half\'), ', now())");
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Wholesale Tax', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_TAX', '0', 'Do you want to charge tax on wholesale orders? , 0 = false, 1 = True', '6', '0', 'tep_cfg_select_option(array(\'0\', \'1\'), ', now())");
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
	  
	  
	  tep_db_query("CREATE TABLE IF NOT EXISTS customers_to_wholesale (
                      wholesale_customers_id int(11) NOT NULL AUTO_INCREMENT,
                      customers_id int(11) NOT NULL,
                    PRIMARY KEY (wholesale_customers_id)
                    )");
	
	  tep_db_query("CREATE TABLE IF NOT EXISTS products_to_wholesale (
                      wholesale_id int(11) NOT NULL AUTO_INCREMENT,
                      products_id int(11) NOT NULL,
                      wholesale_price decimal(15,4) NOT NULL,
                      wholesale_weight decimal(5,2) DEFAULT NULL,
					  wholesale_note varchar(60) DEFAULT NULL,
                    PRIMARY KEY (wholesale_id)
                    )");
	  
	  
    }

   function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	  /* we could remove the tables previously created - but will not to preserve any data for future use */
    }

    function keys() {
      return array('MODULE_CONTENT_WHOLESALE_LOGIN_FORM_STATUS', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_CONTENT_WIDTH', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_TAX', 'MODULE_CONTENT_WHOLESALE_LOGIN_FORM_SORT_ORDER');
    }
  }
?>
