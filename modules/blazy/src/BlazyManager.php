<?php

namespace Drupal\blazy;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Cache\Cache;

/**
 * Implements a public facing blazy manager.
 *
 * A few modules re-use this: GridStack, Mason, Slick...
 */
class BlazyManager extends BlazyManagerBase {

  /**
   * Checks if image dimensions are set.
   *
   * @var array
   */
  private $isDimensionSet;

  /**
   * Sets dimensions once to reduce method calls, if image style contains crop.
   *
   * The implementor should only call this if not using Responsive image style.
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $item
   *   The first image item found.
   */
  public function setDimensionsOnce(array &$settings = [], $item = NULL) {
    if (!isset($this->isDimensionSet[md5($settings['first_uri'])])) {
      $dimensions['width']  = $settings['original_width'] = $item && isset($item->width) ? $item->width : NULL;
      $dimensions['height'] = $settings['original_height'] = $item && isset($item->height) ? $item->height : NULL;

      // If image style contains crop, sets dimension once, and let all inherit.
      if (!empty($settings['image_style']) && ($style = $this->entityLoad($settings['image_style']))) {
        if ($this->isCrop($style)) {
          $style->transformDimensions($dimensions, $settings['first_uri']);

          $settings['height'] = $dimensions['height'];
          $settings['width']  = $dimensions['width'];

          // Informs individual images that dimensions are already set once.
          $settings['_dimensions'] = TRUE;
        }
      }

      // Also sets breakpoint dimensions once, if cropped.
      if (!empty($settings['breakpoints'])) {
        $this->buildDataBlazy($settings, $item);
      }

      $this->isDimensionSet[md5($settings['first_uri'])] = TRUE;
    }
  }

  /**
   * Returns the enforced content, or image using theme_blazy().
   *
   * @param array $build
   *   The array containing: item, content, settings, or optional captions.
   *
   * @return array
   *   The alterable and renderable array of enforced content, or theme_blazy().
   */
  public function getBlazy(array $build = []) {
    /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    $item = $build['item'] = isset($build['item']) ? $build['item'] : NULL;
    $settings = &$build['settings'];
    $settings['delta'] = isset($settings['delta']) ? $settings['delta'] : 0;
    $settings['image_style'] = isset($settings['image_style']) ? $settings['image_style'] : '';

    // Respects content not handled by theme_blazy(), but passed through.
    if (empty($build['content'])) {
      $image = empty($settings['uri']) ? [] : [
        '#theme'       => 'blazy',
        '#delta'       => $settings['delta'],
        '#item'        => isset($settings['entity_type_id']) && $settings['entity_type_id'] == 'user' ? $item : [],
        '#image_style' => $settings['image_style'],
        '#build'       => $build,
        '#pre_render'  => [[$this, 'preRenderImage']],
      ];
    }
    else {
      $image = $build['content'];
    }

    $this->moduleHandler->alter('blazy', $image, $settings);
    return $image;
  }

  /**
   * Builds the Blazy image as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRenderImage(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    if (empty($build['settings']['uri'])) {
      return [];
    }

    // Prepare the main image.
    $this->prepareImage($element, $build);

    // Fetch the newly modified settings.
    $settings = $element['#settings'];

    if (!empty($settings['media_switch'])) {
      if ($settings['media_switch'] == 'content' && !empty($settings['content_url'])) {
        $element['#url'] = $settings['content_url'];
      }
      elseif (!empty($settings['lightbox'])) {
        BlazyLightbox::build($element);
      }
    }

    return $element;
  }

  /**
   * Prepares the Blazy image as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The renderable array being modified.
   * @param array $build
   *   The array of information containing the required Image or File item
   *   object, settings, optional container attributes.
   */
  protected function prepareImage(array &$element, array $build) {
    $item = $build['item'];
    $settings = $build['settings'];
    $settings += BlazyDefault::itemSettings();
    $settings['_api'] = TRUE;

    foreach (BlazyDefault::themeAttributes() as $key) {
      $key = $key . '_attributes';
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $attributes = isset($build['attributes']) ? $build['attributes'] : [];
    $item_attributes = isset($build['item_attributes']) ? $build['item_attributes'] : [];
    $url_attributes = $build['url_attributes'];

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    if ($item && isset($item->_attributes)) {
      $item_attributes += $item->_attributes;
      unset($item->_attributes);
    }

    // Gets the file extension, and ensures the image has valid extension.
    $pathinfo = pathinfo($settings['uri']);
    $settings['extension'] = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
    $settings['ratio'] = empty($settings['ratio']) ? '' : str_replace(':', '', $settings['ratio']);

    // Prepare image URL and its dimensions.
    Blazy::buildUrlAndDimensions($settings, $item);

    // Responsive image integration.
    $settings['responsive_image_style_id'] = '';
    if (!empty($settings['resimage']) && !empty($settings['responsive_image_style'])) {
      $responsive_image_style = $this->entityLoad($settings['responsive_image_style'], 'responsive_image_style');
      $settings['lazy'] = '';
      if (!empty($responsive_image_style)) {
        $settings['responsive_image_style_id'] = $responsive_image_style->id();
        if ($this->configLoad('responsive_image')) {
          $item_attributes['data-srcset'] = TRUE;
          $settings['lazy'] = 'responsive';
        }
        $element['#cache']['tags'] = $this->getResponsiveImageCacheTags($responsive_image_style);
      }
    }

    // Regular image with custom responsive breakpoints.
    if (empty($settings['responsive_image_style_id'])) {
      if ($settings['width'] && !empty($settings['ratio']) && in_array($settings['ratio'], ['enforced', 'fluid'])) {
        $padding = empty($settings['padding_bottom']) ? round((($settings['height'] / $settings['width']) * 100), 2) : $settings['padding_bottom'];
        $attributes['style'] = 'padding-bottom: ' . $padding . '%';

        // Provides hint to breakpoints to work with multi-breakpoint ratio.
        $settings['_breakpoint_ratio'] = $settings['ratio'];

        // Views rewrite results or Twig inline_template may strip out `style`
        // attributes, provide hint to JS.
        $attributes['data-ratio'] = $padding;
      }

      if (!empty($settings['lazy'])) {
        // Attach data attributes to either IMG tag, or DIV container.
        if (!empty($settings['background'])) {
          Blazy::buildBreakpointAttributes($attributes, $settings);
          $attributes['class'][] = 'media--background';
        }
        else {
          Blazy::buildBreakpointAttributes($item_attributes, $settings);
        }

        // Multi-breakpoint aspect ratio only applies if lazyloaded.
        if (!empty($settings['blazy_data']['dimensions'])) {
          $attributes['data-dimensions'] = Json::encode($settings['blazy_data']['dimensions']);
        }
      }

      if (empty($settings['_no_cache'])) {
        $file_tags = isset($settings['file_tags']) ? $settings['file_tags'] : [];
        $settings['cache_tags'] = empty($settings['cache_tags']) ? $file_tags : Cache::mergeTags($settings['cache_tags'], $file_tags);

        $element['#cache']['max-age'] = -1;
        foreach (['contexts', 'keys', 'tags'] as $key) {
          if (!empty($settings['cache_' . $key])) {
            $element['#cache'][$key] = $settings['cache_' . $key];
          }
        }
      }
    }

    // Provides extra attributes as needed, excluding url, item, done above.
    foreach (['caption', 'media', 'wrapper'] as $key) {
      $element["#$key" . '_attributes'] = $build[$key . '_attributes'];
    }

    $captions = empty($build['captions']) ? [] : $this->buildCaption($build['captions'], $settings);
    if ($captions) {
      $element['#caption_attributes']['class'][] = $settings['item_id'] . '__caption';
    }

    $element['#attributes']      = $attributes;
    $element['#captions']        = $captions;
    $element['#item']            = $item;
    $element['#item_attributes'] = $item_attributes;
    $element['#url_attributes']  = $url_attributes;
    $element['#settings']        = $settings;
  }

  /**
   * Build captions for both old image, or media entity.
   */
  public function buildCaption(array $captions, array $settings) {
    $content = [];
    foreach ($captions as $key => $caption_content) {
      if ($caption_content) {
        $content[$key]['content'] = $caption_content;
        $content[$key]['tag'] = strpos($key, 'title') !== FALSE ? 'h2' : 'div';
        $class = $key == 'alt' ? 'description' : str_replace('field_', '', $key);
        $content[$key]['attributes'] = new Attribute();
        $content[$key]['attributes']->addClass($settings['item_id'] . '__caption--' . str_replace('_', '-', $class));
      }
    }

    return $content ? ['inline' => $content] : [];
  }

  /**
   * Returns the contents using theme_field(), or theme_item_list().
   *
   * @param array $build
   *   The array containing: settings, children elements, or optional items.
   *
   * @return array
   *   The alterable and renderable array of contents.
   */
  public function build(array $build = []) {
    $settings = $build['settings'];
    $settings['_grid'] = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));

    // If not a grid, pass the items as regular index children to theme_field().
    // This #pre_render doesn't work if called from Views results.
    if (empty($settings['_grid'])) {
      $settings = $this->prepareBuild($build);
      $build['#blazy'] = $settings;
      $build['#attached'] = $this->attach($settings);
    }
    else {
      $build = [
        '#build'      => $build,
        '#settings'   => $settings,
        '#pre_render' => [[$this, 'preRenderBuild']],
      ];
    }

    $this->moduleHandler->alter('blazy_build', $build, $settings);
    return $build;
  }

  /**
   * Builds the Blazy outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = $this->prepareBuild($build);
    $element = BlazyGrid::build($build, $settings);
    $element['#attached'] = $this->attach($settings);
    return $element;
  }

  /**
   * Prepares Blazy outputs, extract items, and return updated $settings.
   */
  public function prepareBuild(array &$build) {
    // If children are stored within items, reset.
    $settings = isset($build['settings']) ? $build['settings'] : [];
    $build = isset($build['items']) ? $build['items'] : $build;

    // Supports Blazy multi-breakpoint images if provided, updates $settings.
    // Cases: Blazy within Views gallery, or references without direct image.
    if (!empty($settings['first_image']) && !empty($settings['check_blazy'])) {

      // Views may flatten out the array, bail out.
      // What we do here is extract the formatter settings from the first found
      // image and pass its settings to this container so that Blazy Grid which
      // lacks of settings may know if it should load/ display a lightbox, etc.
      // Lightbox should work without `Use field template` checked.
      if (is_array($settings['first_image'])) {
        $this->isBlazy($settings, $settings['first_image']);
      }
    }

    unset($build['items'], $build['settings']);
    return $settings;
  }

  /**
   * Returns the entity view, if available.
   *
   * @deprecated to remove for BlazyEntity::getEntityView().
   */
  public function getEntityView($entity, array $settings = [], $fallback = '') {
    return FALSE;
  }

  /**
   * Returns the enforced content, or image using theme_blazy().
   *
   * @deprecated to remove post 2.x for self::getBlazy() for clarity.
   * FYI, most Blazy codes were originally Slick's, PHP, CSS and JS.
   * It was poorly named self::getImage() while Blazy may also contain Media
   * video with iframe element. Probably getMedia() is cool, but let's stick to
   * self::getBlazy() as Blazy also works without Image nor Media video, such as
   * with just a DIV element for CSS background.
   */
  public function getImage(array $build = []) {
    return $this->getBlazy($build);
  }

}
