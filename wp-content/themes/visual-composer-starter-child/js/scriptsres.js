
   $(document).ready(function(){
    $(".scroll").click(function(event){
 
                    //bloqueamos la función del anchor original
                    event.preventDefault();
 
                    //dirigimos de manera animada al id del anchor
                    $('html,body').animate({
                                            //le indicamos al scroll vertical que se dirija al objeto con el id
                                            //guardado en el anchor a su posición top.
                                            scrollTop:$(this.hash).offset().top - 64
                                            },1000);
 
    });
    $(".scroll").click(function(){
        $(".collapse").removeClass("in");
    });



            // When the window has finished loading create our google map below
            google.maps.event.addDomListener(window, 'load', init);
        
            function init() {
                // Basic options for a simple Google Map
                // For more options see: https://developers.google.com/maps/documentation/javascript/reference#MapOptions
                var mapOptions = {
                    // How zoomed in you want the map to start at (always required)
                    zoom: 16,

                    // The latitude and longitude to center the map (always required)
                    center: new google.maps.LatLng(19.379129, -99.1430909), // New York

                    // How you would like to style the map. 
                    // This is where you would paste any style found on Snazzy Maps.
                    styles: [   {       "featureType":"landscape",      "stylers":[         {               "hue":"#FFBB00"         },          {               "saturation":43.400000000000006         },          {               "lightness":37.599999999999994          },          {               "gamma":1           }       ]   },  {       "featureType":"road.highway",       "stylers":[         {               "hue":"#FFC200"         },          {               "saturation":-61.8          },          {               "lightness":45.599999999999994          },          {               "gamma":1           }       ]   },  {       "featureType":"road.arterial",      "stylers":[         {               "hue":"#FF0300"         },          {               "saturation":-100           },          {               "lightness":51.19999999999999           },          {               "gamma":1           }       ]   },  {       "featureType":"road.local",     "stylers":[         {               "hue":"#FF0300"         },          {               "saturation":-100           },          {               "lightness":52          },          {               "gamma":1           }       ]   },  {       "featureType":"water",      "stylers":[         {               "hue":"#0078FF"         },          {               "saturation":-13.200000000000003            },          {               "lightness":2.4000000000000057          },          {               "gamma":1           }       ]   },  {       "featureType":"poi",        "stylers":[         {               "hue":"#00FF6A"         },          {               "saturation":-1.0989010989011234            },          {               "lightness":11.200000000000017          },          {               "gamma":1           }       ]   }]
                };
                
                var customMarker = '/alef/wp-content/themes/visual-composer-starter-child/img/ping.png';

                // Get the HTML DOM element that will contain your map 
                // We are using a div with id="map" seen below in the <body>
                var mapElement = document.getElementById('map');

                // Create the Google Map using our element and options defined above
                var map = new google.maps.Map(mapElement, mapOptions);

                // Let's also add a marker while we're at it
                var marker = new google.maps.Marker({
                    position: new google.maps.LatLng(19.379129, -99.1430909),
                    icon: customMarker, 
                    map: map,
                    title: 'Óptica ALEF'
                });
            }


      });