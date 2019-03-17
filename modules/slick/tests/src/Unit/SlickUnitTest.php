<?php

namespace Drupal\Tests\slick\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\slick\SlickDefault;
use Drupal\slick\Entity\Slick;

/**
 * @coversDefaultClass \Drupal\slick\Entity\Slick
 *
 * @group slick
 */
class SlickUnitTest extends UnitTestCase {

  /**
   * Tests for slick entity methods.
   *
   * @covers \Drupal\slick\SlickDefault::htmlSettings
   * @covers \Drupal\slick\SlickDefault::jsSettings
   * @covers ::getDependentOptions
   */
  public function testSlickEntity() {
    $html_settings = SlickDefault::htmlSettings();
    $this->assertArrayHasKey('display', $html_settings);

    $js_settings = SlickDefault::jsSettings();
    $this->assertArrayHasKey('lazyLoad', $js_settings);

    $dependent_options = Slick::getDependentOptions();
    $this->assertArrayHasKey('useCSS', $dependent_options);
  }

}
