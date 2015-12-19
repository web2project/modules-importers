<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}

$obj = $AppUI->restoreObject();
$importer = CImporter::castToClass($obj);

if (is_null($importer)) {
	$AppUI->setMsg('Failure: '.$AppUI->_('taskerror'), UI_MSG_ERROR, true);
	$AppUI->redirect('m=importers');
}

$titleBlock = new w2p_Theme_TitleBlock('Project Importer', 'icon.png', $m, "$m.$a");
$titleBlock->show();

$AppUI->addFooterJavascriptFile('modules/importers/view.js');
?>
<form name="preForm" action="?m=importers" method="post" accept-charset="utf-8">
	<input type="hidden" name="action" value="save">
	<input type="hidden" name="dosql" value="do_importer_aed" />
	<input type="hidden" name="filetype" value="<? echo $importer->fileType;?>">

	<table cellspacing="1" cellpadding="1" border="0" width='100%' class="std">
		<tr>
			<td>
				<?php echo $importer->view($AppUI); ?>
			</td>
		</tr>
		<tr>
			<td>
				<input type="submit" class="text" name="submit" value="<? echo $AppUI->_('Import');?>" onClick="validateImport(); return false;">
				<input type="submit" class="text" name="submit" value="<? echo $AppUI->_('cancel');?>" onClick="this.form.action.value='cancel'">
			</td>
		</tr>
	</table>
</form>