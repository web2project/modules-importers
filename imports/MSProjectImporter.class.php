<?php
if (!defined('W2P_BASE_DIR'))	{
  die('You should not access this file directly.');
}

include_once('xml.inc.php');
//TODO: there needs to be a naming convention here for the autoloader
class MSProjectImporter extends CImporter {



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
        }

        //dependencies have to be handled alone after all tasks have been saved since the
        //predecessor (ms project term) task might come later and the associated task id
        //is not yet available.
        foreach ($tasks as $k => $task) {
            // Task Dependencies
//TODO:  Replace with the regular dependency handling
            if (isset($task['dependencies']) && is_array($task['dependencies'])) {
                $q->clear();
                $q->setDelete('task_dependencies');
                $q->addWhere('dependencies_task_id = ' . (int) $task_id);
                $q->exec();
                $dependencyArray = array();

                foreach($task['dependencies'] as $task_uid) {
                    if ($task_uid > 0 && $tasks[$task_uid]['task_id'] > 0) {
                        $q->clear();

                        if (!in_array($tasks[$task_uid]['task_id'], $dependencyArray)) {
                            $q->addInsert('dependencies_task_id', $task['task_id']);
                            $q->addInsert('dependencies_req_task_id', $tasks[$task_uid]['task_id']);
                            $q->addTable('task_dependencies');
                            $q->exec();
                        }
                        $dependencyTestArray[] = $tasks[$task_uid]['task_id'];
                    }
                }
            }
        }
        $this->_deDynamicLeafNodes($this->project_id);
        addHistory('projects', $this->project_id, 'add', $projectName, $this->project_id);
        return true;
    }

    public function view(w2p_Core_CAppUI $AppUI) {
        $perms = $this->AppUI->acl();

        $output = '';
        $data = $this->scrubbedData;
        $data = str_replace('& ', '&amp; ', $data);

		$xml = simplexml_load_string($data);
        $tree = xmlParse($data);
        $i = ((int) $tree[0]['children'][0]['cdata']) ? 1 : 0;

        $project_name = $xml->Title;
        $tree = rebuildTree($tree);
        $tree = $tree['PROJECT'][0];

        $output .= '
            <table width="100%">
            <tr>
            <td align="right">' . $this->AppUI->_('Company Name') . ':</td>';


        $output .= $this->_createCompanySelection($this->AppUI, $xml->Company);
        $output .= $this->_createProjectSelection($this->AppUI, $project_name);

        $users = $perms->getPermittedUsers('projects');
        $output .= '<tr><td align="right">' . $this->AppUI->_('Project Owner') . ':</td><td>';
        $output .= arraySelect( $users, 'project_owner', 'size="1" style="width:200px;" class="text"', $this->AppUI->user_id );
        $output .= '<td/></tr>';

        $pstatus =  w2PgetSysVal( 'ProjectStatus' );
        $output .= '<tr><td align="right">' . $this->AppUI->_('Project Status') . ':</td><td>';
        $output .= arraySelect( $pstatus, 'project_status', 'size="1" class="text"', $row->project_status, true );
        $output .= '<td/></tr>';

		$startDate = $this->_formatDate($this->AppUI, $xml->StartDate);
		$endDate = $this->_formatDate($this->AppUI, $xml->FinishDate);

        $output .= '
            <tr>
                <td align="right">' . $this->AppUI->_('Start Date') . ':</td>
                <td>
					<input type="hidden" name="project_start_date" value="'.$startDate.'" class="text" />
					<input type="text" name="start_date" value="'.$xml->StartDate.'" class="text" />
				</td>
            </tr>
            <tr>
                <td align="right">' . $this->AppUI->_('End Date') . ':</td>
                <td>
					<input type="hidden" name="project_end_date" value="'.$endDate.'" class="text" />
					<input type="text" name="end_date" value="'.$xml->FinishDate.'" class="text" />
				</td>
            </tr>
            <tr>
                <td align="right">' . $this->AppUI->_('Do Not Import Users') . ':</td>
                <td><input type="checkbox" name="nouserimport" value="true" onclick="ToggleUserFields()" /></td>
            </tr>
            <tr>
                <td colspan="2">' . $this->AppUI->_('Users') . ':</td>
            </tr>
            <tr>
                <td colspan="2"><div name="userRelated"><br /><em>'.$this->AppUI->_('userinfo').'</em>
            <table>';

        $percent = array(0 => '0', 5 => '5', 10 => '10', 15 => '15', 20 => '20', 25 => '25', 30 => '30', 35 => '35', 40 => '40', 45 => '45', 50 => '50', 55 => '55', 60 => '60', 65 => '65', 70 => '70', 75 => '75', 80 => '80', 85 => '85', 90 => '90', 95 => '95', 100 => '100');

        // Users (Resources)
        $allUsers = w2PgetUsersList();
        foreach($allUsers as $userInfo) {
            $workers[$userInfo['user_id']] = $userInfo['contact_name'];
        }
        $resources = array(0 => '');

		$q = new w2p_Database_Query();
        foreach($tree['RESOURCES'][0]['RESOURCE'] as $r) {
            $q->clear();
            $q->addQuery('user_id');
            $q->addTable('users');
            $q->leftJoin('contacts', 'c', 'user_contact = contact_id');
            $myusername =  mysql_real_escape_string(strtolower($r['NAME']));
            $q->addWhere("LOWER(user_username) LIKE '{$myusername}' OR LOWER(CONCAT_WS(' ', contact_first_name, contact_last_name)) = '{$myusername}'");
            $r['LID'] = $q->loadResult();
			$r['UID'] = (int) $r['UID'];

            if (!empty($myusername)) {
                $output .= '
                    <tr>
                    <td>' . $this->AppUI->_('User name') . ': </td>
                    <td align="left">
                    <select name="users[r'.$r['UID'].'][user_userselect]" onChange="process_choice(this);" size="1" class="text">';

                if (empty($r['LID'])) {
                    $resources['r'.$r['UID']] = ucwords(strtolower($r['NAME']));
                    $output .= '
                    <option value="-1" selected>'.$this->AppUI->_('Add New').'</option>\n';
                }
                foreach ($workers as $user_id => $contact_name) {
                    if (!empty($r['LID']) && $user_id == $r['LID']) {
                        $resources[$r['UID']] = $contact_name;
                    }
                    $output .= '<option value="'.$user_id.'"'.(!empty($r['LID']) && $user_id == $r['LID']?"selected":"").'>'.$contact_name.'</option>\n';
                }
                $output .= '</select></td><td>';

                if (empty($r['LID'])) {
                    $output .= '<input class="text" type="text" name="users[r'.$r['UID'].'][user_username]" value="' . ucwords(strtolower($r['NAME'])) . '" onfocus="check_choice(this)" onChange="process_input(this);"/>';
                } else {
                    $output .= '&nbsp;';
                }
                $output .= '</td><td>(' . $this->AppUI->_('Resource') . ' UID r' . $r['UID'] . ')</td>';
                if (empty($r['LID'])) {
                    if (function_exists('w2PUTF8strlen')) {
                        if (w2PUTF8strlen($r['NAME']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $this->AppUI->_('username_min_len.') . '</em>';
                        }
                    } else {
                        if (strlen($r['NAME']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $this->AppUI->_('username_min_len.') . '</em>';
                        }
                    }
                }
                $output .= '</td></tr>';
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

        foreach($tree['TASKS'][0]['TASK'] as $k => $task) {
			if ($task['UID'] != 0 && trim($task['NAME']) != '') {

				$note= htmlentities($task['NOTES']);
				$output .= "\n".'<tr><td>';
                $output .= '<input type="hidden" name="tasks['.$k.'][UID]" value="' . $task['UID'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][OUTLINENUMBER]" value="' . $task['OUTLINENUMBER'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_name]" value="' . $task['NAME'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_description]" value="' . $task['NOTES'] . '" />';

                $priority = ($task['PRIORITY'] > 0) ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_priority]" value="' . $priority . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_start_date]" value="' . $task['START'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_end_date]" value="' . $task['FINISH'] . '" />';

				$myDuration = $this->_calculateWork($task['REGULARWORK'], $task['DURATION']);
				switch($task['DURATIONFORMAT']) {
					case '7':
						$myDurationType = 24;
						break;
					default:
						$myDurationType = 1;
				}
                $output .= '<input type="hidden" name="tasks['.$k.'][task_duration]" value="' . $myDuration . '" />';
				$output .= '<input type="hidden" name="tasks['.$k.'][task_duration_type]" value="' . $myDurationType . '" />';

				$percentComplete = isset($task['PERCENTCOMPLETE']) ? $task['PERCENTCOMPLETE'] : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_percent_complete]" value="' . $percentComplete . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_owner]" value="'.$this->AppUI->user_id.'" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_type]" value="0" />';

                $milestone = ($task['MILESTONE'] == '1') ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_milestone]" value="' . $milestone . '" />';
                if (is_array($task['PREDECESSORLINK'])) {
                    foreach ($task['PREDECESSORLINK'] as $dependency) {
                        $output .= '<input type="hidden" name="tasks['.$k.'][dependencies][]" value="' . $dependency['PREDECESSORUID'] . '" />';
                    }
                }

                $tasklevel = substr_count($task['OUTLINENUMBER'], '.');
				if ($tasklevel) {
					for($i = 0; $i < $tasklevel; $i++) {
						$output .= '&nbsp;&nbsp;';
					}
					$output .= '<img src="' . w2PfindImage('corner-dots.gif') . '" border="0" />&nbsp;';
				}

                $output .= $task['NAME'];

                if ($milestone) {
                    $output .= '<img src="' . w2PfindImage('icons/milestone.gif', $m) . '" border="0" />';
                }
//TODO: the formatting for the dates should be better
                $output .= '</td>
							<td class="center">'.$task['START'].'</td>
							<td class="center">'.$task['FINISH'].'</td>
							<td class="center">';

				if (count($tree['ASSIGNMENTS'][0]['ASSIGNMENT'])) {
					foreach($tree['ASSIGNMENTS'][0]['ASSIGNMENT'] as $a) {
						if ($a['TASKUID'] == $task['UID']) {
							if ($this->_calculateWork($task['REGULARWORK'], $task['DURATION']) > 0) {
								$perc = 100 * $a['UNITS'];
							}
							$output .= '<div name="userRelated">';
							$output .= arraySelect($resources, 'tasks['.$k.'][resources][]', 'size="1" class="text"', 'r'.$a['RESOURCEUID']);
							$output .= '&nbsp;';
							$output .= arraySelect($percent, 'tasks['.$k.'][resources_alloc][]', 'size="1" class="text"', intval(round($perc/5))*5) . '%';
							$output .= '</div>';
						}
					}
				} else {
					$output .= '';
				}
                $output .= '</td></tr>';
            }
        }
        $output .= '</table></td></tr>';

        $output .= '</table>';
        return $output;
    }

    public function loadFile($AppUI, $fileArray) {
		$filename = $fileArray['upload_file']['tmp_name'];

        $file = fopen($filename, "r");
        $this->scrubbedData = fread($file, $fileArray['upload_file']['size']);
        fclose($file);

        return true;
    }

    private function _calculateWork($regularWork, $regularDuration = '') {
        $hourOffset = strpos($regularWork, 'H', 0);
        $minOffset = strpos($regularWork, 'M', 0);
        $hours = substr($regularWork, 2, $hourOffset - 2);
        $minutes = substr($regularWork, $hourOffset + 1, $minOffset - $hourOffset - 1);
        $workHours = $hours + $minutes/60;

        if ($workHours == 0 && $regularDuration != '') {
            $workHours = $this->_calculateWork($regularDuration);
        }

        return round($workHours, 2);
    }
}