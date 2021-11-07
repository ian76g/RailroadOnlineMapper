// Don't use window.onLoad like this in production, because it can only listen to one function.
window.onload = function () {
  // Expose to window namespase for testing purposes
  window.zoomTiger = svgPanZoom("#demo-tiger", {
    zoomEnabled: true,
    controlIconsEnabled: true,
    fit: true,
    center: true,
    // viewportSelector: document.getElementById('demo-tiger').querySelector('#g4') // this option will make library to misbehave. Viewport should have no transform attribute
  });

  document.getElementById("enable").addEventListener("click", function () {
    window.zoomTiger.enableControlIcons();
  });
  document.getElementById("disable").addEventListener("click", function () {
    window.zoomTiger.disableControlIcons();
  });
};
