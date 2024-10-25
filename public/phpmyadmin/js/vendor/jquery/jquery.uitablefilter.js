/*
 * Copyright (c) 2008 Greg Weber greg at gregweber.info
 * Dual licensed under the MIT and GPLv2 licenses just as jQuery is:
 * http://jquery.org/license
 *
 * Multi-columns support by natinusala
 *
 * documentation at http://gregweber.info/projects/uitablefilter
 *
 * allows table rows to be filtered (made invisible)
 * <code>
 * t = $('table')
 * $.uiTableFilter( t, phrase )
 * </code>
 * arguments:
 *   jQuery object containing table rows
 *   phrase to search for
 *   optional arguments:
 *     array of columns to limit search too (the column title in the table header)
 *     ifHidden - callback to execute if one or more elements was hidden
 *     tdElem - specific element within <td> to be considered for searching or to limit search to,
 *     default:whole <td>. useful if <td> has more than one elements inside but want to
 *     limit search within only some of elements or only visible elements. eg tdElem can be "td span"
 */
(function ($) {
	$.uiTableFilter = function (jq, phrase, column, ifHidden, tdElem) {
		if (!tdElem) tdElem = "td";
		var new_hidden = false;
		if (this.last_phrase === phrase) return false;

		var phrase_length = phrase.length;
		var words = phrase.toLowerCase().split(" ");

		// these function pointers may change
		var matches = function (elem) { elem.show() }
		var noMatch = function (elem) { elem.hide(); new_hidden = true }
		var getText = function (elem) { return elem.text() }

		if (column) {
			if (!$.isArray(column)) {
				column = new Array(column);
			}

			var index = new Array();

			jq.find("thead > tr:last > th").each(function (i) {
				for (var j = 0; j < column.length; j++) {
					if ($.trim($(this).text()) == column[j]) {
						index[j] = i;
						break;
					}
				}

			});

			getText = function (elem) {
				var selector = "";
				for (var i = 0; i < index.length; i++) {
					if (i != 0) { selector += ","; }
					selector += tdElem + ":eq(" + index[i] + ")";
				}
				return $(elem.find((selector))).text();
			}
		}

		// if added one letter to last time,
		// just check newest word and only need to hide
		if ((words.size > 1) && (phrase.substr(0, phrase_length - 1) ===
			this.last_phrase)) {

			if (phrase[-1] === " ") { this.last_phrase = phrase; return false; }

			var words = words[-1]; // just search for the newest word

			// only hide visible rows
			matches = function (elem) { ; }
			var elems = jq.find("tbody:first > tr:visible")
		}
		else {
			new_hidden = true;
			var elems = jq.find("tbody:first > tr")
		}

		elems.each(function () {
			var elem = $(this);
			$.uiTableFilter.has_words(getText(elem), words, false) ?
				matches(elem) : noMatch(elem);
		});

		last_phrase = phrase;
		if (ifHidden && new_hidden) ifHidden();
		return jq;
	};

	// caching for speedup
	$.uiTableFilter.last_phrase = ""

	// not jQuery dependent
	// "" [""] -> Boolean
	// "" [""] Boolean -> Boolean
	$.uiTableFilter.has_words = function (str, words, caseSensitive) {
		var text = caseSensitive ? str : str.toLowerCase();
		for (var i = 0; i < words.length; i++) {
			if (text.indexOf(words[i]) === -1) return false;
		}
		return true;
	}
})(jQuery);
