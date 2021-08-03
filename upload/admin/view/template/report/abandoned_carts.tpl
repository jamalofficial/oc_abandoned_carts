<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="button" id="button-recover" form="form-order" formaction="<?php echo $recover; ?>" data-toggle="tooltip" title="<?php echo $button_recover; ?>" class="btn btn-info"><i class="fa fa-paper-plane"></i></button>
        <button type="button" id="button-delete" form="form-order" formaction="<?php echo $delete; ?>" data-toggle="tooltip" title="<?php echo $button_delete; ?>" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $text_list; ?></h3>
      </div>

        <form method="post" action="" enctype="multipart/form-data" id="form-order">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" /></td>
                  <td class="text-left"><?php echo $column_customer; ?></td>
                  <td>Products</td>
                  <td class="text-right"><?php echo $column_total; ?></td>
                  <td class="text-center"><?php echo $column_action; ?></td>
                </tr>
              </thead>
              <tbody>
                <?php if ($carts): ?>
                  <?php foreach ($carts as $customer_id => $cart) : ?>
                    <?php $total_value = 0; ?>
                    <tr>
                      <td class="text-center">
                        <?php if (in_array($customer_id, $selected)) : ?>
                          <input type="checkbox" name="selected[]" value="<?php echo $customer_id; ?>" checked="checked" />
                        <?php else:  ?>
                          <input type="checkbox" name="selected[]" value="<?php echo $customer_id; ?>" />
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php echo "{$cart['info'][firstname]} {$cart['info'][firstname]}"; ?><br>
                        <small>(<?php echo $cart['info']['email'];?>)</small>    
                      </td>
                      <!-- for products -->
                      <td>
                        <?php if( !empty($cart['products']) ): ?>
                        <h4><b>Products:</b></h4>
                        <ol>
                        <?php foreach($cart['products'] as $product_id => $item) :?>
                          <?php $total_value += $item['quantity']*$item['price']; ?>
                          <li>
                            <?php echo "{$item['name']}&nbsp;<b>(x{$item['quantity']})</b>"; ?>
                            <br><small>Added On: <?php echo $item['date_added']; ?></small>
                          </li>
                        <?php endforeach; ?>
                        </ol>
                        <?php endif; ?>
                      </td>
                      <td class="text-right"><?php echo $total_value; ?></td>
                      <td class="text-center">
                        <a 
                          href='<?php echo $this->url->link("report/abandoned_carts/delete", "token=$token&customer_id=$customer_id", true); ?>'
                          data-toggle="tooltip" title="<?php echo $button_delete; ?>" 
                          class="btn btn-danger">
                          <i class="fa fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                <tr>
                  <td class="text-center" colspan="8"><?php echo $text_no_results; ?></td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </form>
        <div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
          <div class="col-sm-6 text-right"><?php echo $results; ?></div>
        </div>
      </div>
    </div>
  </div>
<script type="text/javascript"><!--
$('#button-recover').on('click', function(e) {
	$('#form-order').attr('action', this.getAttribute('formAction'));

	if (confirm('<?php echo $text_confirm; ?>')) {
		$('#form-order').submit();
	} else {
		return false;
	}
});
$('#button-delete').on('click', function(e) {
	$('#form-order').attr('action', this.getAttribute('formAction'));

	if (confirm('<?php echo $text_confirm; ?>')) {
		$('#form-order').submit();
	} else {
		return false;
	}
});
//--></script>
</div>
<?php echo $footer; ?>
