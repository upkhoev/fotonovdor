<form class="form-horizontal" role="form" method="POST" action="index.php?do=saveSetting">
    <?php if ($data['setting']):?>
    <?php foreach ($data['setting'] as $val):?>
    
    <div class="form-group">
        <label for="inputEmail3" class="col-sm-2 control-label"><?php echo $val['description'];?></label>
        <div class="col-sm-10">
            <input type="text" name="config[<?php echo $val['settingKey'];?>]" value="<?php echo $val['settingVal'];?>" class="form-control">
        </div>
    </div>
    
    <?php endforeach;?>
    <?php endif;?>
    
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" class="btn btn-default">Сохранить</button>
      </div>
    </div>
    <input type="hidden" name="save" value="1">
</form>

<?php if ( isset($data['msg']) ):?>
<div class="alert alert-success"><b>Выполнено успешно!</b> <?php echo $data['msg'];?></div>
<?php endif; ?>
