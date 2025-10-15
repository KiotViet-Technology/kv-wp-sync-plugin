<div id="view_log"></div>
<div id="myModal" class="modal log-modal">
    <!-- Modal content -->
    <div class="modal-content">
        <p><pre class="detail-log"></pre></p>
    </div>
</div>
<form method="get" action="admin.php">
    <input type="hidden" name="page" value="plugin-kiotviet-sync-order">
    <div class="kvsync-wrapper wrap">
        <h2 class="kv-title-top">
          Lịch sử đồng bộ
          <a href="https://www.kiotviet.vn/hdsd-kiotviet-sync/" target="_blank" style="float:right;color:red !important">
            Hướng dẫn sử dụng
          </a>
        </h2>
        <?php
            $logsList->prepare_items();
            $logsList->display();
        ?>
    </div>
</form>

<script>
  jQuery(function(){
    jQuery('.view_log').click(function () {
      jQuery('#myModal').css('display', 'block');
      jQuery('.detail-log').text(JSON.stringify(jQuery(this).data('value'), null, 4));
    });
  });

  jQuery(window).click(function(event){
    var modal = document.getElementById('myModal');
    if (event.target == modal) {
      jQuery('#myModal').css('display', 'none');
    }
  })
</script>
