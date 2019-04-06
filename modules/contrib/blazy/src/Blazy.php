<?php

namespace Drupal\blazy;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;

/**
 * Implements BlazyInterface.
 */
class Blazy implements BlazyInterface {

  /**
   * Defines constant placeholder Data URI image.
   */
  const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * The blazy HTML ID.
   *
   * @var int
   */
  private static $blazyId;

  /**
   * Prepares variables for blazy.html.twig templates.
   */
  public static function buildAttributes(&$variables) {
    $element = $variables['element'];
    foreach (BlazyDefault::themeProperties() as $key) {
      $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
    }

    // Provides optional attributes, see BlazyFilter.
    foreach (BlazyDefault::themeAttributes() as $key) {
      $key = $key . '_attributes';
      $variables[$key] = empty($element["#$key"]) ? [] : new Attribute($element["#$key"]);
    }

    // Provides sensible default html settings to shutup notices when lacking.
    $item             = $variables['item'];
    $attributes       = &$variables['attributes'];
    $image            = &$variables['image'];
    $image_attributes = &$variables['item_attributes'];
    $settings         = &$variables['settings'];
    $settings        += BlazyDefault::itemSettings();

    // Still provides a failsafe for direct theme call with a valid Image item.
    if (empty($settings['uri']) && $item) {
      $settings['uri'] = ($entity = $item->entity) && empty($item->uri) ? $entity->getFileUri() : $item->uri;
    }

    // Do not proceed if no URI is provided.
    // URI is stored within settings, not theme_blazy() property, as it is
    // always called for different purposes prior to arriving at theme_blazy().
    if (empty($settings['uri'])) {
      return;
    }

    // URL and dimensions are built out at BlazyManager::preRenderImage().
    // Still provides a failsafe for direct call to this theme.
    if (empty($settings['_api'])) {
      self::buildUrlAndDimensions($settings, $item);
    }

    // Check whether we have responsive image (no svg), or Blazy one.
    if (!empty($settings['responsive_image_style_id']) && $settings['extension'] != 'svg') {
      self::buildResponsiveImage($variables);
    }
    else {
      self::buildImage($variables);
    }

    // Image is optional for Video, and Blazy CSS background images.
    if ($image) {
      // Respects hand-coded image attributes.
      if ($item) {
        if (!isset($image_attributes['alt'])) {
          $image_attributes['alt'] = isset($item->alt) ? $item->alt : NULL;
        }

        // Do not output an empty 'title' attribute.
        if (isset($item->title) && (mb_strlen($item->title) != 0)) {
          $image_attributes['title'] = $item->title;
        }
      }

      $image_attributes['class'][] = 'media__image media__element';
      $image['#attributes'] = $image_attributes;
    }

    // Thumbnails.
    // With CSS background, IMG may be empty, add thumbnail to the container.
    // Supports unique thumbnail different from main image, such as logo for
    // thumbnail and main image for company profile.
    if (!empty($settings['thumbnail_uri'])) {
      $attributes['data-thumb'] = file_url_transform_relative(file_create_url($settings['thumbnail_uri']));
    }
    elseif (!empty($settings['thumbnail_style'])) {
      $attributes['data-thumb'] = ImageStyle::load($settings['thumbnail_style'])->buildUrl($settings['uri']);
    }

    // Prepares a media player, and allows a tiny video preview without iframe.
    $media = !empty($settings['embed_url']) && in_array($settings['type'], ['audio', 'video']);
    if ($media && empty($settings['_noiframe'])) {
      self::buildIframeAttributes($variables);
    }
  }

  /**
   * Modifies variables for responsive image.
   */
  public static function buildResponsiveImage(&$variables) {
    $image = &$variables['image'];
    $settings = &$variables['settings'];

    $image['#type'] = 'responsive_image';
    $image['#responsive_image_style_id'] = $settings['responsive_image_style_id'];
    $image['#uri'] = $settings['uri'];

    // Responsive images with height and width save a lot of calls to
    // image.factory service for every image and breakpoint in
    // _responsive_image_build_source_attributes(). Very necessary for
    // external file system like Amazon S3.
    if (empty($image['#width']) || empty($image['#height'])) {
      $image['#width'] = $settings['width'];
      $image['#height'] = $settings['height'];
    }

    // Disable aspect ratio which is not yet supported due to complexity.
    $settings['ratio'] = FALSE;
  }

  /**
   * Modifies variables for blazy (non-)lazyloaded image.
   */
  public static function buildImage(&$variables) {
    $image = &$variables['image'];
    $settings = &$variables['settings'];
    $image_attributes = &$variables['item_attributes'];

    // Supports non-lazyloaded image.
    $image['#theme'] = 'image';

    // Supports either lazy loaded image, or not, which is overriden later.
    $image['#uri'] = $settings['image_url'];

    // Aspect ratio to fix layout reflow with lazyloaded images responsively.
    // Only output dimensions for images that are not svg.
    // Respects hand-coded image attributes.
    if (!isset($image_attributes['width']) && $settings['extension'] != 'svg') {
      $image_attributes['height'] = $settings['height'];
      $image_attributes['width'] = $settings['width'];
    }

    // Supports lazyload: blazy, ondemand, progressive, etc. or just TRUE.
    if (!empty($settings['lazy'])) {
      $image['#uri'] = static::PLACEHOLDER;

      // BC for calling this theme directly bypassing the API.
      if (empty($settings['_api'])) {
        self::buildLazyAttributes($image_attributes, $settings);
      }

      // Image is optional for Video, and Blazy CSS background images.
      if (!empty($settings['background'])) {
        $image = [];
      }
    }
  }

  /**
   * Modifies variables for iframes.
   */
  public static function buildIframeAttributes(&$variables) {
    // Prepares a media player, and allows a tiny video preview without iframe.
    // image : If iframe switch disabled, fallback to iframe, remove image.
    // player: If no colorbox/photobox, it is an image to iframe switcher.
    // data- : Gets consistent with colorbox to share JS manipulation.
    $settings           = &$variables['settings'];
    $variables['image'] = empty($settings['media_switch']) ? [] : $variables['image'];
    $settings['player'] = empty($settings['player']) ? (empty($settings['lightbox']) && $settings['media_switch'] != 'content') : $settings['player'];
    $iframe['data-src'] = $settings['embed_url'];
    $iframe['src']      = 'about:blank';
    $iframe['class'][]  = 'b-lazy';

    // Prevents broken iframe when aspect ratio is empty.
    if (empty($settings['ratio']) && !empty($settings['width'])) {
      $iframe['width']  = $settings['width'];
      $iframe['height'] = $settings['height'];
    }

    // Pass iframe attributes to template.
    $variables['iframe_attributes'] = new Attribute($iframe);

    // Iframe is removed on lazyloaded, puts data at non-removable storage.
    $variables['attributes']['data-media'] = Json::encode(['type' => $settings['type'], 'scheme' => $settings['scheme']]);
  }

  /**
   * Defines attributes, builtin, or supported lazyload such as Slick.
   */
  private static function buildLazyAttributes(array &$attributes, $settings = []) {
    $attributes['class'][] = $settings['lazy_class'];
    $attributes['data-' . $settings['lazy_attribute']] = $settings['image_url'];
  }

  /**
   * Provides re-usable breakpoint data-attributes.
   *
   * These attributes can be applied to either IMG or DIV as CSS background.
   *
   * $settings['breakpoints'] must contain: xs, sm, md, lg breakpoints with
   * the expected keys: width, image_style.
   *
   * @see self::buildAttributes()
   */
  public static function buildBreakpointAttributes(array &$attributes = [], array &$settings = []) {
    self::buildLazyAttributes($attributes, $settings);

    // Only provide multi-serving image URLs if breakpoints are provided.
    if (empty($settings['breakpoints'])) {
      return;
    }

    $srcset = $json = [];
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if ($style = ImageStyle::load($breakpoint['image_style'])) {
        $url = file_url_transform_relative($style->buildUrl($settings['uri']));

        // Supports multi-breakpoint aspect ratio with irregular sizes.
        // Yet, only provide individual dimensions if not already set.
        // @see Drupal\blazy\BlazyManager::setDimensionsOnce().
        if (!empty($settings['_breakpoint_ratio']) && empty($settings['blazy_data']['dimensions'])) {
          $dimensions = [
            'width'  => $settings['width'],
            'height' => $settings['height'],
          ];

          $style->transformDimensions($dimensions, $settings['uri']);
          if ($width = self::widthFromDescriptors($breakpoint['width'])) {
            $json[$width] = round((($dimensions['height'] / $dimensions['width']) * 100), 2);
          }
        }

        $settings['breakpoints'][$key]['url'] = $url;

        // Recheck library if multi-styled BG is still supported anyway.
        // Confirmed: still working with GridStack multi-image-style per item.
        if (!empty($settings['background'])) {
          $attributes['data-src-' . $key] = $url;
        }
        else {
          $width = trim($breakpoint['width']);
          $width = is_numeric($width) ? $width . 'w' : $width;
          $srcset[] = $url . ' ' . $width;
        }
      }
    }

    if ($srcset) {
      $settings['srcset'] = implode(', ', $srcset);

      $attributes['srcset'] = '';
      $attributes['data-srcset'] = $settings['srcset'];
      $attributes['sizes'] = '100w';

      if (!empty($settings['sizes'])) {
        $attributes['sizes'] = trim($settings['sizes']);
        unset($attributes['height'], $attributes['width']);
      }
    }

    if ($json) {
      $settings['blazy_data']['dimensions'] = $json;
    }
  }

  /**
   * Builds URLs, cache tags, and dimensions for individual image.
   */
  public static function buildUrlAndDimensions(array &$settings = [], $item = NULL) {
    // Sets dimensions.
    // VEF without image style, or image style with crop, may already set these.
    if (empty($settings['width'])) {
      $settings['width'] = $item && isset($item->width) ? $item->width : NULL;
      $settings['height'] = $item && isset($item->height) ? $item->height : NULL;
    }

    // Respects a few scenarios:
    // 1. Blazy Filter or unmanaged file with/ without valid URI.
    // 2. Hand-coded image_url with/ without valid URI.
    // 3. Respects first_uri without image_url such as colorbox/zoom-like.
    // 4. File API via field formatters or Views fields/ styles with valid URI.
    // If we have a valid URI, provides the correct image URL.
    // Otherwise leave it as is, likely hotlinking to external/ sister sites.
    // Hence URI validity is not crucial in regards to anything but #4.
    // The image will fail silently at any rate given unexpected URI.
    $image_url = file_valid_uri($settings['uri']) ? file_url_transform_relative(file_create_url($settings['uri'])) : $settings['uri'];
    $settings['image_url'] = $settings['image_url'] ?: $image_url;

    // Image style modifier can be multi-style images such as GridStack.
    if (!empty($settings['image_style']) && ($style = ImageStyle::load($settings['image_style']))) {
      $settings['image_url'] = file_url_transform_relative($style->buildUrl($settings['uri']));
      $settings['cache_tags'] = $style->getCacheTags();

      // Only re-calculate dimensions if not cropped, nor already set.
      if (empty($settings['_dimensions'])) {
        $dimensions = [
          'width'  => $settings['width'],
          'height' => $settings['height'],
        ];

        $style->transformDimensions($dimensions, $settings['uri']);
        $settings['height'] = $dimensions['height'];
        $settings['width'] = $dimensions['width'];
      }
    }

    // Just in case, an attempted kidding gets in the way.
    $settings['image_url'] = UrlHelper::stripDangerousProtocols($settings['image_url']);
  }

  /**
   * Gets the numeric "width" part from a descriptor.
   */
  public static function widthFromDescriptors($descriptor = '') {
    // Dynamic multi-serving aspect ratio with backward compatibility.
    $descriptor = trim($descriptor);
    if (is_numeric($descriptor)) {
      return (int) $descriptor;
    }

    // Cleanup w descriptor to fetch numerical width for JS aspect ratio.
    $width = strpos($descriptor, "w") !== FALSE ? str_replace('w', '', $descriptor) : $descriptor;

    // If both w and x descriptors are provided.
    if (strpos($descriptor, " ") !== FALSE) {
      // If the position is expected: 640w 2x.
      list($width, $px) = array_pad(array_map('trim', explode(" ", $width, 2)), 2, NULL);

      // If the position is reversed: 2x 640w.
      if (is_numeric($px) && strpos($width, "x") !== FALSE) {
        $width = $px;
      }
    }

    return is_numeric($width) ? (int) $width : FALSE;
  }

  /**
   * Overrides variables for responsive-image.html.twig templates.
   *
   * @todo move this into BlazyManager::preRenderImage() if you can.
   */
  public static function preprocessResponsiveImage(&$variables) {
    $config = \Drupal::service('blazy.manager')->configLoad();

    // Prepare all <picture> [data-srcset] attributes on <source> elements.
    if (!$variables['output_image_tag']) {
      /** @var \Drupal\Core\Template\Attribute $source */
      if (isset($variables['sources']) && is_array($variables['sources'])) {
        foreach ($variables['sources'] as &$source) {
          $srcset = $source['srcset'];
          $srcset_values = $srcset->value();

          $source->setAttribute('data-srcset', $srcset_values);
          $source->removeAttribute('srcset');
        }
      }

      // Fetches the picture element fallback URI, and empty it later, 8.x-3+.
      $fallback_uri = $variables['img_element']['#uri'];

      // Cleans up the no-longer relevant attributes for controlling element.
      unset($variables['attributes']['data-srcset'], $variables['img_element']['#attributes']['data-srcset']);
      $variables['img_element']['#srcset'] = '';

      // Prevents invalid IMG tag when one pixel placeholder is disabled.
      $variables['img_element']['#uri'] = static::PLACEHOLDER;
    }
    else {
      $srcset = $variables['attributes']['srcset'];
      $srcset_values = $srcset->value();
      $fallback_uri = $variables['img_element']['#uri'];

      $variables['attributes']['data-srcset'] = $srcset_values;
      $variables['img_element']['#attributes']['data-srcset'] = $srcset_values;
      $variables['img_element']['#attributes']['srcset'] = '';
    }

    // Blazy needs controlling element to have fallback [data-src], else error.
    $variables['img_element']['#attributes']['data-src'] = $fallback_uri;
    $variables['img_element']['#attributes']['class'][] = 'b-lazy b-responsive';

    // Only replace fallback image URI with 1px placeholder, if so configured.
    // This prevents double-downloading the fallback image.
    if ($config['one_pixel']) {
      $variables['img_element']['#uri'] = static::PLACEHOLDER;
    }

    $variables['img_element']['#attached']['drupalSettings']['blazy'] = $config['blazy'];
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  public static function configSchemaInfoAlter(array &$definitions, $formatter = 'blazy_base', $settings = []) {
    if (isset($definitions[$formatter])) {
      $mappings = &$definitions[$formatter]['mapping'];
      $settings = $settings ?: BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
      foreach ($settings as $key => $value) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($value);
        $type = $type == 'double' ? 'float' : $type;
        $mappings[$key]['type'] = $key == 'breakpoints' ? 'mapping' : (is_array($value) ? 'sequence' : $type);

        if (!is_array($value)) {
          $mappings[$key]['label'] = Unicode::ucfirst(str_replace('_', ' ', $key));
        }
      }

      if (isset($mappings['breakpoints'])) {
        foreach (BlazyDefault::getConstantBreakpoints() as $breakpoint) {
          $mappings['breakpoints']['mapping'][$breakpoint]['type'] = 'mapping';
          foreach (['breakpoint', 'width', 'image_style'] as $item) {
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['type']  = 'string';
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['label'] = Unicode::ucfirst(str_replace('_', ' ', $item));
          }
        }
      }
    }
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   *
   * @todo recheck if any core method for this aside from file_build_uri().
   */
  public static function buildUri($image_url) {
    if (!UrlHelper::isExternal($image_url) && $normal_path = UrlHelper::parse($image_url)['path']) {
      $public_path = Settings::get('file_public_path');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return file_build_uri($rel_path);
      }
    }
    return FALSE;
  }

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'blazy', $id = '') {
    if (!isset(static::$blazyId)) {
      static::$blazyId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    $id = empty($id) ? ($string . '-' . ++static::$blazyId) : $id;
    return trim(str_replace('_', '-', strip_tags($id)));
  }

  /**
   * Builds URLs, cache tags, and dimensions for individual image.
   *
   * @deprecated to be removed for self::buildUrlAndDimensions().
   */
  public static function buildUrl(array &$settings = [], $item = NULL) {
    self::buildUrlAndDimensions($settings, $item);
  }

}
