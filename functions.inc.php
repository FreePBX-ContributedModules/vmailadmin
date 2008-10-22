<?
// draw list for users and devices with paging
function vmaildrawListMenu($results, $type="setup", $dispnum="vmailedit", $extdisplay, $description=false) {
        $index = 0;
        echo "<ul>\n";
        if ($description !== false) {
                //echo "\t<li><a ".($extdisplay=='' ? 'class="current"':'')." href=\"config.php?type=".$type."&display=".$dispnum."\">"._("Add")." ".$description."</a></li>\n";
		echo "\t<li><a ".($extdisplay=='' ? 'class="current"':'')." href=\"config.php?type=".$type."&display=".$dispnum."\">"._("". $description. "")."</a></li>\n";
        }
        if (isset($results)) {
                foreach ($results as $key=>$result) {
                        $index= $index + 1;
                        echo "\t<li><a".($extdisplay==$result[0] ? ' class="current"':''). " href=\"config.php?type=".$type."&display=".$dispnum."&extdisplay={$result[0]}\">{$result[1]} &lt;{$result[0]}&gt;</a></li>\n";
                }
        }
        echo "</ul>\n";
}
?>
