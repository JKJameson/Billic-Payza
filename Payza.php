<?php
class Payza {
	public $settings = array(
		'description' => 'Accept payments via Payza.',
	);
	function payment_button($params) {
		global $billic, $db;
		$html = '';
		if (get_config('payza_email') == '') {
			return $html;
		}
		if ($billic->user['verified'] == 0 && get_config('payza_require_verification') == 1) {
			return 'verify';
		} else {
			$html.= '<form action="https://secure.payza.com/checkout" method="post">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_merchant" value="' . get_config('payza_email') . '">';
			$html.= '<input type="hidden" name="ap_itemname" value="Invoice #' . $params['invoice']['id'] . '">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_amount" value="' . $params['charge'] . '">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_purchasetype" value="service">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_currency" value="' . get_config('billic_currency_code') . '">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_returnurl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/User/Invoices/ID/' . $params['invoice']['id'] . '/Status/Complete/">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_cancelurl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/User/Invoices/ID/' . $params['invoice']['id'] . '/Status/Cancelled/">' . PHP_EOL;
			$html.= '<input type="hidden" name="ap_alerturl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/Gateway/Payza/">' . PHP_EOL;
			$html.= '<input type="submit" class="btn btn-default" name="submit" value="Pay via Payza">' . PHP_EOL;
			$html.= '</form>';
			// get service if applicable
			$serviceid = false;
			$items = $db->q('SELECT * FROM `invoiceitems` WHERE `invoiceid` = ?', $params['invoice']['id']);
			foreach ($items as $item) {
				if ($item['relid'] > 0) {
					$serviceid = $item['relid'];
				}
			}
			if ($serviceid !== false) {
				// get days of billing cycle
				$billingcycle = $db->q('SELECT `billingcycle` FROM `services` WHERE `id` = ?', $serviceid);
				$billingcycle = $billingcycle[0]['billingcycle'];
				if (empty($billingcycle)) {
					$serviceid = false;
				} else {
					$billingcycle = $db->q('SELECT * FROM `billingcycles` WHERE `name` = ?', $billingcycle);
					$billingcycle = $billingcycle[0];
					if (empty($billingcycle)) {
						$serviceid = false;
					} else {
						$days = ceil($billingcycle['seconds'] / 60 / 60 / 24);
						if ($days < 1) {
							$serviceid = false;
						}
					}
				}
			}
			if ($serviceid !== false) {
				// Subscription payment button
				$html.= '<form action="https://secure.payza.com/checkout" method="post">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_merchant" value="' . get_config('payza_email') . '">';
				$html.= '<input type="hidden" name="ap_itemname" value="Invoice #' . $params['invoice']['id'] . '">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_amount" value="' . $params['charge'] . '">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_purchasetype" value="subscription">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_currency" value="' . get_config('billic_currency_code') . '">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_returnurl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/User/Invoices/ID/' . $params['invoice']['id'] . '/Status/Complete/">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_cancelurl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/User/Invoices/ID/' . $params['invoice']['id'] . '/Status/Cancelled/">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_alerturl" value="http' . (get_config('billic_ssl') == 1 ? 's' : '') . '://' . get_config('billic_domain') . '/Gateway/Payza/">' . PHP_EOL;
				$html.= '<input type="submit" class="btn btn-default" name="submit" value="Subscribe via Payza">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_timeunit" value="Day">' . PHP_EOL;
				$html.= '<input type="hidden" name="ap_periodlength" value="' . $days . '">' . PHP_EOL;
				$html.= '</form>';
			}
		}
		return $html;
	}
	function payment_callback() {
		global $billic, $db;
		return 'Feature not done yet';
	}
	function settings($array) {
		global $billic, $db;
		if (empty($_POST['update'])) {
			echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="Payza"><table class="table table-striped">';
			echo '<tr><th>Setting</th><th>Value</th></tr>';
			echo '<tr><td>Require Verification</td><td><input type="checkbox" name="payza_require_verification" value="1"' . (get_config('payza_require_verification') == 1 ? ' checked' : '') . '></td></tr>';
			echo '<tr><td>Payza Email</td><td><input type="text" class="form-control" name="payza_email" value="' . safe(get_config('payza_email')) . '"></td></tr>';
			echo '<tr><td colspan="2" align="center"><input type="submit" class="btn btn-default" name="update" value="Update &raquo;"></td></tr>';
			echo '</table></form>';
		} else {
			if (empty($billic->errors)) {
				set_config('payza_require_verification', $_POST['payza_require_verification']);
				set_config('payza_email', $_POST['payza_email']);
				$billic->status = 'updated';
			}
		}
	}
}
