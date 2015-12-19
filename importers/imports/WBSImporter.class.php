<?php
if (!defined('W2P_BASE_DIR')){
	die('You should not access this file directly.');
}

/* Added by Wellison da Rocha Pereira, credited in license.txt */
//TODO: there needs to be a naming convention here for the autoloader
class WBSImporter extends CImporter {
	protected $depthArray = array();
	protected $lastLevel = 0;
	protected $maxLevel = 0;

	/*
	 * The $fields array here is actually the $_POST. I switched it to fields
	 *   so we could test it more easily.
	 *   dkc - 15 April 2011
	 */
    public function import(w2p_Core_CAppUI $AppUI, array $fields) {

		parent::_import($this->AppUI, $fields);

        $q = new w2p_Database_Query();
        // Users Setup
        if (isset($fields['users']) && is_array($fields['users']) && $fields['nouserimport'] != "true") {
            foreach($fields['users'] as $ruid => $r) {
                $q->clear();

                if (!empty($r['user_username'])) {
                    $contact_id = (int) $this->_processContact($this->AppUI, $r['user_username'], $this->company_id);
                    if ($contact_id) {
//TODO:  Replace with the regular create users functionality
						$q->addInsert('user_username', $r['user_username']);
						$q->addInsert('user_contact', $contact_id);
						$q->addTable('users');
						$q->exec();
						$insert_id = db_insert_id();

						$r['user_id'] = $insert_id;
                    } else {
//TODO:  This error message doesn't make it through..
						$this->AppUI->setMsg($result, UI_MSG_ERROR);
					}
                } else {
                    $r['user_id'] = $r['user_userselect'];
                }
                if (!empty($r['user_id'])) {
                    $resources[$ruid] = $r;
                }
            }
        }

        // Tasks Setup
        foreach ($fields['tasks'] as $k => $task) {
            $result = $this->_processTask($this->AppUI, $this->project_id, $task);
            if (is_array($result)) {
                $this->AppUI->setMsg($result, UI_MSG_ERROR);
                $this->AppUI->redirect('m=importers');
            }
            $task_id = $result;

            // Task Parenthood
            $outline[$task['OUTLINENUMBER']] = $task_id;
            $q->clear();
//TODO:  Replace with the regular task parent handling
            if (!strpos($task['OUTLINENUMBER'], '.')) {
                $q->addUpdate('task_parent', $task_id);
                $q->addWhere('task_id = ' . $task_id);
                $q->addTable('tasks');
            } else {
                $parent_string = substr($task['OUTLINENUMBER'], 0, strrpos($task['OUTLINENUMBER'], '.'));
                $parent_outline = isset($outline[$parent_string]) ? $outline[$parent_string] : $task_id;
                $q->addUpdate('task_parent', $parent_outline);
                $q->addWhere('task_id = ' . $task_id);
                $q->addTable('tasks');
            }
            $q->exec();

            $task['task_id'] = $task_id;
			$tasks[$task['UID']] = $task;
//TODO:  Replace with the regular task assignment handling
            // Resources (Workers)
            if (count($task['resources']) > 0) {
				$resourceArray = array();
//TODO: figure out how to assign to existing users
				foreach($task['resources'] as $uk => $user) {
                    $alloc = $task['resources_alloc'][$uk];
					$q->clear();
                    if ($alloc > 0 && $resources[$user]['user_id'] > 0) {
						$user_id = $resources[$user]['user_id'];
						if (!in_array($user_id, $resourceArray)) {
							$q->addInsert('user_id', $user_id);
							$q->addInsert('task_id', $task_id);
							$q->addInsert('perc_assignment', $alloc);
							$q->addTable('user_tasks');
							$q->exec();
						}
						$resourceArray[] = $resources[$user]['user_id'];
                    }
					if ((int) $user) {
						$q->addInsert('user_id', $user);
						$q->addInsert('task_id', $task_id);
						$q->addInsert('perc_assignment', $alloc);
						$q->addTable('user_tasks');
						$q->exec();
					}
                }
            }

            // Task Dependencies
            if (is_array($task['dependencies'])) {
                foreach($task['dependencies'] as $task_uid) {
                    if ($task_uid > 0 && $tasks[$task_uid]['task_id'] > 0) {
                        $q->clear();
                        $q->addInsert('dependencies_task_id', $task_id);
                        $q->addInsert('dependencies_req_task_id', $tasks[$task_uid]['task_id']);
                        $q->addTable('task_dependencies');
                        $q->exec();
                    }
                }
            }
        }
        $this->_deDynamicLeafNodes($this->project_id);
        addHistory('projects', $this->project_id, 'add', $projectName, $this->project_id);
        return true;
    }

    public function view(w2p_Core_CAppUI $AppUI) {
        /*
		 * The whole view is based on the SimpleXML. It was a easier decision 
		 *   to get atributes from the wbs files and it actually worked! I 
		 *   tried XMLReader but i've failed completely.
		 *
		 */
        $perms = $this->AppUI->acl();

        $output = '';
        $data = $this->scrubbedData;

        $reader = simplexml_load_string($data);

        $project_name = $reader->proj->summary['Title'];
        if (empty($project_name)) {
            $project_name=$this->proName;
		}

        $output .= '
			<table width="100%">
			<tr>
			<td align="right">' . $this->AppUI->_('Company Name') . ':</td>';

		$projectClass = new CProject();
		$output .= $this->_createCompanySelection($this->AppUI, $tree['COMPANY']);
		$output .= $this->_createProjectSelection($this->AppUI, $project_name);

		$users = $perms->getPermittedUsers('projects');
		$output .= '<tr><td align="right">' . $this->AppUI->_('Project Owner') . ':</td><td>';
		$output .= arraySelect( $users, 'project_owner', 'size="1" style="width:200px;" class="text"', $this->AppUI->user_id );
		$output .= '<td/></tr>';

		$pstatus =  w2PgetSysVal( 'ProjectStatus' );
		$output .= '<tr><td align="right">' . $this->AppUI->_('Project Status') . ':</td><td>';
		$output .= arraySelect( $pstatus, 'project_status', 'size="1" class="text"', $row->project_status, true );
		$output .= '<td/></tr>';

		$startDate = $this->_formatDate($this->AppUI, $reader->proj->summary['Start']);
		$endDate = $this->_formatDate($this->AppUI, $reader->proj->summary['Finish']);

		$output .= '
            <tr>
                <td align="right">' . $this->AppUI->_('Start Date') . ':</td>
                <td>
					<input type="hidden" name="project_start_date" value="'.$startDate.'" class="text" />
					<input type="text" name="start_date" value="'.$reader->proj->summary['Start'].'" class="text" />
				</td>
            </tr>
            <tr>
                <td align="right">' . $this->AppUI->_('End Date') . ':</td>
                <td>
					<input type="hidden" name="project_end_date" value="'.$endDate.'" class="text" />
					<input type="text" name="end_date" value="'.$reader->proj->summary['Finish'].'" class="text" />
				</td>
            </tr><!--
            <tr>
                <td align="right">' . $this->AppUI->_('Do Not Import Users') . ':</td>
                <td><input type="checkbox" name="nouserimport" value="true" onclick="ToggleUserFields()" /></td>
            </tr>
            <tr>
                <td colspan="2">' . $this->AppUI->_('Users') . ':</td>
            </tr>
            <tr>
                <td colspan="2"><div name="userRelated"><br /><em>'.$this->AppUI->_('userinfo').'</em></td>
			</tr>-->
			<tr>
				<td colspan="2"><table>';

        $percent = array(0 => '0', 5 => '5', 10 => '10', 15 => '15', 20 => '20', 25 => '25', 30 => '30', 35 => '35', 40 => '40', 45 => '45', 50 => '50', 55 => '55', 60 => '60', 65 => '65', 70 => '70', 75 => '75', 80 => '80', 85 => '85', 90 => '90', 95 => '95', 100 => '100');

		// Users (Resources)
		$workers = $perms->getPermittedUsers('tasks');
        $resources = array(0 => '');

		$q = new w2p_Database_Query();
        if ($this->user_control) {		//check the existence of resources before trying to import
            $trabalhadores=$reader->proj->resources->children();
            foreach($trabalhadores as $r) {
                $q->clear();
                $q->addQuery('user_id');
                $q->addTable('users');
                $q->leftJoin('contacts', 'c', 'user_contact = contact_id');
                $q->addWhere("user_username LIKE '{$r['name']}' OR CONCAT_WS(contact_first_name, ' ', contact_last_name) = '{$r['name']}'");
                $r['LID'] = $q->loadResult();
                if (!empty($r['name'])) {
                    $output .= '
						<tr>
						<td>' . $this->AppUI->_('User name') . ': </td>
						<td>
						<input type="text" name="users[' . $r['uid'] . '][user_username]" value="' . ucwords(strtolower($r['name'])) . '"' . (empty($r['LID'])?'':' readonly') . ' />
						<input type="hidden" name="users[' . $r['uid'] . '][user_id]" value="' . $r['LID'] . '" />
						(' . $this->AppUI->_('Resource UID').": ".$r['uid'] . ')';

                    if (function_exists('w2PUTF8strlen')) {
                        if (w2PUTF8strlen($r['name']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $this->AppUI->_('username_min_len.') . '</em>';
                        }
                    } else {
                        if (strlen($r['name']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $this->AppUI->_('username_min_len.') . '</em>';
                        }
                    }
                    $output .= '</td></tr>';
                    $resources[sizeof($resources)] = strtolower($r['name']);
                }
            }
        }
		$resources = arrayMerge($resources, $workers);

        $output .= '
            </table>
            </div></td></tr>';

		// Insert Tasks
        $output .= '
            <tr>
            <td colspan="2">' . $this->AppUI->_('Tasks') . ':</td>
            </tr>
            <tr>
            <td colspan="2">
            <table width="100%" class="tbl" cellspacing="1" cellpadding="2" border="0">
            <tr>
            <th>' . $this->AppUI->_('Name') . '</th>
            <th>' . $this->AppUI->_('Start Date') . '</th>
            <th>' . $this->AppUI->_('End Date') . '</th>
            <th>' . $this->AppUI->_('Assigned Users') . '</th>
            </tr>';

        foreach($reader->proj->tasks->children() as $task) {
            if (trim($task['Name']) != '') {
                $k = $task['ID'];
				$newWBS = $this->montar_wbs($task['OutlineLevel']);
                $note= htmlentities($task['NOTES']);
                $output .= '<tr><td>';
                $output .= '<input type="hidden" name="tasks['.$k.'][UID]" value="' . $k . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][OUTLINENUMBER]" value="' . $newWBS . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_name]" value="' . $task['Name'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_description]" value="' . $note . '" />';

                $priority = 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_priority]" value="' . $priority . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_start_date]" value="' . $task['Start'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_end_date]" value="' . $task['Finish'] . '" />';

                $myDuration = $this->dur($task['Duration']);
				$output .= '<input type="hidden" name="tasks['.$k.'][task_duration]" value="' . $myDuration . '" />';
				$output .= '<input type="hidden" name="tasks['.$k.'][task_duration_type]" value="1" />';

                $percentComplete = isset($task['PercentComplete']) ? $task['PercentComplete'] : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_percent_complete]" value="' . $percentComplete . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_owner]" value="'.$this->AppUI->user_id.'" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_type]" value="0" />';

                $milestone = ($task['Milestone'] == 'yes') ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_milestone]" value="' . $milestone . '" />';

                $temp = 0;
                if (!empty($task['UniqueIDPredecessors'])) {
                    $x=strpos($task['UniqueIDPredecessors'],",");
                    foreach ($task['UniqueIDPredecessors'] as $dependency) {
                        $output .= '<input type="hidden" name="tasks['.$k.'][dependencies][]" value="' . $dependency['UniqueIDPredecessors'] . '" />';
                        ++$temp;
                    }
                }

                $tasklevel = (int) $task['OutlineLevel'];
				if ($tasklevel) {
					for($i = 0; $i < $tasklevel; $i++) {
						$output .= '&nbsp;&nbsp;';
					}
					$output .= '<img src="' . w2PfindImage('corner-dots.gif') . '" border="0" />&nbsp;';
				}

				$output .= $task['Name'];

                if ($milestone) {
                    $output .= '<img src="' . w2PfindImage('icons/milestone.gif', $m) . '" border="0" />';
                }
//TODO: the formatting for the dates should be better
                $output .= '</td>
							<td class="center">'.$task['Start'].'</td>
							<td class="center">'.$task['Finish'].'</td>
							<td class="center">';

                //This is a bizarre function i've made to associate the resources with their tasks
                //If there's only one resource associate to a task, it skip the while. If there's more than one
                //the loop take care of it until the last resource
                //strange but it works, that's my moto.
                if (!empty($task['Resources'])) {
                    $x=0;
                    $y=strpos($task['Resources'],';');
                    while (!empty($y)) {
                        $recurso=substr($task['Resources'],$x,($y-$x));
						$output .= '<div name="userRelated">';
                        $output.= arraySelect($resources, 'tasks['.$k.'][resources][]', 'size="1" class="text"', $recurso);
						$output .= '&nbsp;';
						$output .= arraySelect($percent, 'tasks['.$k.'][resources_alloc][]', 'size="1" class="text"', 100) . '%';
						$output .= '</div>';
                        $x=$y+1;
                        $y=strpos($task['Resources'],';',$x);
                    }
                } else {
					$output .= '<div name="userRelated">';
					$output.= arraySelect($resources, 'tasks['.$k.'][resources][]', 'size="1" class="text"', $recurso);
					$output .= '&nbsp;';
					$output .= arraySelect($percent, 'tasks['.$k.'][resources_alloc][]', 'size="1" class="text"', 100) . '%';
					$output .= '</div>';
				}
                $output .= '</td></tr>';
            }
        }
        $output .= '</table></td></tr>';

        $output .= '</table>';
        return $output;
    }

    public function loadFile($AppUI) {
        $filename = $_FILES['upload_file']['tmp_name'];

        $file = fopen($filename, "r");
        $filedata = fread($file, $_FILES['upload_file']['size']);
        fclose($file);

        if (substr_count($filedata, '<tasks>') < 1) {
            return false;
        }
        $x = strpos($filedata, '<calendar>');
        $header = substr($filedata, 0, $x);
        $summaryNode = $this->stripper("<summary ","/>",$filedata);
        $taskNodes = $this->stripper("<tasks>","</tasks>",$filedata);
        $endNodes = "</proj></project>";

        if (substr_count($filedata, '<resources>') < 1) {
            echo "<b>".$this->AppUI->_("Failure")."</b> ".$this->AppUI->_("impinfo")."<BR>";
            $filedata=$header.$summaryNode.$taskNodes.$endNodes;
        } else {
            $userNodes=$this->stripper("<resources>","</resources>",$filedata);
            $filedata=$header.$summaryNode.$userNodes.$taskNodes.$endNodes;
        }
        /*
         * O resultado esperado Ã© esse:
         * <project>
         * 		<proj attributes....>
         * 			<summary ...../>
         * 			<resources>
         * 				<resource id="0"......./>
         * 				...
         * 				<resource id="X"......./>
         * 			</resources>
         * 			<tasks>
         * 				<task id="0"......./>
         * 				...
         * 				<task id="X"......./>
         * 			</tasks>
         * 		</proj>
         * </project>
         */

        $this->scrubbedData = $filedata;
        return true;
    }
    /* Extrai uma determinada tag xml de uma string que com
     * conteúdo de um arquivo
     * @param    string    $startTag Tag de inicio
     *          string    $endTag    Tag de final
     *             string    $data Escopo onde vai ser procurado a tag
    */
    private function stripper($startTag,$endTag,$data) {
        $x=strpos($data, $startTag);
        $y=strpos($data, $endTag,$x)+strlen($endTag);
        $data = substr($data, $x, ($y-$x));
        return $data;
    }
    private function dur($duration) {
        //Not a very good duration function, just take the number of days and multiplies it for 8
        $Offset = strpos($duration, 'd');
        $x = substr($duration, 0, $Offset);
        return ($x*8);
    }

	/*
	 * This nasty little function builds the WBS id structure for import.
	 */
    private function montar_wbs($current_level) {
		$current_level = (int) $current_level;

		if ($this->lastLevel == $current_level) {
			$this->depthArray['l-'.$current_level]++;
		}

		if ($current_level > $this->lastLevel) {
			$this->depthArray['l-'.$current_level]++;
		} elseif ($current_level < $this->lastLevel) {
			$this->depthArray['l-'.$current_level]++;
			for($i = $current_level+1; $i <= $this->maxLevel; $i++) {
				unset($this->depthArray['l-'.$i]);
			}
		}

		$this->maxLevel = ($current_level > $this->maxLevel) ? $current_level : $this->maxLevel;
		$this->lastLevel = (int) $current_level;
		$wbs = implode($this->depthArray, '.');

		return $wbs;
    }
}