<!DOCTYPE html>
<html>

<head>
    <title>Show distance on map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map {
            height: 850px;
            width: 100%;
        }
    </style>
</head>

<body>
    <h1>Show distance on map</h1>
    <div id="map"></div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        /// parse points
        var points = {!! json_encode($distance->points) !!};
        points = JSON.parse(points);
        /// set view to first point
        var startLatLng = [points[0][1], points[0][0]];
        var endLatLng = [points[points.length - 1][1], points[points.length - 1][0]];
        var bounds = L.latLngBounds(startLatLng, endLatLng);
        var map = L.map('map').fitBounds(bounds);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        /// add Starting point (green)
        var markerStart = L.marker([points[0][1], points[0][0]]).addTo(map)
            .bindPopup('Starting point').openPopup();
        /// add End point (red)
        var markerEnd = L.marker([points[points.length - 1][1], points[points.length - 1][0]]).addTo(map)
            .bindPopup('End point').openPopup();

        /// add middle points (blue)
        for (var i = 1; i < points.length - 1; i++) {
            L.marker([points[i][1], points[i][0]]).addTo(map)
        }
        @php
            $geometry = json_decode($distance->geometry);
            $coordinates = $geometry->coordinates; // assuming single geometry for simplicity
            $distanceInKm = $distance->distance;
        @endphp
        /// path (geometric shape)
        var coordinates = {!! json_encode($coordinates) !!};
        var latlngs = coordinates.map(function(coord) {
            return [coord[1], coord[0]];
        });
        var polyline = L.polyline(latlngs, {
            color: 'green'
        }).addTo(map);
        map.fitBounds(polyline.getBounds());
        /// Calculate the middle of the path
        var midpointIndex = Math.floor(latlngs.length / 2);
        var midpoint = latlngs[midpointIndex];
        /// popup
        var distanceInKm = {{ $distanceInKm }};
        var popup = L.popup()
            .setLatLng(midpoint)
            .setContent('distance: ' + distanceInKm + ' km')
            .addTo(map);
        polyline.bindPopup(popup);
    </script>
</body>

</html>
