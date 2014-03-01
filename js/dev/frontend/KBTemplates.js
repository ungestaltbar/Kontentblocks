var KB = KB || {};
KB.Templates = (function($) {

    var tmpl_cache = {};

    function getTmplCache(){
        return tmpl_cache;
    }

    function render(tmpl_name, tmpl_data) {
        
        if (!tmpl_cache[tmpl_name]) {
            var tmpl_dir = kontentblocks.config.url + 'js/templates';
            var tmpl_url = tmpl_dir + '/' + tmpl_name + '.html';

            var tmpl_string;
            $.ajax({
                url: tmpl_url,
                method: 'GET',
                async: false,
                success: function(data) {
                    tmpl_string = data;
                }
            });

            tmpl_cache[tmpl_name] = _.template(tmpl_string);
        }
        return tmpl_cache[tmpl_name](tmpl_data);
    }
    

    return {
        render: render
    };
}(jQuery));