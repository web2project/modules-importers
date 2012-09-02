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

if ($myMemLimit < 256) {
    ini_set('memory_limit', '256M');
    ini_set('post_max_size', '256M');
    ini_set('upload_max_filesize', '256M');
}