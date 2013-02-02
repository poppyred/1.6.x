<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	
	
	main();

function main(){
	$t=time();$page=CurrentPageName();
	$html="<div id='main-$t'></div><script>LoadAjax('main-$t','$page?popup=yes&t=$t');</script>";echo $html;
		
}	
	
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=$_GET["t"];
	$array=unserialize(@file_get_contents("ressources/logs/web/squidlogs.stats"));
	if(!isset($array["syslogstore"])){@unlink("ressources/logs/web/squidlogs.stats");}

	if(!is_file("ressources/logs/web/squidlogs.stats")){
		$sock=new sockets();
		$sock->getFrameWork("squid.php?squidlogs-stats=yes");
		$tt=time();
		$html="<center>
				<p style='font-size:18px;font-weight:bold'>{please_wait_while_calculate_informations}</p>
				<p>&nbsp;</p>
				<img src='img/wait_verybig_mini_red.gif'></center>
			</center>
				<script>
					function Wait$tt(){
						LoadAjax('main-$t','$page?popup=yes&t=$t');
					
					}
					setTimeout(\" Wait$tt()\",5000);
				</script>
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	$array=unserialize(@file_get_contents("ressources/logs/web/squidlogs.stats"));

	$html="
	<div class=explain style='font-size:14px'>{squidlogs_explain}</div>
	<table style='width:100%'>
	<tr>
		<td>
			<table style='width:100%' class=form>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{hard_drive}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["squidlogs"]["DEV"]} </td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{hard_drive_status}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["squidlogs"]["OC"]}/{$array["squidlogs"]["SIZE"]}</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:14px' valign='top'></td>
				<td><strong style='font-size:14px;font-weight:bold'>". pourcentage($array["squidlogs"]["POURC"])."</td>
			</tr>	
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{database_size}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["squidlogs"]["REALPATH"]}:<span style='text-align:right'><i>". FormatBytes($array["squidlogs"]["PATHSIZE"]/1024)."</span></td>
			</tr>	
			</table>
		</td>
		<td>
				<table style='width:100%' class=form>
				<tr>
					<td width=1%><img src='img/database-32-delete.png'></td>
					<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('mysql.empty.database.php?db=squidlogs');\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{empty_database}</a></td>
				<tr>
					<td width=1%><img src='img/arrow-blue-left-32.png'></td>
					<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('mysql.mv.database.php?db=squidlogs');\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{move_database_disk}</a></td>
				</tr>				
				
				
				
				</table>		
		</td>
		</tr>
	</table>
	<div class=explain style='font-size:14px'>{syslogstore_explain}</div>	
	<table style='width:100%'>
	<tr>
		<td>
			<table style='width:100%' class=form>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{hard_drive}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["syslogstore"]["DEV"]} </td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{hard_drive_status}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["syslogstore"]["OC"]}/{$array["syslogstore"]["SIZE"]}</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:14px' valign='top'></td>
				<td><strong style='font-size:14px;font-weight:bold'>". pourcentage($array["syslogstore"]["POURC"])."</td>
			</tr>	
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{database_size}:</td>
				<td><strong style='font-size:14px;font-weight:bold'>{$array["syslogstore"]["REALPATH"]}:<span style='text-align:right'><i>". FormatBytes($array["syslogstore"]["PATHSIZE"]/1024)."</span></td>
			</tr>
			</table>
		</td>
			<td valign='top'>
				<table style='width:100%' class=form>
				<tr>
					<td width=1%><img src='img/database-32-delete.png'></td>
					<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('mysql.empty.database.php?db=syslogstore');\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{empty_database}</a></td>
				<tr>						
				<tr>
					<td width=1%><img src='img/arrow-blue-left-32.png'></td>
					<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('mysql.mv.database.php?db=syslogstore');\" style='font-size:14px;font-weight:bold;text-decoration:underline'>{move_database_disk}</a></td>
				</tr>
				</table>		
			</td>
		</tr>
	</table>													
";
	
	echo $tpl->_ENGINE_parse_body($html);
}
