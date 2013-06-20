<?php
/**
 * PackagingTests
 *
 *
 * @author John Dillick
 * @version 1.0
 * @copyright Ingenesis Limited, 6 April, 2011
 * @package
 **/

class PackagingTests extends ShoppTestCase {
	/**
	 * Initialize
	 **/
	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		parent::setUp();

		// doesn't matter... packaged alone in all models
		if ( is_a($this->prod1, 'Product') ) return;
		$data = array(
			'name' => "Packager Test Product 1",
			'publish' => array( 'flag' => true ),
			'description' => "item 1",
			'packaging' => true
		);
		$data['single'] = array(
			'type' => 'Shipped',
			'price' => 41.00,
			'shipping' => array('flag'=>true, 'fee'=>1.50, 'weight'=>1, 'length'=>1, 'width'=>1, 'height'=>1)
		);
		$this->prod1 = shopp_add_product($data);

		// Square item 10 lbs
		$data = array(
			'name' => "Packager Test Product 2",
			'publish' => array( 'flag' => true ),
			'description' => "item 2",
			'packaging' => false
		);
		$data['single'] = array(
			'type' => 'Shipped',
			'price' => 42.00,
			// doesn't matter... packaged alone in all models
			'shipping' => array('flag'=>true, 'fee'=>1.50, 'weight'=>10, 'length'=>5, 'width'=>5, 'height'=>5)
		);
		$this->prod2 = shopp_add_product($data);

		// long item 15 lbs
		$data = array(
			'name' => "Packager Test Product 3",
			'publish' => array( 'flag' => true ),
			'description' => "item 3",
			'packaging' => false
		);
		$data['single'] = array(
			'type' => 'Shipped',
			'price' => 42.00,
			// doesn't matter... packaged alone in all models
			'shipping' => array('flag'=>true, 'fee'=>1.50, 'weight'=>15, 'length'=>15, 'width'=>5, 'height'=>5)
		);
		$this->prod3 = shopp_add_product($data);

		// tall item 50 lbs
		$data = array(
			'name' => "Packager Test Product 4",
			'publish' => array( 'flag' => true ),
			'description' => "item 4",
			'packaging' => false
		);
		$data['single'] = array(
			'type' => 'Shipped',
			'price' => 42.00,
			// doesn't matter... packaged alone in all models
			'shipping' => array('flag'=>true, 'fee'=>1.50, 'weight'=>50, 'length'=>10, 'width'=>10, 'height'=>20)
		);
		$this->prod4 = shopp_add_product($data);

	}

	function tearDown () {
		parent::tearDown();

		unset($this->packer);
	}

	// testing packaging all items together, by mass
	function test_package_mass () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";
		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		foreach ( $products as $i => $Product ) {
			$items[$i] = new Item ( $Product, false );
			$items[$i]->quantity( $i + 1 );
		}
		ShoppOrder()->Cart->contents = $items;

		$this->packer = new ShippingPackager(array('type'=>'mass', 'limits'=>array('wtl'=>-1,'ll'=>-1,'wl'=>-1,'hl'=>-1)),'test_package_mass');

		foreach ($items as $Item) {
			$this->packer->add_item($Item);
		}
		$pkgs = array();
		while ( $this->packer->packages() ) $pkgs[] = $p = $this->packer->package();

		// check package 1
		$this->AssertEquals(2, count($pkgs));
		$pkg = $pkgs[0];
		$this->AssertEquals(1, count($pkg->contents()));
		$this->AssertEquals(1, $pkg->weight());
		$this->AssertEquals(41, $pkg->value());
		$contents = $pkg->contents();
		$this->AssertEquals(1, reset($contents)->quantity);

		// check package 2
		$pkg = $pkgs[1];
		$this->AssertEquals(3, count($pkg->contents()));
		$this->AssertEquals(265, $pkg->weight());
		$this->AssertEquals(378, $pkg->value());
		$contents = $pkg->contents();

		$item = reset($contents);
		$this->AssertEquals(2, $item->quantity);
		$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);

		$item = next($contents);
		$this->AssertEquals(3, $item->quantity);
		$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);

		$item = next($contents);
		$this->AssertEquals(4, $item->quantity);
		$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		foreach ( $products as $i => $Product ) {
			$items[$i] = new Item ( $Product, false );
			$items[$i]->quantity( 4 - $i );
		}
		ShoppOrder()->Cart->contents = $items;

		foreach ($items as $Item) {
			$this->packer->add_item($Item);
		}

		$pkgs = array();
		while ( $this->packer->packages() ) $pkgs[] = $p = $this->packer->package();

		$this->AssertEquals(6, count($pkgs));

		$count = 0;
		foreach($pkgs as $i => $pkg) {
			$wt = $pkg->weight();
			$w = $pkg->width();
			$l = $pkg->length();
			$h = $pkg->height();
			$v = $pkg->value();
			$contents = $pkg->contents();
			$items = array();
			foreach( $contents as $item ) {
				$items[] = array( 'QTY'=>$item->quantity, 'name'=>$item->parentItem()->name );
			}

			switch ($count++) {
				case 0:
				case 2:
				case 3:
				case 4:
				case 5:
					$this->AssertEquals(1, $wt);
					$this->AssertEquals(1, $w);
					$this->AssertEquals(1, $l);
					$this->AssertEquals(1, $h);
					$this->AssertEquals(41, $v);
					$this->AssertEquals(1, count($items));
					break;

				case 1:
					$this->AssertEquals(375, $wt);
					$this->AssertEquals(0, $w);
					$this->AssertEquals(0, $l);
					$this->AssertEquals(0, $h);
					$this->AssertEquals(630, $v);
					$this->AssertEquals(3, count($items));
					break;

			}
		}
	}

	// testing packaging all like items together
	function test_package_like () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";
		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		$this->packer = new ShippingPackager(array('type'=>'like','limits'=>array('wtl'=>-1,'ll'=>-1,'wl'=>-1,'hl'=>-1)),'test_package_like');
		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";
		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 80, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 5, $p->length());
					$this->AssertEquals( 40, $p->height());
					$this->AssertEquals( 336, $p->value());
					break;
				case 5:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 90, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 252, $p->value());
					break;
				case 6:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 600, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 10, $p->length());
					$this->AssertEquals( 240, $p->height());
					$this->AssertEquals( 504, $p->value());
			}
		}
	}

	// testing packaging all items together, with dimensions
	function test_package_all () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 5 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		$this->packer = new ShippingPackager(array('type'=>'all','limits'=>array('wtl'=>-1,'ll'=>-1,'wl'=>-1,'hl'=>-1)),'test_package_all');
		// echo "\nItems\n";

		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 7:
					$this->AssertEquals( 3, count($p->contents()));
					$this->AssertEquals( 975, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 385, $p->height());
					$this->AssertEquals( 1218, $p->value());
			}
		}
	}

	// packaging all items in separate packages
	function test_package_piece () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < count($products); $i++ ) {
				$items[$i] = new Item($products[$i], false);
				$items[$i]->quantity( max( 1, (6 - $i) % 4 ) );
		}
		ShoppOrder()->Cart->contents = $items;

		$this->packer = new ShippingPackager(array('type'=>'piece','limits'=>array('wtl'=>-1,'ll'=>-1,'wl'=>-1,'hl'=>-1)),'test_package_piece');

		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 10, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 5, $p->length());
					$this->AssertEquals( 5, $p->height());
					$this->AssertEquals( 42, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 15, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 5, $p->height());
					$this->AssertEquals( 42, $p->value());
					break;
				case 5:
				case 6:
				case 7:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 50, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 10, $p->length());
					$this->AssertEquals( 20, $p->height());
					$this->AssertEquals( 42, $p->value());
					break;
			}
		}
	}

	function test_package_mass_limited_base () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod2,$this->prod3,$this->prod4);
		$items = array();

		$items[0] = new Item($products[0], false);
		$items[0]->quantity(9);
		$items[1] = new Item($products[1], false);
		$items[1]->quantity(7);
		$items[2] = new Item($products[2], false);
		$items[2]->quantity(4);
		ShoppOrder()->Cart->contents = $items;

		// set 75 lbs limit
		$this->packer = new ShippingPackager(array('type'=>'mass', 'limits'=>array('wtl' => 75)),'test_package_mass_limited_base');

		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}

		$this->AssertEquals(7, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// first 6 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(7, $item->quantity);

					$this->AssertEquals( 70, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 294, $p->value());
					break;
				case 2:
					$this->AssertEquals( 2, count($p->contents()));
					$item = reset($p->contents());
					// last 2 of 8 will fit
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(2, $item->quantity);

					// first 2 of 6 will fit
					$item = next($p->contents());
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(3, $item->quantity);

					$this->AssertEquals( 65, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 210, $p->value());
					break;
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// last 4 of 6 will fit
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(4, $item->quantity);

					$this->AssertEquals( 60, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 168, $p->value());
					break;
				case 4:
				case 5:
				case 6:
				case 7:
				// last 4 of 4 all require individual package due to weight
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(1, $item->quantity);

					$this->AssertEquals( 50, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 42, $p->value());
					break;
			}
		}
	}


	function test_package_mass_limited () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		// set 60 lbs limit
		$this->packer = new ShippingPackager(array('type'=>'mass', 'limits'=>array('wtl' => 60)),'test_package_mass_limited');

		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";

		$this->AssertEquals(18, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// first 6 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(6, $item->quantity);

					$this->AssertEquals( 60, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 252, $p->value());
					break;
				case 5:
					$this->AssertEquals( 2, count($p->contents()));
					$item = reset($p->contents());
					// last 2 of 8 will fit
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(2, $item->quantity);

					// first 2 of 6 will fit
					$item = next($p->contents());
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(2, $item->quantity);

					$this->AssertEquals( 50, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 168, $p->value());
					break;
				case 6:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// last 4 of 6 will fit
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(4, $item->quantity);

					$this->AssertEquals( 60, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 168, $p->value());
					break;
				case 7:
				case 8:
				case 9:
				case 10:
				case 11:
				case 12:
				case 13:
				case 14:
				case 15:
				case 16:
				case 17:
				case 18:
				// last 12 of 12 all require individual package due to weight
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(1, $item->quantity);

					$this->AssertEquals( 50, $p->weight());
					$this->AssertEquals( 0, $p->width());
					$this->AssertEquals( 0, $p->length());
					$this->AssertEquals( 0, $p->height());
					$this->AssertEquals( 42, $p->value());
					break;
			}
		}
	}

	function test_package_like_limited () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		// set 100 lbs limit
		$this->packer = new ShippingPackager(array('type'=>'like', 'limits'=>array('wtl' => 100)),'test_package_like_limited');

		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";

		// $this->AssertEquals(11, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 8 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(8, $item->quantity);

					$this->AssertEquals( 80, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 5, $p->length());
					$this->AssertEquals( 40, $p->height());
					$this->AssertEquals( 336, $p->value());
					break;
				case 5:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 6 of 6 will fit in one package
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(6, $item->quantity);

					$this->AssertEquals( 90, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 252, $p->value());
					break;
				case 6:
				case 7:
				case 8:
				case 9:
				case 10:
				case 11:
				// last 12 will only fit 2 in each package
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(2, $item->quantity);

					$this->AssertEquals( 100, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 10, $p->length());
					$this->AssertEquals( 40, $p->height());
					$this->AssertEquals( 84, $p->value());
					break;
			}
		}
	}

	function test_package_all_limited () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		// set 225 lbs limit
		$this->packer = new ShippingPackager(array('type'=>'all', 'limits'=>array('wtl' => 225)),'test_package_all_limited');

		// echo "\nItems\n";
		foreach ( $items as $item ) {
			// echo "item $item->name - QTY: $item->quantity Each wt: $item->weight h: $item->height w: $item->width l: $item->length val: $item->unitprice\n";
			$this->packer->add_item($item);
		}
		// echo "\n";

		$this->AssertEquals(7, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();
			// echo "Package ".count($pkgs).":\nItems:\n";
			// foreach ( $p->contents() as $item ) {
			// 	echo "Item '$item->name' QTY($item->quantity) - Each wt: $item->weight w: $item->width l: $item->length h: $item->height val: $item->unitprice\n";
			// }
			// echo sprintf(
			// 	"Dimensions (w x l x h): %d x %d x %d\tWght: %d\tVal: \$ %d".
			// 	"\n---------------------------------------------------------\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 3, count($p->contents()));
					$contents = $p->contents();
					$item = reset($contents);

					// all 8 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(8, $item->quantity);

					$item = next($contents);
					// all 6 of 6 fit in here too
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(6, $item->quantity);

					$item = next($contents);
					// 1 of 12 fit in here too
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(1, $item->quantity);

					$this->AssertEquals( 220, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 90, $p->height());
					$this->AssertEquals( 630, $p->value());
					break;
				case 5:
				case 6:
					$this->AssertEquals( 1, count($p->contents()));
					$contents = $p->contents();
					$item = reset($contents);

					// all 4 of 12 will fit in one package
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(4, $item->quantity);

					$this->AssertEquals( 200, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 10, $p->length());
					$this->AssertEquals( 80, $p->height());
					$this->AssertEquals( 168, $p->value());
					break;
				case 7:
					$this->AssertEquals( 1, count($p->contents()));
					$contents = $p->contents();
					$item = reset($contents);

					// all 3 of 12 will fit in one package
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(3, $item->quantity);

					$this->AssertEquals( 150, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 10, $p->length());
					$this->AssertEquals( 60, $p->height());
					$this->AssertEquals( 126, $p->value());
					break;
			}
		}
	}

	function test_package_like_limited_dims () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		// set 150 lbs limit, and box size limited to (w x l x h) 40 x 40 x 40
		$this->packer = new ShippingPackager(array('type'=>'like', 'limits'=>array('wtl' => 150, 'wl' => 40, 'll' => 40, 'hl' => 40)), 'test_package_like_limited_dims');

		// echo "\n====================================== Items ============================================\n";
		foreach ( $items as $item ) {
			// echo sprintf(
			// 	"'%s' QTY(%d) - dims (%d W x %d L x %d H) \tWght: %d\tVal: \$ %d\n",
			// 	$item->name, $item->quantity, $item->width, $item->length, $item->height, $item->weight, $item->unitprice
			// 	);

			$this->packer->add_item($item);
		}
		// echo "\n=========================================================================================\n";

		$this->AssertEquals(9, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();

			// Debugging code
			// echo "Package ".count($pkgs). sprintf(
			// 	"- dims (%d W x %d L x %d H)\tWght: %d\tVal: \$ %d\n\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			// echo "--Contents--\n";
			// foreach ( $p->contents() as $item ) {
			// 		echo sprintf(
			// 			"'%s' QTY(%d) - dims (%d W x %d L x %d H) \tWght: %d\tVal: \$ %d\n",
			// 			$item->name, $item->quantity, $item->width, $item->length, $item->height, $item->weight, $item->unitprice
			// 			);
			// }
			// echo "\n---------------------------------------------------------\n\n";

			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 8 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(8, $item->quantity);

					$this->AssertEquals( 80, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 5, $p->length());
					$this->AssertEquals( 40, $p->height());
					$this->AssertEquals( 336, $p->value());
					break;
				case 5:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 6 of 6 will fit in one package
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(6, $item->quantity);

					$this->AssertEquals( 90, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 252, $p->value());
					break;
				case 6:
				case 7:
				case 8:
				case 9:
				// last 12 will only fit 3 in each package
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(3, $item->quantity);

					$this->AssertEquals( 150, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 20, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 126, $p->value());
					break;
			}
		}
	}

	function test_package_all_limited_dims () {
		// return;
		// echo "\n".__FUNCTION__." Tests:\n----------------------\n";

		$products = array($this->prod1, $this->prod2, $this->prod3, $this->prod4);
		$items = array();
		for ($i = 0; $i < ( 2 * count($products) ); $i++ ) {
			$p = $i % count($products);
			if ( isset($items[$p]) ) $items[$p]->quantity($items[$p]->quantity + ($p + 1) * ($i % 3 + 1) );
			else {
				$items[$p] = new Item($products[$p], false);
				$items[$p]->quantity($i + 1);
			}
		}
		ShoppOrder()->Cart->contents = $items;

		// set 150 lbs limit, and box size limited to (w x l x h) 40 x 40 x 40
		$this->packer = new ShippingPackager(array('type'=>'like', 'limits'=>array('wtl' => 150, 'wl' => 40, 'll' => 40, 'hl' => 40)), 'test_package_all_limited_dims');

		// echo "\n====================================== Items ============================================\n";
		foreach ( $items as $item ) {
			// echo sprintf(
			// 	"'%s' QTY(%d) - dims (%d W x %d L x %d H) \tWght: %d\tVal: \$ %d\n",
			// 	$item->name, $item->quantity, $item->width, $item->length, $item->height, $item->weight, $item->unitprice
			// 	);

			$this->packer->add_item($item);
		}
		// echo "\n=========================================================================================\n";

		$this->AssertEquals(9, $this->packer->count());

		$pkgs = array();
		while ( $this->packer->packages() ) {
			$pkgs[] = $p = $this->packer->package();

			// Debugging code
			// echo "Package ".count($pkgs). sprintf(
			// 	"- dims (%d W x %d L x %d H)\tWght: %d\tVal: \$ %d\n\n",
			// 	$p->width(), $p->length(), $p->height(), $p->weight(), $p->value());
			// echo "--Contents--\n";
			// foreach ( $p->contents() as $item ) {
			// 		echo sprintf(
			// 			"'%s' QTY(%d) - dims (%d W x %d L x %d H) \tWght: %d\tVal: \$ %d\n",
			// 			$item->name, $item->quantity, $item->width, $item->length, $item->height, $item->weight, $item->unitprice
			// 			);
			// }
			// echo "\n---------------------------------------------------------\n\n";

			$pc = count($pkgs);
			switch ( $pc ) {
				case 1:
				case 2:
				case 3:
					$this->AssertEquals( 1, count($p->contents()));
					$this->AssertEquals( 1, $p->weight());
					$this->AssertEquals( 1, $p->width());
					$this->AssertEquals( 1, $p->length());
					$this->AssertEquals( 1, $p->height());
					$this->AssertEquals( 41, $p->value());
					break;
				case 4:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 8 of 8 will fit in one package
					$this->AssertEquals('Packager Test Product 2', $item->parentItem()->name);
					$this->AssertEquals(8, $item->quantity);

					$this->AssertEquals( 80, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 5, $p->length());
					$this->AssertEquals( 40, $p->height());
					$this->AssertEquals( 336, $p->value());
					break;
				case 5:
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					// all 6 of 6 will fit in one package
					$this->AssertEquals('Packager Test Product 3', $item->parentItem()->name);
					$this->AssertEquals(6, $item->quantity);

					$this->AssertEquals( 90, $p->weight());
					$this->AssertEquals( 5, $p->width());
					$this->AssertEquals( 15, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 252, $p->value());
					break;
				case 6:
				case 7:
				case 8:
				case 9:
				// last 12 will only fit 3 in each package
					$this->AssertEquals( 1, count($p->contents()));
					$item = reset($p->contents());
					$this->AssertEquals('Packager Test Product 4', $item->parentItem()->name);
					$this->AssertEquals(3, $item->quantity);

					$this->AssertEquals( 150, $p->weight());
					$this->AssertEquals( 10, $p->width());
					$this->AssertEquals( 20, $p->length());
					$this->AssertEquals( 30, $p->height());
					$this->AssertEquals( 126, $p->value());
					break;
			}
		}
	}
}
?>