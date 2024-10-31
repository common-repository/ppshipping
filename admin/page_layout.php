<?php
function ppshipping_shipping_package_manage() {
	global $wpdb;
	
	$packages = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages ORDER BY created_date_time DESC');
	
	$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."pp_shipping_packages' AND column_name = 'ignore'");

	if(empty($row)){
	   $wpdb->query("ALTER TABLE `".$wpdb->prefix."pp_shipping_packages` ADD `ignore` BOOLEAN NOT NULL DEFAULT FALSE");
	}
	
	?>
	
	<p><a class="ppshipping_Btn-Primary" href="<?php echo get_admin_url(); ?>admin.php?page=ppshipping_packages_add" style="margin-left:-5px;">Add Package</a></p>
	<div class="ppshipping_adminBlock">
	<h2>Parcel Perfect Shipping Setup</h2>
		<table cellpadding="0" cellspacing="0" style="width: 100%;">
		<tr>
				<td>ID</td>
				<td><strong>Created Date</strong></td>
				<td style="padding-left: 10px;"><strong>Shipping Class</strong></td>
				<td><strong>Max no Items per package</strong></td>
				<td></td>
				<td></td>
			</tr>
		<?php
			foreach ($packages as $p):
			
			?>
			<tr>
				<td style="padding: 10px 5px;"><?php echo $p->id; ?></td>
				<td style="padding: 10px 5px;"><?php echo $p->created_date_time; ?></td>
				<td style="padding: 10px 5px;"><?php echo $p->shipping_class; ?></td>
				<td style="padding: 10px 5px;"><?php echo $p->no_items; ?></td>
				<td><a class="button"  href="admin.php?page=ppshipping_packages_edit&id=<?php echo $p->id; ?>">Edit Package</a></td>				
				<td><a class="button"  href="admin.php?page=ppshipping_packages_delete&id=<?php echo $p->id; ?>">Delete Package</a></td>				
			</tr>
		<?php endforeach; ?>
		</table>
	</div>
	<?php
}

function ppshipping_shipping_package_add() {
	global $wpdb;
	
	$shipping_classes = get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => false ) );	
	
	$default = new stdClass();
	$default->slug = 'default-no-shipping-class';
	$default->name = 'Default - No shipping class';
	
	$shipping_classes[] = $default;
	?>
	<p><a class="ppshipping_Btn-Primary" href="<?php echo get_admin_url(); ?>admin.php?page=ppshipping_admin" style="margin-left:-5px;">Go Back</a></p>
	<div class="ppshipping_adminBlock">
		<h2>Add Shipping Package</h2>
		
		<div id="ppshipping_loader" style="display: none;"><div class="loader-icon"></div></div>
		<div id="FormContent">
			<form action="admin.php?page=ppshipping_admin" method="post" onsubmit="return Add_Shipping_Package();">
				<p><i>Please select a shipping package to use for this shipping box specification:</i></p>
				<select id="pp_pack_shipping_class" name="pp_pack_shipping_class" required>
					<option value=""></option>
					<?php
						foreach ($shipping_classes as $sc) {
							$exist = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages WHERE shipping_class = "'.$sc->slug.'"');
							
							if (empty($exist)) {
								echo '<option value="'.$sc->slug.'">'.$sc->name.'</option>';
							}
						}
					?>
				</select>
				<div style="clear: both;"></div>
				<p><i>Please select the maximum amount of items per package:</i></p>
				<select id="pp_pack_no_items" name="pp_pack_no_items" required>
					<option value=""></option>
					<?php
						$i = 1;
						
						for ($i; $i <= 50; $i++) {
							echo '<option value="'.$i.'">'.$i.'</option>';
						}
					?>
				</select>
				<div style="clear: both;"></div>
				<p><i>Please indicate whether this shipping class should be ignored from waybills:</i></p>
				<select id="pp_pack_ignore" name="pp_pack_ignore" required>
					<option value="0">Include in waybills</option>
					<option value="1">Exclude from waybills</option>
				</select>
				<div style="clear: both;"></div>
				<div id="pp_package_dim_breakdown" style="display: none;">
					<h3>Package dimensions breakdown</h3>
					<div>
					
					</div>
				</div>
				<input type="submit" name="submit" value="Save Shipping Package" class="ppshipping_Btn-Primary" style="margin: 25px 0px 0px 0px;" />
			</form>
		</div>
		<div id="AjaxMessages" style="display: none;">
		
		</div>
		<div style="clear: both;"></div>
	</div>
	
	<?php	
}

function ppshipping_shipping_package_edit() {
	global $wpdb;
	
	$shipping_classes = get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => false ) );
	$p = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages WHERE id = '.$_GET["id"]);
	
	$default = new stdClass();
	$default->slug = 'default-no-shipping-class';
	$default->name = 'Default - No shipping class';
	
	$shipping_classes[] = $default;
	
	$pp_pack_include = 'selected="selected"';
	$pp_pack_exclude = '';
	
	if ($p->ignore) {
		$pp_pack_include = '';
		$pp_pack_exclude = 'selected="selected"';
	}

	?>
	<p><a class="ppshipping_Btn-Primary" href="<?php echo get_admin_url(); ?>admin.php?page=ppshipping_admin" style="margin-left:-5px;">Go Back</a></p>
	<div class="ppshipping_adminBlock">
		<h2>Edit Shipping Package</h2>
		
		<div id="ppshipping_loader" style="display: none;"><div class="loader-icon"></div></div>
		<div id="FormContent">
			<form action="admin.php?page=ppshipping_admin" method="post" onsubmit="return Update_Shipping_Package();">
				<p><i>Please select a shipping package to use for this shipping box specification:</i></p>
				<select id="pp_pack_shipping_class" name="pp_pack_shipping_class" required>
					<option value=""></option>
					<option value="<?php echo $p->shipping_class; ?>" selected="selected"><?php echo $p->shipping_class_name; ?></option>
					<?php
						foreach ($shipping_classes as $sc) {
							$exist = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages WHERE shipping_class = "'.$sc->slug.'"');
							
							if (empty($exist)) {
								echo '<option value="'.$sc->slug.'">'.$sc->name.'</option>';
							}
						}
					?>
				</select>
				<div style="clear: both;"></div>
				<p><i>Please select the maximum amount of items per package:</i></p>
				<select id="pp_pack_no_items" name="pp_pack_no_items" required>
					<?php
						$i = 1;
						
						for ($i; $i <= 50; $i++) {
							if ($i == $p->no_items) {
								$selected = 'selected="selected"';
							}
							else {
								$selected = '';
							}
							
							echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
						}
					?>
				</select>
				<div style="clear: both;"></div>
				<p><i>Please indicate whether this shipping class should be ignored from waybills:</i></p>
				<select id="pp_pack_ignore" name="pp_pack_ignore" required>
					<option value="0" <?php echo $pp_pack_include; ?>>Include in waybills</option>
					<option value="1" <?php echo $pp_pack_exclude; ?>>Exclude from waybills</option>
				</select>
				<div style="clear: both;"></div>
				<div id="pp_package_dim_breakdown">
					<h3>Package dimensions breakdown</h3>
					<div>
						<table id="ppshipping_package_breakdown" cellpadding="0" cellspacing="0">
							<?php
								$amount = $p->no_items;
								$i = 0;
								
								$label = explode(';',$p->label);
								$width = explode(';',$p->width);
								$length = explode(';',$p->length);
								$height = explode(';',$p->height);
								$weight = explode(';',$p->weight);
								
								echo '<table id="ppshipping_package_breakdown" cellpadding="0" cellspacing="0">';
								
								for ($i; $i < $amount; $i++) {
									echo '<tr>
													<td>Label:</td>
													<td><input type="text" name="ppshipping_breakdown_label[]" value="'.$label[$i].'" /></td>
													<td>Width (cm):</td>
													<td><input type="number" name="ppshipping_breakdown_width[]" value="'.$width[$i].'" min="1" max="1000" /></td>
													<td>Length (cm):</td>
													<td><input type="number" name="ppshipping_breakdown_length[]" value="'.$length[$i].'" min="1" max="1000" /></td>
													<td>Height (cm):</td>
													<td><input type="number" name="ppshipping_breakdown_height[]" value="'.$height[$i].'" min="1" max="1000" /></td>
													<td>Weight (kg):</td>
													<td><input type="number" name="ppshipping_breakdown_weight[]" value="'.$weight[$i].'" min="1" max="50" /></td>
												</tr>';
								}
								
								echo '</table>';
							?>
						</table>
					</div>
				</div>
				<input id="package_id" type="hidden" name="package_id" value="<?php echo $p->id; ?>" />
				<input type="submit" name="submit" value="Update Shipping Package" class="ppshipping_Btn-Primary" style="margin: 25px 0px 0px 0px;" />
			</form>
		</div>
		<div id="AjaxMessages" style="display: none;">
		
		</div>
		<div style="clear: both;"></div>
	</div>
	
	<?php	
}

function ppshipping_shipping_package_delete() {
	global $wpdb;
	
	$p = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages WHERE id = '.$_GET["id"]);
	?>
	<p><a class="ppshipping_Btn-Primary" href="<?php echo get_admin_url(); ?>admin.php?page=ppshipping_admin" style="margin-left:-5px;">Go Back</a></p>
	<div class="ppshipping_adminBlock">
		<h2>Delete Shipping Package</h2>
		
		<div id="ppshipping_loader" style="display: none;"><div class="loader-icon"></div></div>
		<div id="FormContent">
			<form action="admin.php?page=ppshipping_admin" method="post" onsubmit="return Delete_Shipping_Package();">
				<p><i>Are you sure you would like to delete the shipping package connected to <?php echo $p->shipping_class_name; ?>?</i></p>
				<input id="package_id" type="hidden" name="package_id" value="<?php echo $p->id; ?>" />
				<input type="submit" name="submit" value="Delete Shipping Package" class="ppshipping_Btn-Primary" />
			</form>
		</div>
		<div id="AjaxMessages" style="display: none;">
		
		</div>
		<div style="clear: both;"></div>
	</div>
	
	<?php	
}