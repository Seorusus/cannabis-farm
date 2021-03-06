<?php

namespace Drupal\blazy;

use Drupal\Component\Serialization\Json;

/**
 * Provides grid utilities.
 */
class BlazyGrid {

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * @param array $items
   *   The grid items being modified.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   */
  public static function build(array $items = [], array $settings = []) {
    $blazy      = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);
    $style      = empty($settings['style']) ? '' : $settings['style'];
    $is_gallery = !empty($settings['lightbox']) && !empty($settings['gallery_id']);
    $is_grid    = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));
    $class_item = $is_grid ? 'grid' : 'blazy__item';

    $contents = [];
    foreach ($items as $item) {
      // Support non-Blazy which normally uses item_id.
      $attributes    = isset($item['attributes']) ? $item['attributes'] : [];
      $item_settings = isset($item['settings']) ? $item['settings'] : $settings;
      $item_settings = isset($item['#build']) && isset($item['#build']['settings']) ? $item['#build']['settings'] : $item_settings;
      unset($item['settings'], $item['attributes'], $item['item']);

      // Supports both single formatter field and complex fields such as Views.
      $content['content'] = $is_grid ? [
        '#theme'      => 'container',
        '#children'   => $item,
        '#attributes' => ['class' => ['grid__content']],
      ] : $item;

      $classes = isset($attributes['class']) ? $attributes['class'] : [];
      $attributes['class'] = array_merge([$class_item], $classes);
      $content['#wrapper_attributes'] = $attributes;

      $contents[] = $content;
    }

    $count   = empty($settings['count']) ? count($contents) : $settings['count'];
    $wrapper = $style ? ['item-list--blazy', 'item-list--blazy-' . $style] : ['item-list--blazy'];
    $element = [
      '#theme'              => 'item_list',
      '#items'              => $contents,
      '#context'            => ['settings' => $settings],
      '#attributes'         => ['class' => ['blazy'], 'data-blazy' => $blazy],
      '#wrapper_attributes' => ['class' => array_merge(['item-list'], $wrapper)],
    ];

    // Provides data-attributes to avoid conflict with original implementations.
    if (!empty($settings['media_switch'])) {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $element['#attributes']['data-' . $switch . '-gallery'] = TRUE;
    }

    if (!empty($settings['field_name'])) {
      $element['#attributes']['class'][] = 'blazy--field blazy--' . str_replace('_', '-', $settings['field_name']);
    }

    // Provides gallery ID, although Colorbox works without it, others may not.
    // Uniqueness is not crucial as a gallery needs to work across entities.
    if (!empty($settings['id'])) {
      $element['#attributes']['id'] = $is_gallery ? $settings['gallery_id'] : $settings['id'];
    }

    // Limit to grid only, so to be usable for plain list.
    if ($is_grid) {
      $element['#attributes']['class'][] = 'blazy--grid block-' . $style . ' block-count-' . $count;

      // Adds common grid attributes for CSS3 column, Foundation, etc.
      if ($settings['grid_large'] = $settings['grid']) {
        foreach (['small', 'medium', 'large'] as $grid) {
          if (!empty($settings['grid_' . $grid])) {
            $element['#attributes']['class'][] = $grid . '-block-' . $style . '-' . $settings['grid_' . $grid];
          }
        }
      }
    }

    return $element;
  }

}
