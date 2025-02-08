/**
 * Chart type enumerations
 */
var ChartType = {
  LINE: 'line',
  SPLINE: 'spline',
  AREA: 'area',
  BAR: 'bar',
  COLUMN: 'column',
  PIE: 'pie',
  TIMELINE: 'timeline',
  SCATTER: 'scatter'
};

/**
 * Column type enumeration
 */
var ColumnType = {
  STRING: 'string',
  NUMBER: 'number',
  BOOLEAN: 'boolean',
  DATE: 'date'
};

/**
 * Abstract chart factory which defines the contract for chart factories
 */
var ChartFactory = function () {};
ChartFactory.prototype = {
  createChart: function () {
    throw new Error('createChart must be implemented by a subclass');
  }
};

/**
 * Abstract chart which defines the contract for charts
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var Chart = function (elementId) {
  this.elementId = elementId;
};
Chart.prototype = {
  draw: function () {
    throw new Error('draw must be implemented by a subclass');
  },
  redraw: function () {
    throw new Error('redraw must be implemented by a subclass');
  },
  destroy: function () {
    throw new Error('destroy must be implemented by a subclass');
  },
  toImageString: function () {
    throw new Error('toImageString must be implemented by a subclass');
  }
};

/**
 * Abstract representation of charts that operates on DataTable where,<br>
 * <ul>
 * <li>First column provides index to the data.</li>
 * <li>Each subsequent columns are of type
 * <code>ColumnType.NUMBER<code> and represents a data series.</li>
 * </ul>
 * Line chart, area chart, bar chart, column chart are typical examples.
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var BaseChart = function (elementId) {
  Chart.call(this, elementId);
};
BaseChart.prototype = new Chart();
BaseChart.prototype.constructor = BaseChart;
BaseChart.prototype.validateColumns = function (dataTable) {
  var columns = dataTable.getColumns();
  if (columns.length < 2) {
    throw new Error('Minimum of two columns are required for this chart');
  }
  for (var i = 1; i < columns.length; i++) {
    if (columns[i].type !== ColumnType.NUMBER) {
      throw new Error('Column ' + (i + 1) + ' should be of type \'Number\'');
    }
  }
  return true;
};

/**
 * Abstract pie chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var PieChart = function (elementId) {
  BaseChart.call(this, elementId);
};
PieChart.prototype = new BaseChart();
PieChart.prototype.constructor = PieChart;
PieChart.prototype.validateColumns = function (dataTable) {
  var columns = dataTable.getColumns();
  if (columns.length > 2) {
    throw new Error('Pie charts can draw only one series');
  }
  return BaseChart.prototype.validateColumns.call(this, dataTable);
};

/**
 * Abstract timeline chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var TimelineChart = function (elementId) {
  BaseChart.call(this, elementId);
};
TimelineChart.prototype = new BaseChart();
TimelineChart.prototype.constructor = TimelineChart;
TimelineChart.prototype.validateColumns = function (dataTable) {
  var result = BaseChart.prototype.validateColumns.call(this, dataTable);
  if (result) {
    var columns = dataTable.getColumns();
    if (columns[0].type !== ColumnType.DATE) {
      throw new Error('First column of timeline chart need to be a date column');
    }
  }
  return result;
};

/**
 * Abstract scatter chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var ScatterChart = function (elementId) {
  BaseChart.call(this, elementId);
};
ScatterChart.prototype = new BaseChart();
ScatterChart.prototype.constructor = ScatterChart;
ScatterChart.prototype.validateColumns = function (dataTable) {
  var result = BaseChart.prototype.validateColumns.call(this, dataTable);
  if (result) {
    var columns = dataTable.getColumns();
    if (columns[0].type !== ColumnType.NUMBER) {
      throw new Error('First column of scatter chart need to be a numeric column');
    }
  }
  return result;
};

/**
 * The data table contains column information and data for the chart.
 */
// eslint-disable-next-line no-unused-vars
var DataTable = function () {
  var columns = [];
  var data = null;
  this.addColumn = function (type, name) {
    columns.push({
      'type': type,
      'name': name
    });
  };
  this.getColumns = function () {
    return columns;
  };
  this.setData = function (rows) {
    data = rows;
    fillMissingValues();
  };
  this.getData = function () {
    return data;
  };
  var fillMissingValues = function () {
    if (columns.length === 0) {
      throw new Error('Set columns first');
    }
    var row;
    for (var i = 0; i < data.length; i++) {
      row = data[i];
      if (row.length > columns.length) {
        row.splice(columns.length - 1, row.length - columns.length);
      } else if (row.length < columns.length) {
        for (var j = row.length; j < columns.length; j++) {
          row.push(null);
        }
      }
    }
  };
};

/** *****************************************************************************
 * JQPlot specific code
 ******************************************************************************/

/**
 * Abstract JQplot chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotChart = function (elementId) {
  Chart.call(this, elementId);
  this.plot = null;
  this.validator = null;
};
JQPlotChart.prototype = new Chart();
JQPlotChart.prototype.constructor = JQPlotChart;
JQPlotChart.prototype.draw = function (data, options) {
  if (this.validator.validateColumns(data)) {
    this.plot = $.jqplot(this.elementId, this.prepareData(data), this.populateOptions(data, options));
  }
};
JQPlotChart.prototype.destroy = function () {
  if (this.plot !== null) {
    this.plot.destroy();
  }
};
JQPlotChart.prototype.redraw = function (options) {
  if (this.plot !== null) {
    this.plot.replot(options);
  }
};
JQPlotChart.prototype.toImageString = function () {
  if (this.plot !== null) {
    return $('#' + this.elementId).jqplotToImageStr({});
  }
};
JQPlotChart.prototype.populateOptions = function () {
  throw new Error('populateOptions must be implemented by a subclass');
};
JQPlotChart.prototype.prepareData = function () {
  throw new Error('prepareData must be implemented by a subclass');
};

/**
 * JQPlot line chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotLineChart = function (elementId) {
  JQPlotChart.call(this, elementId);
  this.validator = BaseChart.prototype;
};
JQPlotLineChart.prototype = new JQPlotChart();
JQPlotLineChart.prototype.constructor = JQPlotLineChart;
JQPlotLineChart.prototype.populateOptions = function (dataTable, options) {
  var columns = dataTable.getColumns();
  var optional = {
    axes: {
      xaxis: {
        label: columns[0].name,
        renderer: $.jqplot.CategoryAxisRenderer,
        ticks: []
      },
      yaxis: {
        label: columns.length === 2 ? columns[1].name : 'Values',
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer
      }
    },
    highlighter: {
      show: true,
      tooltipAxes: 'y',
      formatString: '%d'
    },
    series: []
  };
  $.extend(true, optional, options);
  if (optional.series.length === 0) {
    for (var i = 1; i < columns.length; i++) {
      optional.series.push({
        label: columns[i].name.toString()
      });
    }
  }
  if (optional.axes.xaxis.ticks.length === 0) {
    var data = dataTable.getData();
    for (var j = 0; j < data.length; j++) {
      optional.axes.xaxis.ticks.push(data[j][0] !== null ? data[j][0].toString() : null);
    }
  }
  return optional;
};
JQPlotLineChart.prototype.prepareData = function (dataTable) {
  var data = dataTable.getData();
  var row;
  var retData = [];
  var retRow;
  for (var i = 0; i < data.length; i++) {
    row = data[i];
    for (var j = 1; j < row.length; j++) {
      retRow = retData[j - 1];
      if (retRow === undefined) {
        retRow = [];
        retData[j - 1] = retRow;
      }
      retRow.push(row[j]);
    }
  }
  return retData;
};

/**
 * JQPlot spline chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotSplineChart = function (elementId) {
  JQPlotLineChart.call(this, elementId);
};
JQPlotSplineChart.prototype = new JQPlotLineChart();
JQPlotSplineChart.prototype.constructor = JQPlotSplineChart;
JQPlotSplineChart.prototype.populateOptions = function (dataTable, options) {
  var optional = {};
  var opt = JQPlotLineChart.prototype.populateOptions.call(this, dataTable, options);
  var compulsory = {
    seriesDefaults: {
      rendererOptions: {
        smooth: true
      }
    }
  };
  $.extend(true, optional, opt, compulsory);
  return optional;
};

/**
 * JQPlot scatter chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotScatterChart = function (elementId) {
  JQPlotChart.call(this, elementId);
  this.validator = ScatterChart.prototype;
};
JQPlotScatterChart.prototype = new JQPlotChart();
JQPlotScatterChart.prototype.constructor = JQPlotScatterChart;
JQPlotScatterChart.prototype.populateOptions = function (dataTable, options) {
  var columns = dataTable.getColumns();
  var optional = {
    axes: {
      xaxis: {
        label: columns[0].name
      },
      yaxis: {
        label: columns.length === 2 ? columns[1].name : 'Values',
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer
      }
    },
    highlighter: {
      show: true,
      tooltipAxes: 'xy',
      formatString: '%d, %d'
    },
    series: []
  };
  for (var i = 1; i < columns.length; i++) {
    optional.series.push({
      label: columns[i].name.toString()
    });
  }
  var compulsory = {
    seriesDefaults: {
      showLine: false,
      markerOptions: {
        size: 7,
        style: 'x'
      }
    }
  };
  $.extend(true, optional, options, compulsory);
  return optional;
};
JQPlotScatterChart.prototype.prepareData = function (dataTable) {
  var data = dataTable.getData();
  var row;
  var retData = [];
  var retRow;
  for (var i = 0; i < data.length; i++) {
    row = data[i];
    if (row[0]) {
      for (var j = 1; j < row.length; j++) {
        retRow = retData[j - 1];
        if (retRow === undefined) {
          retRow = [];
          retData[j - 1] = retRow;
        }
        retRow.push([row[0], row[j]]);
      }
    }
  }
  return retData;
};

/**
 * JQPlot timeline chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotTimelineChart = function (elementId) {
  JQPlotLineChart.call(this, elementId);
  this.validator = TimelineChart.prototype;
};
JQPlotTimelineChart.prototype = new JQPlotLineChart();
JQPlotTimelineChart.prototype.constructor = JQPlotTimelineChart;
JQPlotTimelineChart.prototype.populateOptions = function (dataTable, options) {
  var optional = {
    axes: {
      xaxis: {
        tickOptions: {
          formatString: '%b %#d, %y'
        }
      }
    }
  };
  var opt = JQPlotLineChart.prototype.populateOptions.call(this, dataTable, options);
  var compulsory = {
    axes: {
      xaxis: {
        renderer: $.jqplot.DateAxisRenderer
      }
    }
  };
  $.extend(true, optional, opt, compulsory);
  return optional;
};
JQPlotTimelineChart.prototype.prepareData = function (dataTable) {
  var data = dataTable.getData();
  var row;
  var d;
  var retData = [];
  var retRow;
  for (var i = 0; i < data.length; i++) {
    row = data[i];
    d = row[0];
    for (var j = 1; j < row.length; j++) {
      retRow = retData[j - 1];
      if (retRow === undefined) {
        retRow = [];
        retData[j - 1] = retRow;
      }
      // See https://github.com/phpmyadmin/phpmyadmin/issues/14395 for the block
      if (d !== null && typeof d === 'object') {
        retRow.push([d.getTime(), row[j]]);
      } else if (typeof d === 'string') {
        d = new Date(d);
        retRow.push([d.getTime(), row[j]]);
      }
    }
  }
  return retData;
};

/**
 * JQPlot area chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotAreaChart = function (elementId) {
  JQPlotLineChart.call(this, elementId);
};
JQPlotAreaChart.prototype = new JQPlotLineChart();
JQPlotAreaChart.prototype.constructor = JQPlotAreaChart;
JQPlotAreaChart.prototype.populateOptions = function (dataTable, options) {
  var optional = {
    seriesDefaults: {
      fillToZero: true
    }
  };
  var opt = JQPlotLineChart.prototype.populateOptions.call(this, dataTable, options);
  var compulsory = {
    seriesDefaults: {
      fill: true
    }
  };
  $.extend(true, optional, opt, compulsory);
  return optional;
};

/**
 * JQPlot column chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotColumnChart = function (elementId) {
  JQPlotLineChart.call(this, elementId);
};
JQPlotColumnChart.prototype = new JQPlotLineChart();
JQPlotColumnChart.prototype.constructor = JQPlotColumnChart;
JQPlotColumnChart.prototype.populateOptions = function (dataTable, options) {
  var optional = {
    seriesDefaults: {
      fillToZero: true
    }
  };
  var opt = JQPlotLineChart.prototype.populateOptions.call(this, dataTable, options);
  var compulsory = {
    seriesDefaults: {
      renderer: $.jqplot.BarRenderer
    }
  };
  $.extend(true, optional, opt, compulsory);
  return optional;
};

/**
 * JQPlot bar chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotBarChart = function (elementId) {
  JQPlotLineChart.call(this, elementId);
};
JQPlotBarChart.prototype = new JQPlotLineChart();
JQPlotBarChart.prototype.constructor = JQPlotBarChart;
JQPlotBarChart.prototype.populateOptions = function (dataTable, options) {
  var columns = dataTable.getColumns();
  var optional = {
    axes: {
      yaxis: {
        label: columns[0].name,
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
        renderer: $.jqplot.CategoryAxisRenderer,
        ticks: []
      },
      xaxis: {
        label: columns.length === 2 ? columns[1].name : 'Values',
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer
      }
    },
    highlighter: {
      show: true,
      tooltipAxes: 'x',
      formatString: '%d'
    },
    series: [],
    seriesDefaults: {
      fillToZero: true
    }
  };
  var compulsory = {
    seriesDefaults: {
      renderer: $.jqplot.BarRenderer,
      rendererOptions: {
        barDirection: 'horizontal'
      }
    }
  };
  $.extend(true, optional, options, compulsory);
  if (optional.axes.yaxis.ticks.length === 0) {
    var data = dataTable.getData();
    for (var i = 0; i < data.length; i++) {
      optional.axes.yaxis.ticks.push(data[i][0].toString());
    }
  }
  if (optional.series.length === 0) {
    for (var j = 1; j < columns.length; j++) {
      optional.series.push({
        label: columns[j].name.toString()
      });
    }
  }
  return optional;
};

/**
 * JQPlot pie chart
 *
 * @param elementId
 *            id of the div element the chart is drawn in
 */
var JQPlotPieChart = function (elementId) {
  JQPlotChart.call(this, elementId);
  this.validator = PieChart.prototype;
};
JQPlotPieChart.prototype = new JQPlotChart();
JQPlotPieChart.prototype.constructor = JQPlotPieChart;
JQPlotPieChart.prototype.populateOptions = function (dataTable, options) {
  var optional = {
    highlighter: {
      show: true,
      tooltipAxes: 'xy',
      formatString: '%s, %d',
      useAxesFormatters: false
    },
    legend: {
      renderer: $.jqplot.EnhancedPieLegendRenderer
    }
  };
  var compulsory = {
    seriesDefaults: {
      shadow: false,
      renderer: $.jqplot.PieRenderer,
      rendererOptions: {
        sliceMargin: 1,
        showDataLabels: true
      }
    }
  };
  $.extend(true, optional, options, compulsory);
  return optional;
};
JQPlotPieChart.prototype.prepareData = function (dataTable) {
  var data = dataTable.getData();
  var row;
  var retData = [];
  for (var i = 0; i < data.length; i++) {
    row = data[i];
    retData.push([row[0], row[1]]);
  }
  return [retData];
};

/**
 * Chart factory that returns JQPlotCharts
 */
var JQPlotChartFactory = function () {};
JQPlotChartFactory.prototype = new ChartFactory();
JQPlotChartFactory.prototype.createChart = function (type, elementId) {
  var chart = null;
  switch (type) {
    case ChartType.LINE:
      chart = new JQPlotLineChart(elementId);
      break;
    case ChartType.SPLINE:
      chart = new JQPlotSplineChart(elementId);
      break;
    case ChartType.TIMELINE:
      chart = new JQPlotTimelineChart(elementId);
      break;
    case ChartType.AREA:
      chart = new JQPlotAreaChart(elementId);
      break;
    case ChartType.BAR:
      chart = new JQPlotBarChart(elementId);
      break;
    case ChartType.COLUMN:
      chart = new JQPlotColumnChart(elementId);
      break;
    case ChartType.PIE:
      chart = new JQPlotPieChart(elementId);
      break;
    case ChartType.SCATTER:
      chart = new JQPlotScatterChart(elementId);
      break;
  }
  return chart;
};