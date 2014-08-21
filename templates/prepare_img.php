<div class="col-sm-6 col-md-3">
    <div class="thumbnail">
        <img src="<?php echo $data['val'] ;?>" alt="" width="230">
        <div class="caption">
            <p></p>
            <p>
                <?php if ($data['error']):?>
                
                <a href="index.php?do=handle_imgs&amp;files[]=<?php echo urlencode ($data['origin']) ;?>" class="btn btn-primary" role="button">Рассчитать</a>
                
                
                <?php else:?>
                <b>Координаты не найдены!</b>
                <?php endif;?>
                
                <a href="index.php?do=remove_file&amp;file=<?php echo urlencode ($data['origin']) ;?>" class="btn btn-danger">Удалить</a>
            </p>
        </div>
    </div>
</div>