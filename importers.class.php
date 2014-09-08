<?php
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}

class CImporter
{
    protected $AppUI = null;
    protected $company_id = 0;

    public $fileType = '';
    public $importType='';
    public $project_id = 0;
	public $notices = array();

    protected $scrubbedData = '';
    protected $importClassname = '';
    protected $proName='';
    protected $user_control='';

    public function __construct(w2p_Core_CAppUI $AppUI = null)
    {
        $this->AppUI = $AppUI;
    }

    public static function resolveFiletype($filetype, $AppUI) {
//TODO: rename these importer classes so we can count on our autoloader instead of explicit includes
        include_once 'imports/MSProjectImporter.class.php';
        /* Added by Wellison da Rocha Pereira, credited in license.txt */
        include_once 'imports/WBSImporter.class.php';

        switch($filetype) {
            case '.wbs':
                $importer = new WBSImporter($AppUI);
                $importer->fileType = '.wbs';
                break;
            case '.xml':
                $importer = new MSProjectImporter($AppUI);
                $importer->fileType = '.xml';
				break;
            default:
				$importer = new stdClass();
				$importer->error = 'This file type is not supported';
        }
        return $importer;
    }

	/*
	 * This method feels really hacky but I can't come up with a better solution
	 *   to both load the file and redirect. I've tried redirecting and then
	 *   loading the file but it's already gone.
	 * ~ caseydk 10 April 2011
	 */
	public static function castToClass($object) {
        include_once 'imports/MSProjectImporter.class.php';
        /* Added by Wellison da Rocha Pereira, credited in license.txt */
        include_once 'imports/WBSImporter.class.php';

		foreach ($object as $key => $value) {
			if ('__PHP_Incomplete_Class_Name' == $key) {
				$class = $value;
				break;
			}
		}
		return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($object)));
	}

	/*
	 * The individual subclasses should extend this function to handle the
	 *   custom bits of the import process.
	 */
	protected function _import(w2p_Core_CAppUI $AppUI, array $fields)
    {
        $this->company_id = (int) w2PgetParam($fields, 'company_id', $AppUI->user_company);

        if (!$fields['company_id'] && '' != $fields['company_name']) {
            $company = new CCompany();
            $company->bind($fields);
            $company->store();
            $this->company_id = $company->company_id;
        }

        $project = $this->_processProject($AppUI, $this->company_id, $fields);

        if (!$project->project_id) {
            $this->AppUI->setMsg($project->getError(), UI_MSG_ERROR);
            $this->AppUI->redirect('m=importers');
        }
        $this->project_id = $project->project_id;
	}

    protected function _createCompanySelection(w2p_Core_CAppUI $AppUI, $companyInput) {
        $company = new CCompany();
        $companyMatches = $company->getCompanyList($this->AppUI, -1, $companyInput);
        $company_id = (count($companyMatches) == 1) ? $companyMatches[0]['company_id'] : $this->AppUI->user_company;
        $companies = $company->getAllowedRecords($this->AppUI->user_id, 'company_id,company_name', 'company_name');
        $companies = arrayMerge(array('0' => $this->AppUI->_('Add New Company')), $companies);

        $output .= '<td>' .
            arraySelect($companies, 'company_id', 'class="text" size="1" onChange=this.form.company_name.value=\'\'', $company_id) .
            '<input type="text" name="company_name" value="' . (($company_id > 0) ? '' : $companyInput) . '" class="text" />';
        if ($company_id == 0) {
            $output .= '<br /><em>'.$this->AppUI->_('compinfo').'</em>';
        }
        $output .= '</td></tr>';

        return $output;
    }

    protected function _createProjectSelection(w2p_Core_CAppUI $AppUI, $project_name) {
        $output .= '<tr><td align="right">' . $this->AppUI->_('Project Name') . ':</td>';
        $q = new w2p_Database_Query();
        $q->addQuery('project_id');
        $q->addTable('projects');
        $q->addWhere("project_name = '{$project_name}'");
        $project_id = $q->loadResult();

        $output .= '<td>';
        $output .= '<input type="text" name="project_name" value="' . $project_name . '" size="36" class="text" />';
        if ($project_id) {
            $output .= '<input type="hidden" name="project_id" value="' . $project_id . '" />';
            $output .= $this->AppUI->_('pexist') ;
        }
        $output .= '</td></tr>';

        return $output;
    }

    protected function _deDynamicLeafNodes($projectId) {
        $q = new w2p_Database_Query();
        $q->addUpdate('task_dynamic', 31);
        $q->addWhere("task_project = $projectId");
        $q->addTable('tasks');
        $q->exec();
        
        $q->addQuery('distinct(task_parent)');
        $q->addTable('tasks');
        $q->addWhere("task_project = $projectId");
        $q->addWhere("task_id <> task_parent");
        $taskList = $q->loadHashList();

        foreach($taskList as $id => $nothing){
            $dynamicTasks .= $id.',';
        }
        $dynamicTasks .= '0';
        $q->clear();
        $q->addUpdate('task_dynamic', 1);
        $q->addWhere("task_project = $projectId");
        $q->addWhere("task_id IN ($dynamicTasks)");
        $q->addTable('tasks');
        $q->exec();
    }

    protected function _processContact(w2p_Core_CAppUI $AppUI, $username, $company_id) {
        $space = strrpos($username, ' ');
        if ($space === false) {
            $first_name = $username;
            $last_name = ' ';
        } else {
            $first_name = substr($username, 0, $space);
            $last_name = substr($username, $space + 1);
        }
        $q = new w2p_Database_Query();
		$q->addTable('contacts');
		$q->addQuery('contact_id');
        $q->addWhere("contact_first_name = '$first_name'");
		$q->addWhere("contact_last_name  = '$last_name'");
		$contact_id = $q->loadResult();

		if (!$contact_id) {
			$contact = new CContact;
			$contact->contact_first_name = ucwords($first_name);
			$contact->contact_last_name = ucwords($last_name);
			$contact->contact_display_name = $contact->contact_first_name.' '.$contact->contact_last_name;
			$contact->contact_order_by = $username;
			$contact->contact_owner = $this->AppUI->user_id;
			$contact->contact_company = $company_id;
			$result = $contact->store($this->AppUI);
			$contact_id = $contact->contact_id;
		}

        return $contact_id;
    }

    protected function _processTask(w2p_Core_CAppUI $AppUI, $project_id, $task) {
        $myTask = new CTask;
        $myTask->task_name = w2PgetCleanParam($task, 'task_name', null);
        $myTask->task_project = $project_id;
        $myTask->task_description = w2PgetCleanParam($task, 'task_description', '');
        $myTask->task_start_date = $this->AppUI->convertToSystemTZ($task['task_start_date']);
		$myTask->task_end_date = $this->AppUI->convertToSystemTZ($task['task_end_date']);
        $myTask->task_duration = $task['task_duration'];
        $myTask->task_milestone = (int) $task['task_milestone'];
        $myTask->task_owner = (int) $task['task_owner'];
        // All tasks are marked with dependency tracking = "on" in _deDynamicLeafNodes
        $myTask->task_priority = (int) $task['task_priority'];
        $myTask->task_percent_complete = $task['task_percent_complete'];
        $myTask->task_duration_type = $task['task_duration_type'];
        $result = $myTask->store($this->AppUI);

        return (is_array($result)) ? $result : $myTask->task_id;
    }

    protected function _processProject(w2p_Core_CAppUI $AppUI, $company_id, $projectInfo)
    {
        unset($projectInfo['project_id']);
        $project = new CProject;
        $project->bind($projectInfo);
        $project->project_company = $this->company_id;
        $project->project_active = 1;
        $project->project_color_identifier = 'FFFFFF';
        $project->store();

        return $project;
    }

	protected function _formatDate($notUsed, $dateString) {
		$dateString = str_replace('-', '', $dateString);
		$dateString = str_replace(':', '', $dateString);
		$dateString = str_replace('T', '', $dateString);

		return $dateString;
	}
}