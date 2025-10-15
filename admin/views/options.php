<?php
$definedConstants = get_defined_constants();
if (!empty($definedConstants['DISABLE_WP_CRON']) && $definedConstants['DISABLE_WP_CRON'] === true) {
    ?>
    <div class="notice notice-warning is-dismissible">
        <p><i><strong>WP Cron</strong></i> đang ở trạng thái tắt, bạn không thể sử dụng được tính năng này</p>
    </div>
    <?php
}

if(isset($_POST['kv_saveoption'])) {
    if(!empty($_POST['kv_autosyncorder'])) {
        update_option('kv_autosyncorder', $_POST['kv_autosyncorder']);
    } else {
        update_option('kv_autosyncorder', 0);
    }

    if(!empty($_POST['kv_timeautosyncorder'])) {
        update_option('kv_timeautosyncorder', intval($_POST['kv_timeautosyncorder']));
    }

    if(!empty($_POST['kv_limitautosyncorder'])) {
        update_option('kv_limitautosyncorder', $_POST['kv_limitautosyncorder']);
    }

    if(!empty($_POST['kv_updatebysku'])) {
        update_option('kv_updatebysku', $_POST['kv_updatebysku']);
    } else {
        update_option('kv_updatebysku', 0);
    }

    if(!empty($_POST['kv_syncorderbysku'])) {
        update_option('kv_syncorderbysku', $_POST['kv_syncorderbysku']);
    } else {
        update_option('kv_syncorderbysku', 0);
    }

    if(!empty($_POST['kv_syncshipping'])) {
        update_option('kv_syncshipping', $_POST['kv_syncshipping']);
    } else {
        update_option('kv_syncshipping', 0);
    }

    wp_clear_scheduled_hook( 'isa_add_every_five_minutes' );
}
?>
<style>
    button {
        background-color: #008CBA;
        border: none;
        color: white;
        padding: 8px 15px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        border-radius: 5px;
    }
    button.clear-cache {
        background-color: orange;
    }
</style>
<form method="post" action="">
    <div class="kvsync-wrapper wrap">
        <h2 class="kv-title-top">
            Cài đặt
            <a href="https://www.kiotviet.vn/hdsd-kiotviet-sync/" target="_blank" style="float:right;color:red !important">
                Hướng dẫn sử dụng
            </a>
        </h2>
        <p>
            <input type="checkbox" name="kv_updatebysku" id="kv_updatebysku" value="1" <?php if(get_option('kv_updatebysku') == '1') echo 'checked'; ?>>
            <label for="kv_updatebysku" style="margin-bottom: 0;">Đồng bộ sản phẩm qua mã sản phẩm (SKU)</label>
        </p>
        <p>
            <input type="checkbox" name="kv_syncorderbysku" id="kv_syncorderbysku" value="1" <?php if(get_option('kv_syncorderbysku') == '1') echo 'checked'; ?>>
            <label for="kv_syncorderbysku" style="margin-bottom: 0;">Đồng bộ sản phẩm qua mã sản phẩm (SKU) khi đồng bộ đơn hàng</label>
        </p>
        <p>
            <input type="checkbox" name="kv_syncshipping" id="kv_syncshipping" value="1" <?php if(get_option('kv_syncshipping') == '1') echo 'checked'; ?>>
            <label for="kv_syncshipping" style="margin-bottom: 0;">Đồng bộ phí ship trong đơn hàng</label>
        </p>
        <hr>
        <p>
            <input type="checkbox" name="kv_autosyncorder" id="kv_autosyncorder" value="1" <?php if(get_option('kv_autosyncorder') == '1') echo 'checked'; ?>>
            <label for="kv_autosyncorder" style="margin-bottom: 0;">Tự đồng bộ lại đơn hàng</label>
            <span style="display: block; font-size: 12.5px; color: red; font-style: italic; margin-top: 5px">(Lưu ý: tính năng này chỉ hoạt động khi CronTab được bật, nếu bật tính năng này hệ thống sẽ tự động thực hiện đồng bộ lại các đơn hàng bị lỗi sau 1 khoảng thời gian được chỉ định bên dưới, chỉ bật khi thực sự cần thiết để tránh gây tốn tài nguyên của server)</span>
        </p>
        <p>
            <label for="kv_timeautosyncorder">Kiểm tra đơn hàng đồng bộ sau: </label>
            <input type="number" name="kv_timeautosyncorder" id="kv_timeautosyncorder" min="3600" value="<?php echo get_option('kv_timeautosyncorder'); ?>">
            <span>(s)</span>
        </p>
        <p>
            <label for="kv_limitautosyncorder">Số đơn hàng muốn kiểm tra: </label>
            <input type="number" name="kv_limitautosyncorder" id="kv_limitautosyncorder" max="500" value="<?php echo get_option('kv_limitautosyncorder'); ?>">
            <span>(đơn hàng)</span>
        </p>
        <p>
            <button type="submit" name="kv_saveoption">Lưu</button>
        </p>
    </div>
</form>

<div class="kvsync-wrapper wrap" style="margin-top: 50px;">
    <hr>
    <button id="kv-clear-indexeddb" class="clear-cache">Clear Cache</button>
</div>

<script>
  jQuery(function(){
    jQuery(document).on('click', '#kv-clear-indexeddb', async function(){
      if (confirm('Are you sure!')) {
        const dbs = await window.indexedDB.databases();
        dbs.forEach(db => {
          if(db.name.includes("kiotviet_sync")) {
            window.indexedDB.deleteDatabase(db.name);
          }
        })
        alert("Clear Cache successful!")
      }
    });
  });
</script>
