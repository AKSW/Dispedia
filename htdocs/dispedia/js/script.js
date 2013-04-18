$(document).ready(function() {
    $("li.active").prepend('<canvas id="canvasCircle" width="' + $("li.active").css('width') + '" height="' + $("li.active").css('height') + '">Dein Browser unterstützt das Canvas-Element nicht.</canvas>');
    
    $("canvas#canvasCircle").drawArc({
        fillStyle: "white",
        x: ($("li.active canvas").width()+1)/2, y: 0,
        radius: 4
    });
    $("canvas#canvasCircle").drawArc({
        fillStyle: "white",
        x: ($("li.active canvas").width()+1)/2, y: $("li.active canvas").height(),
        radius: 4
    });
    
    $(".schemaContent .span9 .row canvas").drawLine({
        strokeStyle: "#00A3C6",
        strokeWidth: 3 ,
        rounded: true,
        x1: $(".schemaContent .span9 .row canvas").width()*1/4, y1: $(".schemaContent .span9 .row canvas").height()*5/8,
        x2: $(".schemaContent .span9 .row canvas").width()/2, y2: $(".schemaContent .span9 .row canvas").height()*3/8,
        x3: $(".schemaContent .span9 .row canvas").width()*3/4, y3: $(".schemaContent .span9 .row canvas").height()*5/8
    });
});
