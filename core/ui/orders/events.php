<?php
/**
 * OrderEvent Renderer system
 *
 * Description…
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, June 28, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage shopp
 **/

add_action('shopp_order_manager_event',array('OrderEventRenderer','display'));

/**
 * OrderEventDisplay
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class OrderEventRenderer {

	protected $Event;

	var $markup = array();

	/**
	 * OrderEventRenderer constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function __construct (OrderEventMessage $Event) {
		$this->Event = $Event;
		$this->load();
	}

	function load () {
		if (empty($this->Event)) return;

		// Remap event object information for formatting
		$map = array(
			'type' => 'name',
			'amount' => 'amount',
			'date' => 'created'
		);
		$message = array_combine($this->Event->_xcols,$this->Event->_xcols);
		if (!empty($message)) $map = array_merge($map,$message);

		foreach ($map as $property => $data)
			$this->$property = $this->Event->$data;

	}

	static function renderer (OrderEventMessage $Event) {
		$Renderer = get_class($Event).'Renderer';
		if ( ! class_exists($Renderer) ) $Renderer = __CLASS__;

		return new $Renderer($Event);
	}

	static function display (OrderEventMessage $Event) {
		$UI = self::renderer($Event);
		$UI->render();
	}

	function content () {
		$_ = array();
		$_['date'] = $this->date();
		$_['name'] = $this->name();
		$_['details'] = $this->details();
		$_['amount'] = $this->amount();
		return $_;
	}

	function render () {
		$markup = $this->content();

		// Format into table cells
		foreach ($markup as $name => &$content)
			$content = $this->cell($content,$name);

		// Format into table row
		$markup = $this->row($markup);
		echo $markup;
	}

	function cell ($content,$name) {
		return '<td class="'.$name.'">'.$content.'</td>';
	}

	function row ($content) {
		return '<tr class="'.str_replace('-',' ',$this->type).'">'.join('',$content).'</tr>';
	}

	function strong ($content) {
		return '<strong>'.$content.'</strong>';
	}

	function name () {
		return $this->type;
	}

	function details () {
		return '';
	}

	function amount () {
		return '';
	}

	function date () {
		$ts = current_time('timestamp');
		$today = mktime(0,0,0,date('m',$ts),date('d',$ts),date('Y',$ts));

		$date_format = get_option('date_format');

		// Remove year from the date format if it's the current year
		if (date('Y',$this->date) == date('Y',$today))
			$date_format = preg_replace('/([^\d\w]\s[LoYy])/','',$date_format);

		$date = date($date_format,$this->date);
		$time = date(get_option('time_format'),$this->date);

		$weekdays = array(
			__('Sunday','Shopp'),
			__('Monday','Shopp'),
			__('Tuesday','Shopp'),
			__('Wednesday','Shopp'),
			__('Thursday','Shopp'),
			__('Friday','Shopp'),
			__('Saturday','Shopp')
		);

		if ($this->date > $today-(86400*7))
			$date = $weekdays[date('w',$this->date)];
		if ($this->date > $today-86400)
			$date = __('Yesterday','Shopp');
		if ($this->date > $today)
			$date = __('Today','Shopp');

		return '<span class="day">'.$date.'</span> <span class="time">'.$time.'</span>';
	}

} // END class OrderEventDisplay

class TxnOrderEventRenderer extends OrderEventRenderer {

	var $credit = false;
	var $debit = false;

	function __construct (OrderEventMessage $Event) {
		parent::__construct($Event);

		if (isset($Event->transactional)) {
			$this->credit = $Event->credit;
			$this->debit = $Event->debit;
		}
	}

	function name () {
		return sprintf(__('Transaction %s successful','Shopp'),$this->type);
	}

	function details () {
		$details = array();

		if (isset($this->paymethod) && !empty($this->paymethod)) {
			$payment = $this->paymethod;
			if (!empty($this->payid)) $payment .= " ($this->paytype $this->payid)";
			$details[] = $payment;
		}
		if (isset($this->txnid) && !empty($this->txnid))
			$details[] = sprintf(__('Transaction: %s','Shopp'),$this->txnid);

		return join(' | ',$details);
	}

	function amount() {
		if ($this->debit) $amount = money($this->amount);
		else $amount = '-'.money($this->amount);
		return $amount;
	}

}

class FailureOrderEventRender extends OrderEventRenderer {

	function details () {
		return $this->message;
	}

}

class TxnFailOrderEventRenderer extends TxnOrderEventRenderer {

	function name () {
		return sprintf(__('Transaction %s failed','Shopp'),$this->type);
	}

	function details () {
		return $this->message;
	}

	function amount () {
		if ($this->credit) return parent::amount();
		return parent::amount();

	}

}

class InvoicedOrderEventRenderer extends TxnOrderEventRenderer {

	function name () {
		return __('Order invoiced','Shopp');
	}

}

class AuthOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Payment authorization','Shopp');
	}

}

class AuthedOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Payment authorized','Shopp');
	}

	function details () {
		$details = array();

		if (isset($this->paymethod) && !empty($this->paymethod)) {
			$payment = $this->paymethod;
			if (!empty($this->payid)) $payment .= " ($this->paytype $this->payid)";
			$details[] = $payment;
		}
		if (isset($this->txnid) && !empty($this->txnid))
			$details[] = sprintf(__('Transaction: %s','Shopp'),$this->txnid);

		return join(' | ',$details);
	}

}

class AuthFailOrderEventRenderer extends FailureOrderEventRender {

	function name () {
		return __('Authorization failed','Shopp');
	}

}

class SaleOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Payment authorization & capture','Shopp');
	}

}

class CaptureOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Charge initiated','Shopp');
	}

	function details () {
		if ( (int)$this->user > 0 ) {

			$user = get_user_by('id', $this->user);

			return Shopp::__('by %s', sprintf(' <a href="%s">%s</a> (<a href="%s">%s</a>)',
				"mailto:$user->user_email?subject=RE: Order #{$this->Event->order}",
				"$user->user_firstname $user->user_lastname",
				add_query_arg(array('user_id'=>$this->user),
				admin_url('user-edit.php')),$user->user_login
			));

		}

		return Shopp::__('by %s', $this->user);

	}

}

class AmountVoidedEventRenderer extends TxnOrderEventRenderer {

	function name () {
		return __('Amount voided','Shopp');
	}

}

class CapturedOrderEventRenderer extends TxnOrderEventRenderer {

	function name () {
		return __('Payment received','Shopp');
	}

}

class CaptureFailOrderEventRenderer extends FailureOrderEventRender {

	function name () {
		return __('Payment failed','Shopp');
	}

}

class RefundOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Refund initiated','Shopp');
	}

	function details () {
		if ( (int)$this->user > 0 ) {

			$user = get_user_by('id', $this->user);

			return sprintf('by <a href="%s">%s</a> (<a href="%s">%s</a>)',
				"mailto:$user->user_email?subject=RE: Order #{$this->Event->order}",
				"$user->user_firstname $user->user_lastname",
				add_query_arg(array('user_id'=>$this->user),
				admin_url('user-edit.php')),$user->user_login
			);

		}

		return sprintf('by %s', $this->user);

	}


}

class RefundedOrderEventRenderer extends TxnOrderEventRenderer {

	function name () {
		return __('Refund completed','Shopp');
	}

	function amount () {
		return parent::amount();
	}

}

class RefundFailOrderEventRenderer extends FailureOrderEventRender {

	function name () {
		return __('Refund failed','Shopp');
	}

	function amount () {
		return parent::amount();
	}

}

class ReviewOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Payment review','Shopp');
	}

	function details () {
		return esc_html($this->note);
	}
}

class VoidOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Order cancellation initiated','Shopp');
	}

	function details () {
		$user = get_user_by('id',$this->user);

		return sprintf('by <a href="%s">%s</a> (<a href="%s">%s</a>)',
			"mailto:$user->user_email?subject=RE: Order #{$this->Event->order}",
			"$user->user_firstname $user->user_lastname",
			add_query_arg(array('user_id'=>$this->user),
			admin_url('user-edit.php')),$user->user_login
		);
	}

}

class VoidedOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Order cancelled','Shopp');
	}

}

class VoidFailOrderEventRenderer extends FailureOrderEventRender {

	function name () {
		return __('Order cancellation failed','Shopp');
	}

}

class ShippedOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Order shipped','Shopp');
	}

	function details () {
		if ( 'NOTRACKING' == $this->carrier_name() )
			return Shopp::__('No Tracking');
		return sprintf('%s: %s',$this->carrier_name(),$this->tracklink());
	}

	function carrier () {
		if (isset($this->Carrier)) return;
		$carriers = Lookup::shipcarriers();
		$this->Carrier = $carriers[$this->carrier];
	}

	function carrier_name () {
		$this->carrier();
		if (isset($this->Carrier->name) && !empty($this->Carrier->name))
			return $this->Carrier->name;
		return $this->carrier;
	}

	function trackurl () {
		$this->carrier();
		if (isset($this->Carrier->trackurl)) {
			$params = explode(',',$this->tracking);
			return vsprintf($this->Carrier->trackurl,$params);
		}
	}

	function tracklink () {
		$url = $this->trackurl();
		if (empty($url)) return $this->tracking;
		return sprintf('<a href="%s" target="_top">%s</a>',$url,$this->tracking);
	}

}

class UnstockOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Inventory updated','Shopp');
	}

	function details () {
		$allocated = $this->Event->allocated();

		$total = 0;
		foreach ($allocated as $items) $total += (int)$items->quantity;

		return sprintf(_n('Allocated %d item from inventory','Allocated %d items from inventory',$total,'Shopp'),$total);
	}

}

class DecryptOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Payment details accessed','Shopp');
	}

	function details () {
		$user = get_user_by('id',$this->user);

		return sprintf('by <a href="%s">%s</a> (<a href="%s">%s</a>)',
			"mailto:$user->user_email?subject=RE: Order #{$this->Event->order}",
			"$user->user_firstname $user->user_lastname",
			add_query_arg(array('user_id'=>$this->user),
			admin_url('user-edit.php')),$user->user_login
		);
	}

}

class DownloadOrderEventRenderer extends OrderEventRenderer {

	function name () {
		$Purchased = new ShoppPurchased($this->purchased);
		$Download = new ProductDownload($this->download);
		return sprintf(__('%s downloaded','Shopp'),'<strong>'.$Purchased->name.' ('.$Download->name.')</strong>');
	}

	function details () {
		$Customer = new ShoppCustomer($this->customer);

		return sprintf('by <a href="%2$s">%1$s</a> from %3$s',
			"$Customer->firstname $Customer->lastname",
			add_query_arg(array('page'=>'','id'=>$this->customer),admin_url('admin.php') ),
			$this->ip
		);
	}

}
class NoteOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Message Sent','Shopp');
	}

	function details () {
		if (!empty($this->user) && (int)$this->user > 0) {
			$user = get_user_by('id',$this->user);
			return sprintf(
				__('by %s (%s)','Shopp'),
				'<a href="'.add_query_arg(array('user_id'=>$this->user),admin_url('user-edit.php')).'">'.$user->display_name.'</a>',
				'<a href="'."mailto:$user->user_email?subject=RE: Order #{$this->Event->order}".'">'.$user->user_email.'</a>'
			);
		}

		return '';
	}

}

class NoticeOrderEventRenderer extends OrderEventRenderer {

	function name () {
		return __('Notice','Shopp');
	}

	function details () {
		$_ = array();
		if (!empty($this->notice)) $_[] = $this->notice;

		if (!empty($this->user) && (int)$this->user > 0) {
			$user = get_user_by('id',$this->user);
			$_[] = sprintf(
				__('by %s (%s)','Shopp'),
				'<a href="'.add_query_arg(array('user_id'=>$this->user),admin_url('user-edit.php')).'">'.$user->display_name.'</a>',
				'<a href="'."mailto:$user->user_email?subject=RE: Order #{$this->Event->order}".'">'.$user->user_email.'</a>'
			);
		}

		return join(' ',$_);
	}

}


?>
