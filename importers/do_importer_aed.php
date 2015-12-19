<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}

$action = w2PgetParam($_POST, 'action', '');

switch($action) {
	case 'import':
        if ($_FILES['upload_file']['size'] == 0) {
			$AppUI->setMsg('You must select a file to upload', UI_MSG_ERROR, true);
			$AppUI->redirect('m=importers');
		}

		$fileext = substr($_FILES['upload_file']['name'], -4);
		$importer = CImporter::resolveFiletype($fileext, $AppUI);

		if (is_a($importer, 'stdClass')) {
			$AppUI->setMsg('Failure: '.$AppUI->_($importer->error), UI_MSG_ERROR, true);
			$AppUI->redirect('m=importers');
		}
		$importer->loadFile($AppUI, $_FILES);

		$AppUI->holdObject($importer);
		$AppUI->redirect('m=importers&a=view');
		break;
	case 'save':
        $importer = CImporter::resolveFiletype($_POST['filetype'], $AppUI);

//TODO: this branch still has to be cleaned up
        $importer->import($AppUI, $_POST);

        if (isset($error)) {
//TODO: how does this error get set?
			$AppUI->setMsg('Failure: '.$error, UI_MSG_ERROR, true);
        } else {
            if ($importer->project_id) {
                $AppUI->setMsg('Success', UI_MSG_OK);
				$AppUI->redirect('m=projects&a=view&project_id='.$importer->project_id);
            }
        }
		$AppUI->redirect('m=importers&a=addedit');
		break;
	case 'cancel':
		$AppUI->setMsg('Import cancelled.', UI_MSG_ALERT, true);
		$AppUI->redirect('m=importers');
		break;
	default:
		$AppUI->redirect('m=importers&a=addedit');
}