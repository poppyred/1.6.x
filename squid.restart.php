<?php

$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/restart.squid";

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["VERBOSE"]=true;
}

	include_once('ressources/class.templates.inc');
						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();

function title(){
	
	$tpl=new templates();

	if(isset($_GET["onlySquid"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{restart_service}");
	}
	
	if(isset($_GET["firewall"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{reconfigure_transparent_rules}");
	}
	
	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}
	
	if(isset($_GET["onlyreload"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{reload_service}");
	}
	
	if(isset($_GET["wccp"])){
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{WCCP_LAYER3}");
	}	
	
	if(isset($_GET["CheckCaches"])){
		$warn=$tpl->javascript_parse_text("{check_caches_warning}");
		$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{check_caches}");
	
	}
		
	return $title;
}


function suffix(){
	$reconfigure=null;
	$onlySquid=null;
	$ApplyConfToo=null;
	if($_GET["reconfigure"]=="yes"){
		$reconfigure="&ApplyConfToo=yes";
	}
	
	if(isset($_GET["onlySquid"])){
		$onlySquid="&onlySquid=yes";
	}
	
	if(isset($_GET["firewall"])){
		$onlySquid="&firewall=yes";
	
	}
	
	if(isset($_GET["wccp"])){
		$wccp="&wccp=yes";
	}
	
	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}
	
	if(isset($_GET["onlyreload"])){
		$onlySquid="&onlyreload=yes";
	}
	
	if(isset($_GET["CheckCaches"])){
		$onlySquid="&CheckCaches=yes";
	
	}
	
	if(isset($_GET["firewall"])){
		$onlySquid="&firewall=yes";
	}

	return "$onlySquid$wccp$ApplyConfToo$reconfigure";
	
}


function js(){
	
	
	
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$compile_squid_ask=$tpl->javascript_parse_text("{compile_squid_ask}");
	if($_GET["ask"]=="yes"){
		$warn="if(!confirm('$compile_squid_ask')){return;}";
	}
	
	if(isset($_GET["CheckCaches"])){
		$warn=$tpl->javascript_parse_text("{check_caches_warning}");
		$warn="if(!confirm('$warn')){return;}";
		
	}	
	
	$suffix=suffix();
	$title=title();
	
	echo "
	function Start$t(){	
		$warn
		RTMMail('800','$page?popup=yes$suffix','$title');
	}
	Start$t();";
	
	
}


function buildjs(){
	$t=$_GET["t"];
	$time=time();
	$MEPOST=0;

	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($GLOBALS["CACHEFILE"]));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	
	if($prc==0){
		$prc=intval(trim(@file_get_contents("/usr/share/artica-postfix/ressources/logs/squid.restart.progress2")));
	}
	
	
	$md5file=trim(md5_file($GLOBALS["LOGSFILES"]));
	
	echo "// CACHE FILE: {$GLOBALS["CACHEFILE"]} {$prc}%\n";
	echo "// LOGS FILE: {$GLOBALS["LOGSFILES"]} - $md5file ". strlen($md5file)."\n";
	
if ($prc==0){
	if(strlen($md5file)<32){
	echo "
	// PRC = $prc ; md5file=$md5file
	function Start$time(){
			if(!RTMMailOpen()){return;}
			Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
	}
	setTimeout(\"Start$time()\",1000);";
	
	
	return;
	}
}


if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
		if(!document.getElementById('text-$t')){return;}
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('text-$t').value=res;
		}		
		Loadjs('$page?build-js=yes&t=$t&md5file=$md5file');
	}		
	
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return;
}

if($prc>100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		document.getElementById('title-$t').style.border='1px solid #C60000';
		document.getElementById('title-$t').style.color='#C60000';
		$('#progress-$t').progressbar({ value: 100 });
	}
	setTimeout(\"Start$time()\",1000);
	";
	return;	
	
}

if($prc==100){
	echo "
	function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		LayersTabsAllAfter();
		RTMMailHide();
		
		}
	setTimeout(\"Start$time()\",1000);
	";	
	return;	
}

echo "	
function Start$time(){
		if(!RTMMailOpen()){return;}
		document.getElementById('title-$t').innerHTML='$title';
		$('#progress-$t').progressbar({ value: $prc });
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
	}
	setTimeout(\"Start$time()\",1500);
";
}

function Launch(){
	$sock=new sockets();
	if(isset($_GET["ApplyConfToo"])){
		$ApplyConfToo="&ApplyConfToo=yes";
	}	
	
	$onlyreload=false;
	
	if($_GET["onlyreload"]=="yes"){
		$onlyreload=true;
	}
	
	
	if($_GET["onlySquid"]=="yes"){
		if($_GET["ApplyConfToo"]=="yes"){
			if(!$onlyreload){
				$cmd="squid.php?restart-and-reconfigure=yes";
				writelogs("launch $cmd",__FUNCTION__,__FILE__,__LINE__);
				$sock->getFrameWork($cmd);
				return;
			}
		}
		
	}
	
	
	
	
	$cmd="cmd.php?force-restart-squid=yes$ApplyConfToo";
	if(isset($_GET["onlySquid"])){
		$ApplyConfToo="&ApplyConfToo=yes";
		$cmd="cmd.php?force-restart-squidonly=yes$ApplyConfToo&force=yes";
	}
	
	if(isset($_GET["onlyreload"])){
		$cmd="squid.php?squid-k-reconfigure=yes&force=yes$ApplyConfToo";
		
	}
	
	if(isset($_GET["wccp"])){
		$cmd="cmd.php?force-restart-squid=yes$ApplyConfToo&wccp=yes";
	}
	
	if(isset($_GET["CheckCaches"])){
		$cmd="squid.php?squid-z-reconfigure=yes&force=yes";
	}
	
	if(isset($_GET["firewall"])){
		$cmd="squid.php?firewall=yes";
	}	
	
	if($GLOBALS["VERBOSE"]){echo "<H1>RUN $cmd</H1>";}
	
	writelogs("launch $cmd",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork($cmd);
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	Launch();
	
	
	

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	$title="{PLEASE_WAIT_RESTARTING_ALL_SERVICES}";
	if(isset($_GET["onlySquid"])){
		$title="{please_wait}, {restarting_proxy_service}";
		
	}
	
	if(isset($_GET["onlyreload"])){
		$title="{please_wait_reloading_service}";
	}
	
	if(isset($_GET["CheckCaches"])){
		$onlySquid="&CheckCaches=yes";
		$title="{please_wait_check_caches}";
	}

	if(isset($_GET["firewall"])){
		$title="{please_wait}, {reconfigure_transparent_rules}";
	}
	$text=$tpl->_ENGINE_parse_body($title);
	
	
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:99%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;	
}

function Filllogs(){
	$t=explode("\n",@file_get_contents($GLOBALS["LOGSFILES"]));
	krsort($t);
	echo @implode("\n", $t);
	
}