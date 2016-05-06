<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
  
  Most heavily borrowed from specials.php
  Created June 2014 for addition of 
  WS5 Wholesale Addon
  webmaster@wsfive.com
  http://wsfive.com
*/

  require('includes/application_top.php');
  
  /* adding here as not called anywhere else in admin yet */
  define('TABLE_PRODUCTS_TO_WHOLESALE', 'products_to_wholesale');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'insert':
        $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
        $products_price = tep_db_prepare_input($HTTP_POST_VARS['products_price']);
        $wholesale_price = tep_db_prepare_input($HTTP_POST_VARS['wholesale_price']);
		$wholesale_weight = tep_db_prepare_input($HTTP_POST_VARS['wholesale_weight']);
		$wholesale_note = tep_db_prepare_input($HTTP_POST_VARS['wholesale_note']);

        if (substr($wholesale_price, -1) == '%') {
          $new_wholesale_insert_query = tep_db_query("select products_id, products_price from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
          $new_wholesale_insert = tep_db_fetch_array($new_wholesale_insert_query);

          $products_price = $new_wholesale_insert['products_price'];
          $wholesale_price = ($products_price - (($wholesale_price / 100) * $products_price));
        }


        tep_db_query("insert into " . TABLE_PRODUCTS_TO_WHOLESALE . " (products_id, wholesale_price, wholesale_weight, wholesale_note) values ('" . (int)$products_id . "', '" . tep_db_input($wholesale_price) . "', " . (tep_not_null($wholesale_weight) ? "'" . tep_db_input($wholesale_weight) . "'" : 'null') . ", " . (tep_not_null($wholesale_note) ? "'" . tep_db_input($wholesale_note) . "'" : 'null') . ")");

        tep_redirect(tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page']));
        break;
      case 'update':
        $wholesale_id = tep_db_prepare_input($HTTP_POST_VARS['wholesale_id']);
        $products_price = tep_db_prepare_input($HTTP_POST_VARS['products_price']);
        $wholesale_price = tep_db_prepare_input($HTTP_POST_VARS['wholesale_price']);
        $wholesale_weight = tep_db_prepare_input($HTTP_POST_VARS['wholesale_weight']);
		$wholesale_note = tep_db_prepare_input($HTTP_POST_VARS['wholesale_note']);

        if (substr($wholesale_price, -1) == '%') $wholesale_price = ($products_price - (($wholesale_price / 100) * $products_price));


        tep_db_query("update " . TABLE_PRODUCTS_TO_WHOLESALE . " set wholesale_price = '" . tep_db_input($wholesale_price) . "', wholesale_weight = " . (tep_not_null($wholesale_weight) ? "'" . tep_db_input($wholesale_weight) . "'" : 'null') . ", wholesale_note = " . (tep_not_null($wholesale_note) ? "'" . tep_db_input($wholesale_note) . "'" : 'null') . " where wholesale_id = '" . (int)$wholesale_id . "'");

        tep_redirect(tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wholesale_id));
        break;
      case 'deleteconfirm':
        $wholesale_id = tep_db_prepare_input($HTTP_GET_VARS['wID']);

        tep_db_query("delete from " . TABLE_PRODUCTS_TO_WHOLESALE . " where wholesale_id = '" . (int)$wholesale_id . "'");

        tep_redirect(tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page']));
        break;
    }
  }

  require(DIR_WS_INCLUDES . 'template_top.php');
?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
<?php
  if ( ($action == 'new') || ($action == 'edit') ) {
    $form_action = 'insert';
    if ( ($action == 'edit') && isset($HTTP_GET_VARS['wID']) ) {
      $form_action = 'update';

      $product_query = tep_db_query("select p.products_id, pd.products_name, p.products_price, w.wholesale_price, w.wholesale_weight, w.wholesale_note from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_WHOLESALE . " w where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = w.products_id and w.wholesale_id = '" . (int)$HTTP_GET_VARS['wID'] . "'");
      $product = tep_db_fetch_array($product_query);

      $wInfo = new objectInfo($product);
    } else {
      $wInfo = new objectInfo(array());
// create an array of products for wholesale, which will be excluded from the pull down menu of products
// (when creating a new product for wholesale)
      $wholesale_array = array();
      $wholesale_query = tep_db_query("select p.products_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_WHOLESALE . " w where w.products_id = p.products_id");
      while ($wholesale = tep_db_fetch_array($wholesale_query)) {
        $wholesale_array[] = $wholesale['products_id'];
      }
    }
?>
      <tr><form name="new_wholesale" <?php echo 'action="' . tep_href_link(FILENAME_WHOLESALE, tep_get_all_get_params(array('action', 'info', 'wID')) . 'action=' . $form_action) . '"'; ?> method="post"><?php if ($form_action == 'update') echo tep_draw_hidden_field('wholesale_id', $HTTP_GET_VARS['wID']); ?>
        <td><br /><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_WHOLESALE_PRODUCT; ?>&nbsp;</td>
            <td class="main"><?php echo (isset($wInfo->products_name)) ? $wInfo->products_name . ' <small>(' . $currencies->format($wInfo->products_price) . ')</small>' : tep_draw_products_pull_down('products_id', 'style="font-size:10px"', $wholesale_array); echo tep_draw_hidden_field('products_price', (isset($wInfo->products_price) ? $wInfo->products_price : '')); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_WHOLESALE_PRICE; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('wholesale_price', (isset($wInfo->wholesale_price) ? $wInfo->wholesale_price : '')); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_WHOLESALE_WEIGHT; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('wholesale_weight', (isset($wInfo->wholesale_weight) ? $wInfo->wholesale_weight : '')); ?></td>
          </tr>
           <tr>
            <td class="main"><?php echo TEXT_WHOLESALE_NOTE; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('wholesale_note', (isset($wInfo->wholesale_note) ? $wInfo->wholesale_note : '')); ?></td>
          </tr>
        </table>

        </td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><br /><?php echo TEXT_WHOLESALE_PRICE_TIP; ?></td>
            <td class="smallText" align="right" valign="top"><br /><?php echo tep_draw_button(IMAGE_SAVE, 'disk', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . (isset($HTTP_GET_VARS['wID']) ? '&wID=' . $HTTP_GET_VARS['wID'] : ''))); ?></td>
          </tr>
        </table></td>
      </form></tr>
<?php
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRODUCTS_PRICE; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $wholesale_query_raw = "select p.products_id, pd.products_name, p.products_price, w.wholesale_id, w.wholesale_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_WHOLESALE . " w, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = w.products_id order by pd.products_name";
	
    $wholesale_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $wholesale_query_raw, $wholesale_query_numrows);
    $wholesale_query = tep_db_query($wholesale_query_raw);
    while ($wholesale = tep_db_fetch_array($wholesale_query)) {
      if ((!isset($HTTP_GET_VARS['wID']) || (isset($HTTP_GET_VARS['wID']) && ($HTTP_GET_VARS['wID'] == $wholesale['wholesale_id']))) && !isset($wInfo)) {
        $products_query = tep_db_query("select products_image from " . TABLE_PRODUCTS . " where products_id = '" . (int)$wholesale['products_id'] . "'");
        $products = tep_db_fetch_array($products_query);
        $wInfo_array = array_merge($wholesale, $products);
        $wInfo = new objectInfo($wInfo_array);
      }

      if (isset($wInfo) && is_object($wInfo) && ($wholesale['wholesale_id'] == $wInfo->wholesale_id)) {
        echo '                  <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wInfo->wholesale_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '                  <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wholesale['wholesale_id']) . '\'">' . "\n";
      }
?>
                <td  class="dataTableContent"><?php echo $wholesale['products_name']; ?></td>
                <td  class="dataTableContent" align="right"><span class="oldPrice"><?php echo $currencies->format($wholesale['products_price']); ?></span> <span class="specialPrice"><?php echo $currencies->format($wholesale['wholesale_price']); ?></span></td>
                
                <td class="dataTableContent" align="right"><?php if (isset($wInfo) && is_object($wInfo) && ($wholesale['wholesale_id'] == $wInfo->wholesale_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wholesale['wholesale_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
      </tr>
<?php
    }
?>
              <tr>
                <td colspan="4"><table border="0" width="100%" cellpadding="0"cellspacing="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $wholesale_split->display_count($wholesale_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                    <td class="smallText" align="right"><?php echo $wholesale_split->display_links($wholesale_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
<?php
  if (empty($action)) {
?>
                  <tr>
                    <td class="smallText" colspan="2" align="right"><?php echo tep_draw_button(IMAGE_NEW_PRODUCT, 'plus', tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&action=new')); ?></td>
                  </tr>
<?php
  }
?>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<strong>' . TEXT_INFO_HEADING_DELETE_WHOLESALE . '</strong>');

      $contents = array('form' => tep_draw_form('wholesale', FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wInfo->wholesale_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br /><strong>' . $wInfo->products_name . '</strong>');
      $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DELETE, 'trash', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wInfo->wholesale_id)));
      break;
    default:
      if (is_object($wInfo)) {
        $heading[] = array('text' => '<strong>' . $wInfo->products_name . '</strong>');

        $contents[] = array('align' => 'center', 'text' => tep_draw_button(IMAGE_EDIT, 'document', tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wInfo->wholesale_id . '&action=edit')) . tep_draw_button(IMAGE_DELETE, 'trash', tep_href_link(FILENAME_WHOLESALE, 'page=' . $HTTP_GET_VARS['page'] . '&wID=' . $wInfo->wholesale_id . '&action=delete')));
        $contents[] = array('align' => 'center', 'text' => '<br />' . tep_info_image($wInfo->products_image, $wInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT));
        $contents[] = array('text' => '<br />' . TEXT_INFO_ORIGINAL_PRICE . ' ' . $currencies->format($wInfo->products_price));
        $contents[] = array('text' => '' . TEXT_INFO_NEW_PRICE . ' ' . $currencies->format($wInfo->wholesale_price));
        $contents[] = array('text' => '' . TEXT_INFO_PERCENTAGE . ' ' . number_format(100 - (($wInfo->wholesale_price / $wInfo->products_price) * 100)) . '%');
      }
      break;
  }
  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
}
?>
          </tr>
        </table></td>
      </tr>
    </table>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
