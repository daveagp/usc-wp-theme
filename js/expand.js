$(
    function() {
    $(".expand").each(function(index, element) {
        //console.log(index, element);
        element = $(element);
        element.html("<div class='expand-header'>Click to toggle answer</div><div class='expand-body'>"+element.html()+"</div>");
        element.toggle();
    });
    $("body").on("click", ".expand-header", function(event) {
        $(event.target.parentElement).find(".expand-body").toggle("slow");
    });
    }
);
