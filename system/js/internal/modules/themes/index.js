define(['./views/application'],
    function(ApplicationView) {
        window.themesModule = new ApplicationView();
        $(function(){
            $(document).trigger('themes:loaded');
        });
    }
);