var testHelpers = {
  dispatch: function (element, type) {
    setTimeout(function () {
      if (typeof element === 'string') {
        element = jQuery(element);
      }
      if (element instanceof jQuery) {
        if (!element.length) return;
        element = element[0];
      }
      var e = document.createEvent('HTMLEvents');
      e.initEvent(type, true, true);
      element.dispatchEvent(e);
    }, 0)
  },
  click: function (element) {
    testHelpers.dispatch(element, 'click')
  }
};
