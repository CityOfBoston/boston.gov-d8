function runMap() {
    const coords = drupalSettings.bos_assessing.coords[0];
    const polyCoords = [];

    jQuery(coords).each(function(index,value) {
        polyCoords.push( {"lng" : value[0], "lat" : value[1]} );
    });

    const map = new google.maps.Map(document.getElementById("map-container"), {
        zoom: 18,
        center: polyCoords[0],
        mapTypeId: 'satellite',
        tilt: 0,
    });

    // Construct the polygon.
    const parcel = new google.maps.Polygon({
        paths: polyCoords,
        strokeColor: "#FF0000",
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: "#FF0000",
        fillOpacity: 0.35,
    });
    parcel.setMap(map);
}

function waintUntilMapIsAvailable(){
    if (Drupal.geolocation.maps !== undefined) {
        clearInterval(checkGeo);
        runMap();
    } 
}

const checkGeo = setInterval(waintUntilMapIsAvailable, 500);
