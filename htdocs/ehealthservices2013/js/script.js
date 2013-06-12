$(document).ready(function() {
    $("li.active").prepend('<canvas id="canvasCircle" width="' + $("li.active").css('width') + '" height="' + $("li.active").css('height') + '"></canvas>');
    
    $("canvas#canvasCircle").drawArc({
        fillStyle: "#21576b",
        x: ($("li.active canvas").width()+1)/2, y: 0,
        radius: 4
    });
    
});
