<?php
session_start();
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");


if(!isset($_SESSION["uid"])){if(isset($_SERVER["PHP_AUTH_USER"])){BuildSession($_SERVER["PHP_AUTH_USER"]);}}


//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_SESSION["uid"])){header('location:miniadm.index.php');die();}
if(isset($_POST["username"])){checklogon();exit;}
if(isset($_GET["credentials"])){checklogonCreds();exit;}
if(isset($_GET["js"])){js();exit;}
$t=time();
$page=CurrentPageName();
$content=@file_get_contents("ressources/templates/endusers/logon.html");
$button=button("{logon}", "Loadjs('$page?js=yes')","18px");


$content=str_replace("{TEMPLATE_TITLE_HEAD}", $_SERVER["SERVER_NAME"], $content);
$content=str_replace("{LOGON_BUTTON}",$button, $content);
$content=str_replace("{SCRIPTS}","Loadjs('$page?js=yes&t=$t');", $content);
$tpl=new templates();
$content=$tpl->_ENGINE_parse_body($content);
echo $content;



function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$t=time();

	$html="

var x_SendLogonButton$t = function (obj) {
	var response=obj.responseText;
	if(response.length>3){alert(response);return;}
	document.location.href='miniadm.index.php';
}

function SendLogonButton$t(){
	var username=document.getElementById('artica_username').value;
	var password=MD5(document.getElementById('artica_password').value);
	var XHR = new XHRConnection();
	XHR.appendData('username',username);
	XHR.appendData('password',password);
	XHR.sendAndLoad('$page', 'POST',x_SendLogonButton$t);

}

	

SendLogonButton$t();
";

echo $html;
	
	
}

function checklogonCreds(){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	$FixedLanguage=null;

	if(!isset($_GET["credentials"])){
		header("location:".basename(__FILE__));
		return;
	}
	$array=unserialize(base64_decode($_GET["credentials"]));
	
	
	
	$username=$array["USERNAME"];
	$password=$array["PASSWORD"];
	
	$u=new user($username);
	$tpl=new templates();
	$userPassword=$u->password;
	if(trim($u->uidNumber)==null){header("location:".basename(__FILE__));return;}	
	if( trim($password)<>md5(trim($userPassword))){header("location:".basename(__FILE__));return;}	
	
	$ldap=new clladp();
	$users=new usersMenus();
	$_SESSION["CORP"]=$users->CORP_LICENSE;
	$privs=new privileges($u->uid);
	$privs->SearchPrivileges();
	$privileges_array=$privs->privs;
	$_SESSION["privileges_array"]=$privs->privs;
	if(!isset($privileges_array["VIRTUALS_SERVERS"])){$privileges_array["VIRTUALS_SERVERS"]=array();}
	$_SESSION["privs"]=$privileges_array;
	if(isset($privileges_array["ForceLanguageUsers"])){$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];}
	$_SESSION["uid"]=$username;
	$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
	$_SESSION["VIRTUALS_SERVERS"]=$privileges_array["VIRTUALS_SERVERS"];
	$_SESSION["POSTFIX_SERVERS"]=$privileges_array["POSTFIX_SERVERS"];
	
	$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
	$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
	$_SESSION["MailboxActive"]=$u->MailboxActive;
	$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
	$_SESSION["ou"]=$u->ou;
	$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
	$_SESSION["AsWebStatisticsAdministrator"]=$users->AsWebStatisticsAdministrator;
	if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]=null;}
	
			if(trim($_SESSION["OU_LANG"])<>null){
				$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
			}else{
				include_once(dirname(__FILE__)."/ressources/class.langages.inc");
				$lang=new articaLang();
				$_SESSION["detected_lang"]=$lang->get_languages();
			}
			if(trim($FixedLanguage)<>null){$_SESSION["detected_lang"]=$FixedLanguage;}	
			
			include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
			$cr=new TranslateRights(null, null);
			$r=$cr->GetPrivsArray();
			while (list ($key, $val) = each ($r) ){if($users->$key){$_SESSION[$key]=$users->$key;}}
				
			if(is_array($_SESSION["privs"])){
				$r=$_SESSION["privs"];
				while (list ($key, $val) = each ($r) ){
					$_SESSION[$key]=$val;
				}
			}			
			
			
	header("location:miniadm.index.php");
}

function checklogon(){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	$FixedLanguage=null;
	$username=$_POST["username"];
	$password=$_POST["password"];
	$u=new user($username);
	$tpl=new templates();
	
	$userPassword=$u->password;
	if(trim($u->uidNumber)==null){
		writelogs('Unable to get user infos abort',__FUNCTION__,__FILE__);
		echo "Unknown user";
		die();
	}
	
	if( trim($password)<>md5(trim($userPassword))){
		writelogs("[{$_POST["username"]}]: The password typed  is not the same in ldap database...",__FUNCTION__,__FILE__);
		artica_mysql_events("Failed to logon on the management console as user `$username` from {$_SERVER["REMOTE_HOST"]} (bad password)",@implode("\n",$notice),"security","security");
		echo "Error: (".__LINE__.") bad password";
		return null;		
		
	}
	
	
	
			$ldap=new clladp();
			$users=new usersMenus();
			$_SESSION["CORP"]=$users->CORP_LICENSE;
			
			$privs=new privileges($u->uid);
			$privs->SearchPrivileges();
			$privileges_array=$privs->privs;

			$_SESSION["privileges_array"]=$privs->privs;
			$_SESSION["privs"]=$privileges_array;
			if(isset($privileges_array["ForceLanguageUsers"])){$_SESSION["OU_LANG"]=$privileges_array["ForceLanguageUsers"];}
			$_SESSION["uid"]=$username;
			$_SESSION["privileges"]["ArticaGroupPrivileges"]=$privs->content;
			$_SESSION["groupid"]=$ldap->UserGetGroups($_POST["username"],1);
			$_SESSION["DotClearUserEnabled"]=$u->DotClearUserEnabled;
			$_SESSION["MailboxActive"]=$u->MailboxActive;
			$_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
			$_SESSION["ou"]=$u->ou;
			$_SESSION["UsersInterfaceDatas"]=trim($u->UsersInterfaceDatas);
			include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
			$cr=new TranslateRights(null, null);
			$r=$cr->GetPrivsArray();
			while (list ($key, $val) = each ($r) ){
				
				if($users->$key){$_SESSION[$key]=$users->$key;}}
			
			if(is_array($_SESSION["privs"])){
				$r=$_SESSION["privs"];
				while (list ($key, $val) = each ($r) ){
					$t[$key]=$val;
					$_SESSION[$key]=$val;
				}
			}
			
			
			
			if(!isset($_SESSION["OU_LANG"])){$_SESSION["OU_LANG"]=null;}
			if(!isset($_SESSION["ASDCHPAdmin"])){$_SESSION["ASDCHPAdmin"]=false;}
			
		
			if(trim($_SESSION["OU_LANG"])<>null){
				$_SESSION["detected_lang"]=$_SESSION["OU_LANG"];
			}else{
				include_once(dirname(__FILE__)."/ressources/class.langages.inc");
				$lang=new articaLang();
				$_SESSION["detected_lang"]=$lang->get_languages();
			}
			if(trim($FixedLanguage)<>null){$_SESSION["detected_lang"]=$FixedLanguage;}
}

