<?php

$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid_reload.txt";

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

	include_once('ressources/class.templates.inc');
						
	
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{refresh_all_status}");
	echo "RTMMail('800','$page?popup=yes','$title')";
	
	
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
echo "
function Start$time(){
		if(!RTMMailOpen()){return;}
		Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
}
setTimeout(\"Start$time()\",1000);";
return;
}

$md5file=md5_file($GLOBALS["LOGSFILES"]);
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

if($prc>=100){
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




//Loadjs('$page?build-js=yes&t=$t&md5file={$_GET["md5file"]}');
		
	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-refresh=yes");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	
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