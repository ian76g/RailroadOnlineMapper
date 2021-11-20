document.addEventListener("DOMContentLoaded", function () {
    const panZoom = svgPanZoom("#demo-tiger", {
        zoomEnabled: true,
        controlIconsEnabled: false,
        fit: true,
        center: true,
    });

    document.getElementById("info-panel-toggle").addEventListener("click", function () {
        togglePanel('info-panel')
    });

    document.getElementById("edit-panel-toggle").addEventListener("click", function () {
        togglePanel('edit-panel')
    });

    document.getElementById('zoom-in').addEventListener('click', function(e){
        e.preventDefault()
        panZoom.zoomIn()
    });

    document.getElementById('zoom-out').addEventListener('click', function(e){
        e.preventDefault()
        panZoom.zoomOut()
    });

    document.getElementById('reset').addEventListener('click', function(e){
        e.preventDefault()
        panZoom.resetZoom()
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