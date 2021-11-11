document.addEventListener("DOMContentLoaded", function () {
    svgPanZoom("#demo-tiger", {
        zoomEnabled: true,
        controlIconsEnabled: true,
        fit: true,
        center: true,
    });

    document.getElementById("menu-toggle").addEventListener("click", function () {
        const menuClassList = document.querySelector(".export__menu").classList;
        const openClass = "export__menu--open";

        menuClassList.contains(openClass)
            ? menuClassList.remove(openClass)
            : menuClassList.add(openClass);
    });
});
