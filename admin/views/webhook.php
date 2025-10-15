<form method="get" action="admin.php">
    <input type="hidden" name="page" value="plugin-kiotviet-sync-webhook">
    <div class="kvsync-wrapper wrap">
        <h2 class="kv-title-top">
            Danh sách webhook
            <a href="https://www.kiotviet.vn/hdsd-kiotviet-sync/" target="_blank" style="float:right;color:red !important">
                Hướng dẫn sử dụng
            </a>
        </h2>
        <?php
            $kiotviet_sync_retailer = get_option('kiotviet_sync_retailer');
            if(!empty($kiotviet_sync_retailer)) {
                $webhooksList->prepare_items();
                $webhooksList->display();
            } else { ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e( 'Bạn phải đăng nhập để xem các webhook!', 'kiotviet-sync' ); ?></p>
                </div>
            <?php }
        ?>
    </div>
</form>
