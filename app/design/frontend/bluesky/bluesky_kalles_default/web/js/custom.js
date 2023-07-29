require(["jquery", "slick"], function ($) {
      jQuery(document).ready(function () {
		
        if(jQuery(".grid-mode-show-type-products").find(".actived")){
          
           jQuery(".products-grid").addClass("category_page_grid_3");



          }

          if(jQuery(".page-wrapper").find("#maincontent")){
          
           jQuery(".page-main").addClass("category-page");

           

          }



	});

    jQuery(".grid-mode-2").click(function() {
               jQuery(".products-grid").addClass("category_page_grid_2");
    });
    jQuery(".grid-mode-3").click(function() {
               jQuery(".products-grid").addClass("category_page_grid_3");
    });
    jQuery(".grid-mode-4").click(function() {
               jQuery(".products-grid").addClass("category_page_grid_4");
    });

   
   link = decodeURIComponent(link);
   var urlPaths = link.split('?');
   var urlParams = urlPaths[1] ? urlPaths[1].split('&') : [];
   var paramcheck = urlParams[0].split("=");

   if (paramcheck[0] == "product_list_dir") {
       var urlPathsParams = urlPaths[1].split('&');
       urlPathsParams.splice(0, 1);;
       var urlParams = urlPathsParams.join('&');
       urlPaths[1] = urlParams;
       link = urlPaths.join('?');
   }

});

