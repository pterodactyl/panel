/**
 * @fileoverview    functions used for visualizing GIS data
 *
 * @requires    jquery
 */

/* global drawOpenLayers PASSIVE_EVENT_LISTENERS */ // templates/table/gis_visualization/gis_visualization.twig

// Constants
var zoomFactor = 1.5;
var defaultX = 0;
var defaultY = 0;
var defaultScale = 1;

// Variables
var x = defaultX;
var y = defaultY;
var scale = defaultScale;

/** @type {SVGElement|undefined} */
var gisSvg;
/** @type {ol.Map|undefined} */
var map;

/**
 * Zooms and pans the visualization.
 */
function zoomAndPan () {
    var g = gisSvg.getElementById('groupPanel');
    if (!g) {
        return;
    }

    $('#groupPanel', gisSvg).attr('transform', 'translate(' + x + ', ' + y + ') scale(' + scale + ')');
    $('circle.vector', gisSvg).attr('r', 3 / scale);
    $('circle.vector', gisSvg).attr('stroke-width', 2 / scale);
    $('polyline.vector', gisSvg).attr('stroke-width', 2 / scale);
    $('path.vector', gisSvg).attr('stroke-width', 0.5 / scale);
}

/**
 * Initially loads either SVG or OSM visualization based on the choice.
 */
function selectVisualization () {
    if ($('#choice').prop('checked') !== true) {
        $('#openlayersmap').hide();
    } else {
        $('#placeholder').hide();
    }
}

/**
 * Adds necessary styles to the div that contains the openStreetMap.
 */
function styleOSM () {
    var $placeholder = $('#placeholder');
    var cssObj = {
        'border' : '1px solid #aaa',
        'width' : $placeholder.width(),
        'height' : $placeholder.height(),
        'float' : 'right'
    };
    $('#openlayersmap').css(cssObj);
}

/**
 * Store a reference to the gis svg element.
 */
function storeGisSvgRef () {
    gisSvg = $('#placeholder').find('svg').get(0);
}

/**
 * Adds controls for zooming and panning.
 */
function addZoomPanControllers () {
    if (!gisSvg) {
        return;
    }
    var themeImagePath = $('#themeImagePath').val();
    $('#placeholder').append(
        // pan arrows
        '<img class="button" id="left_arrow" src="' + themeImagePath + 'west-mini.png">',
        '<img class="button" id="right_arrow" src="' + themeImagePath + 'east-mini.png">',
        '<img class="button" id="up_arrow" src="' + themeImagePath + 'north-mini.png">',
        '<img class="button" id="down_arrow" src="' + themeImagePath + 'south-mini.png">',
        // zoom controls
        '<img class="button" id="zoom_in" src="' + themeImagePath + 'zoom-plus-mini.png">',
        '<img class="button" id="zoom_world" src="' + themeImagePath + 'zoom-world-mini.png">',
        '<img class="button" id="zoom_out" src="' + themeImagePath + 'zoom-minus-mini.png">'
    );
}

/**
 * Resizes the GIS visualization to fit into the space available.
 */
function resizeGISVisualization () {
    var $placeholder = $('#placeholder');
    var oldWidth = $placeholder.width();
    var visWidth = $('#div_view_options').width() - 48;

    // Assign new value for width
    $placeholder.width(visWidth);
    $(gisSvg).attr('width', visWidth);

    // Assign the offset created due to resizing to defaultX and center the svg.
    defaultX = (visWidth - oldWidth) / 2;
    x = defaultX;
    y = defaultY;
    scale = defaultScale;
}

/**
 * Initialize the GIS visualization.
 */
function initGISVisualization () {
    storeGisSvgRef();
    // Loads either SVG or OSM visualization based on the choice
    selectVisualization();
    // Resizes the GIS visualization to fit into the space available
    resizeGISVisualization();

    if (typeof ol !== 'undefined') {
        // Adds necessary styles to the div that contains the openStreetMap
        styleOSM();
    }
    // Adds controllers for zooming and panning
    addZoomPanControllers();
    zoomAndPan();
}

function drawOpenLayerMap () {
    $('#placeholder').hide();
    $('#openlayersmap').show();
    // Function doesn't work properly if #openlayersmap is hidden
    if (typeof map !== 'object') {
        // Draws openStreetMap with openLayers
        map = drawOpenLayers();
    }
}

function getRelativeCoords (e) {
    var position = $('#placeholder').offset();
    return {
        x : e.pageX - position.left,
        y : e.pageY - position.top
    };
}

/**
 * @param {WheelEvent} event
 */
function onGisMouseWheel (event) {
    if (event.deltaY === 0) {
        return;
    }
    event.preventDefault();

    var relCoords = getRelativeCoords(event);
    var factor = event.deltaY > 0 ? zoomFactor : 1 / zoomFactor;
    // zoom
    scale *= factor;
    // zooming keeping the position under mouse pointer unmoved.
    x = relCoords.x - (relCoords.x - x) * factor;
    y = relCoords.y - (relCoords.y - y) * factor;
    zoomAndPan();
}

/**
 * Ajax handlers for GIS visualization page
 *
 * Actions Ajaxified here:
 *
 * Zooming in and zooming out on mouse wheel movement.
 * Panning the visualization on dragging.
 * Zooming in on double clicking.
 * Zooming out on clicking the zoom out button.
 * Panning on clicking the arrow buttons.
 * Displaying tooltips for GIS objects.
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/gis_visualization.js', function () {
    $(document).off('click', '#choice');
    $(document).off('dragstart', 'svg');
    $(document).off('mouseup', 'svg');
    $(document).off('drag', 'svg');
    $(document).off('dblclick', '#placeholder');
    $(document).off('click', '#zoom_in');
    $(document).off('click', '#zoom_world');
    $(document).off('click', '#zoom_out');
    $(document).off('click', '#left_arrow');
    $(document).off('click', '#right_arrow');
    $(document).off('click', '#up_arrow');
    $(document).off('click', '#down_arrow');
    $('.vector').off('mousemove').off('mouseout');
    $('#placeholder').get(0).removeEventListener(
        'wheel',
        onGisMouseWheel,
        PASSIVE_EVENT_LISTENERS ? { passive: false } : undefined
    );
    if (map) {
        // Removes ol.Map's resize listener from window
        map.setTarget(null);
        map = undefined;
    }
});

AJAX.registerOnload('table/gis_visualization.js', function () {
    // If we are in GIS visualization, initialize it
    if ($('#gis_div').length > 0) {
        initGISVisualization();
    }

    if ($('#choice').prop('checked') === true) {
        drawOpenLayerMap();
    }

    if (typeof ol === 'undefined') {
        $('#choice, #labelChoice').hide();
    }

    $(document).on('click', '#choice', function () {
        if ($(this).prop('checked') === false) {
            $('#placeholder').show();
            $('#openlayersmap').hide();
        } else {
            drawOpenLayerMap();
        }
    });

    $('#placeholder').get(0).addEventListener(
        'wheel',
        onGisMouseWheel,
        PASSIVE_EVENT_LISTENERS ? { passive: false } : undefined
    );

    var dragX = 0;
    var dragY = 0;
    $('svg').draggable({
        helper: function () {
            return $('<div>');// Give a fake element to be used for dragging display
        }
    });
    $(document).on('dragstart', 'svg', function (event, dd) {
        $('#placeholder').addClass('placeholderDrag');
        dragX = Math.round(dd.offset.left);
        dragY = Math.round(dd.offset.top);
    });

    $(document).on('mouseup', 'svg', function () {
        $('#placeholder').removeClass('placeholderDrag');
    });

    $(document).on('drag', 'svg', function (event, dd) {
        var newX = Math.round(dd.offset.left);
        x +=  newX - dragX;
        dragX = newX;
        var newY = Math.round(dd.offset.top);
        y +=  newY - dragY;
        dragY = newY;
        zoomAndPan();
    });

    $(document).on('dblclick', '#placeholder', function (event) {
        if (event.target.classList.contains('button')) {
            return;
        }
        scale *= zoomFactor;
        // zooming in keeping the position under mouse pointer unmoved.
        var relCoords = getRelativeCoords(event);
        x = relCoords.x - (relCoords.x - x) * zoomFactor;
        y = relCoords.y - (relCoords.y - y) * zoomFactor;
        zoomAndPan();
    });

    $(document).on('click', '#zoom_in', function (e) {
        e.preventDefault();
        // zoom in
        scale *= zoomFactor;

        var width = $(gisSvg).attr('width');
        var height = $(gisSvg).attr('height');
        // zooming in keeping the center unmoved.
        x = width / 2 - (width / 2 - x) * zoomFactor;
        y = height / 2 - (height / 2 - y) * zoomFactor;
        zoomAndPan();
    });

    $(document).on('click', '#zoom_world', function (e) {
        e.preventDefault();
        scale = 1;
        x = defaultX;
        y = defaultY;
        zoomAndPan();
    });

    $(document).on('click', '#zoom_out', function (e) {
        e.preventDefault();
        // zoom out
        scale /= zoomFactor;

        var width = $(gisSvg).attr('width');
        var height = $(gisSvg).attr('height');
        // zooming out keeping the center unmoved.
        x = width / 2 - (width / 2 - x) / zoomFactor;
        y = height / 2 - (height / 2 - y) / zoomFactor;
        zoomAndPan();
    });

    $(document).on('click', '#left_arrow', function (e) {
        e.preventDefault();
        x += 100;
        zoomAndPan();
    });

    $(document).on('click', '#right_arrow', function (e) {
        e.preventDefault();
        x -= 100;
        zoomAndPan();
    });

    $(document).on('click', '#up_arrow', function (e) {
        e.preventDefault();
        y += 100;
        zoomAndPan();
    });

    $(document).on('click', '#down_arrow', function (e) {
        e.preventDefault();
        y -= 100;
        zoomAndPan();
    });

    /**
     * Detect the mousemove event and show tooltips.
     */
    $('.vector').on('mousemove', function (event) {
        var contents = Functions.escapeHtml($(this).attr('data-label')).trim();
        $('#tooltip').remove();
        if (contents !== '') {
            $('<div id="tooltip">' + contents + '</div>').css({
                position : 'absolute',
                top : event.pageY + 10,
                left : event.pageX + 10,
                border : '1px solid #fdd',
                padding : '2px',
                'background-color' : '#fee',
                opacity : 0.90
            }).appendTo('body').fadeIn(200);
        }
    });

    /**
     * Detect the mouseout event and hide tooltips.
     */
    $('.vector').on('mouseout', function () {
        $('#tooltip').remove();
    });
});
