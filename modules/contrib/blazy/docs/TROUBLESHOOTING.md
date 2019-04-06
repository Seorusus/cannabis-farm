***
***

## TROUBLESHOOTING AND KNOWN ISSUES
Resizing is not supported. Just reload the page.


### 1. VIEWS INTEGRATION
Blazy provides a simple Views field for File Entity, and Media.

When using Blazy formatter within Views, check **Use field template** under
**Style settings**, if trouble with Blazy Formatter as a stand alone Views
output.

On the contrary, uncheck **Use field template**, when Blazy formatter
is embedded inside another module such as Slick so to pass the renderable
array to work with accordingly.

This is a Views common gotcha with field formatter, so be aware of it.
If confusing, just toggle **Use field template**, and see the output. You'll
know which works.


### 2. BLAZY GRID WITH SINGLE VALUE FIELD (D7 ONLY)
This is no issue at D8. Blazy Grid formatter is designed for multi-value fields.
Unfortunately no handy way to disable formatters for single value at D7. So
the formatter is available even for single value, but not actually
functioning. Please ignore it till we can get rid of it at D7, if possible,
without extra legs.

### 3. MIN-WIDTH
If the images appear to be shrink within a **floating** container, add
some expected width or min-width to the parent container via CSS accordingly.
Non-floating image parent containers aren't affected.

### 4. MIN-HEIGHT
Add a min-height CSS to individual element to avoid layout reflow if not using
**Aspect ratio** or when **Aspect ratio** is not supported such as with
Responsive image. Otherwise some collapsed image containers will defeat the
purpose of lazyloading. When using CSS background, the container may also be
collapsed.

### 5. SOLUTIONS
Both layout reflow and lazyloading delay issues are actually taken care of
if **Aspect ratio** option is enabled in the first place.

Adjust, and override blazy CSS/ JS files accordingly.

### 6. BLAZY FILTER
Blazy Filter must run after **Align/ Caption filters** as otherwise the required
CSS class `b-lazy` will be moved into `<figure>` elements and make Blazy fail
with JS error due to not finding the required `SRC` and `[data-src]` attributes.
**Align/ Caption filters** output are respected and moved into Blazy markups
accordingly when Blazy Filter runs after them.
