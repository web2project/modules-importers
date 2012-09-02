<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}

$canAuthor = canAdd('projects');

if (!$canAuthor) {
  $AppUI->redirect("m=public&a=access_denied");
}

$titleBlock = new CTitleBlock($AppUI->_('Project Importer'), 'projectimporter.png', $m, "$m.$a");
$titleBlock->show();

//TODO: This message should be placed somewhere better without using echo.
echo $AppUI->_('msinfo');

$tabBox = new CTabBox("?m=$m", W2P_BASE_DIR . "/modules/$m/", 0);
$tabBox->add('addedit', $AppUI->_('Import'));
$tabBox->show();