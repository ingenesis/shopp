<?php
/**
 * AdminOrderNotesBox.php
 *
 * Renders the order notes metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderNotesBox extends ShoppAdminMetabox {

	protected $id = 'order-notes';
	protected $view = 'orders/notes.php';

	protected function title() {
		return Shopp::__('Notes');
	}

	protected function init() {
		add_filter('shopp_order_note', 'esc_html');
		add_filter('shopp_order_note', 'wptexturize');
		add_filter('shopp_order_note', 'convert_chars');
		add_filter('shopp_order_note', 'make_clickable');
		add_filter('shopp_order_note', 'force_balance_tags');
		add_filter('shopp_order_note', 'convert_smilies');
		add_filter('shopp_order_note', 'wpautop');

		$Purchase = $this->references['Purchase'];
		$this->references['Notes'] = new ObjectMeta($Purchase->id, 'purchase', 'order_note');
	}

	protected function ops() {
		return array(
			'add',
			'edit',
			'delete',
			'send'
		);
	}

	public function add() {
		if ( ! $this->form('note') ) return;
		$Purchase = $this->references['Purchase'];

		$user = wp_get_current_user();
		$Note = new ShoppMetaObject();
		$Note->parent = $Purchase->id;
		$Note->context = 'purchase';
		$Note->type = 'order_note';
		$Note->name = 'note';
		$Note->value = new stdClass();
		$Note->value->author = $user->ID;
		$Note->value->message = stripslashes($this->form('note'));
		$Note->value->sent = ( 1 == $this->form('send-note') );

		$Note->save();

		if ( ! $Note->value->sent )
			$this->notice(Shopp::__('Added note.'));
	}

	public function delete() {
		if ( ! $this->form('delete-note') ) return;

		$id = key($this->form('delete-note'));

		$Note = new ShoppMetaObject(array('id' => $id, 'type' => 'order_note'));
		if ( ! $Note->exists() ) return;

		$Note->delete();

		$this->notice(Shopp::__('Note deleted.'));
	}

	public function edit() {
		if ( ! $this->form('edit-note') ) return;
		$edited = $this->form('note-editor');

		$id = key($edited);
		if ( empty($edited[ $id ]) ) return;

		$Note = new ShoppMetaObject(array('id' => $id, 'type' => 'order_note'));
		if ( ! $Note->exists() ) return;

		$Note->value->message = stripslashes($edited[ $id ]);
		$Note->save();

		$this->notice(Shopp::__('Note updated.'));
	}

	public function send() {

		if ( ! $this->form('send-note') ) return;

		$Purchase = $this->references['Purchase'];
		$user = wp_get_current_user();

		shopp_add_order_event($Purchase->id, 'note', array(
			'note' => $this->form('note'),
			'user' => $user->ID
		));

		$Purchase->load_events();

		$this->notice(Shopp::__('Note sent to <strong>%s</strong>.', $Purchase->email));

	}

}