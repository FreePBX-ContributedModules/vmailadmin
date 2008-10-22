<div class ="rnav">
<?php
global $astman;
$extens = core_users_list();
$extension = $extdisplay = (isset($_REQUEST["extdisplay"]) && $_REQUEST["extdisplay"] != "")?$_REQUEST["extdisplay"]:$extens[0][0];
vmaildrawListMenu($extens, $type="setup", $dispnum="vmailadmin", $extdisplay, $description=false);
?>
</div>
<?php
// For the module:
$type = isset($_REQUEST["type"])?$_REQUEST["type"]:"";
$display = isset($_REQUEST["display"])?$_REQUEST["display"]:"";
$action = isset($_REQUEST["action"])?$_REQUEST["action"]:"";
// Was an update to a user's voicemail account performed? ($update_flag is true in such a case)
$update_flag = isset($_REQUEST["update_flag"])?$_REQUEST["update_flag"]:"false";

// Verify that voicemail module exists on the system.
if (function_exists("voicemail_getVoicemail") && function_exists("voicemail_saveVoicemail") && function_exists("voicemail_mailbox_get") && function_exists("voicemail_mailbox_add") && function_exists("voicemail_mailbox_del")) {
	$vm_exists = TRUE;
} else {
	$vm_exists = FALSE;
}
// If submitting form, update selected user's voicemail settings.
switch ($action) {
	case "changevmailsettings":
		// The voicemail functions have their own internal
		// checking.
		// If the voicemail box in question does not exist,
		// the functions simply return.  No harm done.
		//
		// We do NOT call voicemail_mailbox_remove - we do NOT want to delete
		// anything from the user's mailbox.
		if ($vm_exists) {
			$mbox = isset($_REQUEST["extension"])?$_REQUEST["extension"]:NULL;
			$vm = isset($_REQUEST["vm"])?$_REQUEST["vm"]:"disabled";
			// Delete the user's current voicemail settings.
			voicemail_mailbox_del($_REQUEST["extension"]);
			if ($vm != "disabled") {
				// Prepare all data for being saved into the voicemail configuration.
				// We do not use voicemail_mailbox_add, since that function also
				// tweaks the vmx settings.  This module only accesses the voicemail 
				// configuration stored in voicemail.conf.
				if ($mbox !== NULL) {
					$uservm = voicemail_getVoicemail();
					$vmpwd = isset($_REQUEST["vmpwd"])?$_REQUEST["vmpwd"]:"";
					//$name = (isset($_REQUEST["name"]) && $_REQUEST["name"] != "")?$_REQUEST["name"]:$this_exten[1];
					if (isset($_REQUEST["name"]) && $_REQUEST["name"] != "") {
						$name = $_REQUEST["name"];
					} else {
						$this_exten = core_users_get($extension);
						$name = $this_exten["name"];
					}
					$email = isset($_REQUEST["email"])?$_REQUEST["email"]:"";
					$pager = isset($_REQUEST["pager"])?$_REQUEST["pager"]:"";
					$options = isset($_REQUEST["options"])?$_REQUEST["options"]:"";
					if ($options!="") {
						$options = explode("|", $options);
						foreach ($options as $option) {
							$vmoption = explode("=", $option);
							$vmoptions[$vmoption[0]] = $vmoption[1];
						}
					}
					$attach = isset($_REQUEST["attach"])?$_REQUEST["attach"]:"";
					$saycid = isset($_REQUEST["saycid"])?$_REQUEST["saycid"]:"";
					$envelope = isset($_REQUEST["envelope"])?$_REQUEST["envelope"]:"";
					$delete = isset($_REQUEST["delete"])?$_REQUEST["delete"]:"";
					$vmoption = explode("=", $attach);
					$vmoptions[$vmoption[0]] = $vmoption[1];
					$vmoption = explode("=", $saycid);
					$vmoptions[$vmoption[0]] = $vmoption[1];
					$vmoption = explode("=", $envelope);
					$vmoptions[$vmoption[0]] = $vmoption[1];
					$vmoption = explode("=", $delete);
					$vmoptions[$vmoption[0]] = $vmoption[1];
					$vmcontext = (isset($_REQUEST["vmcontext"]) && ($_REQUEST["vmcontext"] != ""))?$_REQUEST["vmcontext"]:"default";
					$uservm[$vmcontext][$mbox] = array(
						"mailbox" => $mbox,
						"pwd" => $vmpwd,
						"name" => $name,
						"email" => $email,
						"pager" => $pager,
						"options" => $vmoptions
						);
					// Save the user's new voicemail settings.
					voicemail_saveVoicemail($uservm);
					$astman->send_request("Command", array("Command"=>"reload app_voicemail.so"));
				} else {
					$output_buf .= "INVALID MAILBOX NUMBER <BR/>";
				}
			}
		} else {
			$output_buf .= "<BR/>Voicemail is not supported on this system<BR/>";
		}
		break;
	default:
		break;
}
// Get mailbox settings.
$vmbox = null;
if ($extens === NULL) {
	$page_output = "<div class='content'>
				<BR/>$output_buf<BR/>
				<h2>" . _("Voicemail") . "</h2>
				<h3>No voicemail users are defined on this system.</h3>";
}
else {
	$vmbox = voicemail_mailbox_get($extension);
	if ($vmbox !== null) {
		$status_enabled = "selected";
		$status_disabled = "";
	} else {
		$status_enabled = "";
		$status_disabled = "selected";
	}
	$context = isset($vmbox["vmcontext"])?$vmbox["vmcontext"]:"default";
	$pwd = isset($vmbox["pwd"])?$vmbox["pwd"]:"";
	$name = (isset($vmbox["name"]) && $vmbox["name"] != "")?$vmbox["name"]:"";
	if ($name == "") {
		$this_exten = core_users_get($extension);
		$name = $this_exten["name"];
	}
	$email = isset($vmbox["email"])?$vmbox["email"]:"";
	$pager = isset($vmbox["pager"])?$vmbox["pager"]:"";
	$options = isset($vmbox["options"])?$vmbox["options"]:array();
	if ($options !== NULL) {
		if (isset($options["attach"])) {
			if ($options["attach"] == "yes") {
				$yes_attach = "checked=checked";
				$no_attach = "";
			} else {
				$yes_attach = "";
				$no_attach = "checked=checked";
			}
		} else {
			$yes_attach = "";
			$no_attach = "checked=checked";
		}
		if (isset($options["saycid"])) {
			if ($options["saycid"] == "yes") {
				$yes_cid = "checked=checked";
				$no_cid = "";
			} else {
				$yes_cid = "";
				$no_cid = "checked=checked";
			}
		} else {
			$yes_cid = "";
			$no_cid = "checked=checked";
		}
		if (isset($options["envelope"])) {
			if ($options["envelope"] == "yes") {
				$yes_envelope = "checked=checked";
				$no_envelope = "";
			} else {
				$yes_envelope = "";
				$no_envelope = "checked=checked";
			}
		} else {
			$yes_envelope = "";
			$no_envelope = "checked=checked";
		}
		if (isset($options["delete"])) {
			if ($options["delete"] == "yes") {
				$yes_delete = "checked=checked";
				$no_delete = "";
			} else {
				$yes_delete = "";
				$no_delete = "checked=checked";
			}
		} else {
			$yes_delete = "";
			$no_delete = "checked=checked";
		}
		$opts = "";
		if (isset($options) && is_array($options)) {
			$alloptions = array_keys($options);
			if (isset($alloptions)) {
				foreach ($alloptions as $opt) {
					if (($opt != "attach") && ($opt != "envelope") && ($opt != "saycid") && ($opt != "delete") && ($opt != "")) {
						$opts .= $opt . "=" . $options[$opt] . "|";
					}
				}
				$opts = rtrim($opts, "|");
				// remove the = if no options were set.
				$opts = rtrim($opts, "=");
			}
		}
	}

	$js_functions	= "<script type='text/javascript'><!--
	function frm_vmailedit_voicemailEnabled(notused) {

			if (document.getElementById('vm').value == 'disabled') {
				var dval=true;
			} else {
				var dval=false;
			}
			document.getElementById('vmpwd').disabled=dval;
			document.getElementById('email').disabled=dval;
			document.getElementById('pager').disabled=dval;
			document.getElementById('attach0').disabled=dval;
			document.getElementById('attach1').disabled=dval;
			document.getElementById('saycid0').disabled=dval;
			document.getElementById('saycid1').disabled=dval;
			document.getElementById('envelope0').disabled=dval;
			document.getElementById('envelope1').disabled=dval;
			document.getElementById('delete0').disabled=dval;
			document.getElementById('delete1').disabled=dval;
			document.getElementById('options').disabled=dval;
			document.getElementById('vmcontext').disabled=dval;
			return true;
	}
	//--></script>";

	$status_html	 = "<tr><td>Status</td>";
	$status_html	.= "<td><select name='vm' id='vm' tabindex=1 onchange='frm_vmailedit_voicemailEnabled();'>";
	$status_html 	.= "<option value='enabled' " . $status_enabled . ">Enabled</option>";
	$status_html 	.= "<option value='disabled' " . $status_disabled . ">Disabled</option>";
	$status_html 	.= "</select></td></tr>";

	$pwd_html = "<tr><td><a href='#' class='info'>
			Voicemail Password<span>This is the password used to access the voicemail system.
			<br /><br />This password can only contain numbers.
			<br /><br />A user can change the password you enter here after logging into the voicemail system (*98) with a phone.
			</span></a></td>
			<td>
			<input type='password' name='vmpwd' id='vmpwd' tabindex=1 value='" . $pwd . "'></td></tr>";

	$email_html = "<tr>
			<td><a href='#' class='info'>Email Address<span>The email address that voicemails are sent to.</span></a></td>
			<td><input type='text' name='email' id='email' tabindex=1 value='" . $email . "'></td></tr>";

	$pager_html = "<tr>
			<td><a href='#' class='info'>Pager Email Address<span>Pager/mobile email address that short voicemail notifications are sent to.</span></a></td>
			<td><input type='text' name='pager' id='pager'   tabindex=1 value='" . $pager . "'></td></tr>";

	$att_html = "<tr><td><a href='#' class='info'>Email Attachment<span>Option to attach voicemails to email.</span></a></td>
			<td><input type='radio' name='attach' id='attach0'  tabindex=1 value='attach=yes' " . $yes_attach .  " />yes&nbsp;&nbsp;&nbsp;&nbsp;
			<input type='radio' name='attach' id='attach1'  tabindex=1 value='attach=no' " . $no_attach . "/>no&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>";

	$cid_html = "<tr>
			<td><a href='#' class='info'>Play CID<span>Read back caller's telephone number prior to playing the incoming message, and just after announcing the date and time the message was left.</span></a></td>
			<td><input type='radio' name='saycid' id='saycid0'  tabindex=1 value='saycid=yes' " . $yes_cid . " />yes&nbsp;&nbsp;&nbsp;&nbsp;
			<input type='radio' name='saycid' id='saycid1'  tabindex=1 value='saycid=no' " . $no_cid . " />no&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>";

	$envelope_html = "<tr>
			<td><a href='#' class='info'>Play Envelope<span>Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message. This setting does not affect the operation of the envelope option in the advanced voicemail menu.</span></a></td>
			<td><input type='radio' name='envelope' id='envelope0'  tabindex=1 value='envelope=yes' " . $yes_envelope . " />yes&nbsp;&nbsp;&nbsp;&nbsp;
			<input type='radio' name='envelope' id='envelope1'  tabindex=1 value='envelope=no' " . $no_envelope . " />no&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>";

	$delete_html = "<tr>
			<td><a href='#' class='info'>Delete Voicemail<span>If set to \"yes\" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER.</span></a></td>
			<td><input type='radio' name='delete' id='delete0'  tabindex=1 value='delete=yes' " . $yes_delete . " />yes&nbsp;&nbsp;&nbsp;&nbsp;
			<input type='radio' name='delete' id='delete1'  tabindex=1 value='delete=no' " . $no_delete . " />no&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>";

	$opts_html = "<tr>
			<td><a href='#' class='info'>VM Options<span>Separate options with pipe ( | )<br /><br />ie: review=yes|maxmessage=60</span></a></td>
			<td><input type='text' name='options' id='options'   tabindex=1 value='" . $opts . "'></td></tr>";

	$context_html = "<tr>
			<td><a href='#' class='info'>VM Context<span>This is the Voicemail Context which is normally set to default. Do not change unless you understand the implications.</span></a></td>
			<td><input type='text' name='vmcontext' id='vmcontext'   tabindex=1 value='" . $context . "'></td></tr>";


	$submit_html = "<tr><td><h6><input name='Submit' type='submit' tabindex='1' value='Submit'></h6></td>";
	if ($update_flag == "true") {
		$submit_html .= "<td><b>Settings update complete.</b></td>";
	}
	$submit_html .= "</tr>";

	$table_output = $status_html . $pwd_html . $name_html . $email_html . $pager_html . $att_html . $cid_html . $envelope_html . $delete_html . $opts_html . $context_html . $submit_html;

	$page_output = "<div class='content'>
					<BR/>$output_buf<BR/>
					<h2>" . _("Voicemail: $extension $name") . "</h2>
					<form autocomplete='off' name='ampuserVoicemailEdit' action='config.php' method='post'>
						<input type='hidden' name='update_flag' value='true'/>
						<input type='hidden' name='display' value='$display'/>
						<input type='hidden' name='action' id='action' value='changevmailsettings'/>
						<input type='hidden' name='extension' id='extension' value='$extension'/>
						<input type='hidden' name='extdisplay' id='extdisplay' value='$extension'/>
						<input type='hidden' name='name' id='name' value='$name'>
						<table>$table_output</table>
					</form>
					$js_functions
					<script type='text/javascript'>frm_vmailedit_voicemailEnabled();</script>";
} 
echo $page_output;
?>
