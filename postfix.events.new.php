<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if(!CheckRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_POST["maillogToMysql"])){maillogToMysqlSave();exit;}
	if(isset($_GET["popup"])){page();exit;}
	if(isset($_GET["table-list"])){events_list();exit;}
	if(isset($_GET["js-zarafa"])){js_zarafa();exit;}
	if(isset($_GET["js-mgreylist"])){js_mgreylist();exit;}
	if(isset($_GET["ZoomEvents"])){ZoomEvents();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	
tabs();

function CheckRights(){
	$user=new usersMenus();
	if($user->AsPostfixAdministrator){return true;}
	if($user->AsMailBoxAdministrator){return true;}
	return false;
}

function js_zarafa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_ZARAFA}:{events}");
	$html="YahooWinBrowse('942','$page?zarafa-filter=yes','$title')";
	echo $html;
	
}
function js_mgreylist(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_MILTERGREYLIST}:{events}");
	$html="YahooWinBrowse('942','$page?miltergrey-filter=yes','$title')";
	echo $html;

}

function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$maillogToMysql=$sock->GET_INFO("maillogToMysql");
	if(!is_numeric($maillogToMysql)){$maillogToMysql=1;}
	$maillogStoragePath=$sock->GET_INFO("maillogStoragePath");
	if($maillogStoragePath==null){$maillogStoragePath="/home/postfix/maillog";}
	$maillogMaxDays=$sock->GET_INFO("maillogMaxDays");
	if(!is_numeric($maillogMaxDays)){$maillogMaxDays=7;}
	$t=time();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{store_events_to_mysql}:</td>
		<td>". Field_checkbox("maillogToMysql", 1,$maillogToMysql,"Check$t()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{storage_directory}:</td>
		<td>". Field_text("maillogStoragePath", $maillogStoragePath,"font-size:16px;width:300px")."</td>
		<td width=1% nowrap>". button_browse("maillogStoragePath")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_days}:</td>
		<td style='font-size:16px'>". Field_text("maillogMaxDays", $maillogMaxDays,"font-size:16px;width:90px")."&nbsp;{days}</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td colspan=3 align='right'>
				<hr>". button("{apply}", "Save$t()",18)."
		</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	var results=trim(obj.responseText);
	if(results.length>0){alert(results);}
	RefreshTab('main_postfix_events');
	}
		
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('maillogToMysql').checked){
		XHR.appendData('maillogToMysql',1);
	}else{
		XHR.appendData('maillogToMysql',0);
	}
	XHR.appendData('maillogStoragePath',document.getElementById('maillogStoragePath').value);
	XHR.appendData('maillogMaxDays',document.getElementById('maillogMaxDays').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);				
}	
	
function Check$t(){
	if(document.getElementById('maillogToMysql').checked){
		document.getElementById('maillogStoragePath').disabled=true;
		document.getElementById('maillogMaxDays').disabled=true;
	}else{
		document.getElementById('maillogStoragePath').disabled=false;
		document.getElementById('maillogMaxDays').disabled=false;	
	}

}
Check$t();	
</script>				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function maillogToMysqlSave(){
	$sock=new sockets();
	$sock->SET_INFO("maillogToMysql", $_POST["maillogToMysql"]);
	$sock->SET_INFO("maillogStoragePath", $_POST["maillogStoragePath"]);
	$sock->SET_INFO("maillogMaxDays", $_POST["maillogMaxDays"]);
	
}


function tabs(){
	
	$tpl=new templates();
	
	$page=CurrentPageName();
	$array["events"]='{events}';
	$array["parameters"]='{parameters}';

	
	$style="style='font-size:18px'";
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= "<li $style><a href=\"$page?popup=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}

	
	
		$html[]= "<li $style><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "main_postfix_events",950)."<script>LeftDesign('logs-white-256-opac20.png');</script>";	
	
	
}



function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{POSTFIX_EVENTS}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$destination=$tpl->javascript_parse_text("{destination}");
	$events=$tpl->javascript_parse_text("{events}");
	$hostname=$_GET["hostname"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$form="<div style='width:900px' class=form>";
	if(isset($_GET["noform"])){$form="<div style='margin-left:-15px'>";}
	if($_GET["mimedefang-filter"]=="yes"){
		$title=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{events}");
	}
	
	$table_width=900;
	$events_wdht=546;
	if(isset($_GET["miniadm"])){
		$table_width=955;
		$events_wdht=601;
	}
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var memid='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&t=$t&zarafa-filter={$_GET["zarafa-filter"]}&miltergrey-filter={$_GET["miltergrey-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width : 58, sortable : true, align: 'left'},
		{display: '$host', name : 'host', width : 71, sortable : true, align: 'left'},
		{display: '$service', name : 'host', width : 58, sortable : true, align: 'left'},
		{display: 'PID', name : 'host', width : 43, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'left'},
		{display: '$events', name : 'events', width :$events_wdht, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'zDate'},
		],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '$title ($maillog_path)',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ZoomEvents(content){
	RTMMail(650,'$page?ZoomEvents='+content);
}

</script>
";
	
	echo $html;
			
	
	
}

function events_list(){
	
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_POST["query"]);
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_POST["rp"]}&miltergrey-filter={$_GET["miltergrey-filter"]}&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}")));
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/query.mail.log"));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($index, $line) = each ($array) ){
		$lineenc=base64_encode($line);
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
			
			
		}
		
		if($date==null){
			if(preg_match("#([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?):(.+)#", $line,$re)){
				$date="{$re[1]} {$re[2]} {$re[3]}";
				$host=$re[4];
				$service=$re[5];
				$line=$re[6];
			}
			
		}
		
		$img=statusLogs($line);
		
		$loupejs="ZoomEvents('$lineenc')";
		

	
	$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
				<span style='font-size:12px'>$date</span>",
				"<span style='font-size:12px'>$host</span>",
				"<span style='font-size:12px'>$service</span>",
				"<span style='font-size:12px'>$pid</span>",
				"<img src='$img'>",
				"<span style='font-size:12px'>$line</span>")
				);	

				
	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);		
	
}

function ZoomEvents(){
	
	$ev=base64_decode($_GET["ZoomEvents"]);
	echo "<div style='font-size:14px;width:95%' class=form>$ev</div>";
	
}


