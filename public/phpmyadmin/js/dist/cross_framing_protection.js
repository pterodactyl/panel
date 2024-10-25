/**
 * Conditionally included if framing is not allowed
 */
if (self === top) {
  var styleElement = document.getElementById('cfs-style');
  // check if styleElement has already been removed
  // to avoid frequently reported js error
  if (typeof styleElement !== 'undefined' && styleElement !== null) {
    styleElement.parentNode.removeChild(styleElement);
  }
} else {
  top.location = self.location;
}