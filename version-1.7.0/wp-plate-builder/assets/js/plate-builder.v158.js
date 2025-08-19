/* WP Plate Builder JS v1.5.8 */
/* WP Plate Builder JS v1.5.7 */
/* WP Plate Builder JS v1.5.6 */
/**
 * Interactivity logic for the number plate builder.
 *
 * This script is written in plain ES6 without relying on jQuery. It
 * initialises each instance of the plate builder by wiring up event
 * listeners to all controls and updating the preview and price in real
 * time. It also hooks into Elementor's frontend hooks to re‑initialise
 * builders when widgets are loaded dynamically in the editor.
 */

(function () {
  /**
   * Utility to format a numeric price to two decimal places.
   *
   * @param {number|string} num
   * @returns {string}
   */
  function formatPrice(num) {
    return parseFloat(num).toFixed(2);
  }

  // Default pricing and sizing used if no configuration is provided via
  // data‑config. These are sensible defaults that mirror the DemonPlates
  // baseline pricing and dimensions.
  const DEFAULT_STYLE_PRICES = {
    standard: 14.95,
    '3d': 26.0,
    '4d': 26.0,
    '5d': 35.5,
    '5mm3mm4d': 26.0,
    '4dgel': 26.0,
    ghost: 30.0,
    piano: 30.0,
    matte: 28.0,
    shortstyle: 20.0
  };
  const DEFAULT_PLATE_SIZES = {
    // Default sizes approximating DemonPlates preview dimensions.
    std: { width: 520, height: 140 },
    short: { width: 350, height: 140 },
    square: { width: 285, height: 285 },
    hex: { width: 520, height: 140 }
  };
  // Note: motorcycle size has been removed by default. Site editors can add it back via Elementor if needed.
  const DEFAULT_SURROUND_COSTS = {
    none: { single: 0, pair: 0 },
    plain: { single: 28, pair: 50 },
    marque: { single: 28, pair: 50 }
  };

  /**
   * Initialise a plate builder instance.
   *
   * Each builder instance is isolated and manages its own state. It
   * attaches event listeners to inputs inside the provided container
   * element and updates the preview and price whenever selections change.
   *
   * @param {HTMLElement} container
   */
  function initPlateBuilder(container) {
    if (!container) return;
    // Prevent double initialisation by marking the container.
    if (container.dataset.initialised) return;
    container.dataset.initialised = 'true';

    // Query all relevant elements within this builder.
    const frontPlate = container.querySelector('.front-plate');
    const rearPlate = container.querySelector('.rear-plate');
    const priceEl = container.querySelector('.price-value');
    const plateCountInputs = Array.from(
      container.querySelectorAll('input[name="plate_count"]')
    );
    const regInput = container.querySelector('.plate-registration');
    const textStyleSelect = container.querySelector('.text-style');
    const badgePicker = container.querySelector('.badge-picker');
    const borderPicker = container.querySelector('.border-picker');
    const plateSizeSelect = container.querySelector('.plate-size');
    const plateSurroundSelect = container.querySelector('.plate-surround');
    const electricSelect = container.querySelector('.electric-option');
    const plateUseInputs = Array.from(
      container.querySelectorAll('input[name="plate_use_type"]')
    );

    // Error message element for registration validation
    const regError = container.querySelector('.reg-error');

    // Track the currently selected border colour. This is applied to the
    // outline of the SVG plates when they are re-rendered. A value of
    // 'none' or 'transparent' indicates no border should be drawn.
    let currentBorderColour = '#222';

    /**
     * Generate and insert an SVG representation of a number plate into the
     * provided <svg> element.  The drawing includes the background
     * rectangle/arrow, a coloured badge strip and the registration text.  All
     * dimensions, colours and content are derived from the current
     * configuration state (selected size, badge colour, electric option and
     * border colour).  The caller should pass a flag indicating whether
     * this plate is a rear plate so the correct gradient colours are
     * applied.
     *
     * @param {SVGElement} svgEl The <svg> element to populate.
     * @param {boolean} isRear   Whether this is the rear plate (affects colour).
     * @param {Object} dims      The width and height of the plate.
     */
    function renderSVG(svgEl, isRear, dims) {
      if (!svgEl || !dims) return;
      const width = dims.width;
      const height = dims.height;
      // Determine if the arrow (hex) style is selected. For the hex size we
      // create a tapered arrow on the right. The arrow length is roughly
      // 31% of the width, matching the ratio used in DemonPlates. When the
      // standard/short/square sizes are selected the arrow length is zero.
      const sizeKey = plateSizeSelect ? plateSizeSelect.value : 'std';
      // Treat any size whose key contains "hex" (case-insensitive) as an arrow
      // (hex) plate. Some configurations may label the option as
      // "Hex (arrow shape)" or similar, so checking for the substring makes
      // detection more robust.  When a hex plate is selected the arrow
      // length is a fixed proportion of the total width (about 30%).
      // Hex/arrow rendering temporarily disabled.  All plates render as
      // simple rectangles while sizing adjustments are refined.
      const isHexShape2 = false;
      const arrow = 0;
      // Choose gradient colours based on whether this is the front or rear
      // plate. Front plates are off‑white/grey; rear plates are yellow/orange.
      const gradId = `${isRear ? 'rear' : 'front'}Gradient`;
      // Adjust the gradient stops to more closely match real UK plates.  Front
      // plates have a subtle off‑white gradient that darkens towards the
      // bottom; rear plates have a brighter yellow top fading to a deeper
      // golden hue.  These colours were chosen by eye based on the
      // reference designs supplied.
      const gradStops = isRear
        ? '<stop offset="0%" stop-color="#f9e84a"/><stop offset="100%" stop-color="#cfa208"/>'
        : '<stop offset="0%" stop-color="#fafafa"/><stop offset="100%" stop-color="#d5d5d5"/>';
      // Create a drop shadow filter for the registration text when a
      // non-standard style is selected. Two successive shadows produce a
      // subtle raised effect reminiscent of embossed or domed characters.
      // A random suffix prevents ID collisions when multiple plates are
      // rendered on the same page.
      const filterId = `textShadow${Math.random().toString(36).slice(2, 7)}`;
      // Always include the drop shadow filter so that the registration
      // characters appear slightly raised on every style.  Two successive
      // drop shadows create a subtle bevelled effect without overwhelming
      // the characters.
      const textFilter = `<filter id="${filterId}"><feDropShadow dx="1" dy="1" stdDeviation="0" flood-color="#444"/><feDropShadow dx="2" dy="2" stdDeviation="0" flood-color="#777"/></filter>`;
      let extraDefs = '';
      const defs = `<defs><linearGradient id="${gradId}" x1="0%" y1="0%" x2="0%" y2="100%">${gradStops}</linearGradient>${textFilter}</defs>`;
      // Determine stroke colour and width. If the currentBorderColour is set to
      // 'none' or 'transparent', do not draw a stroke.
      let strokeColour = currentBorderColour;
      let strokeWidth = 5;
      if (!strokeColour || strokeColour === 'none' || strokeColour === 'transparent') {
        strokeColour = 'none';
        strokeWidth = 0;
      }

      // Compute an additional inner margin (in pixels) between the border and
      // the gradient fill. A small margin of 3 mm is used to emulate
      // real plates where the coloured area does not meet the border. We
      // approximate the mm-to-pixel conversion using the plate height. For
      // example, a standard UK plate is 111 mm tall, so height/111 gives
      // pixels per millimetre. If other sizes are used this approximation
      // still provides a proportional margin. The margin value can be tuned
      // to match other builders.
      const innerMarginMm = 3;
      const innerMarginPx = (height / 111) * innerMarginMm;
      // Combined offset from the outer edge to the start of the coloured
      // area (border + inner margin).  This is reused for positioning the
      // text horizontally and vertically.
      const strokeOffsetTotal = strokeWidth + innerMarginPx;
      // Build the background shapes. We draw two rectangles: an outer
      // rectangle with a coloured stroke and no fill, and an inner rectangle
      // containing the gradient fill. This creates a small inner margin
      // between the border and the coloured area, mimicking real number
      // plates where the border does not touch the lettering area.
      
      let shape;
      const sizeKeyShape = (plateSizeSelect && plateSizeSelect.value) ? plateSizeSelect.value : 'std';
      const isHexShape = sizeKeyShape && sizeKeyShape.indexOf('hex') === 0;
      const radius = Math.min(height, width) * 0.1;

      if (isHexShape) {
        const inset = Math.max(height * 0.18, 10); // angle depth
        const outer = [
          [strokeWidth + inset, strokeWidth],
          [width - strokeWidth - inset, strokeWidth],
          [width - strokeWidth, height/2],
          [width - strokeWidth - inset, height - strokeWidth],
          [strokeWidth + inset, height - strokeWidth],
          [strokeWidth, height/2]
        ].map(p => p.join(',')).join(' ');

        const fillInset = strokeOffsetTotal;
        const innerInset = Math.max(inset - fillInset, 4);
        const inner = [
          [fillInset + innerInset, fillInset],
          [width - fillInset - innerInset, fillInset],
          [width - fillInset, height/2],
          [width - fillInset - innerInset, height - fillInset],
          [fillInset + innerInset, height - fillInset],
          [fillInset, height/2]
        ].map(p => p.join(',')).join(' ');

        const outerPoly = `<polygon points="${outer}" fill="none" stroke="${strokeColour}" stroke-width="${strokeWidth}" />`;
        const innerPoly = `<polygon points="${inner}" fill="url(#${gradId})" stroke="none" />`;
        shape = outerPoly + innerPoly;

        // clip path id for badge inside shape
        extraDefs = `<clipPath id="${gradId}-clip"><polygon points="${inner}"/></clipPath>`;
      } else if (arrow > 0) {
        // Arrow plates not implemented; fall back to rectangular drawing.
        shape = `<rect x="0" y="0" width="${width}" height="${height}" rx="${radius}" ry="${radius}" fill="url(#${gradId})" stroke="${strokeColour}" stroke-width="${strokeWidth}"/>`;
      } else {
        // Rounded rectangle (standard)
        const outerOffset = strokeWidth;
        const outerW = width - 2 * strokeWidth;
        const outerH = height - 2 * strokeWidth;
        const outerRect = `<rect x="${outerOffset}" y="${outerOffset}" width="${outerW}" height="${outerH}" rx="${radius}" ry="${radius}" fill="none" stroke="${strokeColour}" stroke-width="${strokeWidth}"/>`;
        const fillOffset = strokeOffsetTotal;
        const fillW = width - 2 * strokeOffsetTotal;
        const fillH = height - 2 * strokeOffsetTotal;
        const innerRect = `<rect x="${fillOffset}" y="${fillOffset}" width="${fillW}" height="${fillH}" rx="${radius}" ry="${radius}" fill="url(#${gradId})" stroke="none"/>`;
        shape = outerRect + innerRect;
      }
// Compute badge strip dimensions. The badge occupies 15% of the plate's
      // width and spans the full height. When the electric option is
      // selected, the green flash overrides any badge colour choice.  The
      // badge position does not include the stroke offset as it sits
      // beneath the border.  If needed we could reduce its width by the
      // stroke width but the visible difference would be negligible.
      // Determine if a badge or electric flash is selected.  If neither is
      // chosen we do not reserve space for a badge strip so that the
      // registration text is centred across the full width.  When a badge
      // (or green flash) is selected we reserve a fixed proportion of the
      // width (about 8–10%) for the strip.  A value of 0.1 works well on
      // a 520 mm plate (≈52 mm) which closely matches the real world
      // national identifier area.  Adjust this ratio if your design
      // uses a narrower or wider badge.
      const hasBadge = (electricSelect && electricSelect.value && electricSelect.value !== 'none') ||
        (selectedBadgeColour && selectedBadgeColour !== 'none' && selectedBadgeColour !== 'transparent');
      const badgeRatio = 0.1;
      const badgeW = hasBadge ? width * badgeRatio : 0;
      // Determine badge fill colour.  Electric option overrides the badge
      // colour; if no badge is selected the fill is transparent.
      const badgeFill = (electricSelect && electricSelect.value && electricSelect.value !== 'none')
        ? '#00a650'
        : (hasBadge
            ? selectedBadgeColour
            : 'transparent');
      // Render the badge strip only when space is reserved.  When no
      // badge/electric flash is selected the strip is omitted.
      const badge = hasBadge
        ? `<rect class="badge-area" x="0" y="0" width="${badgeW}" height="${height}" fill="${badgeFill}" ` + (extraDefs ? `clip-path="url(#${gradId}-clip)"` : '') + `/>`
        : '';
      // Determine the registration text. Sanitize to upper‑case alphanumerics
      // and spaces. Default to 'YOUR REG' when empty.
      let reg = regInput ? regInput.value.toUpperCase() : '';
      reg = reg.replace(/[^A-Z0-9 ]/g, '');
      if (!reg) reg = 'YOUR REG';
      // Calculate a font size relative to the plate height. A factor of 0.65
      // works well across sizes to fill the plate comfortably without
      // overflowing. Letter spacing is scaled with width to provide even
      // spacing across different plate lengths.
      // Set text sizing using simple ratios.  Characters should fill
      // roughly 72% of the plate height and spacing scales with plate
      // width.  These values were chosen by comparing against
      // reference plate builders.
      // Compute precise character sizing and positioning based on DVLA
      // specifications.  A standard plate is 520 mm wide by 111 mm high.
      // Characters are 50 mm wide and 79 mm high with 11 mm spacing
      // between characters and 33 mm between the two groups.  Side and
      // top/bottom margins are 11 mm.  Convert these millimetre values
      // into pixels based on the current plate dimensions.
      /*
       * Compute character geometry based on official UK number plate
       * specifications.  Each size of plate (standard, short, square)
       * corresponds to a particular physical width and height in
       * millimetres.  Characters on road‑legal plates must be 50 mm
       * wide by 79 mm tall with a 14 mm stroke, 11 mm spacing between
       * characters, 33 mm spacing between the two groups, and 11 mm
       * margins on all sides.  These values are converted into pixels
       * relative to the current plate dimensions to ensure consistent
       * proportions across all preview sizes.  See PlateSpec.png for
       * reference.
       */
      const sizeMms = {
        std: { w: 520, h: 111 },
        short: { w: 350, h: 111 },
        square: { w: 285, h: 285 }
      };
      // Determine the current size key and fall back to std if unknown
      const keyForMm = sizeMms[sizeKey] ? sizeKey : 'std';
      const mmDims = sizeMms[keyForMm];
      /*
       * Base conversion factor: derive pixels-per-millimetre from the
       * plate height.  All official dimensions (character size,
       * spacing, margins) are defined relative to the physical height
       * of a UK plate (111 mm).  Using the height maintains the
       * correct character proportions regardless of the preview’s
       * width.
       */
      const pxPerMm = height / mmDims.h;
      // Define official millimetre measurements for characters and spacing
      const charMmWidth = 50;
      const charMmHeight = 79;
      const charMmSpacing = 11;
      const groupMmSpacing = 33;
      const marginMm = 11;
      // Initial pixel dimensions based on the height-scaling factor
      let charWidthPx = charMmWidth * pxPerMm;
      let charHeightPx = charMmHeight * pxPerMm;
      let charSpacingPx = charMmSpacing * pxPerMm;
      let groupSpacingPx = groupMmSpacing * pxPerMm;
      let marginSidePx = marginMm * pxPerMm;
      let marginTopPx = marginMm * pxPerMm;
      // Border thickness: 2 mm converted using the same factor
      const borderMm = 2;
      strokeWidth = borderMm * pxPerMm;
      /*
       * Dynamically scale down character and spacing sizes if the
       * registration string plus margins would overflow the available
       * width.  This ensures the text always fits on the plate while
       * preserving the official proportions.  We perform a trial
       * calculation using the initial sizes; if the total width
       * including margins exceeds the SVG width, we apply a uniform
       * scaling factor.
       */
      // Helper to compute the total rendered width of the registration
      // string (excluding margins).  The result includes character
      // widths and the appropriate spacing between characters and
      // groups.
      function computeTextWidth() {
        let total = 0;
        for (let i = 0; i < reg.length; i++) {
          const ch = reg[i];
          if (ch === ' ') {
            total += groupSpacingPx;
            continue;
          }
          total += charWidthPx;
          if (i < reg.length - 1 && reg[i + 1] !== ' ') {
            total += charSpacingPx;
          }
        }
        return total;
      }
      // Width reserved for the badge strip on the left.  The badge
      // occupies 15% of the plate width.
      const availableWidth = width - badgeW;
      let textWidth = computeTextWidth();
      // Include side margins in the total horizontal footprint.
      let totalWidthCandidate = textWidth + 2 * marginSidePx;
      if (totalWidthCandidate > availableWidth) {
        const scale = availableWidth / totalWidthCandidate;
        charWidthPx *= scale;
        charHeightPx *= scale;
        charSpacingPx *= scale;
        groupSpacingPx *= scale;
        marginSidePx *= scale;
        marginTopPx *= scale;
        strokeWidth *= scale;
        // Recompute widths after scaling
        textWidth = computeTextWidth();
        totalWidthCandidate = textWidth + 2 * marginSidePx;
      }
      // Build tspans for each character, positioning each at the
      // centre of its allotted cell.  Spaces advance the cursor by
      // the larger group spacing.  Using text-anchor="middle" on the
      // parent <text> element ensures each character is centred on its
      // x coordinate.
      
      // Build tspans for one or two-line layout depending on plate size
      let tspanStr = '';
      const sizeKeyText = (plateSizeSelect && plateSizeSelect.value) ? plateSizeSelect.value : 'std';
      const isTwoLine = sizeKeyText === 'std_4x4';
      let lineGapMm = 19; // DVLA approximate line gap
      let lineGapPx = lineGapMm * pxPerMm;

      function buildTspansFor(text) {
        let out = '';
        let xCurLocal = 0;
        for (let i = 0; i < text.length; i++) {
          const ch = text[i];
          if (ch === ' ') { xCurLocal += groupSpacingPx; continue; }
          const cx = xCurLocal + charWidthPx / 2;
          out += `<tspan x="${cx}" y="0" text-anchor="middle">${ch}</tspan>`;
          xCurLocal += charWidthPx;
          if (i < text.length - 1 && text[i + 1] !== ' ') { xCurLocal += charSpacingPx; }
        }
        return { out, width: xCurLocal };
      }

      var textEl;
      if (isTwoLine) {
        // Split reg into two groups (by space); if no space, split evenly-ish
        let parts = reg.split(' ');
        if (parts.length < 2) {
          const mid = Math.ceil(reg.length / 2);
          parts = [reg.slice(0, mid), reg.slice(mid)];
        }
        const top = buildTspansFor(parts[0]);
        const bottom = buildTspansFor(parts[1]);
        const totalWidthUsed = Math.max(top.width, bottom.width);
        // Stack two <text> elements inside a wrap <g>; y offsets are +/- (charHeight/2 + gap/2)
        const yTop = -(charHeightPx/2 + lineGapPx/2);
        const yBottom = +(charHeightPx/2 + lineGapPx/2);
        const topText = `<text class="reg-text" dominant-baseline="middle" style="${textStyle}"${filterAttr} transform="translate(0,${yTop})">${top.out}</text>`;
        const bottomText = `<text class="reg-text" dominant-baseline="middle" style="${textStyle}"${filterAttr} transform="translate(0,${yBottom})">${bottom.out}</text>`;
        tspanStr = topText + bottomText;
        // Wrap without setting absolute y; we'll BBox-centre afterward
        textEl = `<g class="reg-wrap">${tspanStr}</g>`;
        // Use the larger width for horizontal centring calculations below
        totalTextWidth = totalWidthUsed;
        textWidth = totalWidthUsed;
      } else {
        // Single-line as before
        for (let i = 0; i < reg.length; i++) {
          const ch = reg[i];
          if (ch === ' ') { continue; }
          const cx = (function(){let acc=0; for (let j=0;j<i;j++){ acc += (reg[j]===' ')? groupSpacingPx : charWidthPx + (reg[j+1] && reg[j+1] !== ' ' ? charSpacingPx : 0);} return acc; })() + charWidthPx/2;
          tspanStr += `<tspan x="${cx}" y="0" text-anchor="middle">${ch}</tspan>`;
        }
        textEl = `<g class="reg-wrap"><text class="reg-text" text-anchor="middle" dominant-baseline="middle" style="${textStyle}"${filterAttr}>${tspanStr}</text></g>`;
      }
const textY = strokeOffsetTotal + marginTopPx + charHeightPx / 2;
      textEl = `<g class="reg-wrap"><text class="reg-text" y="${textY}" dominant-baseline="middle" text-anchor="middle" style="${textStyle}"${filterAttr}>${tspanStr}</text></g>`;
      // Apply dimensions and viewBox then assemble the SVG content.
      svgEl.setAttribute('width', width);
      svgEl.setAttribute('height', height);
      svgEl.setAttribute('viewBox', `0 0 ${width} ${height}`);
      svgEl.innerHTML = defs + (extraDefs || '') + shape + badge + textEl;
    
    // BBox centring (horizontal + vertical), with small per-size nudges
    (function(){ try {
      var wrap = svgEl.querySelector('g.reg-wrap');
      if (wrap) {
        var bbox = wrap.getBBox();
        var usableWidth = width - badgeW - 2 * marginSidePx - 2 * strokeOffsetTotal;
        var leftTarget = strokeOffsetTotal + badgeW + marginSidePx + (usableWidth - bbox.width) / 2;
        var dx = Math.round(leftTarget - bbox.x);

        var usableHeight = height - 2 * strokeOffsetTotal - 2 * marginTopPx;
        var topTarget = strokeOffsetTotal + marginTopPx + (usableHeight - bbox.height) / 2;
        var dy = Math.round(topTarget - bbox.y);

        // Per-size downward tweak (mm): large rear & short plates
        var sizeKeyTweak = (plateSizeSelect && plateSizeSelect.value) ? plateSizeSelect.value : 'std';
        var tweakMmMap = { 'large_rear': 1.5, 'short6': 1.5, 'short5': 1.5, 'short4': 1.5 };
        var mmFactor = height / 111;
        if (tweakMmMap[sizeKeyTweak]) { dy += Math.round(tweakMmMap[sizeKeyTweak] * mmFactor); }

        wrap.setAttribute('transform', 'translate(' + dx + ',' + dy + ')');
      }
    } catch(e){} })();

    }

    /**
     * Retrieve the dimensions for the currently selected plate size. Falls
     * back to the default size if the select element is missing.
     *
     * @returns {{width:number,height:number}} The width and height in pixels.
     */
    function getSelectedDims() {
      const key = plateSizeSelect ? plateSizeSelect.value : 'std';
      const dims = plateSizes[key] || DEFAULT_PLATE_SIZES.std;
      return dims;
    }

    /**
     * Render both front and rear plates according to the current state.
     * This helper centralises the drawing logic so that any change to
     * configuration (size, registration text, badge colour, electric option
     * or border colour) simply calls this function to update the preview.
     */
    function renderAll() {
      const dims = getSelectedDims();
      // Only render if the SVG elements exist
      if (frontPlate) renderSVG(frontPlate, false, dims);
      if (rearPlate) renderSVG(rearPlate, true, dims);
    }

    // Parse configuration from data-config attribute. This config is
    // generated by PHP in the widget render method and contains pricing
    // information and dimensions keyed by values from the select lists.
    let config = {};
    const cfgJson = container.getAttribute('data-config');
    if (cfgJson) {
      try {
        config = JSON.parse(cfgJson);
      } catch (e) {
        config = {};
      }
    }
    const stylePrices = config.stylePrices || DEFAULT_STYLE_PRICES;
    const plateSizes = config.plateSizes || DEFAULT_PLATE_SIZES;
    const getMaxForSize = (key) => {
      const rec = plateSizes[key] || {};
      return typeof rec.max === 'number' ? rec.max : 10;
    };

    const surroundCosts = config.surroundCosts || DEFAULT_SURROUND_COSTS;
    const pairDiscount =
      typeof config.pairDiscount !== 'undefined'
        ? parseFloat(config.pairDiscount)
        : 1.0;

    // Form integration: if the container specifies a form selector, prepare
    // hidden fields in that form and synchronise values on every update.
    const formSelector = container.dataset.formSelector;
    let targetForm = null;
    let hiddenFields = {};
    if (formSelector) {
      targetForm = document.querySelector(formSelector);
      if (targetForm) {
        // Create hidden inputs for each option if not present.
        const fields = [
          'plate_count',
          'plate_use_type',
          'registration',
          'plate_size',
          'text_style',
          'badge_colour',
          'border_colour',
          'plate_surround',
          'electric'
        ];
        fields.forEach((name) => {
          let input = targetForm.querySelector(`input[name="${name}"]`);
          if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            targetForm.appendChild(input);
          }
          hiddenFields[name] = input;
        });
      }
    }

    /**
     * Update the registration text displayed on each plate. Converts
     * lower‑case to upper‑case and strips invalid characters.
     */
    function updateRegistration() {
      let reg = regInput.value.toUpperCase();
      reg = reg.replace(/[^A-Z0-9 ]/g, '');
      regInput.value = reg;
      const text = reg || 'YOUR REG';
      const frontReg = frontPlate.querySelector('.reg-text');
      const rearReg = rearPlate.querySelector('.reg-text');
      if (frontReg) frontReg.textContent = text;
      if (rearReg) rearReg.textContent = text;
      validateRegistration(reg);
      // Re-render the plates so the new registration appears in the SVG.
      renderAll();
    }

    /**
     * Validate the entered registration against common UK and Northern Irish
     * number plate formats. If the value is non‑empty and does not match
     * any valid pattern, an error message is shown. Otherwise the error
     * message is hidden.
     *
     * @param {string} reg
     */
    function validateRegistration(reg) {
      if (!regError) return;
      // Trim spaces for validation
      const noSpace = reg.replace(/\s+/g, '');
      // Empty input is allowed
      if (!noSpace) {
        regError.classList.remove('visible');
        regError.textContent = '';
        return;
      }
      // Patterns for modern UK plates (AB12CDE), prefix (A123BCD),
      // Northern Irish (ABC1234) and dateless numbers (1234ABC)
      const patterns = [
        /^[A-Z]{2}[0-9]{2}[A-Z]{3}$/,      // current style
        /^[A-Z][0-9]{1,3}[A-Z]{3}$/,        // prefix style
        /^[A-Z]{3}[0-9]{1,4}$/,             // Northern Irish dateless (JAZ6789)
        /^[0-9]{1,4}[A-Z]{1,3}$/            // Pre‑1960s (7654NB)
      ];
      const valid = patterns.some((pat) => pat.test(noSpace));
      if (!valid) {
        regError.textContent =
          'Invalid UK number plate format. Examples: AB12 CDE, A123 BCD, JAZ 6789';
        regError.classList.add('visible');
      } else {
        regError.classList.remove('visible');
        regError.textContent = '';
      }
    }

    /**
     * Show or hide the front/rear plates based on the selected count.
     * Also updates the price after toggling.
     */
    function updatePlateVisibility() {
      const count = container.querySelector(
        'input[name="plate_count"]:checked'
      )?.value;
      if (count === 'both') {
        frontPlate.style.display = '';
        rearPlate.style.display = '';
      } else if (count === 'front') {
        frontPlate.style.display = '';
        rearPlate.style.display = 'none';
      } else {
        frontPlate.style.display = 'none';
        rearPlate.style.display = '';
      }
      // Ensure the SVGs are updated in case the hidden plate is later
      // re‑enabled with stale content or dimensions.
      renderAll();
    }

    /**
     * Update hidden form fields (if a form is bound) with the current
     * selections. Called after any change to ensure the form reflects
     * the latest user input when submitted.
     */
    function updateFormFields() {
      if (!targetForm || !hiddenFields) return;
      // Plate count
      const countVal = container.querySelector(
        'input[name="plate_count"]:checked'
      )?.value;
      hiddenFields['plate_count'].value = countVal || '';
      // Plate use type
      const useVal = container.querySelector(
        'input[name="plate_use_type"]:checked'
      )?.value;
      hiddenFields['plate_use_type'].value = useVal || '';
      // Registration text
      hiddenFields['registration'].value = regInput.value || '';
      // Plate size
      hiddenFields['plate_size'].value = plateSizeSelect
        ? plateSizeSelect.value
        : '';
      // Text style
      hiddenFields['text_style'].value = textStyleSelect
        ? textStyleSelect.value
        : '';
      // Badge colour: use selectedBadgeColour or empty
      hiddenFields['badge_colour'].value = selectedBadgeColour || '';
      // Border colour: derive from border style
      // Store the current border colour. Blank if none selected.
      hiddenFields['border_colour'].value =
        currentBorderColour && currentBorderColour !== 'none' && currentBorderColour !== 'transparent'
          ? currentBorderColour
          : '';
      // Plate surround
      hiddenFields['plate_surround'].value = plateSurroundSelect
        ? plateSurroundSelect.value
        : '';
      // Electric
      hiddenFields['electric'].value = electricSelect
        ? electricSelect.value
        : '';
    }

    /**
     * Apply the selected text style class (standard, 3d, 4d, 5d) to the
     * registration text elements.
     */
    function applyTextStyle() {
      const style = textStyleSelect.value;
      const regTexts = container.querySelectorAll('.plate .reg-text');
      // Remove any existing style-* classes before adding the new one.
      const knownStyles = [
        'standard',
        '3d',
        '4d',
        '5d',
        '5mm3mm4d',
        '4dgel',
        'ghost',
        'piano',
        'matte',
        'shortstyle'
      ];
      regTexts.forEach((el) => {
        knownStyles.forEach((st) => {
          el.classList.remove('style-' + st);
        });
        el.classList.add('style-' + style);
      });
      // Re-render to apply any style-specific changes via filters in the SVG.
      renderAll();
    }

    /**
     * Update the dimensions of the plates when a new size is selected.
     */
    function updatePlateSize() {
      // Re-render plates when the size changes. The renderAll helper will
      // rebuild the SVGs using the new dimensions and arrow shape.
      renderAll();
    }

    /**
     * Update surround appearance (padding and border) based on selection.
     */
    function updateSurround() {
      const sur = plateSurroundSelect.value;
      const apply = sur && sur !== 'none';
      if (apply) {
        frontPlate.classList.add('with-surround');
        rearPlate.classList.add('with-surround');
      } else {
        frontPlate.classList.remove('with-surround');
        rearPlate.classList.remove('with-surround');
      }
      // Surround does not change the SVG geometry, but re-render so that
      // any calculated sizes or borders remain in sync.
      renderAll();
    }

    /**
     * Update the electric car badge (adds green stripe). This does not
     * affect pricing but toggles the electric class.
     */
    function updateElectric() {
      const val = electricSelect.value;
      const apply = val && val !== 'none';
      if (apply) {
        frontPlate.classList.add('electric');
        rearPlate.classList.add('electric');
      } else {
        frontPlate.classList.remove('electric');
        rearPlate.classList.remove('electric');
      }
      // Re-render so the badge colour or electric flash is reflected in
      // the SVG plates.
      renderAll();
    }

    /**
     * Calculate and update the displayed price based on selected options.
     */
    function updatePrice() {
      const count = container.querySelector(
        'input[name="plate_count"]:checked'
      )?.value;
      const style = textStyleSelect.value;
      const surround = plateSurroundSelect.value;
      const perPlate = stylePrices[style] || stylePrices.standard;
      const platesNum = count === 'both' ? 2 : 1;
      let price = perPlate * platesNum;
      if (platesNum === 2) {
        price -= pairDiscount;
      }
      if (surround && surround !== 'none') {
        const costs = surroundCosts[surround] || { single: 0, pair: 0 };
        price += platesNum === 2 ? costs.pair : costs.single;
      }
      priceEl.textContent = formatPrice(price);
    }

    /**
     * Ensure road legal plates restrict text style to standard only. Show
     * other styles for show plates.
     */
    function updatePlateUseType() {
      const use = container.querySelector(
        'input[name="plate_use_type"]:checked'
      )?.value;
      if (!textStyleSelect) return;
      if (use === 'road') {
        // When road legal is selected, only styles marked as road legal
        // (data-legal="yes") should be available. Others are disabled.
        let firstLegal = null;
        Array.from(textStyleSelect.options).forEach((opt) => {
          const legal = opt.dataset.legal === 'yes';
          opt.disabled = !legal;
          if (legal && !firstLegal) firstLegal = opt.value;
        });
        // If the currently selected option is not legal, reset to the first legal option.
        const current = textStyleSelect.value;
        const currentOpt = textStyleSelect.querySelector(
          `option[value="${current}"]`
        );
        const currentLegal = currentOpt && currentOpt.dataset.legal === 'yes';
        if (!currentLegal && firstLegal) {
          textStyleSelect.value = firstLegal;
        }
      } else {
        // Enable all styles for show plates.
        Array.from(textStyleSelect.options).forEach((opt) => {
          opt.disabled = false;
        });
      }
      applyTextStyle();
    }

    // Track the currently selected badge colour. If null, no badge colour is selected.
    let selectedBadgeColour = null;

    /**
     * Apply the stored badge colour or electric stripe to the badge area.
     * If an electric plate is selected, the CSS rule will set the
     * background colour and any inline badge colour is removed. Otherwise the
     * selected badge colour is applied (or transparent if none).
     */
    function applyBadgeStyle() {
      // Badge styles are now handled when rendering the SVG plates.  This
      // function remains for backward compatibility and simply re-renders
      // the plates based on the latest state.
      renderAll();
    }

    /**
     * Store and highlight the chosen badge colour. Once stored, call
     * applyBadgeStyle() to update the plates. A selected swatch will be
     * highlighted with the primary colour.
     *
     * @param {HTMLElement} selectedSwatch
     */
    function selectBadgeColour(selectedSwatch) {
      // Clear previous selection
      badgePicker
        .querySelectorAll('.badge-colour')
        .forEach((el) => el.classList.remove('selected'));
      selectedSwatch.classList.add('selected');
      // Store the chosen badge colour. When electric mode is active the
      // badge colour is ignored.
      selectedBadgeColour = selectedSwatch.dataset.colour || 'transparent';
      // Re-render so that the badge strip reflects the new colour.
      renderAll();
    }

    /**
     * Apply the selected border colour to the plate border. Highlight the
     * selected swatch in the UI.
     *
     * @param {HTMLElement} selectedSwatch
     */
    function selectBorderColour(selectedSwatch) {
      // Highlight the selected swatch in the UI
      borderPicker
        .querySelectorAll('.border-colour')
        .forEach((el) => el.classList.remove('selected'));
      selectedSwatch.classList.add('selected');
      // Store the chosen border colour. If 'none' or 'transparent' is
      // selected the border will be removed when rendering the SVG.
      const colour = selectedSwatch.dataset.colour || 'transparent';
      currentBorderColour = colour;
      // Re-render the plates to apply the new border colour.
      renderAll();
    }

    
    if (plateSizeSelect && regInput) {
      const applyMax = () => {
        const key = plateSizeSelect.value || 'std';
        const m = getMaxForSize(key);
        regInput.setAttribute('maxlength', String(m));
        // Trim existing value to max characters (ignoring spaces)
        const raw = regInput.value.toUpperCase().replace(/[^A-Z0-9 ]/g, '');
        const trimmed = raw.replace(/\s+/g, '').slice(0, m);
        regInput.value = trimmed;
        updateRegistration();
      };
      plateSizeSelect.addEventListener('change', () => { applyMax(); renderAll(); applyMaxForSize();
        updatePrice(); updateFormFields(); });
      // Initialise on load
      applyMax();
    }

    // Ensure maxlength tracks the selected size on load and change
    function applyMaxForSize() {
      try {
        if (!regInput || !plateSizeSelect) return;
        const key = plateSizeSelect.value || 'std';
        const m = getMaxForSize(key);
        regInput.setAttribute('maxlength', String(m));
        // Do not force-trim here; updateRegistration handles value -> glyphs
      } catch(e) {}
    }
// Bind event listeners
    if (regInput) {
      regInput.addEventListener('input', () => {
        updateRegistration();
        updatePrice();
        updateFormFields();
      });
    }
    plateCountInputs.forEach((input) => {
      input.addEventListener('change', () => {
        updatePlateVisibility();
        updatePrice();
        updateFormFields();
      });
    });
    if (textStyleSelect) {
      textStyleSelect.addEventListener('change', () => {
        applyTextStyle();
        updatePrice();
        updateFormFields();
      });
    }
    if (plateSizeSelect) {
      plateSizeSelect.addEventListener('change', () => {
        updatePlateSize();
        updatePrice();
        updateFormFields();
      });
    }
    if (plateSurroundSelect) {
      plateSurroundSelect.addEventListener('change', () => {
        updateSurround();
        updatePrice();
        updateFormFields();
      });
    }
    if (electricSelect) {
      electricSelect.addEventListener('change', () => {
        applyMaxForSize();
    updateElectric();
        updateFormFields();
      });
    }
    plateUseInputs.forEach((input) => {
      input.addEventListener('change', () => {
        updatePlateUseType();
        updatePrice();
        updateFormFields();
      });
    });
    if (badgePicker) {
      badgePicker.addEventListener('click', (ev) => {
        const target = ev.target.closest('.badge-colour');
        if (target) {
          selectBadgeColour(target);
          updatePrice();
          updateFormFields();
        }
      });
    }
    if (borderPicker) {
      borderPicker.addEventListener('click', (ev) => {
        const target = ev.target.closest('.border-colour');
        if (target) {
          selectBorderColour(target);
          updatePrice();
          updateFormFields();
        }
      });
    }

    // Initialise state.  Each call updates internal variables and
    // re-renders the SVG plates where appropriate.
    updateRegistration();
    updatePlateVisibility();
    updatePlateUseType();
    applyTextStyle();
    updatePlateSize();
    updateSurround();
    updateElectric();
    // Ensure the plates are drawn once all initial settings have been
    // processed.  updateElectric() will trigger a render but calling
    // renderAll() explicitly guarantees a fresh draw.
    renderAll();
    updatePrice();
    // Populate form fields on load if a form is bound
    updateFormFields();
  }

  /**
   * Initialise all existing builders on the page. Called on DOM ready.
   */
  function initAllBuilders() {
    const containers = document.querySelectorAll('.wp-plate-builder-container');
    containers.forEach((container) => initPlateBuilder(container));
  }

  // Run initialisation once DOM is ready.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllBuilders);
  } else {
    initAllBuilders();
  }

  // Hook into Elementor's dynamic frontend initialization. When Elementor
  // loads a widget asynchronously, this hook is called with the scope.
  window.addEventListener('elementor/frontend/init', function () {
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction(
        'frontend/element_ready/wp_plate_builder.default',
        function ($scope) {
          // $scope is a jQuery element; find the container within it and
          // initialise it using plain JS.
          const scopeEl = $scope[0];
          if (!scopeEl) return;
          scopeEl
            .querySelectorAll('.wp-plate-builder-container')
            .forEach((container) => initPlateBuilder(container));
        }
      );
    }
  });
})()
      // Ensure textStyle is defined for SVG <text> elements
      const textStyle = `font-size:${charHeightPx}px;font-family:'CharlesWright','Arial',sans-serif;letter-spacing:${charSpacingPx}px;font-weight:bold;`;
;