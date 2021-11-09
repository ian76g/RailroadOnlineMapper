document.addEventListener("DOMContentLoaded", function () {
  svgPanZoom("#demo-tiger", {
    zoomEnabled: true,
    controlIconsEnabled: true,
    fit: true,
    center: true,
  });

  document.getElementById("info-panel-toggle").addEventListener("click", function () {
    togglePanel('info-panel')
  });

  document.getElementById("edit-panel-toggle").addEventListener("click", function () {
    togglePanel('edit-panel')
  });

  const togglePanel = panelClass => {
    const openClass = `export__panel--open`;
    let openPanels = document.querySelector(`.${openClass}`);
    let panelClassList = document.querySelector(`.${panelClass}`).classList;

    // Close any open panels
    if (openPanels !== null && !openPanels.classList.contains(panelClass)) {
      openPanels.classList.remove(openClass)
    }

    panelClassList.contains(openClass)
      ? panelClassList.remove(openClass)
      : panelClassList.add(openClass);
  }
});
