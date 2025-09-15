jQuery(function($){
    $('#fsync-sync-btn').on('click', function(e){
        e.preventDefault();
        var log = $('#fsync-sync-log');
        log.html('Syncing… please wait.');

        $.post(fsync_vars.ajaxurl, {
            action: 'fsync_sync_products',
            nonce: fsync_vars.nonce
        }, function(resp){
            if(resp.success){
                log.html('Imported: ' + resp.data.imported + ' | Updated: ' + resp.data.updated);
                $('#fsync-last-sync').text(new Date().toLocaleString());
            } else {
                log.html('Error: ' + (resp.data ? resp.data : 'Unknown error'));
            }
        });
    });
});
