<?php

namespace Drupal\slick;

use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class SlickDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'optionset'    => 'default',
      'override'     => FALSE,
      'overridables' => [],
      'skin'         => '',
      'skin_arrows'  => '',
      'skin_dots'    => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function gridSettings() {
    return [
      'preserve_keys' => FALSE,
      'visible_items' => 0,
    ] + parent::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    return [
      'optionset_thumbnail' => '',
      'skin_thumbnail'      => '',
      'thumbnail_caption'   => '',
      'thumbnail_effect'    => '',
      'thumbnail_position'  => '',
    ] + self::baseSettings() + parent::imageSettings() + self::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return [
      'thumbnail' => '',
    ] + self::imageSettings() + parent::extendedSettings();
  }

  /**
   * Returns HTML or layout related settings to shut up notices.
   *
   * @return array
   *   The default settings.
   */
  public static function htmlSettings() {
    return [
      'cache'             => 0,
      'current_view_mode' => '',
      'display'           => 'main',
      'grid'              => 0,
      'id'                => '',
      'nav'               => FALSE,
      'navpos'            => FALSE,
      'media_switch'      => '',
      'optionset'         => 'default',
      'ratio'             => '',
      'skin'              => '',
      'unslick'           => FALSE,
      'vanilla'           => FALSE,
      'vertical'          => FALSE,
      'vertical_tn'       => FALSE,
      'view_name'         => '',
    ];
  }

  /**
   * Defines JS options required by theme_slick(), used with optimized option.
   */
  public static function jsSettings() {
    return [
      'asNavFor'        => '',
      'downArrowTarget' => '',
      'downArrowOffset' => '',
      'lazyLoad'        => 'ondemand',
      'prevArrow'       => '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" tabindex="0">Previous</button>',
      'nextArrow'       => '<button type="button" data-role="none" class="slick-next" aria-label="Next" tabindex="0">Next</button>',
      'rows'            => 1,
      'slidesPerRow'    => 1,
      'slide'           => '',
      'slidesToShow'    => 1,
      'vertical'        => FALSE,
    ];
  }

}
