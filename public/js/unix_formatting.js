/**@license
 *       __ _____                     ________                              __
 *      / // _  /__ __ _____ ___ __ _/__  ___/__ ___ ______ __ __  __ ___  / /
 *  __ / // // // // // _  // _// // / / // _  // _//     // //  \/ // _ \/ /
 * /  / // // // // // ___// / / // / / // ___// / / / / // // /\  // // / /__
 * \___//____ \\___//____//_/ _\_  / /_//____//_/ /_/ /_//_//_/ /_/ \__\_\___/
 *           \/              /____/
 * http://terminal.jcubic.pl
 *
 * This is example of how to create custom formatter for jQuery Terminal
 *
 * Copyright (c) 2014-2016 Jakub Jankiewicz <http://jcubic.pl>
 * Released under the MIT license
 *
 */
(function($) {
    if (!$.terminal) {
        throw new Error('$.terminal is not defined');
    }
    // ---------------------------------------------------------------------
    // :: Replace overtyping (from man) formatting with terminal formatting
    // ---------------------------------------------------------------------
    $.terminal.overtyping = function(string) {
        return string.replace(/((?:_\x08.|.\x08_)+)/g, function(full, g) {
            var striped = full.replace(/_x08|\x08_|_\u0008|\u0008_/g, '');
            return '[[u;;]' + striped + ']';
        }).replace(/((?:.\x08.)+)/g, function(full, g) {
            return '[[b;#fff;]' + full.replace(/(.)(?:\x08|\u0008)\1/g,
                                               function(full, g) {
                                                   return g;
                                               }) + ']';
        });
    };
    // ---------------------------------------------------------------------
    // :: Html colors taken from ANSI formatting in Linux Terminal
    // ---------------------------------------------------------------------
    $.terminal.ansi_colors = {
        normal: {
            black: '#000',
            red: '#A00',
            green: '#008400',
            yellow: '#A50',
            blue: '#00A',
            magenta: '#A0A',
            cyan: '#0AA',
            white: '#AAA'
        },
        faited: {
            black: '#000',
            red: '#640000',
            green: '#006100',
            yellow: '#737300',
            blue: '#000087',
            magenta: '#650065',
            cyan: '#008787',
            white: '#818181'
        },
        bold: {
            black: '#000',
            red: '#F55',
            green: '#44D544',
            yellow: '#FF5',
            blue: '#55F',
            magenta: '#F5F',
            cyan: '#5FF',
            white: '#FFF'
        },
        // XTerm 8-bit pallete
        palette: [
            '#000000', '#AA0000', '#00AA00', '#AA5500', '#0000AA', '#AA00AA',
            '#00AAAA', '#AAAAAA', '#555555', '#FF5555', '#55FF55', '#FFFF55',
            '#5555FF', '#FF55FF', '#55FFFF', '#FFFFFF', '#000000', '#00005F',
            '#000087', '#0000AF', '#0000D7', '#0000FF', '#005F00', '#005F5F',
            '#005F87', '#005FAF', '#005FD7', '#005FFF', '#008700', '#00875F',
            '#008787', '#0087AF', '#0087D7', '#0087FF', '#00AF00', '#00AF5F',
            '#00AF87', '#00AFAF', '#00AFD7', '#00AFFF', '#00D700', '#00D75F',
            '#00D787', '#00D7AF', '#00D7D7', '#00D7FF', '#00FF00', '#00FF5F',
            '#00FF87', '#00FFAF', '#00FFD7', '#00FFFF', '#5F0000', '#5F005F',
            '#5F0087', '#5F00AF', '#5F00D7', '#5F00FF', '#5F5F00', '#5F5F5F',
            '#5F5F87', '#5F5FAF', '#5F5FD7', '#5F5FFF', '#5F8700', '#5F875F',
            '#5F8787', '#5F87AF', '#5F87D7', '#5F87FF', '#5FAF00', '#5FAF5F',
            '#5FAF87', '#5FAFAF', '#5FAFD7', '#5FAFFF', '#5FD700', '#5FD75F',
            '#5FD787', '#5FD7AF', '#5FD7D7', '#5FD7FF', '#5FFF00', '#5FFF5F',
            '#5FFF87', '#5FFFAF', '#5FFFD7', '#5FFFFF', '#870000', '#87005F',
            '#870087', '#8700AF', '#8700D7', '#8700FF', '#875F00', '#875F5F',
            '#875F87', '#875FAF', '#875FD7', '#875FFF', '#878700', '#87875F',
            '#878787', '#8787AF', '#8787D7', '#8787FF', '#87AF00', '#87AF5F',
            '#87AF87', '#87AFAF', '#87AFD7', '#87AFFF', '#87D700', '#87D75F',
            '#87D787', '#87D7AF', '#87D7D7', '#87D7FF', '#87FF00', '#87FF5F',
            '#87FF87', '#87FFAF', '#87FFD7', '#87FFFF', '#AF0000', '#AF005F',
            '#AF0087', '#AF00AF', '#AF00D7', '#AF00FF', '#AF5F00', '#AF5F5F',
            '#AF5F87', '#AF5FAF', '#AF5FD7', '#AF5FFF', '#AF8700', '#AF875F',
            '#AF8787', '#AF87AF', '#AF87D7', '#AF87FF', '#AFAF00', '#AFAF5F',
            '#AFAF87', '#AFAFAF', '#AFAFD7', '#AFAFFF', '#AFD700', '#AFD75F',
            '#AFD787', '#AFD7AF', '#AFD7D7', '#AFD7FF', '#AFFF00', '#AFFF5F',
            '#AFFF87', '#AFFFAF', '#AFFFD7', '#AFFFFF', '#D70000', '#D7005F',
            '#D70087', '#D700AF', '#D700D7', '#D700FF', '#D75F00', '#D75F5F',
            '#D75F87', '#D75FAF', '#D75FD7', '#D75FFF', '#D78700', '#D7875F',
            '#D78787', '#D787AF', '#D787D7', '#D787FF', '#D7AF00', '#D7AF5F',
            '#D7AF87', '#D7AFAF', '#D7AFD7', '#D7AFFF', '#D7D700', '#D7D75F',
            '#D7D787', '#D7D7AF', '#D7D7D7', '#D7D7FF', '#D7FF00', '#D7FF5F',
            '#D7FF87', '#D7FFAF', '#D7FFD7', '#D7FFFF', '#FF0000', '#FF005F',
            '#FF0087', '#FF00AF', '#FF00D7', '#FF00FF', '#FF5F00', '#FF5F5F',
            '#FF5F87', '#FF5FAF', '#FF5FD7', '#FF5FFF', '#FF8700', '#FF875F',
            '#FF8787', '#FF87AF', '#FF87D7', '#FF87FF', '#FFAF00', '#FFAF5F',
            '#FFAF87', '#FFAFAF', '#FFAFD7', '#FFAFFF', '#FFD700', '#FFD75F',
            '#FFD787', '#FFD7AF', '#FFD7D7', '#FFD7FF', '#FFFF00', '#FFFF5F',
            '#FFFF87', '#FFFFAF', '#FFFFD7', '#FFFFFF', '#080808', '#121212',
            '#1C1C1C', '#262626', '#303030', '#3A3A3A', '#444444', '#4E4E4E',
            '#585858', '#626262', '#6C6C6C', '#767676', '#808080', '#8A8A8A',
            '#949494', '#9E9E9E', '#A8A8A8', '#B2B2B2', '#BCBCBC', '#C6C6C6',
            '#D0D0D0', '#DADADA', '#E4E4E4', '#EEEEEE'
        ]
    };
    // ---------------------------------------------------------------------
    // :: Replace ANSI formatting with terminal formatting
    // ---------------------------------------------------------------------
    $.terminal.from_ansi = (function() {
        var color_list = {
            30: 'black',
            31: 'red',
            32: 'green',
            33: 'yellow',
            34: 'blue',
            35: 'magenta',
            36: 'cyan',
            37: 'white',

            39: 'inherit' // default color
        };
        var background_list = {
            40: 'black',
            41: 'red',
            42: 'green',
            43: 'yellow',
            44: 'blue',
            45: 'magenta',
            46: 'cyan',
            47: 'white',

            49: 'transparent' // default background
        };
        function format_ansi(code) {
            var controls = code.split(';');
            var num;
            var faited = false;
            var reverse = false;
            var bold = false;
            var styles = [];
            var output_color = '';
            var output_background = '';
            var _8bit_color = false;
            var _8bit_background = false;
            var process_8bit = false;
            var palette = $.terminal.ansi_colors.palette;
            for(var i in controls) {
                if (controls.hasOwnProperty(i)) {
                    num = parseInt(controls[i], 10);
                    if (process_8bit && (_8bit_background || _8bit_color)) {
                        if (_8bit_color && palette[num]) {
                            output_color = palette[num];
                        }
                        if (_8bit_background && palette[num]) {
                            output_background = palette[num];
                        }
                    } else {
                        switch(num) {
                        case 1:
                            styles.push('b');
                            bold = true;
                            faited = false;
                            break;
                        case 4:
                            styles.push('u');
                            break;
                        case 3:
                            styles.push('i');
                            break;
                        case 5:
                            process_8bit = true;
                            break;
                        case 38:
                            _8bit_color = true;
                            break;
                        case 48:
                            _8bit_background = true;
                            break;
                        case 2:
                            faited = true;
                            bold = false;
                            break;
                        case 7:
                            reverse = true;
                            break;
                        default:
                            if (controls.indexOf('5') == -1) {
                                if (color_list[num]) {
                                    output_color = color_list[num];
                                }
                                if (background_list[num]) {
                                    output_background = background_list[num];
                                }
                            }
                        }
                    }
                }
            }
            if (reverse) {
                if (output_color || output_background) {
                    var tmp = output_background;
                    output_background = output_color;
                    output_color = tmp;
                } else {
                    output_color = 'black';
                    output_background = 'white';
                }
            }
            var colors, color, background, backgrounds;
            if (bold) {
                colors = backgrounds = $.terminal.ansi_colors.bold;
            } else if (faited) {
                colors = backgrounds = $.terminal.ansi_colors.faited;
            } else {
                colors = backgrounds = $.terminal.ansi_colors.normal;
            }
            if (_8bit_color) {
                color = output_color;
            } else if (output_color == 'inherit') {
                color = output_color;
            } else {
                color = colors[output_color];
            }
            if (_8bit_background) {
                background = output_background;
            } else if (output_background == 'transparent') {
                background = output_background;
            } else {
                background = backgrounds[output_background];
            }
            return [styles.join(''), color, background];
        }
        return function(input) {
            //merge multiple codes
            /*input = input.replace(/((?:\x1B\[[0-9;]*[A-Za-z])*)/g, function(group) {
                return group.replace(/m\x1B\[/g, ';');
            });*/
            var splitted = input.split(/(\x1B\[[0-9;]*[A-Za-z])/g);
            if (splitted.length == 1) {
                return input;
            }
            var output = [];
            //skip closing at the begining
            if (splitted.length > 3) {
                var str = splitted.slice(0,3).join('');
                if (str.match(/^\[0*m$/)) {
                    splitted = splitted.slice(3);
                }
            }
            var next, prev_color, prev_background, code, match;
            var inside = false;
            for (var i=0; i<splitted.length; ++i) {
                match = splitted[i].match(/^\x1B\[([0-9;]*)([A-Za-z])$/);
                if (match) {
                    switch (match[2]) {
                    case 'm':
                        if (+match[1] !== 0) {
                            code = format_ansi(match[1]);
                        }
                        if (inside) {
                            output.push(']');
                            if (+match[1] === 0) {
                                //just closing
                                inside = false;
                                prev_color = prev_background = '';
                            } else {
                                // someone forget to close - move to next
                                code[1] = code[1] || prev_color;
                                code[2] = code[2] || prev_background;
                                output.push('[[' + code.join(';') + ']');
                                // store colors to next use
                                if (code[1]) {
                                    prev_color = code[1];
                                }
                                if (code[2]) {
                                    prev_background = code[2];
                                }
                            }
                        } else {
                            if (+match[1] !== 0) {
                                inside = true;
                                code[1] = code[1] || prev_color;
                                code[2] = code[2] || prev_background;
                                output.push('[[' + code.join(';') + ']');
                                // store colors to next use
                                if (code[1]) {
                                    prev_color = code[1];
                                }
                                if (code[2]) {
                                    prev_background = code[2];
                                }
                            }
                        }
                        break;
                    }
                } else {
                    output.push(splitted[i]);
                }
            }
            if (inside) {
                output.push(']');
            }
            return output.join(''); //.replace(/\[\[[^\]]+\]\]/g, '');
        };
    })();
    $.terminal.defaults.formatters.push($.terminal.overtyping);
    $.terminal.defaults.formatters.push($.terminal.from_ansi);
})(jQuery);
