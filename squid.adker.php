<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');
	
	
	if(isset($_GET["status"])){status_kerb();exit;}
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["cntml-status"])){cntlm_status();exit;}
	if(isset($_GET["ldap-params"])){ldap_params();exit;}
	if(isset($_GET["schedule-params"])){schedule_params();exit;}
	if(isset($_POST["AdSchBuildProxy"])){schedule_save();exit;}
	if(isset($_GET["testauth-results"])){test_auth_results();exit;}
	if(isset($_POST["EnableCNTLM"])){EnableCNTLM_save();exit;}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_POST["SAVE_RECURSIVE_GROUPS"])){SAVE_RECURSIVE_GROUPS();exit;}
	if(isset($_POST["EnableKerbAuth"])){settingsSave();exit;}
	if(isset($_POST["SambeReconnectAD"])){SambeReconnectAD();exit;}
	
	if(isset($_GET["kerbchkconf"])){kerbchkconf();exit;}
	if(isset($_GET["test-popup"])){test_popup();exit;}
	if(isset($_GET["test-nettestjoin"])){test_testjoin();exit;}
	if(isset($_GET["test-netadsinfo"])){test_netadsinfo();exit;}
	if(isset($_GET["test-netrpcinfo"])){test_netrpcinfo();exit;}
	if(isset($_GET["test-wbinfoalldom"])){test_wbinfoalldom();exit;}
	if(isset($_GET["test-wbinfomoinst"])){test_wbinfomoinst();exit;}
	if(isset($_GET["test-wbinfomoinsa"])){test_wbinfomoinsa();exit;}
	
	if(isset($_GET["test-auth"])){test_auth();exit;}
	if(isset($_POST["SaveSambaBindInterface"])){SaveSambaBindInterface();exit;}
	if(isset($_POST["TESTAUTHUSER"])){test_auth_perform();exit;}
	if(isset($_POST["LDAP_SUFFIX"])){ldap_params_save();exit;}
	if(isset($_GET["test-popup-js"])){test_popup_js();exit;}
	if(isset($_GET["intro"])){intro();exit;}
	if(isset($_GET["join-js"])){join_js();exit;}
	if(isset($_GET["join-popup"])){join_popup();exit;}
	if(isset($_GET["join-perform"])){join_perform();exit;}
	
	if(isset($_GET["diconnect-js"])){diconnect_js();exit;}
	if(isset($_GET["disconnect-popup"])){diconnect_popup();exit;}
	if(isset($_GET["disconnect-perform"])){diconnect_perform();exit;}
	if(isset($_GET["cntlm"])){cntlm();exit;}
	
js();

function join_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{restart_connection}");
	echo "YahooWin6('905','$page?join-popup=yes','$title')";
	
}
function diconnect_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{disconnect}");
	echo "YahooWin6('905','$page?disconnect-popup=yes','$title')";
	
}
function join_perform(){
	$sock=new sockets();
	$users=new usersMenus();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?join-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
	</script>
	
	";	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_CONNECT");
	}
	
	echo $html;
}
function diconnect_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?disconnect-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
		RefreshTab('main_adker_tabs');
	</script>
	";

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_DISCONNECT");
	}	
	
	echo $html;	
}

function join_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?join-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function diconnect_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?disconnect-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function status_kerb(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	$tpl=new templates();
	$t=time();
	$squid=new squidbee();

	if($EnableKerbAuth==0){
		echo"<script>UnlockPage();</script>";
		
		return;}
	writelogs("squid.php?ping-kdc=yes",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("squid.php?ping-kdc=yes");
	$datas=unserialize(@file_get_contents("ressources/logs/kinit.array"));
	
	if(count($datas)==0){
		echo "
		<script>UnlockPage();LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');</script>";
		return;
		
	}
	
	$img="img/error-24.png";
	$textcolor="#8A0D0D";
	$text=$datas["INFO"];
	if(preg_match("#Authenticated to#is", $text)){
		$img="img/ok24.png";$textcolor="black";
	}
	
	
	if(trim($text)<>null){$text=": $text";}
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	
	<tr>
		<td width=1% valign='top'><img src='$img'></td>
		<td nowrap style='font-size:18px' valign='top'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.adker.php',true);\" style='color:$textcolor;font-weight:bold;text-decoration:underline'>Active Directory $text</strong></td>
		<td width=1%>".imgtootltip("refresh-24.png","{refresh}","LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}



function test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$viaSmamba=null;
	$t=time();
	if(!isset($_GET["via-samba"])){
		if($EnableKerbAuth==0){
			echo $tpl->_ENGINE_parse_body("<p class=text-error>{EnableWindowsAuthentication}: {disabled}</p>");
			return;
		}
		$reconnectJS="SambeReconnectAD();";
		
		
	}else{
		$viaSmamba="&via-samba=yes";
		$reconnectJS="SambbReconnectAD();";
	}
	
	$html="
	<div id='animate-$t'></div>
	<div id='main-$t'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' style='font-size:26px' nowrap class=legend>{is_connected}?:</td>
		<td width=99%><div id='$t-nettestjoin' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Active Directory Infos:</td>
		<td width=99%><div id='$t-netadsinfo' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>RPC Infos:</td>
		<td width=99%><div id='$t-netrpcinfo' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Domains:</td>
		<td width=99%><div id='$t-wbinfoalldom' style='margin-top:20px'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Check shared secret:</td>
		<td width=99%><div id='$t-wbinfomoinst' style='margin-top:20px'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>NTLM Auth:</td>
		<td width=99%><div id='$t-wbinfomoinsa' style='margin-top:20px'></div></td>
	</tr>		
	<tr>
		<td colspan=2 align='right' style='padding-top:50px;text-align:right'>". imgtootltip("64-refresh.png","{refresh}","StartAgain()")."</td>
	</tr>		
	</tbody>
	</table>
	<center style='margin-top:20px'>". button("{restart_connection}","$reconnectJS",32)."</center>
	</div>
	<script>
		function StartAgain(){
			LoadAjaxTiny('$t-nettestjoin','$page?test-nettestjoin=yes&time=$t$viaSmamba');
		}
		
	var x_SambeReconnectAD= function (obj) {
		RefreshTab('main_adker_tabs');
	}		
	
		function SambeReconnectAD(){
			var XHR = new XHRConnection();
			XHR.appendData('SambeReconnectAD','yes');
			AnimateDiv('main-$t');
			XHR.sendAndLoad('$page', 'POST',x_SambeReconnectAD);
		
		}
		
	var x_SambbReconnectAD= function (obj) {
		document.getElementById('animate-$t').innerHTML='';
		StartAgain();
	}			
		
		function SambbReconnectAD(){
			Loadjs('squid.ad.progress.php');		
		}
		
StartAgain();
		
		
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_netadsinfo(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
	$html="<hr><div style='font-size:18px'>";
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-netrpcinfo','$page?test-netrpcinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function test_testjoin(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpctestjoin=yes")));
	$html="<hr><div style='font-size:18px'>";
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
	LoadAjaxTiny('{$_GET["time"]}-netadsinfo','$page?test-netadsinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_netrpcinfo(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}

	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpcinfo=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfoalldom','$page?test-wbinfoalldom=yes&time={$_GET["time"]}$viaSmamba');
	</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function test_wbinfoalldom(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
	$html=$html.test_results($datas);	
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinst','$page?test-wbinfomoinst=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function test_wbinfomoinst(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinsa','$page?test-wbinfomoinsa=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_wbinfomoinsa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
		$SambaWinbindUseDefaultDomain=$sock->GET_INFO("SambaWinbindUseDefaultDomain");
		if(!is_numeric($SambaWinbindUseDefaultDomain)){$SambaWinbindUseDefaultDomain=0;}
		if($SambaWinbindUseDefaultDomain==0){$AR["WORKGROUP"]=$array["WORKGROUP"];}
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline$viaSmamba")));
	$html=$html.test_results($datas)."</div>
	<script>
		LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');
	</script>
			
			";

	echo $tpl->_ENGINE_parse_body($html);	
	
}


function test_results($array){
	$tpl=new templates();
	$html=null;
	while (list ($num, $ligne) = each ($array) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color="black";
		
		if(preg_match("#No logon#", $ligne)){$color="#D30F0F;font-weight:bold";
			$ligne=$ligne.$tpl->_ENGINE_parse_body("<br> {should_change_ad_dns}");
		}
		
		if(preg_match("#UNSUCCESSFUL#i", $ligne)){$color="#009809;font-weight:bold";}
		if(preg_match("#invalid credential#i", $ligne)){$color="#009809;font-weight:bold";}
		if(preg_match("#is OK#", $ligne)){$color="#009809;font-weight:bold";}
		if(preg_match("#online#", $ligne)){$color="#009809";}
		if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);}
		if(preg_match("#Could not#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#failed#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#_CANT_#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#succeeded#i", $ligne)){$color="#009809;font-weight:bold";}
		if($color=="black"){
			if(preg_match("#^(.+?):\s+(.+)#", $ligne,$re)){$ligne="<span style='color:#656060;font-weight:bold;font-size:18px'>{$re[1]}:&nbsp;</span><span style='color:#009809;font-weight:bold'>{$re[2]}</span>";}
		}
		$html=$html."<div style='font-size:18px;color:$color'>$ligne</div>";
	}	
	return $html;
}


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	
	if($DisableWinbindd==1){
		echo "alert('".$tpl->javascript_parse_text("{DisableWinbindd_error}")."')";
		return;
	}
	
	
	$title=$tpl->_ENGINE_parse_body("{APP_SQUIDKERAUTH}");
	
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
	return;
	
	$html="YahooWin4(650,'$page?tabs=yes','$title');";
	echo $html;
	}
	
function test_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{analyze}");
	if(isset($_GET["via-samba"])){$e="&via-samba=yes";}
	$html="YahooWin6(600,'$page?test-popup=yes$e','$title');";
	echo $html;	
	
	
}
	
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	
	if($EnableKerbAuth==1){
		$array["active_directory_users"]="{active_directory_users}";
	}
	
	
	if($users->AsSystemAdministrator){
		$array["popup"]='{activedirectory_connection}';
		$array["ldap-params"]='{ldap_parameters2}';
	}
	$array["test-popup"]='{analyze}';
	$array["test-auth"]='{test_auth}';

	if($users->AsSquidAdministrator){
		$array["cntlm"]='{APP_CNTLM}';
	}
	
	
	
	$fontsize=18;

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="active_directory_users"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"browse-ad-groups.php?popup=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}	
		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_adker_tabs",1100);
	
}
	
function popup(){
$page=CurrentPageName();
$users=new usersMenus();
$sock=new sockets();

$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	


$tpl=new templates();
	if(!$users->MSKTUTIL_INSTALLED){
		echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'>
				<div style='font-size:16px'>{error_missing_mskutil}<br>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('setup.index.progress.php?product=APP_MSKTUTIL&start-install=yes')\" style='font-size:16px;text-decoration:underline'>{install}</a></div>
			</td>
		</tr>
		</table>
		");return;
	}
	if($EnableWebProxyStatsAppliance==0){
		if(strlen($users->squid_kerb_auth_path)<2){
			echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'><div style='font-size:16px'>{error_missing_kerbauth}</div></td>
		</tr>
		</table>
		");return;
		}   
	}

	$html="
	<div id='serverkerb-animated'></div>
	<div id='serverkerb-popup'></div>
	
	<script>
	function RefreshServerKerb(){
		LoadAjax('serverkerb-popup','$page?settings=yes');
		}
	
		RefreshServerKerb();
	</script>
	";
		
echo $html;		
}	

function intro(){
	
	$tpl=new templates();
	$intro="{APP_SQUIDKERAUTH_TEXT}<br>{APP_SQUIDKERAUTH_TEXT_REF}";
	if($_GET["switch-template"]=="samba"){$intro="{APP_SAMBAKERAUTH_TEXT}<br>{APP_SAMBAKERAUTH_TEXT_REF}";}	
	echo $tpl->_ENGINE_parse_body("<div class=text-info style='font-size:18px'>$intro</div>");
}
	
function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$sock=new sockets();
	
	
	
	$hostname=$sock->getFrameWork("cmd.php?full-hostname=yes");
	if(strpos($hostname, ".")>0){
		$tre=explode(".",$hostname);
		$hostname=$tre[0];
	}
	
	if(strlen($hostname)>15){
		$hostname_exceed_15=$tpl->_ENGINE_parse_body("{hostname_exceed_15}");
		$hostname_exceed_15=str_replace("%s", "$hostname", $hostname_exceed_15);
		echo FATAL_ERROR_SHOW_128("$hostname_exceed_15
				<center style='margin:20px'>". button("{change_hostname}","Loadjs('system.nic.config.php?change-hostname-js=yes');",32)."</center>
				
				");
		return;
	}
	
	$severtype["WIN_2003"]="Windows 2000/2003";
	$severtype["WIN_2008AES"]="Windows 2008/2012";
	if(isset($_GET["switch-template"])){$_GET["switch-template"]=null;}
	$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$schedule_parameters=$tpl->javascript_parse_text("{schedule_parameters}");
	$disconnect=$tpl->_ENGINE_parse_body("{disconnect}");
	$samba36=0;
	if(preg_match("#^3\.6\.#", $samba_version)){$samba36=1;}
	

	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$configADSamba=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	$LockKerberosAuthentication=$sock->GET_INFO("LockKerberosAuthentication");
	$KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
	$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
	
	$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	
	$arrayAuth[0]="{all_methods}";
	$arrayAuth[1]="{only_ntlm}";
	$arrayAuth[2]="{only_basic_authentication}";
	
	
	$NTPDATE_INSTALLED=0;
	if($users->NTPDATE){$NTPDATE_INSTALLED=1;}
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
	if(!is_numeric($DisableSpecialCharacters)){$DisableSpecialCharacters=0;}
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	
	if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	if(!is_numeric($KerbAuthDisableNsswitch)){$KerbAuthDisableNsswitch=0;}
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
	if(!is_numeric($UseADAsNameServer)){$UseADAsNameServer=0;}
	$SambaBindInterface=$sock->GET_INFO("SambaBindInterface");
	
	
	$net=new networking();
	$nics=$net->Local_interfaces();
	while (list ($interface, $val) = each ($nics) ){
		$ni=new system_nic($interface);
		if($ni->NICNAME<>null){
			$nics[$interface]="[$interface] $ni->NICNAME - $ni->netzone";
		}
	}
	$nics[null]="{all}";
	reset($nics);
	//interfaces = eth0 lo
	//bind interfaces only = yes
	
	
	
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	
	
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if(!is_numeric("$EnableKerberosAuthentication")){$EnableKerberosAuthentication=0;}
	if(!is_numeric("$LockKerberosAuthentication")){$LockKerberosAuthentication=1;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$samba_installed=1;
	if(!$users->SAMBA_INSTALLED){$samba_installed=0;}
	
	if(!isset($array["SAMBA_BACKEND"])){$array["SAMBA_BACKEND"]="tdb";}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($array["COMPUTER_BRANCH"]==null){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($samba36==1){$arrayBCK["autorid"]="autorid";}
	$arrayBCK["ad"]="ad";
	$arrayBCK["rid"]="rid";
	$arrayBCK["tdb"]="tdb";
	if($LockKerberosAuthentication==1){$EnableKerberosAuthentication=0;}
	$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
	
	
	if($EnableKerbAuth==1){
		if(!$users->CORP_LICENSE){
			$Days=86400*30;
			$DayToLeft=30;
			if(is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){
				include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
				$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
				$seconds_diff = $EndTime - time();
				$DayToLeft=floor($seconds_diff/3600/24);
			}
			$MAIN_ERROR=$tpl->_ENGINE_parse_body("<p class=text-error style='font-size:18px'>{warn_no_license_activedirectory_30days}</p>");
			$MAIN_ERROR=str_replace("%s", $DayToLeft, $MAIN_ERROR);
		}
	}
	
	if($EnableKerbAuth==1){
		$disconnectTR="
		<tr>
			<td width=1%><img src='img/stop-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:Loadjs('$page?diconnect-js=yes')\" 
					style='font-size:18px;text-decoration:underline'>$disconnect</a>
				</td>
		</tr>";
	}
	
	if($samba_installed==0){
		
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{samba_is_not_installed}"));
		return;
	}
	
	$Myhostname=strtolower($sock->getFrameWork("cmd.php?full-hostname=yes"));	
	$error_dom1=$tpl->javascript_parse_text("{error}: {WINDOWS_DNS_SUFFIX}");
	$error_dom2=$tpl->javascript_parse_text("{is_not_a_part_of}");
	$error_dom3=$tpl->javascript_parse_text("{ask_change_hostname}");
	$t_tmp=time();
	
	$html="$MAIN_ERROR
	<table style='width:100%'>
	<tr>
	<td valign='top' width=50%><span id='kerbchkconf'></span>
		<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshAll()")."</div></td>
	<td valign='top' width=50%'>
		<table style='width:50%'>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap><a href=\"javascript:blur();\" 
			OnClick=\"javascript:YahooWinBrowse('550','$page?intro=yes&switch-template={$_GET["switch-template"]}','$about_this_section');\" 
			style='font-size:18px;text-decoration:underline'>{about_this_section}</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap><a href=\"javascript:blur();\" 
			OnClick=\"javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=170','1024','900');\" 
			style='font-size:18px;text-decoration:underline'>{online_help}</a></td>
		</tr>

		<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:YahooSearchUser('550','$page?schedule-params=yes','$schedule_parameters');\" 
					style='font-size:18px;text-decoration:underline'>$schedule_parameters</a>
				</td>
		</tr>	
		$disconnectTR	
		</table>		
	</td>
	</table>
	
	<div style='width:98%' class=form>
	
	". Paragraphe_switch_img("{EnableWindowsAuthentication}", 
			"{EnableWindowsAuthentication_text}","EnableKerbAuth",$EnableKerbAuth,null,950,"EnableKerbAuthCheck()")
	."<br>". Paragraphe_switch_img("{EnableRecursiveGroups}",
			"{EnableRecursiveGroups_text}","RECURSIVE_GROUPS",$array["RECURSIVE_GROUPS"],null,950,"EnableRECURSIVE_GROUPS()")."
	
	<table style='width:100%'>
	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{authentication_method}:</td>
		<td>". Field_array_Hash($arrayAuth, "KerbAuthMethod",$KerbAuthMethod,null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:22px'>{KerbAuthDisableNsswitch}:</td>
		<td>". Field_checkbox_design("KerbAuthDisableNsswitch",1,"$KerbAuthDisableNsswitch")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{KerbAuthTrusted}:</td>
		<td>". Field_checkbox_design("KerbAuthTrusted",1,"$KerbAuthTrusted")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{KerbAuthDisableGroupListing}:</td>
		<td>". Field_checkbox_design("KerbAuthDisableGroupListing",1,"$KerbAuthDisableGroupListing")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{KerbAuthDisableNormalizeName}:</td>
		<td>". Field_checkbox_design("KerbAuthDisableNormalizeName",1,"$KerbAuthDisableNormalizeName")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{map_untrusted_to_domain}:</td>
		<td>". Field_checkbox_design("KerbAuthMapUntrustedDomain",1,"$KerbAuthMapUntrustedDomain")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{interface}:</td>
		<td>". Field_array_Hash($nics,"SambaBindInterface",$SambaBindInterface,"style:font-size:22px;padding:3px")."</td>
		<td>". imgtootltip("disk-save-24.png","{save}","SaveSambaBindInterface()")."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{keep_alive}:</td>
		<td>". Field_checkbox_design("SquidNTLMKeepAlive",1,"$SquidNTLMKeepAlive")."</td>
		<td>". help_icon("{SquidNTLMKeepAlive_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{synchronize_time_with_ad}:</td>
		<td>". Field_checkbox_design("NtpdateAD",1,"$NtpdateAD")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px' nowrap>{UseADAsNameServer}:</td>
		<td>". Field_checkbox_design("UseADAsNameServer",1,"$UseADAsNameServer")."</td>
		<td>&nbsp;</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:22px'>{authenticate_from_kerberos}:</td>
		<td>". Field_checkbox_design("EnableKerberosAuthentication",1,"$EnableKerberosAuthentication","EnableKerbAuthCheck()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_DNS_SUFFIX}:</td>
		<td>". Field_text("WINDOWS_DNS_SUFFIX",$array["WINDOWS_DNS_SUFFIX"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_SERVER_NETBIOSNAME}:</td>
		<td>". Field_text("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ADNETBIOSDOMAIN}:</td>
		<td>". Field_text("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>". help_icon("{howto_ADNETBIOSDOMAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ADNETIPADDR}:</td>
		<td>". field_ipv4("ADNETIPADDR",$array["ADNETIPADDR"],"font-size:22px")."</td>
		<td>". help_icon("{howto_ADNETIPADDR}")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_SERVER_TYPE}:</td>
		<td>". Field_array_Hash($severtype,"WINDOWS_SERVER_TYPE",$array["WINDOWS_SERVER_TYPE"],"style:font-size:22px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{COMPUTERS_BRANCH}:</td>
		<td>". Field_text("COMPUTER_BRANCH",$array["COMPUTER_BRANCH"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>	
	
	
	
	<tr>
		<td class=legend style='font-size:22px'>{database_backend}:</td>
			<td>". Field_array_Hash($arrayBCK,"SAMBA_BACKEND",$array["SAMBA_BACKEND"],"style:font-size:22px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$array["WINDOWS_SERVER_ADMIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$array["WINDOWS_SERVER_PASS"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveKERBProxy()",40)."</td>
	</tr>
	</table>
	</div>
	<script>
		function CheckHostname$t_tmp(){
		var domainz=trim(document.getElementById('WINDOWS_DNS_SUFFIX').value);
		thewhole='$Myhostname';
		var regexp = /([^.]+)\.(.*?)$/;
		var match = regexp.exec(thewhole);
		var domain = match[1];
		var ext = match[2];
		domainz=domainz.toLowerCase();
		domain=ext.toLowerCase();
		if(domain!==domainz){
			if(confirm('$error_dom1 '+domainz+' $error_dom2 ('+domain+')\\n$error_dom3')){
				Loadjs('system.nic.config.php?change-hostname-js=yes');
			}
			return false;
		}
		return true;
		}
		
		
	var x_EnableRECURSIVE_GROUPS= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		
	}		
	
	
		function EnableRECURSIVE_GROUPS(){
			var XHR = new XHRConnection();
			XHR.appendData('SAVE_RECURSIVE_GROUPS',document.getElementById('RECURSIVE_GROUPS').value);
			XHR.sendAndLoad('$page', 'POST',x_EnableRECURSIVE_GROUPS);
		}
	
		function EnableKerbAuthCheck(){
			
			var EnableKerbAuth=0;
			var EnableKerberosAuthentication=$EnableKerberosAuthentication;
			var LockKerberosAuthentication=$LockKerberosAuthentication;
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			var NTPDATE_INSTALLED=$NTPDATE_INSTALLED;
			var samba_installed=$samba_installed;
			document.getElementById('WINDOWS_DNS_SUFFIX').disabled=true;
			document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=true;
			document.getElementById('WINDOWS_SERVER_TYPE').disabled=true;
			document.getElementById('WINDOWS_SERVER_ADMIN').disabled=true;
			document.getElementById('WINDOWS_SERVER_PASS').disabled=true;
			document.getElementById('ADNETBIOSDOMAIN').disabled=true;
			document.getElementById('ADNETIPADDR').disabled=true;
			document.getElementById('SAMBA_BACKEND').disabled=true;
			document.getElementById('COMPUTER_BRANCH').disabled=true;
			document.getElementById('KerbAuthDisableNsswitch').disabled=true;
			document.getElementById('KerbAuthDisableGroupListing').disabled=true;
			document.getElementById('KerbAuthDisableNormalizeName').disabled=true;
			document.getElementById('KerbAuthMapUntrustedDomain').disabled=true;
			document.getElementById('NtpdateAD').disabled=true;
			document.getElementById('KerbAuthMethod').disabled=true;
			document.getElementById('SquidNTLMKeepAlive').disabled=true;
			document.getElementById('UseADAsNameServer').disabled=true;
			document.getElementById('SambaBindInterface').disabled=true;
			
			
			
			document.getElementById('KerbAuthTrusted').disabled=true;
			EnableKerbAuth=0;
			EnableKerbAuth=document.getElementById('EnableKerbAuth').value;
			
			
			if(LockKerberosAuthentication==0){
				if(document.getElementById('EnableKerberosAuthentication').checked){
					EnableKerbAuth=1;
					EnableKerberosAuthentication=1;
				}
			}
			
			
			if(EnableRemoteStatisticsAppliance==1){
				document.getElementById('EnableKerbAuth').disabled=true;
			}
			
			if(EnableKerbAuth==1){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('WINDOWS_DNS_SUFFIX').disabled=false;
					document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=false;
					document.getElementById('WINDOWS_SERVER_TYPE').disabled=false;
					document.getElementById('WINDOWS_SERVER_ADMIN').disabled=false;
					document.getElementById('WINDOWS_SERVER_PASS').disabled=false;							
					document.getElementById('ADNETBIOSDOMAIN').disabled=false;
					document.getElementById('ADNETIPADDR').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
					document.getElementById('COMPUTER_BRANCH').disabled=false;
					document.getElementById('KerbAuthDisableNsswitch').disabled=false;
					document.getElementById('KerbAuthDisableGroupListing').disabled=false;
					document.getElementById('KerbAuthDisableNormalizeName').disabled=false;
					document.getElementById('KerbAuthMapUntrustedDomain').disabled=false;
					document.getElementById('KerbAuthTrusted').disabled=false;
					document.getElementById('KerbAuthMethod').disabled=false;
					document.getElementById('SquidNTLMKeepAlive').disabled=false;
					document.getElementById('UseADAsNameServer').disabled=false;
					document.getElementById('SambaBindInterface').disabled=false;
					
					
					
					if(NTPDATE_INSTALLED==1){
						document.getElementById('NtpdateAD').disabled=false;
					
					}
					
					if(document.getElementById('EnableKerberosAuthentication').checked){
						document.getElementById('EnableKerbAuth').checked=false
						document.getElementById('EnableKerbAuth').disabled=true;
						document.getElementById('SAMBA_BACKEND').disabled=true;
					}else{
						document.getElementById('EnableKerbAuth').disabled=false;
						document.getElementById('SAMBA_BACKEND').disabled=false;
					}
					if(document.getElementById('EnableKerbAuth').checked){
						document.getElementById('EnableKerberosAuthentication').checked=false;
						document.getElementById('EnableKerberosAuthentication').disabled=true;
						document.getElementById('SAMBA_BACKEND').disabled=false;
					}else{
						document.getElementById('EnableKerberosAuthentication').disabled=false;
					}
				}			
			}
			
			if(document.getElementById('EnableKerbAuth').checked){
				if(samba_installed==1){
					if(EnableRemoteStatisticsAppliance==0){
						document.getElementById('ADNETBIOSDOMAIN').disabled=false;
					}
				}
			}
			
			if(!document.getElementById('EnableKerberosAuthentication').checked){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('EnableKerbAuth').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
				}
				
			}
			if(document.getElementById('EnableKerbAuth').checked){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('EnableKerberosAuthentication').disabled=false;
					document.getElementById('SAMBA_BACKEND').disabled=false;
				}
			}
			
			if(samba_installed==0){
				if(EnableRemoteStatisticsAppliance==0){
					document.getElementById('SAMBA_BACKEND').disabled=true;
					document.getElementById('EnableKerbAuth').disabled=true;
				}
			}
			
			if(LockKerberosAuthentication==1){
				document.getElementById('EnableKerberosAuthentication').disabled=true;
			}
			
			
		}
		
		function RefreshAll(){
			RefreshServerKerb();
		}
		
	var x_SaveKERBProxy= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('serverkerb-animated').innerHTML='';return;}
		RefreshServerKerb();
		document.getElementById('serverkerb-animated').innerHTML='';
		if(document.getElementById('AdSquidStatusLeft')){RefreshDansguardianMainService();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		Loadjs('squid.ad.progress.php');
	}	

	var x_SaveSambaBindInterface= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('serverkerb-animated').innerHTML='';return;}
		RefreshServerKerb();
		document.getElementById('serverkerb-animated').innerHTML='';
		if(document.getElementById('AdSquidStatusLeft')){RefreshDansguardianMainService();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		
	}	
	
	function SaveSambaBindInterface(){
		var XHR = new XHRConnection();
		XHR.appendData('SaveSambaBindInterface',document.getElementById('SambaBindInterface').value);
		AnimateDiv('serverkerb-animated');
		XHR.sendAndLoad('$page', 'POST',x_SaveSambaBindInterface);
	}
	
	
		function SaveKERBProxy(){
			if(!CheckHostname$t_tmp()){return;}
			var DisableSpecialCharacters=$DisableSpecialCharacters;
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			if(EnableRemoteStatisticsAppliance==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
			var pp=encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value);
			var XHR = new XHRConnection();
			
			if(document.getElementById('EnableKerberosAuthentication').checked){XHR.appendData('EnableKerberosAuthentication',1);}else{XHR.appendData('EnableKerberosAuthentication',0);}
			if(document.getElementById('KerbAuthDisableNsswitch').checked){XHR.appendData('KerbAuthDisableNsswitch',1);}else{XHR.appendData('KerbAuthDisableNsswitch',0);}
			if(document.getElementById('KerbAuthDisableGroupListing').checked){XHR.appendData('KerbAuthDisableGroupListing',1);}else{XHR.appendData('KerbAuthDisableGroupListing',0);}
			if(document.getElementById('KerbAuthDisableNormalizeName').checked){XHR.appendData('KerbAuthDisableNormalizeName',1);}else{XHR.appendData('KerbAuthDisableNormalizeName',0);}
			if(document.getElementById('KerbAuthTrusted').checked){XHR.appendData('KerbAuthTrusted',1);}else{XHR.appendData('KerbAuthTrusted',0);}
			if(document.getElementById('KerbAuthMapUntrustedDomain').checked){XHR.appendData('KerbAuthMapUntrustedDomain',1);}else{XHR.appendData('KerbAuthMapUntrustedDomain',0);}
			if(document.getElementById('NtpdateAD').checked){XHR.appendData('NtpdateAD',1);}else{XHR.appendData('NtpdateAD',0);}
			if(document.getElementById('SquidNTLMKeepAlive').checked){XHR.appendData('SquidNTLMKeepAlive',1);}else{XHR.appendData('SquidNTLMKeepAlive',0);}
			if(document.getElementById('UseADAsNameServer').checked){XHR.appendData('UseADAsNameServer',1);}else{XHR.appendData('UseADAsNameServer',0);}
			
			
			
			XHR.appendData('EnableKerbAuth',document.getElementById('EnableKerbAuth').value);
			XHR.appendData('KerbAuthMethod',document.getElementById('KerbAuthMethod').value);
			
			XHR.appendData('RECURSIVE_GROUPS',document.getElementById('RECURSIVE_GROUPS').value);
			XHR.appendData('SambaBindInterface',document.getElementById('SambaBindInterface').value);
			XHR.appendData('COMPUTER_BRANCH',document.getElementById('COMPUTER_BRANCH').value);
			XHR.appendData('SAMBA_BACKEND',document.getElementById('SAMBA_BACKEND').value);
			XHR.appendData('WINDOWS_DNS_SUFFIX',document.getElementById('WINDOWS_DNS_SUFFIX').value);
			XHR.appendData('WINDOWS_SERVER_NETBIOSNAME',document.getElementById('WINDOWS_SERVER_NETBIOSNAME').value);
			XHR.appendData('WINDOWS_SERVER_TYPE',document.getElementById('WINDOWS_SERVER_TYPE').value);
			XHR.appendData('WINDOWS_SERVER_ADMIN',document.getElementById('WINDOWS_SERVER_ADMIN').value);
			XHR.appendData('WINDOWS_SERVER_PASS',pp);
			XHR.appendData('ADNETBIOSDOMAIN',document.getElementById('ADNETBIOSDOMAIN').value);
			XHR.appendData('ADNETIPADDR',document.getElementById('ADNETIPADDR').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveKERBProxy);
		
		}
		
		
		
		
		EnableKerbAuthCheck();
		LoadAjax('kerbchkconf','$page?kerbchkconf=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	



function ldap_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$active=new ActiveDirectory();
	$sock=new sockets();
	$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}	
	$DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
	if(!is_numeric($DisableSpecialCharacters)){$DisableSpecialCharacters=0;}
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$t=time();
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
	if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
	if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
	if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
	if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
	if(!is_numeric($array["LDAP_RECURSIVE"])){$array["LDAP_RECURSIVE"]=0;}
	$html="
	<div id='serverkerb-$t'></div>
	<div class=text-info style='font-size:18px' nowrap>{ldap_ntlm_parameters_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{use_dynamic_groups_acls}:</td>
		<td>". Field_checkbox("UseDynamicGroupsAcls",1,$UseDynamicGroupsAcls,"UseDynamicGroupsAclsCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{TTL_CACHE}:</td>
		<td style='font-size:18px'>". Field_text("DynamicGroupsAclsTTL",$DynamicGroupsAclsTTL,"font-size:18px;padding:3px;width:90px")."&nbsp;{seconds}</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:18px' nowrap>{non_ntlm_domain}:</td>
		<td>". Field_text("LDAP_NONTLM_DOMAIN",$array["LDAP_NONTLM_DOMAIN"],"font-size:18px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>". Field_text("LDAP_SERVER",$array["LDAP_SERVER"],"font-size:18px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT",$array["LDAP_PORT"],"font-size:18px;padding:3px;width:90px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX",$array["LDAP_SUFFIX"],"font-size:18px;padding:3px;width:310px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN",$array["LDAP_DN"],"font-size:18px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$t",$array["LDAP_PASSWORD"],"font-size:18px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{recursive}:</td>
		<td>". Field_checkbox("LDAP_RECURSIVE-$t",1,$array["LDAP_RECURSIVE"])."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveLDAPADker()",26)."</td>
	</tr>
	</table>
<script>
	var x_SaveLDAPADker= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('serverkerb-$t').innerHTML='';return;}
		document.getElementById('serverkerb-$t').innerHTML='';
		YahooSearchUserHide();
		
	}		
	
		function SaveLDAPADker(){
			var UseDynamicGroupsAcls=0;
			var DisableSpecialCharacters=$DisableSpecialCharacters;
			var pp=encodeURIComponent(document.getElementById('LDAP_PASSWORD-$t').value);
			var XHR = new XHRConnection();
			if(document.getElementById('UseDynamicGroupsAcls').checked){UseDynamicGroupsAcls=1;}
			XHR.appendData('UseDynamicGroupsAcls',UseDynamicGroupsAcls);
			XHR.appendData('DynamicGroupsAclsTTL',document.getElementById('DynamicGroupsAclsTTL').value);
			
			XHR.appendData('LDAP_NONTLM_DOMAIN',document.getElementById('LDAP_NONTLM_DOMAIN').value);
			XHR.appendData('LDAP_SERVER',document.getElementById('LDAP_SERVER').value);
			XHR.appendData('LDAP_PORT',document.getElementById('LDAP_PORT').value);
			XHR.appendData('LDAP_SUFFIX',document.getElementById('LDAP_SUFFIX').value);
			XHR.appendData('LDAP_DN',document.getElementById('LDAP_DN').value);
			if(document.getElementById('LDAP_RECURSIVE-$t').checked){XHR.appendData('LDAP_RECURSIVE',1);}else{XHR.appendData('LDAP_RECURSIVE',0);}
			XHR.appendData('LDAP_PASSWORD',pp);
			AnimateDiv('serverkerb-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveLDAPADker);
		
		}
		
		function UseDynamicGroupsAclsCheck(){
			document.getElementById('DynamicGroupsAclsTTL').disabled=true;
			if(document.getElementById('UseDynamicGroupsAcls').checked){
				document.getElementById('DynamicGroupsAclsTTL').disabled=false;
			}
		}
		UseDynamicGroupsAclsCheck();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function ldap_params_save(){
	$sock=new sockets();
	$tpl=new templates();
	$sock->SET_INFO("DynamicGroupsAclsTTL", $_POST["DynamicGroupsAclsTTL"]);
	$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($_POST["UseDynamicGroupsAcls"]==1){
		if($_POST["LDAP_SERVER"]==null){echo $tpl->javascript_parse_text("LDAP: {hostname} Not set\n");return;}
		if(!is_numeric($_POST["LDAP_PORT"])){echo $tpl->javascript_parse_text("LDAP: {ldap_port} Not set\n");return;}			
		if($_POST["LDAP_SUFFIX"]==null){echo $tpl->javascript_parse_text("LDAP: {suffix} Not set\n");return;}
		if($_POST["LDAP_DN"]==null){echo $tpl->javascript_parse_text("LDAP: {bind_dn} Not set\n");return;}
		if($_POST["LDAP_PASSWORD"]==null){echo $tpl->javascript_parse_text("LDAP: {password} Not set\n");return;}		
		
	}
	
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){
		$array[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
	
	$ldap_connection=@ldap_connect($_POST["LDAP_SERVER"],$_POST["LDAP_PORT"]);
	if(!$ldap_connection){
		echo "Connection Failed to connect to DC ldap://{$_POST["LDAP_SERVER"]}:{$_POST["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}		
		@ldap_close();
		return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $_POST["LDAP_DN"],$_POST["LDAP_PASSWORD"]);
	if(!$bind){
		
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}
		
		echo "Failed to login to DC {$_POST["LDAP_SERVER"]} - {$_POST["LDAP_DN"]} \n`$error`";
		return false;
	}	
	
	
	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("BUILDCONF");
		return;
	}
	
	
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
}

function SaveSambaBindInterface(){
	$sock=new sockets();
	$sock->SET_INFO("SambaBindInterface", $_POST["SaveSambaBindInterface"]);
	$sock->getFrameWork("squid.php?samba-proxy=yes");
}

function SAVE_RECURSIVE_GROUPS(){
	$sock=new sockets();
	$tpl=new templates();
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$ArrayKerbAuthInfos["RECURSIVE_GROUPS"]=$_POST["SAVE_RECURSIVE_GROUPS"];
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	$en="{disabled}";
	if($_POST["SAVE_RECURSIVE_GROUPS"]==1){
		$en="{enabled}";
	}
	
	
	$sock->getFrameWork("squid.php?reconfigure-squid=yes");
	echo $tpl->javascript_parse_text("{recursive_group_is} $en");
	
}

function settingsSave(){
	include_once(dirname(__FILE__)."/ressources/externals/Net_DNS2/DNS2.php");
	include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
	$ipClass=new IP();
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	
	if(strpos($_POST["WINDOWS_SERVER_ADMIN"], "@")>0){
		$trx=explode("@",$_POST["WINDOWS_SERVER_ADMIN"]);
		$_POST["WINDOWS_SERVER_ADMIN"]=$trx[0];
	}
	
	unset($_SESSION["EnableKerbAuth"]);
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	
	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	
	if($_POST["WINDOWS_DNS_SUFFIX"]==null){
		echo "Please set the DNS domain of your Active Directory server";
		return;
	}
	
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");	
	$MyhostnameTR=explode(".", $Myhostname);
	$MyNetbiosName=$MyhostnameTR[0];
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));
	if($MyDomain<>$_POST["WINDOWS_DNS_SUFFIX"]){
		$nic=new system_nic();
		$nic->set_hostname("$MyNetbiosName.{$_POST["WINDOWS_DNS_SUFFIX"]}");
	}
	
	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	
	
	if(strtolower($adhost)==strtolower($Myhostname)){
		echo "Active Directory: $adhost as the same name of this server:$Myhostname\n";return;
		
	}
	
	if($_POST["ADNETIPADDR"]<>null){
		$ipaddrZ=explode(".",$_POST["ADNETIPADDR"]);
		while (list ($num, $a) = each ($ipaddrZ) ){
			$ipaddrZ[$num]=intval($a);
		}
		$_POST["ADNETIPADDR"]=@implode(".", $ipaddrZ);
	}
	
	$resolved=gethostbyname($adhost);
	if(!$ipClass->isValid($resolved)){
		if($ipClass->isValid($_POST["ADNETIPADDR"])){
			$resolved=CheckDNS($adhost,$_POST["ADNETIPADDR"]);
			if($ipClass->isValid($resolved)){$_POST["UseADAsNameServer"]=1;}
		}
	}
	

	
	
	
	
	
	$sock->SET_INFO("SambaBindInterface", $_POST["SambaBindInterface"]);
	$sock->SET_INFO("KerbAuthDisableNormalizeName", $_POST["KerbAuthDisableNormalizeName"]);
	$sock->SET_INFO("EnableKerberosAuthentication", $_POST["EnableKerberosAuthentication"]);
	$sock->SET_INFO("KerbAuthDisableNsswitch", $_POST["KerbAuthDisableNsswitch"]);
	$sock->SET_INFO("KerbAuthDisableGroupListing", $_POST["KerbAuthDisableGroupListing"]);
	$sock->SET_INFO("KerbAuthTrusted", $_POST["KerbAuthTrusted"]);
	$sock->SET_INFO("KerbAuthMapUntrustedDomain", $_POST["KerbAuthMapUntrustedDomain"]);
	$sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);
	$sock->SET_INFO("KerbAuthMethod", $_POST["KerbAuthMethod"]);
	$sock->SET_INFO("SquidNTLMKeepAlive", $_POST["SquidNTLMKeepAlive"]);
	$sock->SET_INFO("UseADAsNameServer", $_POST["UseADAsNameServer"]);
	$sock->SET_INFO("NET_RPC_INFOS",base64_encode(serialize(array())));
	if($_POST["EnableKerberosAuthentication"]==1){$sock->SET_INFO("EnableKerbAuth", 0);}
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){$ArrayKerbAuthInfos[$num]=$ligne;}
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	
	
	
	
		
	if($_POST["UseADAsNameServer"]==1){
		$resolve=new resolv_conf();
		$resolve->MainArray["DNS1"]=$_POST["ADNETIPADDR"];
		$resolve->save();
		
		$resolved=CheckDNS($adhost,$_POST["ADNETIPADDR"]);
		if(!$ipClass->isValid($resolved)){
			
			echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} Active Directory: $adhost {with} {$_POST["ADNETIPADDR"]}",1);
			return;
		}
		
		
	}else{
		$resolved=gethostbyname($adhost);
		if(!$ipClass->isValid($resolved)){
			$tpl=new templates();
			if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
			$sock->SET_INFO("EnableKerberosAuthentication", 0);
			$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
			echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} Active Directory: $adhost",1);
			return;
		}
		
		if($resolved=="127.0.0.1"){
			echo $tpl->javascript_parse_text("{error}: $adhost lookup to 127.0.0.1 !\n");;
			return;
		}
		
		
	}
	
	
	if(strpos($_POST["ADNETBIOSDOMAIN"], ".")>0){
		echo "The netbios domain \"{$_POST["ADNETBIOSDOMAIN"]}\" is invalid.\n";
		$sock->SET_INFO("EnableKerbAuth", 0);
		return;
	}
	
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	
}

function CheckDNS($hostname,$dns){

	
	$ipClass=new IP();
	$rs = new Net_DNS2_Resolver(array('nameservers' => array($dns)));
	try {
		$result = $rs->query($hostname, "A");
			
	} catch(Net_DNS2_Exception $e) {
		echo $e->getMessage();
		return null;
	}
	
	foreach($result->answer as $record){
		if($ipClass->isIPAddress($record->address)){return $record->address;}
	}
	
	
	
}


function SambeReconnectAD(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
	if(!is_numeric($EnableKerberosAuthentication)){$EnableKerberosAuthentication=0;}
	$sock->getFrameWork("services.php?kerbauth=yes");
	if($EnableKerberosAuthentication==0){
		$sock->getFrameWork("services.php?nsswitch=yes");
		$sock->getFrameWork("cmd.php?samba-reconfigure=yes");
	}

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("WINBIND_RECONFIGURE");
	}	
	
}

function test_auth(){
	include_once(dirname(__FILE__)."ressources/class.system.network.inc");
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr=null;}
	$squid=new squidbee();
	$port=$squid->listen_port;
	$t=time();
	if($SquidBinIpaddr==null){
		$ip=new networking();
		$ips=$ip->ALL_IPS_GET_ARRAY();	
		while (list ($num, $ligne) = each ($ips) ){if($num==null){continue;}if($num=="127.0.0.1"){continue;}$net[]=$num;}
		$SquidBinIpaddr=$net[0];
	}
	
	if (!extension_loaded('curl')) {echo "<H2>Fatal curl extension not loaded</H2>";die();}

	$html="
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px'>{proxy}:</td>
		<td style='font-size:24px'>$SquidBinIpaddr:$port</td>
	</tr>
	<tr>
	<tr>
		<td class=legend  style='font-size:24px'>{username}:</td>
		<td>". Field_text("TESTAUTHUSER",$array["WINDOWS_SERVER_ADMIN"],"font-size:24px;padding:3px;width:490px")."</td>
	</tr>		
	<tr>
		<td class=legend  style='font-size:24px'>{password}:</td>
		<td>". Field_password("TESTAUTHPASS",$array["WINDOWS_SERVER_PASS"],"font-size:24px;padding:3px;width:490px")."</td>
	</tr>	
					
	<tr>
		<td colspan=2 align='right' style='padding-top:70px'>". button("{submit}","TestAuthPerform()",32)."</td>
	</tr>
	</table>
	</div>
	<div id='test-$t'></div>
	<script>
	var x_TestAuthPerform= function (obj) {
			LoadAjax('test-$t','$page?testauth-results=yes');	
		
	
			
			
		}		
		
		function TestAuthPerform(){
			var pp=encodeURIComponent(document.getElementById('TESTAUTHPASS').value);
			var XHR = new XHRConnection();
			XHR.appendData('TESTAUTHUSER',document.getElementById('TESTAUTHUSER').value);
			XHR.appendData('TESTPROXYIP','$SquidBinIpaddr');
			XHR.appendData('TESTPROXYPORT','$port');
			XHR.appendData('TESTAUTHPASS',pp);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_TestAuthPerform);
		
		}
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_auth_perform(){
	$tpl=new templates();
	
	$SquidBinIpaddr=$_POST["TESTPROXYIP"];
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
	$port=$_POST["TESTPROXYPORT"];
	$TESTAUTHPASS=url_decode_special_tool($_POST["TESTAUTHPASS"]);
	$TESTAUTHUSER=stripslashes($_POST["TESTAUTHUSER"]);
	$array["BIND"]="$SquidBinIpaddr";
	$array["PROXY"]="$SquidBinIpaddr:$port";
	$array["USER"]=$TESTAUTHUSER;
	$array["PASS"]=$TESTAUTHPASS;
	
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/NTLM_TESTS", serialize($array));
	$sock=new sockets();
	$sock->getFrameWork("squid.php?test-ntlm=yes");
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_INTERFACE,$SquidBinIpaddr);
	curl_setopt($ch, CURLOPT_URL, "http://www.google.com");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
	curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
	curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
	curl_setopt ($ch, CURLOPT_PROXY,"$SquidBinIpaddr:$port");
	curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
	
	
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $TESTAUTHUSER.':'.$TESTAUTHPASS);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));		
	curl_setopt($ch, CURLOPT_NOBODY, true);
	$data=curl_exec($ch);
	
	if(preg_match("#X-Squid-Error:.*?([A-Z\_]+)#is", $data,$re)){echo "****  FAILED WITH ERROR \"{$re[1]}\" ***\n\n";}
	if(preg_match("#Proxy-Authenticate: NTLM\s+(.*?)\s+#",$data,$re)){$data=str_replace($re[1], "***", $data);}
	$error=curl_errno($ch);		
	$curl=new ccurl(null);
	
	if(!$curl->ParseError($error)){
		echo $error_text=$tpl->javascript_parse_text($curl->error)."\n";
	}
	$info = curl_getinfo($ch);
	curl_close($ch);	
	if(is_array($info)){
		while (list ($num, $ligne) = each ($info) ){
			$infos[]="$num: $ligne";
		}
	}
	$sep="\n------------------------------------------------------\n";
	echo "http://www.google.com return error $error$sep Datas:$sep$data\nInfos:$sep".@implode("\n", $infos);
}


function kerbchkconf(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$users=new usersMenus();
	if($users->SAMBA_INSTALLED){
		$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
		echo $tpl->_ENGINE_parse_body("<center><div style='font-size:18px'>{APP_SAMBA}:$samba_version</div></center>");
	}else{
		echo $tpl->_ENGINE_parse_body("<center><div style='font-size:18px'>{APP_SAMBA}: {NOT_INSTALLED}</div></center>");
	}
	
	
	if(!$users->MSKTUTIL_INSTALLED){echo $tpl->_ENGINE_parse_body(Paragraphe32("APP_MSKTUTIL", "APP_MSKTUTIL_NOT_INSTALLED", "Loadjs('setup.index.php?js=yes');", "error-24.png"));return;}
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	if($users->SAMBA_INSTALLED){if($array["ADNETBIOSDOMAIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "ADNETBIOSDOMAIN", null, "error-24.png"));return;}}
	
	
	if($array["WINDOWS_DNS_SUFFIX"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_DNS_SUFFIX", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_NETBIOSNAME", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_TYPE"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "WINDOWS_SERVER_TYPE", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "administrator", null, "error-24.png"));return;}
	if($array["WINDOWS_SERVER_PASS"]==null){echo $tpl->_ENGINE_parse_body(Paragraphe32("MISSING_PARAMETER", "password", null, "error-24.png"));return;}
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){echo $tpl->_ENGINE_parse_body(Paragraphe32("WINDOWS_NAME_SERVICE_NOT_KNOWN", "noacco:<strong style='font-size:18px'>$hostname</strong>", null, "error-24.png"));return;}
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if($EnableKerbAuth==1){
		$page=CurrentPageName();
		echo $tpl->_ENGINE_parse_body("<center style='margin:5px'>".button("{restart_connection}",
		 "Loadjs('squid.ad.progress.php')","22px")."</center>");
	}
	
	
}

function schedule_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	
	$AdSchBuildProxy=$sock->GET_INFO("AdSchBuildProxy");
	$AdSchBuildUfdb=$sock->GET_INFO("AdSchBuildUfdb");
	$AdSchRestartSquid=$sock->GET_INFO("AdSchRestartSquid");
	if(!is_numeric($AdSchBuildProxy)){$AdSchBuildProxy=0;}
	if(!is_numeric($AdSchBuildUfdb)){$AdSchBuildUfdb=0;}
	if(!is_numeric($AdSchRestartSquid)){$AdSchRestartSquid=0;}
	$html="<div class='explain' style='font-size:18px'>
	{ad_kerb_schedule_explain}
	</div>
	<div id='test-$t'></div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{build_proxy_parameters}:</td>
		<td>". Field_checkbox("AdSchBuildProxy", 1,$AdSchBuildProxy)."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{build_web_filtering_rules}:</td>
		<td>". Field_checkbox("AdSchBuildUfdb", 1,$AdSchBuildUfdb)."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{restart_the_web_proxy_service}:</td>
		<td>". Field_checkbox("AdSchRestartSquid", 1,$AdSchRestartSquid)."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "AdSchBuildProxy$t()","16px")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_AdSchBuildProxy$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('test-$t').innerHTML='';
			
		}		
		
		function AdSchBuildProxy$t(){
			var XHR = new XHRConnection();
			AdSchBuildProxy=0;
			AdSchBuildUfdb=0;
			AdSchRestartSquid=0;
			if(document.getElementById('AdSchBuildProxy').checked){AdSchBuildProxy=1;}
			if(document.getElementById('AdSchBuildUfdb').checked){AdSchBuildUfdb=1;}
			if(document.getElementById('AdSchRestartSquid').checked){AdSchRestartSquid=1;}
			
			XHR.appendData('AdSchRestartSquid',AdSchRestartSquid);
			XHR.appendData('AdSchBuildUfdb',AdSchBuildUfdb);
			XHR.appendData('AdSchBuildProxy',AdSchBuildProxy);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_AdSchBuildProxy$t);
		
		}		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function schedule_save(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO("$num",$ligne);
	}
}


function cntlm(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	if(!$users->CNTLM_INSTALLED){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{CNTLM_NOT_INSTALLED}")."</p>";
	}
	$t=time();
	
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	
	$html="
	<div id='test-$t'></div>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:250px' nowrap><div id='cntml-status'></div></td>
		<td valign='top' style='width:100%'>
	<div style='width:98%' class=form>
	
	". Paragraphe_switch_img("{activate_CNTLM_service}", "{APP_CNTLM_EXPLAIN}",
			"EnableCNTLM",$EnableCNTLM,null,780)."	
	<table>

	<tr>
		<td valign='top' class=legend style='font-size:32px'>{listen_port}:</td>
		<td>". Field_text("CnTLMPORT", $CNTLMPort,"font-size:32px;width:110px")."</td>
		<td width=1%>". help_icon("{CnTLMPORT_explain2}")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "CNTLMSave$t()","40px")."</td>
	</tr>
	</table>
	</div>
	</td>
	</tr>
	</table>
	<script>
	var xCNTLMSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('cntlm.restart.progress.php');
		RefreshTab('squid_main_svc');
	}
	function CNTLMSave$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableCNTLM',document.getElementById('EnableCNTLM').value);
		XHR.appendData('CnTLMPORT',document.getElementById('CnTLMPORT').value);
		XHR.sendAndLoad('$page', 'POST',xCNTLMSave$t);
	
	}
	
	LoadAjax('cntml-status','$page?cntml-status=yes');
	
	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function cntlm_status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$APP_CNTLM=DAEMON_STATUS_ROUND("APP_CNTLM",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($APP_CNTLM);
}

function EnableCNTLM_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableCNTLM", $_POST["EnableCNTLM"]);
	$sock->SET_INFO("CnTLMPORT", $_POST["CnTLMPORT"]);
	//$sock->getFrameWork("squid.php?cntlm-restart=yes");
	
}

function test_auth_results(){
	$page=CurrentPageName();
	$tpl=new templates();
	$TESTS=false;
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/NTLM_RESULTS"));
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#(Proxy-Authorization|Proxy-Authenticate)#", $ligne)){continue;}
		if(preg_match("#HTTP\/[0-9\.]+\s+([0-9]+)\s+(.+)#", $ligne,$re)){
			$T[]="<hr>";
			$CODE_ERROR=$re[1];
			$CODE_TEXT=$re[2];
			if(intval($CODE_ERROR)<400){$TESTS=true;}
		}
		$T[]="<div><code style='font-size:12px'>".htmlspecialchars($ligne)."</code></div>";
		
	}
	
	$img="64-red.png";
	$img_text="{failed}";
	
	if($TESTS){
		$img="ok64.png";
		$img_text="{success}";
	}
	
	$html="
	<div style='width:98%' class=form>
	<center style='font-size:22px'>
		<div style='width:98%' class=form>
		<table style='width:40%'>
		<tr><td style='width:64px'><img src='img/$img' style='margin:15px;'></td><td style='font-size:22px'>$img_text
		<div style='font-size:14px;width:99%'>$CODE_TEXT ( $CODE_ERROR )</div>
		</td>
		</tr>
		</table>
		</div>
	</center>	
	".@implode($T)."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

