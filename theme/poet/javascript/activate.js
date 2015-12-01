$(document).ready(function() {
    var slider = $('#slider').leanSlider({
        directionNav: '#slider-direction-nav',
        controlNav: '#slider-control-nav'
    });
    setTimeout(function() {
    	$("#banner-nav, #banner-title").show();
    	$("#banner-nav .init").addClass("animated fadeInLeft");
    	$("#banner-title, #banner-title .init").addClass("animated fadeIn");
    }, 1000);
});