<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}
/* Modified by Thomas Zander Version 1.3 thomas-zander@arcor.de  */
/* Modified by Wellison da Rocha Pereira wellisonpereira@gmail.com */

global $AppUI, $canRead, $canEdit, $m;

$action = w2PgetParam($_POST, 'action', '');
$filetype = w2PgetParam($_POST, 'filetype', null);

$myMemLimit = ini_get('memory_limit');
$myMemLimit = intval(substr($myMemLimit, 0, strlen($myMemLimit) - 1));
$maxFileSize = substr(ini_get('memory_limit'), 0, strlen(ini_get('memory_limit') - 1)) * 1024* 1000;

//TODO: This message should be placed somewhere better without using echo.
echo $AppUI->_('msinfo') .' <br /><br />';
?>
<form enctype="multipart/form-data" action="index.php?m=importers" method="post">
	<input type="hidden" name="dosql" value="do_importer_aed" />
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxFileSize; ?>" />
	<input type="hidden" name="action" value="import" />
	<input type="file" name="upload_file" size="60" />
	<input type="submit" name="submit" value="<?php echo $AppUI->_("Import Data"); ?>" class="button" />
</form>