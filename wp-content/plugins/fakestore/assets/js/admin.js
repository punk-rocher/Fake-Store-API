jQuery(function($){
    $('#fsync-sync-btn').on('click', function(e){
        e.preventDefault();
        var log = $('#fsync-sync-log');
        var results = $('#fsync-results');

        log.html('⏳ Syncing… please wait.');
        results.html('');

        $.post(fsync_vars.ajaxurl, {
            action: 'fsync_sync_products',
            nonce: fsync_vars.nonce
        }, function(resp){
            if(resp.success){
                log.html('✅ Imported: ' + resp.data.imported + ' | Updated: ' + resp.data.updated + ' | Skipped: ' + resp.data.skipped);
                $('#fsync-last-sync').text(new Date().toLocaleString());
                results.html(resp.data.table);
            } else {
                log.html('❌ Error: ' + (resp.data ? resp.data : 'Unknown error'));
            }
        });
    });
});
