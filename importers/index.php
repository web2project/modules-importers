<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}

$canAuthor = canAdd('projects');

if (!$canAuthor) {
    $AppUI->redirect(ACCESS_DENIED);
}

$titleBlock = new w2p_Theme_TitleBlock('Project Importer', 'icon.png', $m, "$m.$a");
$titleBlock->show();

$tabBox = new CTabBox("?m=$m", W2P_BASE_DIR . "/modules/$m/", 0);
$tabBox->add('addedit', $AppUI->_('Import'));
$tabBox->show();