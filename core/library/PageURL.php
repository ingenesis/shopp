<?php
/**
 * PageURL.php
 *
 * Provides Shopp Page URLs that work in WordPress
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Package
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPageURL {

	private $url = '';

	private $baseurl = '';
	private $page = false;
	private $scheme = null;
	private $query = array();
	private $requests = array();
	private $paths = array();
	private $prettyurls = false;

	public function __construct( $page = 'catalog', $request = false, $secure = null ) {
		$this->prettyurls();
		$this->basepath();

		$this->page($page);
		$this->request($request);
		$this->scheme($secure);

		$this->baseurl();
		$this->build();
	}

	public function url() {
		return apply_filters('shopp_url', $this->url);
	}

	protected function prettyurls() {
		global $wp_rewrite;

		$this->prettyurls = $wp_rewrite->using_permalinks();

		// Support IIS index.php/ prefixed permalinks
		if ( $wp_rewrite->using_index_permalinks() )
			$this->paths[] = 'index.php';
	}

	protected function basepath() {
		$this->paths[] = ShoppPages()->baseslug();
	}

	protected function page( $page ) {
		if ( ! $page )
			return;

		if ( 'images' == $page )
			return $this->page = $this->paths[] = 'images';

		$Page = ShoppPages()->get($page);
		if ( ! method_exists($Page, 'slug') )
			return;

		if ( 'catalog' == $page )
			unset($this->paths[0]);

		$this->page = $page;
		$this->paths['shopp_page'] = $Page->slug();
	}

	protected function request( $request ) {
		if ( empty($request) )
			return;

		if ( ! is_array($request) )
			$this->requests = array(urldecode($request));
		else $this->requests = $request;

		$this->paths = array_merge($this->paths, $this->requests);

		if ( 'images' == $this->page ) {
			$this->requests = array('siid' => $request);
			unset($this->paths['shopp_page']); // No basepath for images
		}
	}

	protected function scheme( $secure ) {
		if ( $secure === false || SHOPP_NOSSL)
			$this->scheme = 'http'; // Contextually forced off
 		elseif ( $secure || is_ssl() )
			$this->scheme = 'https'; // HTTPS required
	}

	protected function baseurl() {
		$baseurl = home_url(false, $this->scheme);
		$query = false;
		if ( false !== strpos($baseurl, '?') )
			list($baseurl, $query) = explode('?', $baseurl);

		$this->baseurl = $baseurl;

		$this->query($query);
	}

	protected function query( $query ) {
		$this->query = array();

		if ( ! empty($query) && ! is_array($query) )
			parse_str(urldecode($query), $this->query);

		if ( ! $this->prettyurls ) {
			foreach ( $this->paths as $key => $value )
				if ( is_int($key) && isset($value) )
					unset($this->paths[ $key ]);

			$this->query = array_merge($this->paths, $this->requests, $this->query);
		}
	}

	protected function build() {
		$url = trailingslashit($this->baseurl);

		if ( $this->prettyurls )
			$url .= trailingslashit(join('/', $this->paths));

		if ( ! empty($this->query) )
			$url = add_query_arg($this->query, $url);

		$this->url = $url;
	}

}