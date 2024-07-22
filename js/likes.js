$(document).ready(function() {
    $('.like').click(function() {
        var $this = $(this);
        var id = $this.attr('id');
        $.ajax({
            url: 'http://localhost/redsocial-master/redsocial-master/megusta.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#likes_' + id).text(' (' + response.likes + ')');

                    var $icon = $this.find('i');
                    var $text = $this.contents().filter(function() {
                        return this.nodeType === 3;
                    });

                    if(response.action === 'liked') {
                        $icon.removeClass('fa-thumbs-o-up').addClass('fa-thumbs-up');
                        $text.replaceWith(' No me gusta ');
                    } else {
                        $icon.removeClass('fa-thumbs-up').addClass('fa-thumbs-o-up');
                        $text.replaceWith(' Me gusta ');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Error en la solicitud AJAX:", status, error);
            }
        });
    });
});
