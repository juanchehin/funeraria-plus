<?php

// Data functions (insert, update, delete, form) for table invoices

// This script and data application were generated by AppGini 5.71
// Download AppGini for free from https://bigprof.com/appgini/download/

function invoices_insert(){
	global $Translation;

	// mm: can member insert record?
	$arrPerm=getTablePermissions('invoices');
	if(!$arrPerm[1]){
		return false;
	}

	$data['deceased'] = makeSafe($_REQUEST['deceased']);
		if($data['deceased'] == empty_lookup_value){ $data['deceased'] = ''; }
	$data['room'] = makeSafe($_REQUEST['deceased']);
		if($data['room'] == empty_lookup_value){ $data['room'] = ''; }
	$data['relative'] = makeSafe($_REQUEST['deceased']);
		if($data['relative'] == empty_lookup_value){ $data['relative'] = ''; }
	$data['services'] = br2nl(makeSafe($_REQUEST['services']));
	$data['total'] = makeSafe($_REQUEST['total']);
		if($data['total'] == empty_lookup_value){ $data['total'] = ''; }
	$data['balance'] = makeSafe($_REQUEST['balance']);
		if($data['balance'] == empty_lookup_value){ $data['balance'] = ''; }
	$data['date'] = parseCode('<%%creationDate%%>', true, true);
	if($data['deceased']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Deceased': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	if($data['services']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Services': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	if($data['total']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Total': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}

	// hook: invoices_before_insert
	if(function_exists('invoices_before_insert')){
		$args=array();
		if(!invoices_before_insert($data, getMemberInfo(), $args)){ return false; }
	}

	$o = array('silentErrors' => true);
	sql('insert into `invoices` set       `deceased`=' . (($data['deceased'] !== '' && $data['deceased'] !== NULL) ? "'{$data['deceased']}'" : 'NULL') . ', `room`=' . (($data['room'] !== '' && $data['room'] !== NULL) ? "'{$data['room']}'" : 'NULL') . ', `relative`=' . (($data['relative'] !== '' && $data['relative'] !== NULL) ? "'{$data['relative']}'" : 'NULL') . ', `services`=' . (($data['services'] !== '' && $data['services'] !== NULL) ? "'{$data['services']}'" : 'NULL') . ', `total`=' . (($data['total'] !== '' && $data['total'] !== NULL) ? "'{$data['total']}'" : 'NULL') . ', `balance`=' . (($data['balance'] !== '' && $data['balance'] !== NULL) ? "'{$data['balance']}'" : 'NULL') . ', `date`=' . "'{$data['date']}'", $o);
	if($o['error']!=''){
		echo $o['error'];
		echo "<a href=\"invoices_view.php?addNew_x=1\">{$Translation['< back']}</a>";
		exit;
	}

	$recID = db_insert_id(db_link());

	// hook: invoices_after_insert
	if(function_exists('invoices_after_insert')){
		$res = sql("select * from `invoices` where `id`='" . makeSafe($recID, false) . "' limit 1", $eo);
		if($row = db_fetch_assoc($res)){
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = makeSafe($recID, false);
		$args=array();
		if(!invoices_after_insert($data, getMemberInfo(), $args)){ return $recID; }
	}

	// mm: save ownership data
	set_record_owner('invoices', $recID, getLoggedMemberID());

	return $recID;
}

function invoices_delete($selected_id, $AllowDeleteOfParents=false, $skipChecks=false){
	// insure referential integrity ...
	global $Translation;
	$selected_id=makeSafe($selected_id);

	// mm: can member delete record?
	$arrPerm=getTablePermissions('invoices');
	$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='invoices' and pkValue='$selected_id'");
	$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='invoices' and pkValue='$selected_id'");
	if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3){ // allow delete?
		// delete allowed, so continue ...
	}else{
		return $Translation['You don\'t have enough permissions to delete this record'];
	}

	// hook: invoices_before_delete
	if(function_exists('invoices_before_delete')){
		$args=array();
		if(!invoices_before_delete($selected_id, $skipChecks, getMemberInfo(), $args))
			return $Translation['Couldn\'t delete this record'];
	}

	// child table: bill
	$res = sql("select `id` from `invoices` where `id`='$selected_id'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("select count(1) from `bill` where `deceased`='".addslashes($id[0])."'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks){
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace("<RelatedRecords>", $rirow[0], $RetMsg);
		$RetMsg = str_replace("<TableName>", "bill", $RetMsg);
		return $RetMsg;
	}elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks){
		$RetMsg = $Translation["confirm delete"];
		$RetMsg = str_replace("<RelatedRecords>", $rirow[0], $RetMsg);
		$RetMsg = str_replace("<TableName>", "bill", $RetMsg);
		$RetMsg = str_replace("<Delete>", "<input type=\"button\" class=\"button\" value=\"".$Translation['yes']."\" onClick=\"window.location='invoices_view.php?SelectedID=".urlencode($selected_id)."&delete_x=1&confirmed=1';\">", $RetMsg);
		$RetMsg = str_replace("<Cancel>", "<input type=\"button\" class=\"button\" value=\"".$Translation['no']."\" onClick=\"window.location='invoices_view.php?SelectedID=".urlencode($selected_id)."';\">", $RetMsg);
		return $RetMsg;
	}

	sql("delete from `invoices` where `id`='$selected_id'", $eo);

	// hook: invoices_after_delete
	if(function_exists('invoices_after_delete')){
		$args=array();
		invoices_after_delete($selected_id, getMemberInfo(), $args);
	}

	// mm: delete ownership data
	sql("delete from membership_userrecords where tableName='invoices' and pkValue='$selected_id'", $eo);
}

function invoices_update($selected_id){
	global $Translation;

	// mm: can member edit record?
	$arrPerm=getTablePermissions('invoices');
	$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='invoices' and pkValue='".makeSafe($selected_id)."'");
	$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='invoices' and pkValue='".makeSafe($selected_id)."'");
	if(($arrPerm[3]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[3]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[3]==3){ // allow update?
		// update allowed, so continue ...
	}else{
		return false;
	}

	$data['deceased'] = makeSafe($_REQUEST['deceased']);
		if($data['deceased'] == empty_lookup_value){ $data['deceased'] = ''; }
	if($data['deceased']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Deceased': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['room'] = makeSafe($_REQUEST['deceased']);
		if($data['room'] == empty_lookup_value){ $data['room'] = ''; }
	$data['relative'] = makeSafe($_REQUEST['deceased']);
		if($data['relative'] == empty_lookup_value){ $data['relative'] = ''; }
	$data['services'] = br2nl(makeSafe($_REQUEST['services']));
	if($data['services']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Services': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['total'] = makeSafe($_REQUEST['total']);
		if($data['total'] == empty_lookup_value){ $data['total'] = ''; }
	if($data['total']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Total': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['balance'] = makeSafe($_REQUEST['balance']);
		if($data['balance'] == empty_lookup_value){ $data['balance'] = ''; }
	$data['date'] = parseMySQLDate('', '<%%creationDate%%>');
	$data['selectedID']=makeSafe($selected_id);

	// hook: invoices_before_update
	if(function_exists('invoices_before_update')){
		$args=array();
		if(!invoices_before_update($data, getMemberInfo(), $args)){ return false; }
	}

	$o=array('silentErrors' => true);
	sql('update `invoices` set       `deceased`=' . (($data['deceased'] !== '' && $data['deceased'] !== NULL) ? "'{$data['deceased']}'" : 'NULL') . ', `room`=' . (($data['room'] !== '' && $data['room'] !== NULL) ? "'{$data['room']}'" : 'NULL') . ', `relative`=' . (($data['relative'] !== '' && $data['relative'] !== NULL) ? "'{$data['relative']}'" : 'NULL') . ', `services`=' . (($data['services'] !== '' && $data['services'] !== NULL) ? "'{$data['services']}'" : 'NULL') . ', `total`=' . (($data['total'] !== '' && $data['total'] !== NULL) ? "'{$data['total']}'" : 'NULL') . ', `balance`=' . (($data['balance'] !== '' && $data['balance'] !== NULL) ? "'{$data['balance']}'" : 'NULL') . ', `date`=`date`' . " where `id`='".makeSafe($selected_id)."'", $o);
	if($o['error']!=''){
		echo $o['error'];
		echo '<a href="invoices_view.php?SelectedID='.urlencode($selected_id)."\">{$Translation['< back']}</a>";
		exit;
	}


	// hook: invoices_after_update
	if(function_exists('invoices_after_update')){
		$res = sql("SELECT * FROM `invoices` WHERE `id`='{$data['selectedID']}' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)){
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = $data['id'];
		$args = array();
		if(!invoices_after_update($data, getMemberInfo(), $args)){ return; }
	}

	// mm: update ownership data
	sql("update membership_userrecords set dateUpdated='".time()."' where tableName='invoices' and pkValue='".makeSafe($selected_id)."'", $eo);

}

function invoices_form($selected_id = '', $AllowUpdate = 1, $AllowInsert = 1, $AllowDelete = 1, $ShowCancel = 0, $TemplateDV = '', $TemplateDVP = ''){
	// function to return an editable form for a table records
	// and fill it with data of record whose ID is $selected_id. If $selected_id
	// is empty, an empty form is shown, with only an 'Add New'
	// button displayed.

	global $Translation;

	// mm: get table permissions
	$arrPerm=getTablePermissions('invoices');
	if(!$arrPerm[1] && $selected_id==''){ return ''; }
	$AllowInsert = ($arrPerm[1] ? true : false);
	// print preview?
	$dvprint = false;
	if($selected_id && $_REQUEST['dvprint_x'] != ''){
		$dvprint = true;
	}

	$filterer_deceased = thisOr(undo_magic_quotes($_REQUEST['filterer_deceased']), '');

	// populate filterers, starting from children to grand-parents

	// unique random identifier
	$rnd1 = ($dvprint ? rand(1000000, 9999999) : '');
	// combobox: deceased
	$combo_deceased = new DataCombo;
	// combobox: date
	$combo_date = new DateCombo;
	$combo_date->DateFormat = "mdy";
	$combo_date->MinYear = 1900;
	$combo_date->MaxYear = 2100;
	$combo_date->DefaultDate = parseMySQLDate('<%%creationDate%%>', '<%%creationDate%%>');
	$combo_date->MonthNames = $Translation['month names'];
	$combo_date->NamePrefix = 'date';

	if($selected_id){
		// mm: check member permissions
		if(!$arrPerm[2]){
			return "";
		}
		// mm: who is the owner?
		$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='invoices' and pkValue='".makeSafe($selected_id)."'");
		$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='invoices' and pkValue='".makeSafe($selected_id)."'");
		if($arrPerm[2]==1 && getLoggedMemberID()!=$ownerMemberID){
			return "";
		}
		if($arrPerm[2]==2 && getLoggedGroupID()!=$ownerGroupID){
			return "";
		}

		// can edit?
		if(($arrPerm[3]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[3]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[3]==3){
			$AllowUpdate=1;
		}else{
			$AllowUpdate=0;
		}

		$res = sql("select * from `invoices` where `id`='".makeSafe($selected_id)."'", $eo);
		if(!($row = db_fetch_array($res))){
			return error_message($Translation['No records found'], 'invoices_view.php', false);
		}
		$urow = $row; /* unsanitized data */
		$hc = new CI_Input();
		$row = $hc->xss_clean($row); /* sanitize data */
		$combo_deceased->SelectedData = $row['deceased'];
		$combo_date->DefaultDate = $row['date'];
	}else{
		$combo_deceased->SelectedData = $filterer_deceased;
	}
	$combo_deceased->HTML = '<span id="deceased-container' . $rnd1 . '"></span><input type="hidden" name="deceased" id="deceased' . $rnd1 . '" value="' . html_attr($combo_deceased->SelectedData) . '">';
	$combo_deceased->MatchText = '<span id="deceased-container-readonly' . $rnd1 . '"></span><input type="hidden" name="deceased" id="deceased' . $rnd1 . '" value="' . html_attr($combo_deceased->SelectedData) . '">';

	ob_start();
	?>

	<script>
		// initial lookup values
		AppGini.current_deceased__RAND__ = { text: "", value: "<?php echo addslashes($selected_id ? $urow['deceased'] : $filterer_deceased); ?>"};

		jQuery(function() {
			setTimeout(function(){
				if(typeof(deceased_reload__RAND__) == 'function') deceased_reload__RAND__();
			}, 10); /* we need to slightly delay client-side execution of the above code to allow AppGini.ajaxCache to work */
		});
		function deceased_reload__RAND__(){
		<?php if(($AllowUpdate || $AllowInsert) && !$dvprint){ ?>

			$j("#deceased-container__RAND__").select2({
				/* initial default value */
				initSelection: function(e, c){
					$j.ajax({
						url: 'ajax_combo.php',
						dataType: 'json',
						data: { id: AppGini.current_deceased__RAND__.value, t: 'invoices', f: 'deceased' },
						success: function(resp){
							c({
								id: resp.results[0].id,
								text: resp.results[0].text
							});
							$j('[name="deceased"]').val(resp.results[0].id);
							$j('[id=deceased-container-readonly__RAND__]').html('<span id="deceased-match-text">' + resp.results[0].text + '</span>');
							if(resp.results[0].id == '<?php echo empty_lookup_value; ?>'){ $j('.btn[id=incoming_deceased_view_parent]').hide(); }else{ $j('.btn[id=incoming_deceased_view_parent]').show(); }


							if(typeof(deceased_update_autofills__RAND__) == 'function') deceased_update_autofills__RAND__();
						}
					});
				},
				width: '100%',
				formatNoMatches: function(term){ /* */ return '<?php echo addslashes($Translation['No matches found!']); ?>'; },
				minimumResultsForSearch: 10,
				loadMorePadding: 200,
				ajax: {
					url: 'ajax_combo.php',
					dataType: 'json',
					cache: true,
					data: function(term, page){ /* */ return { s: term, p: page, t: 'invoices', f: 'deceased' }; },
					results: function(resp, page){ /* */ return resp; }
				},
				escapeMarkup: function(str){ /* */ return str; }
			}).on('change', function(e){
				AppGini.current_deceased__RAND__.value = e.added.id;
				AppGini.current_deceased__RAND__.text = e.added.text;
				$j('[name="deceased"]').val(e.added.id);
				if(e.added.id == '<?php echo empty_lookup_value; ?>'){ $j('.btn[id=incoming_deceased_view_parent]').hide(); }else{ $j('.btn[id=incoming_deceased_view_parent]').show(); }


				if(typeof(deceased_update_autofills__RAND__) == 'function') deceased_update_autofills__RAND__();
			});

			if(!$j("#deceased-container__RAND__").length){
				$j.ajax({
					url: 'ajax_combo.php',
					dataType: 'json',
					data: { id: AppGini.current_deceased__RAND__.value, t: 'invoices', f: 'deceased' },
					success: function(resp){
						$j('[name="deceased"]').val(resp.results[0].id);
						$j('[id=deceased-container-readonly__RAND__]').html('<span id="deceased-match-text">' + resp.results[0].text + '</span>');
						if(resp.results[0].id == '<?php echo empty_lookup_value; ?>'){ $j('.btn[id=incoming_deceased_view_parent]').hide(); }else{ $j('.btn[id=incoming_deceased_view_parent]').show(); }

						if(typeof(deceased_update_autofills__RAND__) == 'function') deceased_update_autofills__RAND__();
					}
				});
			}

		<?php }else{ ?>

			$j.ajax({
				url: 'ajax_combo.php',
				dataType: 'json',
				data: { id: AppGini.current_deceased__RAND__.value, t: 'invoices', f: 'deceased' },
				success: function(resp){
					$j('[id=deceased-container__RAND__], [id=deceased-container-readonly__RAND__]').html('<span id="deceased-match-text">' + resp.results[0].text + '</span>');
					if(resp.results[0].id == '<?php echo empty_lookup_value; ?>'){ $j('.btn[id=incoming_deceased_view_parent]').hide(); }else{ $j('.btn[id=incoming_deceased_view_parent]').show(); }

					if(typeof(deceased_update_autofills__RAND__) == 'function') deceased_update_autofills__RAND__();
				}
			});
		<?php } ?>

		}
	</script>
	<?php

	$lookups = str_replace('__RAND__', $rnd1, ob_get_contents());
	ob_end_clean();


	// code for template based detail view forms

	// open the detail view template
	if($dvprint){
		$template_file = is_file("./{$TemplateDVP}") ? "./{$TemplateDVP}" : './templates/invoices_templateDVP.html';
		$templateCode = @file_get_contents($template_file);
	}else{
		$template_file = is_file("./{$TemplateDV}") ? "./{$TemplateDV}" : './templates/invoices_templateDV.html';
		$templateCode = @file_get_contents($template_file);
	}

	// process form title
	$templateCode = str_replace('<%%DETAIL_VIEW_TITLE%%>', 'Invoice details', $templateCode);
	$templateCode = str_replace('<%%RND1%%>', $rnd1, $templateCode);
	$templateCode = str_replace('<%%EMBEDDED%%>', ($_REQUEST['Embedded'] ? 'Embedded=1' : ''), $templateCode);
	// process buttons
	if($AllowInsert){
		if(!$selected_id) $templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-success" id="insert" name="insert_x" value="1" onclick="return invoices_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save New'] . '</button>', $templateCode);
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="insert" name="insert_x" value="1" onclick="return invoices_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save As Copy'] . '</button>', $templateCode);
	}else{
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '', $templateCode);
	}

	// 'Back' button action
	if($_REQUEST['Embedded']){
		$backAction = 'AppGini.closeParentModal(); return false;';
	}else{
		$backAction = '$j(\'form\').eq(0).attr(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;';
	}

	if($selected_id){
		if(!$_REQUEST['Embedded']) $templateCode = str_replace('<%%DVPRINT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="dvprint" name="dvprint_x" value="1" onclick="$$(\'form\')[0].writeAttribute(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;" title="' . html_attr($Translation['Print Preview']) . '"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>', $templateCode);
		if($AllowUpdate){
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '<button type="submit" class="btn btn-success btn-lg" id="update" name="update_x" value="1" onclick="return invoices_validateData();" title="' . html_attr($Translation['Save Changes']) . '"><i class="glyphicon glyphicon-ok"></i> ' . $Translation['Save Changes'] . '</button>', $templateCode);
		}else{
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		}
		if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3){ // allow delete?
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '<button type="submit" class="btn btn-danger" id="delete" name="delete_x" value="1" onclick="return confirm(\'' . $Translation['are you sure?'] . '\');" title="' . html_attr($Translation['Delete']) . '"><i class="glyphicon glyphicon-trash"></i> ' . $Translation['Delete'] . '</button>', $templateCode);
		}else{
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		}
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>', $templateCode);
	}else{
		$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', ($ShowCancel ? '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>' : ''), $templateCode);
	}

	// set records to read only if user can't insert new records and can't edit current record
	if(($selected_id && !$AllowUpdate && !$AllowInsert) || (!$selected_id && !$AllowInsert)){
		$jsReadOnly .= "\tjQuery('#deceased').prop('disabled', true).css({ color: '#555', backgroundColor: '#fff' });\n";
		$jsReadOnly .= "\tjQuery('#deceased_caption').prop('disabled', true).css({ color: '#555', backgroundColor: 'white' });\n";
		$jsReadOnly .= "\tjQuery('#services').replaceWith('<div class=\"form-control-static\" id=\"services\">' + (jQuery('#services').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#total').replaceWith('<div class=\"form-control-static\" id=\"total\">' + (jQuery('#total').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#balance').replaceWith('<div class=\"form-control-static\" id=\"balance\">' + (jQuery('#balance').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('.select2-container').hide();\n";

		$noUploads = true;
	}elseif($AllowInsert){
		$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', true);"; // temporarily disable form change handler
			$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', false);"; // re-enable form change handler
	}

	// process combos
	$templateCode = str_replace('<%%COMBO(deceased)%%>', $combo_deceased->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(deceased)%%>', $combo_deceased->MatchText, $templateCode);
	$templateCode = str_replace('<%%URLCOMBOTEXT(deceased)%%>', urlencode($combo_deceased->MatchText), $templateCode);
	$templateCode = str_replace('<%%COMBO(date)%%>', ($selected_id && !$arrPerm[3] ? '<div class="form-control-static">' . $combo_date->GetHTML(true) . '</div>' : $combo_date->GetHTML()), $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(date)%%>', $combo_date->GetHTML(true), $templateCode);

	/* lookup fields array: 'lookup field name' => array('parent table name', 'lookup field caption') */
	$lookup_fields = array(  'deceased' => array('incoming_deceased', 'Deceased'));
	foreach($lookup_fields as $luf => $ptfc){
		$pt_perm = getTablePermissions($ptfc[0]);

		// process foreign key links
		if($pt_perm['view'] || $pt_perm['edit']){
			$templateCode = str_replace("<%%PLINK({$luf})%%>", '<button type="button" class="btn btn-default view_parent hspacer-md" id="' . $ptfc[0] . '_view_parent" title="' . html_attr($Translation['View'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-eye-open"></i></button>', $templateCode);
		}

		// if user has insert permission to parent table of a lookup field, put an add new button
		if($pt_perm['insert'] && !$_REQUEST['Embedded']){
			$templateCode = str_replace("<%%ADDNEW({$ptfc[0]})%%>", '<button type="button" class="btn btn-success add_new_parent hspacer-md" id="' . $ptfc[0] . '_add_new" title="' . html_attr($Translation['Add New'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-plus-sign"></i></button>', $templateCode);
		}
	}

	// process images
	$templateCode = str_replace('<%%UPLOADFILE(id)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(deceased)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(services)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(total)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(balance)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(date)%%>', '', $templateCode);

	// process values
	if($selected_id){
		if( $dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', safe_html($urow['id']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', html_attr($row['id']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode($urow['id']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(deceased)%%>', safe_html($urow['deceased']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(deceased)%%>', html_attr($row['deceased']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(deceased)%%>', urlencode($urow['deceased']), $templateCode);
		if($dvprint || (!$AllowUpdate && !$AllowInsert)){
			$templateCode = str_replace('<%%VALUE(services)%%>', safe_html($urow['services']), $templateCode);
		}else{
			$templateCode = str_replace('<%%VALUE(services)%%>', html_attr($row['services']), $templateCode);
		}
		$templateCode = str_replace('<%%URLVALUE(services)%%>', urlencode($urow['services']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(total)%%>', safe_html($urow['total']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(total)%%>', html_attr($row['total']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(total)%%>', urlencode($urow['total']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(balance)%%>', safe_html($urow['balance']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(balance)%%>', html_attr($row['balance']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(balance)%%>', urlencode($urow['balance']), $templateCode);
		$templateCode = str_replace('<%%VALUE(date)%%>', @date('m/d/Y', @strtotime(html_attr($row['date']))), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(date)%%>', urlencode(@date('m/d/Y', @strtotime(html_attr($urow['date'])))), $templateCode);
	}else{
		$templateCode = str_replace('<%%VALUE(id)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(deceased)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(deceased)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(services)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(services)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(total)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(total)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(balance)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(balance)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(date)%%>', '<%%creationDate%%>', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(date)%%>', urlencode('<%%creationDate%%>'), $templateCode);
	}

	// process translations
	foreach($Translation as $symbol=>$trans){
		$templateCode = str_replace("<%%TRANSLATION($symbol)%%>", $trans, $templateCode);
	}

	// clear scrap
	$templateCode = str_replace('<%%', '<!-- ', $templateCode);
	$templateCode = str_replace('%%>', ' -->', $templateCode);

	// hide links to inaccessible tables
	if($_REQUEST['dvprint_x'] == ''){
		$templateCode .= "\n\n<script>\$j(function(){\n";
		$arrTables = getTableList();
		foreach($arrTables as $name => $caption){
			$templateCode .= "\t\$j('#{$name}_link').removeClass('hidden');\n";
			$templateCode .= "\t\$j('#xs_{$name}_link').removeClass('hidden');\n";
		}

		$templateCode .= $jsReadOnly;
		$templateCode .= $jsEditable;

		if(!$selected_id){
		}

		$templateCode.="\n});</script>\n";
	}

	// ajaxed auto-fill fields
	$templateCode .= '<script>';
	$templateCode .= '$j(function() {';

	$templateCode .= "\tdeceased_update_autofills$rnd1 = function(){\n";
	$templateCode .= "\t\t\$j.ajax({\n";
	if($dvprint){
		$templateCode .= "\t\t\turl: 'invoices_autofill.php?rnd1=$rnd1&mfk=deceased&id=' + encodeURIComponent('".addslashes($row['deceased'])."'),\n";
		$templateCode .= "\t\t\tcontentType: 'application/x-www-form-urlencoded; charset=" . datalist_db_encoding . "', type: 'GET'\n";
	}else{
		$templateCode .= "\t\t\turl: 'invoices_autofill.php?rnd1=$rnd1&mfk=deceased&id=' + encodeURIComponent(AppGini.current_deceased{$rnd1}.value),\n";
		$templateCode .= "\t\t\tcontentType: 'application/x-www-form-urlencoded; charset=" . datalist_db_encoding . "', type: 'GET', beforeSend: function(){ /* */ \$j('#deceased$rnd1').prop('disabled', true); \$j('#deceasedLoading').html('<img src=loading.gif align=top>'); }, complete: function(){".(($arrPerm[1] || (($arrPerm[3] == 1 && $ownerMemberID == getLoggedMemberID()) || ($arrPerm[3] == 2 && $ownerGroupID == getLoggedGroupID()) || $arrPerm[3] == 3)) ? "\$j('#deceased$rnd1').prop('disabled', false); " : "\$j('#deceased$rnd1').prop('disabled', true); ")."\$j('#deceasedLoading').html('');}\n";
	}
	$templateCode.="\t\t});\n";
	$templateCode.="\t};\n";
	if(!$dvprint) $templateCode.="\tif(\$j('#deceased_caption').length) \$j('#deceased_caption').click(function(){ /* */ deceased_update_autofills$rnd1(); });\n";


	$templateCode.="});";
	$templateCode.="</script>";
	$templateCode .= $lookups;

	// handle enforced parent values for read-only lookup fields

	// don't include blank images in lightbox gallery
	$templateCode = preg_replace('/blank.gif" data-lightbox=".*?"/', 'blank.gif"', $templateCode);

	// don't display empty email links
	$templateCode=preg_replace('/<a .*?href="mailto:".*?<\/a>/', '', $templateCode);

	/* default field values */
	$rdata = $jdata = get_defaults('invoices');
	if($selected_id){
		$jdata = get_joined_record('invoices', $selected_id);
		if($jdata === false) $jdata = get_defaults('invoices');
		$rdata = $row;
	}
	$cache_data = array(
		'rdata' => array_map('nl2br', array_map('html_attr_tags_ok', $rdata)),
		'jdata' => array_map('nl2br', array_map('html_attr_tags_ok', $jdata))
	);
	$templateCode .= loadView('invoices-ajax-cache', $cache_data);

	// hook: invoices_dv
	if(function_exists('invoices_dv')){
		$args=array();
		invoices_dv(($selected_id ? $selected_id : FALSE), getMemberInfo(), $templateCode, $args);
	}

	return $templateCode;
}
?>