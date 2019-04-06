<?php

namespace Drupal\slick;

use Drupal\slick\Entity\Slick;
use Drupal\blazy\BlazyFormatterManager;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Implements SlickFormatterInterface.
 */
class SlickFormatter extends BlazyFormatterManager implements SlickFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Prepare integration with Blazy.
    $settings['item_id']   = 'slide';
    $settings['namespace'] = 'slick';

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    // Checks if we are syncing with Blazy post Beta2.
    // @todo remove conditions post RC1.
    if (method_exists(get_parent_class($this), 'preBuildElements')) {
      parent::preBuildElements($build, $items, $entities);
    }
    else {
      $this->buildSettings($build, $items);
    }

    $settings = &$build['settings'];

    // Slick specific stuffs.
    $build['optionset'] = Slick::loadWithFallback($settings['optionset']);

    if (!isset($settings['nav'])) {
      $settings['nav'] = !empty($settings['optionset_thumbnail']) && isset($items[1]);
    }

    // Do not bother for SlickTextFormatter, or when vanilla is on.
    if (empty($settings['vanilla'])) {
      $lazy              = $build['optionset']->getSetting('lazyLoad');
      $settings['blazy'] = $lazy == 'blazy' || !empty($settings['blazy']);
      $settings['lazy']  = $settings['blazy'] ? 'blazy' : $lazy;

      if (empty($settings['blazy'])) {
        $settings['lazy_class'] = $settings['lazy_attribute'] = 'lazy';
      }
    }
    else {
      // Nothing to work with Vanilla on, disable the asnavfor, else JS error.
      $settings['nav'] = FALSE;
    }

    // Only trim overridables options if disabled.
    if (empty($settings['override']) && isset($settings['overridables'])) {
      $settings['overridables'] = array_filter($settings['overridables']);
    }

    $this->getModuleHandler()->alter('slick_settings', $build, $items);

    // Done at top level works, prevents leaking to child for few settings.
    unset($settings['first_item']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove post Blazy RC1.
   */
  public function postBuildElements(array &$build, $items, array $entities = []) {

    // Checks if we are syncing with Blazy post Beta2.
    if (method_exists(get_parent_class($this), 'postBuildElements')) {
      parent::postBuildElements($build, $items, $entities);
    }
  }

  /**
   * Gets the thumbnail image using theme_image_style().
   *
   * @param array $settings
   *   The array containing: thumbnail_style, etc.
   * @param object $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem object.
   *
   * @return array
   *   The renderable array of thumbnail image.
   */
  public function getThumbnail(array $settings = [], $item = NULL) {
    $thumbnail = [];
    $thumbnail_alt = '';
    if ($item instanceof ImageItem) {
      $thumbnail_alt = $item->getValue()['alt'];
    }
    if (!empty($settings['uri'])) {
      $thumbnail = [
        '#theme'      => 'image_style',
        '#style_name' => isset($settings['thumbnail_style']) ? $settings['thumbnail_style'] : 'thumbnail',
        '#uri'        => $settings['uri'],
        '#item'       => $item,
        '#alt'        => $thumbnail_alt,
      ];
    }
    return $thumbnail;
  }

}
