<?php
function vmailadmin_get_title($action, $context="", $account="") {
	$title = "<h3>" . _("Voicemail Administration") . "<br />&nbsp;&nbsp;";
	switch ($action) {
		case "tz":
			$title .= _("Timezone Definitions");
			break;
		case "bsettings":
			if (!empty($account)) {
				$title .= _("Basic Settings For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("Basic settings view is for individual accounts.");
			}
			break;
		case "settings":
			if (!empty($account)) {
				$title .= _("Advanced Settings For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("System Settings");
			}
			break;
		case "usage":
			if (!empty($account)) {
				$title .= _("Usage Statistics For: ") . "&nbsp;&nbsp;&nbsp;$account&nbsp;&nbsp;&nbsp;($context)";
			} else {
				$title .= _("System Usage Statistics");
			}
			break;
		default:
			$title .= "&nbsp;&nbsp;" . _("Invalid Action");
			break;
	}
	$title .= "</h3>";
	return $title;
}
function vmailadmin_get_scope($extension) {
	if (!empty($extension)) {
		return "account";
	} else {
		return "system";
	}
}
function vmailadmin_update_settings($action, $context="", $extension="", $args=null) {
	global $astman;
	global $tz_settings;
	global $gen_settings;
	/* Ensure we get the most up-to-date voicemail.conf data. */
	$vmconf = voicemail_getVoicemail();
	if ($vmconf !== null) {
		switch ($action) {
			case "tz":
				/* First update all zonemessages opts that are already in vmconf */
				foreach ($vmconf["zonemessages"] as $key => $val) {
					$id = "tz__$key";
					$vmconf["zonemessages"][$key]	= isset($args[$id])?$args[$id]:$vmconf["zonemessages"][$key];
					/* Bad to have empty fields in vmconf. */
					/* And remove deleted fields, too.     */
					if (empty($vmconf["zonemessages"][$key]) || ($args["tzdel__$key"] == "true")) {
						unset($vmconf["zonemessages"][$key]);
					}
					/* Add new field, if one was specified */
					if (!empty($args["tznew_name"]) && !empty($args["tznew_def"])) {
						$vmconf["zonemessages"][$args["tznew_name"]] = $args["tznew_def"];
					}
					unset($args[$id]);
				}
				/* Next record any new zonemessages opts that were on the page but not already in vmconf. */
				foreach ($tz_settings as $key) {
					$id = "tz__$key";
					if (isset($args[$id]) && !empty($args[$id])) {
						$vmconf["zonemessages"][$key] = $args[$id];
					}
				}
				break;
			case "settings":
				if (empty($extension) && $action == "settings") {
					/* First update all general opts that are already in vmconf */
					foreach ($vmconf["general"] as $key => $val) {
						$id = "gen__$key";
						$vmconf["general"][$key] = isset($args[$id])?$args[$id]:$vmconf["general"][$key];
						/* Bad to have empty fields in vmconf. */
						if (empty($vmconf["general"][$key])) {
							unset($vmconf["general"][$key]);
						}
						unset($args[$id]);
					}
					/* Next record any new general opts that were on the page but not already in vmconf. */
					foreach ($gen_settings as $key => $descrip) {
						$id = "gen__$key";
						if (isset($args[$id]) && !empty($args[$id])) {
							$vmconf["general"][$key] = $args[$id];
						}
					}
				} else if (!empty($extension)) {
					global $acct_settings;			/* We need this to know the type for each option (text value or flag) */
					/* Delete user's old settings. */
					voicemail_mailbox_del($extension);
					/* Prepare values for user's new settings. 			  */
					/* Each voicemail account has a line in voicemail.conf like this: */
					/* extension => password,name,email,pager,options		  */
					/* Take care of password, name, email and pager.                  */
					$pwd = isset($args["acct__pwd"])?$args["acct__pwd"]:"";
					unset($args["acct__pwd"]);
					if (isset($args["acct__name"]) && $args["acct__name"] != "") {
						$name = $args["acct__name"];
					} else {
						$this_exten = core_users_get($extension);
						$name = $this_exten["name"];
					}
					unset($args["acct__name"]);
					$email = isset($args["acct__email"])?$args["acct__email"]:"";
					unset($args["acct__email"]);
					$pager = isset($args["acct__pager"])?$args["acct__pager"]:"";
					unset($args["acct__pager"]);

					/* Now handle the options. */
					$options = array();
					foreach ($acct_settings as $key => $descrip) {
						$id = "acct__$key";
						if (isset($args[$id]) && !empty($args[$id]) && $args[$id] != "undefined") {
							$options[$key] = $args[$id];
						}
					}
					/* Remove call me num from options - that is set in ast db */
					unset($options["callmenum"]);
					/* New account values to vmconf */
					$vmconf[$context][$extension] = array(
										"mailbox"	=> $extension,
										"pwd" 		=> $pwd,
										"name" 		=> $name,
										"email" 	=> $email,
										"pager" 	=> $pager,
										"options" 	=> $options
									     );
					$callmenum = (isset($args["acct__callmenum"]) && !empty($args["acct__callmenum"]))?$args["acct__callmenum"]:$extension;
					// Save call me num.
					$cmd = "database put AMPUSER $extension/callmenum $callmenum";
					$astman->send_request("Command", array("Command" => $cmd));
				}
				break;
			case "bsettings":
				if (!empty($extension)) {
					/* Get user's old settings, since we are only replacing the basic settings. */
					$vmbox = voicemail_mailbox_get($extension);
					/* Delete user's old settings. */
					voicemail_mailbox_del($extension);

					/* Prepare values for user's new BASIC settings.		  */
					/* Each voicemail account has a line in voicemail.conf like this: */
					/* extension => password,name,email,pager,options		  */
					/* Take care of password, name, email and pager.                  */
					$pwd = isset($args["acct__pwd"])?$args["acct__pwd"]:"";
					unset($args["acct__pwd"]);
					if (isset($args["acct__name"]) && $args["acct__name"] != "") {
						$name = $args["acct__name"];
					} else {
						$this_exten = core_users_get($extension);
						$name = $this_exten["name"];
					}
					unset($args["acct__name"]);
					$email = isset($args["acct__email"])?$args["acct__email"]:"";
					unset($args["acct__email"]);
					$pager = isset($args["acct__pager"])?$args["acct__pager"]:"";
					unset($args["acct__pager"]);

					/* THESE ARE COMING FROM THE USER'S OLD SETTINGS.                     */
					$options = $vmbox["options"];	/* An array */
					/* Update the four options listed on the "bsettings" page as needed. */
					$basic_opts_list = array("attach", "saycid", "envelope", "delete");
					foreach ($basic_opts_list as $basic_opt) {
						$id = "acct__" . $basic_opt;
						if (isset($args[$id]) && !empty($args[$id]) && $args[$id] != "undefined") {
							$options[$basic_opt] = $args[$id];
						} else if ($args[$id] == "undefined") {
							unset($options[$basic_opt]);
						}
					}
					/* Remove call me num from options - that is set in ast db. Should not be here anyway, since options are coming from the old settings... */
					unset($options["callmenum"]);
					/* New account values to vmconf */
					$vmconf[$context][$extension] = array(
										"mailbox"	=> $extension,
										"pwd" 		=> $pwd,
										"name" 		=> $name,
										"email" 	=> $email,
										"pager" 	=> $pager,
										"options" 	=> $options
									     );
					$callmenum = (isset($args["acct__callmenum"]) && !empty($args["acct__callmenum"]))?$args["acct__callmenum"]:$extension;
					// Save call me num.
					$cmd = "database put AMPUSER $extension/callmenum $callmenum";
					$astman->send_request("Command", array("Command" => $cmd));
				}
				break;
			default:
				return false;
		}
		voicemail_saveVoicemail($vmconf);
		$astman->send_request("Command", array("Command" => "reload app_voicemail.so"));
		return true;
	}
	return false;
}

function vmailadmin_get_settings($vmconf, $action, $extension="") {
	$settings = array();
	switch ($action) {
		case "tz":
			if (is_array($vmconf) && is_array($vmconf["zonemessages"])) {
				foreach ($vmconf["zonemessages"] as $key => $val) {
					$settings[$key] = $val;
				}
			}
			break;
		case "bsettings":
		case "settings":
			/* Settings can apply to system-wide settings OR to account-specific settings. 		       */
			/* Specifying a context and extension indicates account-specific settings are being requested. */
			if (!empty($extension)) {
				$vmbox = voicemail_mailbox_get($extension);
				if ($vmbox !== null) {
					$settings["enabled"] = true;
				} else {
					$settings["enabled"] = false;
				}
				$settings["vmcontext"] = $c = isset($vmbox["vmcontext"])?$vmbox["vmcontext"]:"default";
				$settings["pwd"] = isset($vmbox["pwd"])?$vmbox["pwd"]:"";
				$settings["name"] = (isset($vmbox["name"]) && $vmbox["name"] != "")?$vmbox["name"]:"";
				if ($settings["name"] == "") {
					$this_exten = core_users_get($extension);
					$settings["name"] = $this_exten["name"];
				}
				$settings["email"] = isset($vmbox["email"])?$vmbox["email"]:"";
				$settings["pager"] = isset($vmbox["pager"])?$vmbox["pager"]:"";
				$options = isset($vmbox["options"])?$vmbox["options"]:array();
				foreach ($options as $key => $val) {
					$settings[$key] = $val;
				}

				/* Get Call Me number */
				global $astman;
				$cmd 		= "database get AMPUSER $extension/callmenum";
				$callmenum 	= "";
				$results 	= $astman->send_request("Command", array("Command" => $cmd));
				if (is_array($results))
				{
					foreach ($results as $results_elem)
					{
						if (preg_match('/Value: [^\s]*/', $results_elem, $matches) > 0)
						{
							$parts = split(' ', trim($matches[0]));
							$callmenum = $parts[1];
							break;
						}
					}
				}
				$settings["callmenum"] = $callmenum;
				/* End - Call Me number obtained */
			} else {
				if (is_array($vmconf) && is_array($vmconf["general"])) {
					$settings = $vmconf["general"];
				}
			}
			break;
		default:
			break;
	}
	return $settings;
}
function vmailadmin_update_usage($vmail_info, $context="", $extension="", $args) {
	global $vmail_root;
	$take_action = false;
	if (isset($args["del_msgs"]) && $args["del_msgs"] == "true") {
		$msg = true;
		$take_action = true;
	} else {
		$msg = false;
	}
	if (isset($args["del_names"]) && $args["del_names"] == "true") {
		$name = true;
		$take_action = true;
	} else {
		$name = false;
	}
	if (isset($args["del_unavail"]) && $args["del_unavail"] == "true") {
		$unavail = true;
		$take_action = true;
	} else {
		$unavail = false;
	}
	if (isset($args["del_busy"]) && $args["del_busy"] == "true") {
		$busy = true;
		$take_action = true;
	} else {
		$busy = false;
	}
	if (isset($args["del_temp"]) && $args["del_temp"] == "true") {
		$temp = true;
		$take_action = true;
	} else {
		$temp = false;
	}
	if (isset($args["del_abandoned"]) && $args["del_abandoned"] == "true") {
		$abandoned = true;
		$take_action = true;
	} else {
		$abandoned = false;
	}
	if (!$take_action) {
		return;
	}
	$vmail_path = $vmail_root;
	$scope = "system";
	if (!empty($extension) && !empty($context)) {
		$scope = "account";
	}

	switch ($scope) {
		case "system":
			if ($msg) {
				exec ("rm -f $vmail_root/*/*/*/msg*");
			}
			foreach ($vmail_info["contexts"] as $c) {
				vmailadmin_del_greeting_files($vmail_root, $c, "", $name, $unavail, $busy, $temp, $abandoned);
			}
			break;
		case "account":
			if (isset($vmail_info["activated_info"][$extension]) && $vmail_info["activated_info"][$extension] == $context) {
				$vmail_path = $vmail_root . "/" . $context . "/" . $extension;
				if ($msg) {
					exec ("rm -f $vmail_path/*/msg*");
				}
				vmailadmin_del_greeting_files($vmail_root, $context, $extension, $name, $unavail, $busy, $temp, $abandoned);
			}
			break;
	}
}
function vmailadmin_del_greeting_files($vmail_root, $context="", $exten="", $name=false, $unavail=false, $busy=false, $temp=false, $abandoned=false) {
	$path = $vmail_root;
	if (!empty($context) && !empty($exten)) {
		$path .= "/" . $context . "/" . $exten;
		$ab_name_cmd    = "ls $path/greet.tmp.*";
		$ab_temp_cmd    = "ls $path/temp.tmp.*";
		$ab_busy_cmd    = "ls $path/busy.tmp.*";
		$ab_unavail_cmd = "ls $path/unavail.tmp.*";
		$name_cmd       = "ls $path/greet.*";
		$unavail_cmd    = "ls $path/unavail.*";
		$busy_cmd       = "ls $path/busy.*";
		$temp_cmd       = "ls $path/temp.*";
	} else {
		$ab_name_cmd    = "ls $path/*/*/greet.tmp.*";
		$ab_temp_cmd    = "ls $path/*/*/temp.tmp.*";
		$ab_busy_cmd    = "ls $path/*/*/busy.tmp.*";
		$ab_unavail_cmd = "ls $path/*/*/unavail.tmp.*";
		$name_cmd       = "ls $path/*/*/greet.*";
		$unavail_cmd    = "ls $path/*/*/unavail.*";
		$busy_cmd       = "ls $path/*/*/busy.*";
		$temp_cmd       = "ls $path/*/*/temp.*";
	}
	
	if (is_dir($path)) {
		if ($abandoned) {
			/* First handle abandoned greetings.  Delete abandoned greetings that are at least a day old. */
			$ab_names   	= vmailadmin_get_ab_greetings("greet", $ab_name_cmd);
			$ab_temps    	= vmailadmin_get_ab_greetings("temp", $ab_temp_cmd);
			$ab_busys    	= vmailadmin_get_ab_greetings("busy", $ab_busy_cmd);
			$ab_unavails 	= vmailadmin_get_ab_greetings("unavail", $ab_unavail_cmd);
			$ab_greetings   = array_merge($ab_names, $ab_temps, $ab_busys, $ab_unavails);
			$current_time	= time();
			$one_day	= 24 * 60 * 60;
			foreach ($ab_greetings as $greeting_path) {
				if (time() - filemtime($greeting_path) > $one_day) {
					exec("rm -f $greeting_path");
				}
			}
		}
		if ($name) {
			$names 		= vmailadmin_get_greetings("greet", $name_cmd);
		}
		if ($unavail) {
			$unavails	= vmailadmin_get_greetings("unavail", $unavail_cmd);
		}
		if ($busy) {
			$busys 		= vmailadmin_get_greetings("busy", $busy_cmd);
		}
		if ($temp) {
			$temps		= vmailadmin_get_greetings("temp", $temp_cmd);
		}
		$greetings   = array_merge($names, $temps, $busys, $unavails);
		if (!empty($greetings)) {
			foreach ($greetings as $greeting_path) {
				exec ("rm -f $greeting_path");
			}
		}
	}
}
function vmailadmin_get_storage($path) {
	$cmd            = escapeshellcmd("du -khs $path");
	$storage_result = array();
	$matches        = array();
	exec($cmd, $storage_result);
	if (preg_match("/[0-9]*\.*[0-9]*[a-zA-Z]*/", $storage_result[0], $matches) > 0) {
		$storage = $matches[0];
		unset($matches);
		$matches = array();
		# Expecting storage value as #.#U where # = number, . = dot, and U = units (e.g. M, K, etc.)
		# Massage the string so that there is a space between the number value and character(s)
		# denoting the unit
		#
		# Extract the numeric part. /[0-9]*\.*[0-9]*[a-zA-Z]*/
		if (preg_match("/[0-9]*\.*[0-9]*/", $storage, $matches)) {
			$st_num = $matches[0];
		} else {
			$st_num = "0";
		}
		unset($matches);
		$matches = array();
		if (preg_match("/[a-zA-Z]+$/", $storage, $matches)) {
			$st_unit = $matches[0];
		} else {
			$st_unit = "";	
		}
		# reset $storage to new string
		$storage = $st_num . "&nbsp;" . $st_unit;
	} else {
		$storage = "unknown";
	}
	return $storage;
}
function vmailadmin_get_usage($vmail_info, $scope, &$acts_total, &$acts_act, &$acts_unact, &$disabled_count,
 	                                &$msg_total, &$msg_in, &$msg_other,
	                                &$name, &$unavail, &$busy, &$temp, &$abandoned,
				        &$storage,
					$context="", $extension="") {
	global $vmail_root;
	$msg_total = 0;
	$msg_in    = 0;
	$msg_other = 0;
	$name      = 0;
	$unavail   = 0;
	$busy      = 0;
	$temp      = 0;
	$abandoned = 0;
	switch ($scope) {
		case "system":
			$acts_act       = sizeof($vmail_info["activated_info"]);
			$acts_unact     = sizeof($vmail_info["unactivated_info"]);
			$disabled_count = sizeof($vmail_info["disabled_list"]);
			$acts_total = $acts_act + $acts_unact + $disabled_count;
			$storage    = vmailadmin_get_storage($vmail_root);
			foreach ($vmail_info["contexts"] as $c) {
				$count_msg_in  = 0;
				$count_msg_oth = 0;
				$count_name    = 0;
				$count_unavail = 0;
				$count_busy    = 0;
				$count_temp    = 0;
				$count_abandon = 0;
				$vmail_path = $vmail_root . "/" . $c;
				vmailadmin_file_usage($vmail_path, $count_msg_in, $count_msg_oth, $count_name, $count_unavail, $count_busy, $count_temp, $count_abandon);
				$msg_in    += $count_msg_in;
				$msg_other += $count_msg_oth;
				$name      += $count_name;
				$unavail   += $count_unavail;
				$busy      += $count_busy;
				$temp      += $count_temp;
				$abandoned += $count_abandon;
				
			}
			$msg_total = $msg_in + $msg_other;
			break;
		case "account":
			if (isset($vmail_info["activated_info"][$extension]) && $vmail_info["activated_info"][$extension] == $context) {
				$vmail_path = $vmail_root . "/" . $context . "/" . $extension;
				vmailadmin_file_usage($vmail_path, $msg_in, $msg_other, $name, $unavail, $busy, $temp, $abandoned, true);
				$storage    = vmailadmin_get_storage($vmail_path);
				$msg_total = $msg_in + $msg_other;
				$acts_act = 1;
				$acts_unact = 0;
			} else {
				$acts_unact = 1;
			}
			break;
		default:
			break;
	}
}
function vmailadmin_file_usage($path, &$inmsg_cnt, &$othmsg_cnt, &$greet_cnt, &$unavail_cnt, &$busy_cnt, &$temp_cnt, &$abandoned_cnt, $acct_flag=false) {
	if ($acct_flag) { /* account-specific; account included in path passed in */
		# greetings, all
		$greet_cmd	= "ls $path/greet.*";
		$unavail_cmd 	= "ls $path/unavail.*";
		$busy_cmd	= "ls $path/busy.*";
		$temp_cmd	= "ls $path/temp.*";
	
		# abandoned greetings
		$agreet_cmd	= "ls $path/greet.tmp.*";
		$aunavail_cmd	= "ls $path/unavail.tmp.*";
		$abusy_cmd	= "ls $path/busy.tmp.*";
		$atemp_cmd	= "ls $path/temp.tmp.*";

		# inbox messages
		$inmsg_cmd	= "ls $path/INBOX/msg*.txt";

		# all messages
		$allmsg_cmd	= "ls $path/*/msg*.txt";
	} else { /* system-wide */
		# greetings, all
		$greet_cmd	= "ls $path/*/greet.*";
		$unavail_cmd 	= "ls $path/*/unavail.*";
		$busy_cmd	= "ls $path/*/busy.*";
		$temp_cmd	= "ls $path/*/temp.*";
	
		# abandoned greetings
		$agreet_cmd	= "ls $path/*/greet.tmp.*";
		$aunavail_cmd	= "ls $path/*/unavail.tmp.*";
		$abusy_cmd	= "ls $path/*/busy.tmp.*";
		$atemp_cmd	= "ls $path/*/temp.tmp.*";

		# inbox messages
		$inmsg_cmd	= "ls $path/*/INBOX/msg*.txt";

		# all messages
		$allmsg_cmd	= "ls $path/*/*/msg*.txt";
	}

	if (is_dir($path)) {
		$greet_cnt   	= vmailadmin_count_greetings("greet", $greet_cmd);
		$temp_cnt    	= vmailadmin_count_greetings("temp", $temp_cmd);
		$busy_cnt    	= vmailadmin_count_greetings("busy", $busy_cmd);
		$unavail_cnt 	= vmailadmin_count_greetings("unavail", $unavail_cmd);


		$agreet_cnt 	= vmailadmin_count_ab_greetings("greet", $agreet_cmd);
		$aunavail_cnt 	= vmailadmin_count_ab_greetings("unavail", $aunavail_cmd);
		$abusy_cnt 	= vmailadmin_count_ab_greetings("busy", $abusy_cmd);
		$atemp_cnt 	= vmailadmin_count_ab_greetings("temp", $atemp_cmd);


		$inmsg_cnt 	= vmailadmin_count_msg($inmsg_cmd);
		$allmsg_cnt 	= vmailadmin_count_msg($allmsg_cmd);
		
		$othmsg_cnt 	= $allmsg_cnt - $inmsg_cnt;
		$abandoned_cnt 	= $agreet_cnt + $abusy_cnt + $atemp_cnt + $aunavail_cnt;
		
	}

}
function vmailadmin_strip_exten_from_greet_path($greet_path) {
	$path_array = explode("/", $greet_path);
	$n = sizeof($path_array);
	$exten = $path_array[$n-2];
	return $exten;
}
function vmailadmin_count_greetings($greeting, $cmd) {
	/* get a list of all greeting files */
	$file_list = vmailadmin_get_greetings($greeting, $cmd);
	$greet_list = array();
	/* greeting can be in multiple formats, making file count greater than greeting */
	/* count, so make array with one entry for each extension that has the greeting */
	foreach ($file_list as $greeting_file) {
		$greet_list[vmailadmin_strip_exten_from_greet_path($greeting_file)] = true;
	}
	return sizeof($greet_list);
}
function vmailadmin_get_greetings($greeting, $cmd) {
	$results = array();
	$greet_list = array();
	exec($cmd, $results);
	/* filter out abandoned greeting recordings */
	foreach ($results as $r) {
		$pat = "/.*" . $greeting . "\.tmp\..+/";
		if (!preg_match($pat, $r))
			$greet_list[] = $r;
	}
	return $greet_list;
}
function vmailadmin_count_ab_greetings($greeting, $cmd) {
	$file_list = vmailadmin_get_ab_greetings($greeting, $cmd);
	$greet_list = array();
	/* greeting can be in multiple formats, making file count greater than greeting */
	/* count, so make array with one entry for each extension that has the greeting */
	foreach ($file_list as $greeting_file) {
		$greet_list[vmailadmin_strip_exten_from_greet_path($greeting_file)] = true;
	}
	return sizeof($greet_list);
}
function vmailadmin_get_ab_greetings($greeting, $cmd) {
	$results = array();
	$greet_list = array();
	exec($cmd, $results);
	foreach ($results as $r) {
		$greet_list[] = $r;
	}
	return $greet_list;	
}
function vmailadmin_count_msg($msg_cmd) {
	$results = array();
	$msg_cnt = 0;
	exec($msg_cmd, $results);
	/* Message can be recorded in multiple formats, but there is always one text */
	/* file for each message, so count the text files. */
	foreach ($results as $r) {
		if (preg_match("/.+\/msg[0-9][0-9][0-9][0-9]\.txt\/{0,1}/", $r)) {
			$msg_cnt++;
		}
	}
	return $msg_cnt;
}
function vmailadmin_get_greeting_timestamps($name=0, $unavail=0, $busy=0, $temp=0, $context="", $extension="") {
	global $vmail_root;
	if ($context == "" || $extension == "") {
		return null;
	}
	$vmail_path = $vmail_root . "/$context/$extension";
	$ts["name"] = 0;
	$ts["unavail"] = 0;
	$ts["busy"] = 0;
	$ts["temp"] = 0;
	if ($name) {
		$listing = array();
		exec("ls $vmail_path/greet.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/greet\.tmp\..+/", $entry)) {
				$ts["name"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($unavail) {
		$listing = array();
		exec("ls $vmail_path/unavail.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/unavail\.tmp\..+/", $entry)) {
				$ts["unavail"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($busy) {
		$listing = array();
		exec("ls $vmail_path/busy.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/busy\.tmp\..+/", $entry)) {
				$ts["busy"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	if ($temp) {
		$listing = array();
		exec("ls $vmail_path/temp.*", $listing);
		foreach ($listing as $entry) {
			if (!preg_match("/temp\.tmp\..+/", $entry)) {
				$ts["temp"] = date("Y-m-d", filemtime("$entry"));
				break;
			}
		}
	}
	return $ts;
}
?>
