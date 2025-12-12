document.addEventListener("DOMContentLoaded", () => {
  // Define both locations
  const locations = {
    main: {
      coords: [14.31372, 121.07831],
      name: "PUP Biñan Campus",
      address: "Brgy. Zapote, Biñan, Laguna 4024"
    },
    cite: {
      coords: [14.343843993751669, 121.06868279818723],
      name: "PUP Biñan - CITE Campus",
      address: "243 Manila S Rd, Biñan, 4024 Laguna<br>83V9+FF Biñan, Laguna"
    }
  };

  // Initialize map with main campus coordinates
  const map = L.map("map").setView(locations.main.coords, 17);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Define red marker icon
  const redIcon = L.icon({
    iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

  // Create markers for both locations
  const markers = {};
  
  Object.keys(locations).forEach(key => {
    const location = locations[key];
    markers[key] = L.marker(location.coords, { icon: redIcon })
      .addTo(map)
      .bindPopup(`<b>${location.name}</b><br>${location.address}`, { autoPan: false });
  });

  // Get DOM elements
  const focusBtn = document.getElementById("focus-btn");
  const focusMenu = document.getElementById("focus-menu");
  const focusOptions = document.querySelectorAll(".focus-option");

  // Toggle focus menu when clicking main button
  if (focusBtn) {
    focusBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      focusMenu.classList.toggle("active");
    });
  }

  // Handle focus option clicks
  focusOptions.forEach(option => {
    option.addEventListener("click", (e) => {
      e.stopPropagation();
      const location = option.getAttribute("data-location");
      const coords = locations[location].coords;
      const marker = markers[location];

      // Close all popups
      map.eachLayer((layer) => {
        if (layer.closePopup) {
          layer.closePopup();
        }
      });

      // Fly to selected location
      const targetZoom = Math.max(map.getZoom(), 17);
      map.flyTo(coords, targetZoom, { animate: true, duration: 1.2 });
      
      // Open popup after animation
      map.once("moveend", () => {
        marker.openPopup();
      });

      // Close menu
      focusMenu.classList.remove("active");
    });
  });

  // Close menu when clicking outside
  document.addEventListener("click", () => {
    focusMenu.classList.remove("active");
  });
});
