<form method="get" action="admin.php">
    <input type="hidden" name="page" value="plugin-kiotviet-sync-order">
    <div class="kvsync-wrapper wrap">
        <h2 class="kv-title-top">
            Danh sách đơn đặt hàng
            <a href="https://www.kiotviet.vn/hdsd-kiotviet-sync/" target="_blank" style="float:right;color:red !important">
                Hướng dẫn sử dụng
            </a>
        </h2>
        <?php
            $orderList->prepare_items();
            $orderList->search_box('search', 'search_id');
            $orderList->display();
        ?>
    </div>
</form>

<script>
    jQuery(function(){
        jQuery('.re-sync-order').click(function(){
            var jQuerythis = jQuery(this);
            var orderId = jQuerythis.attr('data-id');
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data:{
                    order: orderId,
                    action: 'kiotviet_re_sync_order'
                },
                dataType: 'json',
                beforeSend: function(){
                    jQuerythis.attr('disabled', 'disabled').val('Đang đồng bộ');
                },
                success: function(resp){
                    //console.log(resp);
                    if(resp.status === 'error'){
                        jQuerythis.closest('td').html(
                            '<strong style=\"color:red\">Thất bại</strong> <br />' +
                             '<small>' + resp.msg + '</small>'
                            );
                    } else {
                        jQuerythis.closest('td').html('<strong style=\"color:green\">Thành công</strong>');
                    }
                }
            });

        });
    });
</script>
