/**
 * Handles the resizing of a menu according to the available screen width
 *
 * Uses themes/original/css/resizable-menu.css.php
 *
 * To initialize:
 * $('#myMenu').menuResizer(function () {
 *     // This function will be called to find out how much
 *     // available horizontal space there is for the menu
 *     return $('body').width() - 5; // Some extra margin for good measure
 * });
 *
 * To trigger a resize operation:
 * $('#myMenu').menuResizer('resize'); // Bind this to $(window).resize()
 *
 * To restore the menu to a state like before it was initialized:
 * $('#myMenu').menuResizer('destroy');
 *
 * @package PhpMyAdmin
 */
(function ($) {
  function MenuResizer($container, widthCalculator) {
    var self = this;
    self.$container = $container;
    self.widthCalculator = widthCalculator;
    var windowWidth = $(window).width();
    if (windowWidth < 768) {
      $('#pma_navigation_resizer').css({
        'width': '0px'
      });
    }

    // create submenu container
    var link = $('<a></a>', {
      'href': '#',
      'class': 'nav-link dropdown-toggle',
      'id': 'navbarDropdown',
      'role': 'button',
      'data-bs-toggle': 'dropdown',
      'aria-haspopup': 'true',
      'aria-expanded': 'false'
    }).text(Messages.strMore);
    var img = $container.find('li img');
    if (img.length) {
      $(Functions.getImage('b_more').toString()).prependTo(link);
    }
    var $submenu = $('<li></li>', {
      'class': 'nav-item dropdown d-none'
    }).append(link).append($('<ul></ul>', {
      'class': 'dropdown-menu dropdown-menu-end',
      'aria-labelledby': 'navbarDropdown'
    }));
    $container.append($submenu);
    setTimeout(function () {
      self.resize();
    }, 4);
  }
  MenuResizer.prototype.resize = function () {
    var wmax = this.widthCalculator.call(this.$container);
    var windowWidth = $(window).width();
    var $submenu = this.$container.find('.nav-item.dropdown').last();
    var submenuW = $submenu.outerWidth(true);
    var $submenuUl = $submenu.find('.dropdown-menu');
    var $li = this.$container.find('> li');
    var $li2 = $submenuUl.find('.dropdown-item');
    var moreShown = $li2.length > 0;
    // Calculate the total width used by all the shown tabs
    var totalLen = moreShown ? submenuW : 0;
    var l = $li.length - 1;
    var i;
    for (i = 0; i < l; i++) {
      totalLen += $($li[i]).outerWidth(true);
    }

    // eslint-disable-next-line compat/compat
    var hasVScroll = document.body.scrollHeight > document.body.clientHeight;
    if (hasVScroll) {
      windowWidth += 15;
    }
    if (windowWidth < 768) {
      wmax = 2000;
    }

    // Now hide menu elements that don't fit into the menubar
    var hidden = false; // Whether we have hidden any tabs
    while (totalLen >= wmax && --l >= 0) {
      // Process the tabs backwards
      hidden = true;
      var el = $($li[l]);
      el.removeClass('nav-item').addClass('dropdown-item');
      var elWidth = el.outerWidth(true);
      el.data('width', elWidth);
      if (!moreShown) {
        totalLen -= elWidth;
        el.prependTo($submenuUl);
        totalLen += submenuW;
        moreShown = true;
      } else {
        totalLen -= elWidth;
        el.prependTo($submenuUl);
      }
    }
    // If we didn't hide any tabs, then there might be some space to show some
    if (!hidden) {
      // Show menu elements that do fit into the menubar
      for (i = 0, l = $li2.length; i < l; i++) {
        totalLen += $($li2[i]).data('width');
        // item fits or (it is the last item
        // and it would fit if More got removed)
        if (totalLen < wmax || i === $li2.length - 1 && totalLen - submenuW < wmax) {
          $($li2[i]).removeClass('dropdown-item').addClass('nav-item');
          $($li2[i]).insertBefore($submenu);
        } else {
          break;
        }
      }
    }
    // Show/hide the "More" tab as needed
    if (windowWidth < 768) {
      $('.navbar-collapse').css({
        'width': windowWidth - 80 - $('#pma_navigation').width()
      });
      $submenu.addClass('d-none');
      $('.navbar-collapse').css({
        'overflow': 'hidden'
      });
    } else {
      $('.navbar-collapse').css({
        'width': 'auto'
      });
      $('.navbar-collapse').css({
        'overflow': 'visible'
      });
      if ($submenuUl.find('li').length > 0) {
        $submenu.removeClass('d-none');
      } else {
        $submenu.addClass('d-none');
      }
    }
  };
  MenuResizer.prototype.destroy = function () {
    var $submenu = this.$container.find('.nav-item.dropdown').removeData();
    $submenu.find('li').appendTo(this.$container);
    $submenu.remove();
  };

  /** Public API */
  var methods = {
    init: function (widthCalculator) {
      return this.each(function () {
        var $this = $(this);
        if (!$this.data('menuResizer')) {
          $this.data('menuResizer', new MenuResizer($this, widthCalculator));
        }
      });
    },
    resize: function () {
      return this.each(function () {
        var self = $(this).data('menuResizer');
        if (self) {
          self.resize();
        }
      });
    },
    destroy: function () {
      return this.each(function () {
        var self = $(this).data('menuResizer');
        if (self) {
          self.destroy();
        }
      });
    }
  };

  /**
   * Extend jQuery
   *
   * @param {string} method
   *
   * @return {any}
   */
  $.fn.menuResizer = function (method) {
    if (methods[method]) {
      return methods[method].call(this);
    } else if (typeof method === 'function') {
      return methods.init.apply(this, [method]);
    } else {
      $.error('Method ' + method + ' does not exist on jQuery.menuResizer');
    }
  };
})(jQuery);