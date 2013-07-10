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

	public function test_small_source () {
		$actual = new ImageProcessor(false,50,50);
		$actual->scale(100,100);

		$this->assertEquals(50,$actual->width());
		$this->assertEquals(50, $actual->height());
	}

	public function test_fit_source_square_to_target_square () {
		$actual = new ImageProcessor(false,100,100);
		$actual->scale(50,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x',$actual->axis);
	}

	public function test_fit_source_landscape_to_target_square () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(50,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(25, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(12.5, $actual->dy);
	}

	public function test_fit_source_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,200);
		$actual->scale(50,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(25, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(12.5, $actual->dx);
	}

	public function test_fit_source_skinny_landscape_to_target_square () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(200, $actual->width);
		$this->assertEquals(67, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(66.5, $actual->dy);
	}

	public function test_fit_source_skinny_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(67, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(66.5, $actual->dx);
	}

	public function test_fit_source_square_to_target_landscape () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(25, $actual->dx);
	}

	public function test_fit_source_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,400,200);
		$actual->scale(100,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
	}

	public function test_fit_source_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,50);
		$actual = json_decode((string)$actual);

		$this->assertEquals(25, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(37.5, $actual->dx);
	}

	public function test_fit_source_skinny_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,100);
		$actual = json_decode((string)$actual);

		$this->assertEquals(200, $actual->width);
		$this->assertEquals(67, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(16.5, $actual->dy);
	}

	public function test_fit_source_skinny_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,100);
		$actual = json_decode((string)$actual);

		$this->assertEquals(34, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(83, $actual->dx);
	}

	public function test_fit_source_square_to_target_portrait () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(50, $actual->dy);
	}

	public function test_fit_source_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(100,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(75, $actual->dy);
	}

	public function test_fit_source_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
	}

	public function test_fit_source_skinny_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,400,100);
		$actual->scale(100,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(25, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(87.5, $actual->dy);
	}

	public function test_fit_source_skinny_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,100,400);
		$actual->scale(100,200);
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(25, $actual->dx);
	}

	public function test_crop_source_small () {
		$actual = new ImageProcessor(false,50,50);
		$actual->scale(100,100,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
	}

	public function test_crop_source_square_to_target_square () {
		$actual = new ImageProcessor(false,100,100);
		$actual->scale(50,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
	}

	public function test_crop_source_landscape_to_target_square () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(50,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(-25, $actual->dx);
	}

	public function test_crop_source_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,200);
		$actual->scale(50,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(50, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(-25, $actual->dy);
	}


	public function test_crop_source_skinny_landscape_to_target_square () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(200, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(-50, $actual->dx);
		$this->assertEquals(50, $actual->dy);
	}

	public function test_crop_source_skinny_portrait_to_target_square () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(50, $actual->dx);
		$this->assertEquals(-50, $actual->dy);
	}

	public function test_crop_source_square_to_target_landscape () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(-25, $actual->dy);
	}

	public function test_crop_source_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,400,200);
		$actual->scale(100,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
	}

	public function test_crop_source_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,50,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(50, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(-75, $actual->dy);
	}

	public function test_crop_source_skinny_landscape_to_target_landscape () {
		$actual = new ImageProcessor(false,300,100);
		$actual->scale(200,100,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(200, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(-50, $actual->dx);
	}

	public function test_crop_source_skinny_portrait_to_target_landscape () {
		$actual = new ImageProcessor(false,100,300);
		$actual->scale(200,100,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(2, $actual->aspect);
		$this->assertEquals(50, $actual->dx);
		$this->assertEquals(-100, $actual->dy);
	}

	public function test_crop_source_square_to_target_portrait () {
		$actual = new ImageProcessor(false,200,200);
		$actual->scale(100,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(-50, $actual->dx);
	}

	public function test_crop_source_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,200,100);
		$actual->scale(100,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(-50, $actual->dx);
		$this->assertEquals(50, $actual->dy);
	}

	public function test_crop_source_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,200,400);
		$actual->scale(100,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
	}

	public function test_crop_source_skinny_landscape_to_target_portrait () {
		$actual = new ImageProcessor(false,400,100);
		$actual->scale(100,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(100, $actual->height);
		$this->assertEquals('y', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(-150, $actual->dx);
		$this->assertEquals(50, $actual->dy);
	}

	public function test_crop_source_skinny_portrait_to_target_portrait () {
		$actual = new ImageProcessor(false,100,400);
		$actual->scale(100,200,'crop');
		$actual = json_decode((string)$actual);

		$this->assertEquals(100, $actual->width);
		$this->assertEquals(200, $actual->height);
		$this->assertEquals('x', $actual->axis);
		$this->assertEquals(0.5, $actual->aspect);
		$this->assertEquals(-100, $actual->dy);
	}

} // END class ImageProcessorTests