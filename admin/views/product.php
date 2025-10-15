<form method="get" action="admin.php">
    <input type="hidden" name="page" value="plugin-kiotviet-sync-product">
    <div class="kvsync-wrapper wrap">
        <h2 class="kv-title-top">
          Danh sách sản phẩm
          <a href="https://www.kiotviet.vn/hdsd-kiotviet-sync/" target="_blank" style="float:right;color:red !important">
            Hướng dẫn sử dụng
          </a>
        </h2>
        <?php
            $productList->prepare_items();
            $productList->search_box('search', 'search_id');
            $productList->display();
        ?>
    </div>
</form>

<script>
  jQuery(function(){
    jQuery(document).on('click', '.product_sync', function(){
      var jQuerythis = jQuery(this);
      var status = jQuerythis.data('status');
      var product_id = jQuerythis.data('product-id');
      jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data:{
          status:status,
          product_id: product_id,
          action: 'kiotviet_sync_update_status',
        },
        dataType: 'json',
        beforeSend: function(){
          jQuerythis.attr('disabled', 'disabled');
        },
        success: function(resp){
          if(resp.status === 'success'){
            if(resp.data){
              jQuerythis.closest('td').find('.product_sync').data('status', 0).removeClass('button-danger').addClass('button-primary').val('Đang đồng bộ').removeAttr('disabled');
            }else{
              jQuerythis.closest('td').find('.product_sync').data('status', 1).removeClass('button-primary').addClass('button-danger').val('Ngừng đồng bộ').removeAttr('disabled');
            }
          }
        }
      });
    });
  });
</script>
