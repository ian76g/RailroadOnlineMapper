document.addEventListener("DOMContentLoaded", function () {
    const panZoom = svgPanZoom("#demo-tiger", {
        zoomEnabled: true,
        controlIconsEnabled: false,
        fit: true,
        center: true,
        maxZoom: 20
    });
    if(cookies !== undefined && cookies.get('zoomLevel') !== undefined && cookies.get('zoomX') !== undefined && cookies.get('zoomY')!==undefined) {
        let point = {x:Math.round(cookies.get('zoomX')), y: Math.round(cookies.get('zoomY'))};
        panZoom.zoom(cookies.get('zoomLevel'));
        panZoom.center();
        panZoom.pan(point);
        // panZoom.zoomAtPoint(cookies.get('zoomLevel'), point);
//        panZoom.zoom(cookies.get('zoomLevel'));
//         console.log('setting pan to ' +point.x + ', '+point.y + ' zoom is '+cookies.get('zoomLevel'));

    }
    panZoom.setOnPan(function(point){
        cookies.set('zoomX', point.x); cookies.set('zoomY', point.y);
        // console.log('setting cookie for ' +point.x + ', '+point.y)
    })
    panZoom.setOnZoom(function(level){
        cookies.set('zoomLevel', level);
        // console.log('setting zoom to ' + level)
    })
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
        panZoom.center()
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