<?php
/**
 * ImageProcessorTests
 *
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 12 May, 2010
 * @package shopp
 * @subpackage
 **/

/**
 * ImageProcessorTests
 *
 * @author
 * @since 1.1
 * @package shopp
 **/
class ImageProcessorTests extends ShoppTestCase {

	function setUp () {
		parent::setUp();
		if ( ! class_exists('ImageProcessor') ) require(SHOPP_MODEL_PATH."/Image.php");
	}

	public function test_small_source () {
		$actual = new ImageProcessor(false,50,50);
		$actual->scale(100,100);
		$actual->processed = false;

		$expected = new ImageProcessor(false,50,50);
		$expected->width = 50;
		$expected->height = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_square_to_target_square () {
		$actual = new ImageProcessor(false,100,100);
		$actual->scale(50,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,100);
		$expected->width = 50;
		$expected->height = 50;
		$expected->axis = 'x';

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_landscape_to_target_square () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(50,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,100);
		$expected->width = 50;
		$expected->height = 25;
		$expected->axis = 'x';
		$expected->dy = 12.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,200);
		$actual->scale(50,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,200);
		$expected->width = 25;
		$expected->height = 50;
		$expected->axis = 'y';
		$expected->dx = 12.5;

		$this->assertEquals($expected,$actual);
	}


	public function test_fit_source_skinny_landscape_to_target_square () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,300,100);
		$expected->width = 200;
		$expected->height = 67;
		$expected->axis = 'x';
		$expected->dy = 66.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_skinny_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,300);
		$expected->width = 67;
		$expected->height = 200;
		$expected->axis = 'y';
		$expected->dx = 66.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_square_to_target_landscape () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,200);
		$expected->width = 50;
		$expected->height = 50;
		$expected->axis = 'y';
		$expected->aspect = 2;
		$expected->dx = 25;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,400,200);
		$actual->scale(100,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,400,200);
		$expected->width = 100;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->aspect = 2;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,50);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,400);
		$expected->width = 25;
		$expected->height = 50;
		$expected->axis = 'y';
		$expected->aspect = 2;
		$expected->dx = 37.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_skinny_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,100);
		$actual->processed = false;

		$expected = new ImageProcessor(false,300,100);
		$expected->width = 200;
		$expected->height = 67;
		$expected->axis = 'x';
		$expected->aspect = 2;
		$expected->dy = 16.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_skinny_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,100);
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,300);
		$expected->width = 34;
		$expected->height = 100;
		$expected->axis = 'y';
		$expected->aspect = 2;
		$expected->dx = 83;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_square_to_target_portrait () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,200);
		$expected->width = 100;
		$expected->height = 100;
		$expected->axis = 'x';
		$expected->aspect = 0.5;
		$expected->dy = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(100,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,100);
		$expected->width = 100;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->aspect = 0.5;
		$expected->dy = 75;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,400);
		$expected->width = 100;
		$expected->height = 200;
		$expected->axis = 'x';
		$expected->aspect = 0.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_skinny_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,400,100);
		$actual->scale(100,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,400,100);
		$expected->width = 100;
		$expected->height = 25;
		$expected->axis = 'x';
		$expected->aspect = 0.5;
		$expected->dy = 87.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_fit_source_skinny_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,100,400);
		$actual->scale(100,200);
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,400);
		$expected->width = 50;
		$expected->height = 200;
		$expected->axis = 'y';
		$expected->aspect = 0.5;
		$expected->dx = 25;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_small () {
		$actual = new ImageProcessor(false,50,50);
		$actual->scale(100,100,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,50,50);
		$expected->width = 50;
		$expected->height = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_square_to_target_square () {
		$actual = new ImageProcessor(false,100,100);
		$actual->scale(50,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,100);
		$expected->width = 50;
		$expected->height = 50;
		$expected->axis = 'x';

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_landscape_to_target_square () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(50,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,100);
		$expected->width = 50;
		$expected->height = 50;
		$expected->axis = 'y';
		$expected->dx = -25;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,200);
		$actual->scale(50,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,200);
		$expected->width = 50;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->dy = -25;

		$this->assertEquals($expected,$actual);
	}


	public function test_crop_source_skinny_landscape_to_target_square () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,300,100);
		$expected->width = 200;
		$expected->height = 100;
		$expected->axis = 'y';
		$expected->dx = -50;
		$expected->dy = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_skinny_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,300);
		$expected->width = 100;
		$expected->height = 200;
		$expected->axis = 'x';
		$expected->dx = 50;
		$expected->dy = -50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_square_to_target_landscape () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,200);
		$expected->width = 100;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->aspect = 2;
		$expected->dy = -25;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,400,200);
		$actual->scale(100,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,400,200);
		$expected->width = 100;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->aspect = 2;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,50,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,400);
		$expected->width = 100;
		$expected->height = 50;
		$expected->axis = 'x';
		$expected->aspect = 2;
		$expected->dy = -75;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_skinny_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,100,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,300,100);
		$expected->width = 200;
		$expected->height = 100;
		$expected->axis = 'y';
		$expected->aspect = 2;
		$expected->dx = -50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_skinny_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,100,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,300);
		$expected->width = 100;
		$expected->height = 100;
		$expected->axis = 'x';
		$expected->aspect = 2;
		$expected->dx = 50;
		$expected->dy = -100;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_square_to_target_portrait () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,200);
		$expected->width = 100;
		$expected->height = 200;
		$expected->axis = 'y';
		$expected->aspect = 0.5;
		$expected->dx = -50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(100,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,100);
		$expected->width = 100;
		$expected->height = 100;
		$expected->axis = 'y';
		$expected->aspect = 0.5;
		$expected->dx = -50;
		$expected->dy = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,200,400);
		$expected->width = 100;
		$expected->height = 200;
		$expected->axis = 'x';
		$expected->aspect = 0.5;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_skinny_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,400,100);
		$actual->scale(100,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,400,100);
		$expected->width = 100;
		$expected->height = 100;
		$expected->axis = 'y';
		$expected->aspect = 0.5;
		$expected->dx = -150;
		$expected->dy = 50;

		$this->assertEquals($expected,$actual);
	}

	public function test_crop_source_skinny_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,100,400);
		$actual->scale(100,200,'crop');
		$actual->processed = false;

		$expected = new ImageProcessor(false,100,400);
		$expected->width = 100;
		$expected->height = 200;
		$expected->axis = 'x';
		$expected->aspect = 0.5;
		$expected->dy = -100;

		$this->assertEquals($expected,$actual);
	}


} // END class ImageProcessorTests

?>