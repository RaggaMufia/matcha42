/**
 * Created by lekz on 24/07/17.
 */
(function ($) {

    setInterval(function(){
        $.get('/notif', function(data){
            $('.notifUnread').html(data);
            $.getJSON('/countNotif', function(data)
            {
                var count = $('#unread').html(data.nb);
            });

            $.fn.notif = function (options) {
                var settings = {
                    html : '<div class="notification animated fadeInLeft {{cls}}">\
            <div class="left">\
            <div class="icon">\
            {{{icon}}}\
                </div>\
                </div>\
                <div class="right">\
            <h2>{{title}}</h2>\
            <p>{{content}}</p>\
            </div>\
            </div>',
                    timeout : false
                }
                if (options.cls == 'message-alert')
                    settings.icon = '<i class="fa fa-envelope fa-2x" aria-hidden="true"></i>';
                if (options.cls == 'like-alert')
                    settings.icon = '<i class="fa fa-thumbs-o-up fa-2x" aria-hidden="true"></i>';
                if (options.cls == 'see-alert')
                    settings.icon = '<i class="fa fa-eye fa-2x" aria-hidden="true"></i>';
                if (options.cls == 'match-alert')
                    settings.icon = '<i class="fa fa-heartbeat fa-2x" aria-hidden="true"></i>';

                var options = $.extend(settings, options);

                return this.each(function () {
                    var $this = $(this);
                    var $notifs = $('> .notifications', this);
                    var $notif = $(Mustache.render(options.html, options));

                    if ($notifs.length == 0){
                        $notifs = $('<div class = "notifications animated flipInX"/>');
                        $this.append($notifs);
                    }
                    $notifs.append($notif);
                    if (options.timeout){
                        setTimeout(function () {
                            $notif.trigger('click');
                        }, options.timeout)
                    }
                    $notif.click(function (event) {
                        event.preventDefault();
                        $notif.addClass('animated fadeOutLeft').slideUp(300, function () {
                            $notif.remove();
                            if ($notifs.prevObject == undefined){
                                $notifs.remove();
                            }
                        });

                    })
                });
            };

            var data = $(data);

                data.find('.notification').each(getOptions(data));

            function getOptions(data) {

                var options = [];

                options.cls = data.find('span').each(function () {
                    return this.innerHTML+'-alert';
                });
                options.cls = cal

                $('body').notif(options);
            };



        }, 'html');
    }, 10000);

    $('.add-notif').click(function (event) {
        event.preventDefault();
        $('body').notif({title: 'Mon titre', content: 'mon contenu', icon: '<i class="fa fa-envelope fa-2x" aria-hidden="true"></i>', cls: 'message-alert', timeout: 5000});
        // $('body').notif({title: 'Mon titre', content: 'mon contenu', icon: '<i class="fa fa-thumbs-o-up fa-2x" aria-hidden="true"></i>', cls: 'like-alert'});
        // $('body').notif({title: 'Mon titre', content: 'mon contenu', icon: '<i class="fa fa-eye fa-2x" aria-hidden="true"></i>', cls: 'see-alert'})
        // $('body').notif({title: 'Mon titre', content: 'mon contenu', icon: '<i class="fa fa-heartbeat fa-2x" aria-hidden="true"></i>', cls: 'match-alert'})
    })

})(jQuery);