#!/usr/bin/php
<?php
//error_reporting(0);
if(preg_match("#--verbose#", @implode(" ", $argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);error_reporting(1);
	error_reporting(1);
	$GLOBALS["VERBOSE"]=true;
	echo "VERBOSED MODE\n";
}
  define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
  $GLOBALS["SplashScreenURI"]=null;
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
  $GLOBALS["TIMELOG"]=0;
  $GLOBALS["QUERIES_NUMBER"]=0;
  $GLOBALS["TIMELOG_TIME"]=time();

  if($argv[1]=="--db"){ufdbguard_checks($argv[2]);	die(0);}
  LoadSettings();

  $max_execution_time=ini_get('max_execution_time'); 
  $GLOBALS["SESSIONS"]=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
  WLOG("[START]: Starting New process with KerbAuthInfos:".count($GLOBALS["KerbAuthInfos"])." Parameters");
  ConnectToLDAP();
  if($argv[1]=="--groups"){$GLOBALS["VERBOSE"]=true;$GROUPZ=GetGroupsFromMember($argv[2]);print_r($GROUPZ);die();}
  
  
while (!feof(STDIN)) {
 $content = trim(fgets(STDIN));
 
 if($content<>null){
 	//WLOG("receive content...$content");
 	$array=explode(" ",$content);
 	$member=trim($array[0]);
 	$group=strtolower($array[1]);
 	//WLOG("GetGroupsFromMember($member) -> `$member`");
 	$GROUPZ=GetGroupsFromMember($member);
 	
 	//WLOG("Checking $group ? {$GROUPZ[$member][$group]}");
 	
 	if($GLOBALS["TIMELOG"]>9){
 		$distanceInSeconds = round(abs(time() - $GLOBALS["TIMELOG_TIME"]));
 		$distanceInMinutes = round($distanceInSeconds / 60);
 		WLOG("[SEND]: 10 queries in {$distanceInMinutes}Mn");
 		$GLOBALS["TIMELOG"]=0;
 		$GLOBALS["TIMELOG_TIME"]=time();
 	}
 	
 	if(isset($GROUPZ[$group])){
 		WLOG("[SEND]: <span style='font-weight:bold;color:#00B218'>OK</span> &laquo;$member&raquo; is a member of &laquo;$group&raquo;");
 		fwrite(STDOUT, "OK\n");
 		continue;
 	}

 	//WLOG("$member is not a member of $group");
 	fwrite(STDOUT, "ERR\n");

	}
}

CleanSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
if($GLOBALS["CONNECTION"]){
	WLOG("[STOP]: Stopping process: shutdown LDAP connections...");
	@ldap_close($GLOBALS["CONNECTION"]);
}
WLOG("[STOP]: <span style='color:#002FB2'>Stopping process v1.2: After ({$distanceInSeconds}s - about {$distanceInMinutes}mn)</span>");
WLOG("[STOP]: <span style='color:#002FB2'>This process was query the LDAP server <strong>{$GLOBALS["QUERIES_NUMBER"]}</strong> times...</span>");



if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}

function LoadSettings(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
	if(!isset($GLOBALS["LoadSettingsFailed"])){$GLOBALS["LoadSettingsFailed"]=0;}
	$fh = fopen('/etc/artica-postfix/settings/Daemons/KerbAuthInfos', 'r');
	if(!$fh){
			$GLOBALS["LoadSettingsFailed"]++;
			usleep(5000);
			if($GLOBALS["LoadSettingsFailed"]<10){
				LoadSettings();
			}
		return;}
	$data = fread($fh, filesize('/etc/artica-postfix/settings/Daemons/KerbAuthInfos'));
	fclose($fh);
	if($GLOBALS["VERBOSE"]){echo "############################\n$data\n############################\n";}	
	$decoded=base64_decode($data);
	if($GLOBALS["VERBOSE"]){echo "############################\n$decoded\n############################\n";}
	$unser=unserialize($decoded);
	$GLOBALS["KerbAuthInfos"]=$unser;
	return $GLOBALS["KerbAuthInfos"];
}

function GetGroupsFromMember($member){
	$GLOBALS["TIMELOG"]++;
	ConnectToLDAP();
	
	if(!$GLOBALS["BIND"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: BIND is broken -> reconnect</strong>");
		ConnectToLDAP();
		if(!$GLOBALS["BIND"]){WLOG("[QUERY]: <strong style='color:red'>Error: BIND pointer is false</strong>");return false;}
	}
	
	if(!$GLOBALS["CONNECTION"]){
			WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is broken -> reconnect twice</strong>");
			ConnectToLDAP();
	}
	
	if(!$GLOBALS["CONNECTION"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is definitively broken aborting !!!...</strong>");
		return false;
	}
	
	$array=array();
	//$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
	$filter=array("memberOf");
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	$searchFilter="(&(objectCategory=Person)(objectClass=user)(sAMAccountName=$member))";
	
	$GLOBALS["QUERIES_NUMBER"]++;
	
	$sr =@ldap_search($GLOBALS["CONNECTION"],$GLOBALS["SUFFIX"],"$searchFilter",$filter,null, 10, 10);
	if (!$sr) {
		if(is_numeric(ldap_errno($GLOBALS["CONNECTION"]))){
			$error=ldap_errno($GLOBALS["CONNECTION"]);
			$errstr=@ldap_err2str($error);
			
			if($error==-1){
				if(!isset($GLOBALS["RETRY_AFTER_ERROR"])){
					WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> re-connect and retry query...");
					$GLOBALS["RETRY_AFTER_ERROR"]=true;
					return GetGroupsFromMember($member);
				}else{
					WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> Connection lost definitively");
					return false;
				}
				
			}
			
			WLOG("[QUERY]: <strong style='color:red'>Error:`$error` ($errstr)</strong> suffix:{$GLOBALS["SUFFIX"]} $searchFilter, return no user");
			return false;
		}else{
			WLOG("[QUERY]: <strong style='color:red'>Error: unknown Error (ldap_errno not a numeric) suffix:{$GLOBALS["SUFFIX"]} $searchFilter, return no user");
		}
	}
	
	
	
	
	$hash=ldap_get_entries($GLOBALS["CONNECTION"],$sr);
	if(!is_array($hash)){
		WLOG("[QUERY]: <strong style='color:red'>Error: undefined, hash is not an array or did not find user</strong>...");
		return false;
	}	
	
	
	unset($GLOBALS["RETRY_AFTER_ERROR"]);
	if(isset($hash[0]["memberof"])){
		//WLOG("{$hash[0]["memberof"]["count"]} items");
		for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
			if(preg_match("#^CN=(.+?),#i", $hash[0]["memberof"][$i],$re)){
				$re[1]=trim(strtolower($re[1]));
				$array[$re[1]]=true;
			}
			
		}
	}
	//WLOG("Search done");
	
	return $array;
	
}

function MemberInfoByDN($dn){
	if(!TestConnectToLDAP()){
		if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: TestConnectToLDAP() -> FALSE\n";}
		return null;
	}
	$searchFilter="(objectClass=*)";
	$filter=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","objectClass","member","memberOf");
	$sr =@ldap_search($GLOBALS["CONNECTION"],$dn,"$searchFilter",$filter,null, null, 10);
	if (!$sr) {WLOG("[QUERY]: MemberInfoByDN()::Bad search $dn / $searchFilter");return null;}
	$hash=ldap_get_entries($GLOBALS["CONNECTION"],$sr);
	if(!is_array($hash)){WLOG("[QUERY]: MemberInfoByDN():: Not an array $dn / $searchFilter");return null;}
	$AsGroup=false;




	for($i=0;$i<$hash[0]["objectclass"]["count"];$i++){
		$class=$hash[0]["objectclass"][$i];
		if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN()::$dn::objectclass -> $class\n";}
		if($class=="group"){$AsGroup=true;break;}
	}




	if($AsGroup){
		$MembersCount=$hash[0]["member"]["count"];
		for($i=0;$i<$MembersCount;$i++){
			$member=MemberInfoByDN($hash[0]["member"][$i]);
			if(is_array($member)){
				while (list ($a, $b) = each ($member) ){
					if(trim($b)==null){continue;}
					$f[$b]=$b;
				}
			}else{
				$f[$member]=$member;
			}
		}
		return $f;
	}


	if(!isset($hash[0]["samaccountname"][0])){WLOG("[QUERY]: MemberInfoByDN():: samaccountname no such attribute");return null;}
	if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN()::$dn:: -> {$hash[0]["samaccountname"][0]}\n";}
	return $hash[0]["samaccountname"][0];
}

function TestConnectToLDAP(){
	if(!isset($GLOBALS["BIND"])){ConnectToLDAP();}
	if(!$GLOBALS["BIND"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: BIND is broken -> reconnect</strong>");
		ConnectToLDAP();
		if(!$GLOBALS["BIND"]){WLOG("[QUERY]: <strong style='color:red'>Error: BIND pointer is false</strong>");return false;}
	}
	
	if(!$GLOBALS["CONNECTION"]){WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is broken -> reconnect</strong>");
	ConnectToLDAP();}
	
	if(!$GLOBALS["CONNECTION"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is definitively broken aborting !!!...</strong>");
		return false;
	}
	return true;	
	
}
function TestConnectToPureLDAP(){
	if(!isset($GLOBALS["BIND_LDAP"])){ConnectToPureLDAP();}
	if(!$GLOBALS["BIND_LDAP"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: BIND is broken -> reconnect</strong>");
		ConnectToPureLDAP();
		if(!$GLOBALS["BIND_LDAP"]){WLOG("[QUERY]: <strong style='color:red'>Error: BIND pointer is false</strong>");return false;}
	}

	if(!$GLOBALS["CONNECTION"]){WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is broken -> reconnect</strong>");
	ConnectToPureLDAP();}

	if(!$GLOBALS["CONNECTION"]){
		WLOG("[QUERY]: <strong style='color:red'>Error: CONNECTION is definitively broken aborting !!!...</strong>");
		return false;
	}
	return true;

}

function HashUsersFromGroupDN($dn){
	$ORGDN=$dn;
	TestConnectToLDAP();
	if(isset($GLOBALS["HashUsersFromGroupDN($ORGDN)"])){return $GLOBALS["HashUsersFromGroupDN($ORGDN)"];}
	$f=array();
	$searchFilter="(objectClass=*)";
	$filter=array("member","memberOf");
	$sr =@ldap_search($GLOBALS["CONNECTION"],$dn,"$searchFilter",$filter,null, null, 10);
	if (!$sr) {WLOG("[QUERY]: Bad search $dn / $searchFilter");return array();}
	$hash=@ldap_get_entries($GLOBALS["CONNECTION"],$sr);
	if(!is_array($hash)){WLOG("[QUERY]:HashUsersFromGroupDN() Not an array...$dn / $searchFilter");return array();}

	$MembersCount=$hash[0]["member"]["count"];
	if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: $MembersCount member(s) $dn / $searchFilter\n";}
	
	for($i=0;$i<$MembersCount;$i++){
		if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: MemberName = {$hash[0]["member"][$i]}\n";}
		$MemberName=MemberInfoByDN($hash[0]["member"][$i]);
		if($MemberName==null){WLOG("[QUERY]:HashUsersFromGroupDN() {$hash[0]["member"][$i]} NO NAME!");continue;}
		if(is_array($MemberName)){
			while (list ($a, $b) = each ($MemberName) ){
				if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: $dn USER= $b\n";}
				if(trim($b)==null){continue;}
				$f[$b]=$b;
			}
		}else{
			$f[$MemberName]=$MemberName;
		}
			
			
	}
	
	if(isset($hash[0]["memberof"]["count"])){
		for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
			$dn1=$hash[0]["memberof"][0];
			$ff=$this->HashUsersFromGroupDN($dn1);
			if(count($ff)>0){
				while (list ($a, $b) = each ($ff) ){
					if(trim($b)==null){continue;}
					$f[$b]=$b;
				}
			}

		}
	}

	
	while (list ($a, $b) = each ($f) ){
		if(trim($b)==null){continue;}
		$Tosend[]=$b;
	}


	$GLOBALS["HashUsersFromGroupDN($ORGDN)"]=$Tosend;
	if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: --> ".count($Tosend)."\n";}
	return $Tosend;

}

function BuildDefault_ldap_server(){
	if(isset($GLOBALS["KerbAuthInfos"]["ADNETIPADDR"])){return $GLOBALS["KerbAuthInfos"]["ADNETIPADDR"];}
	if(isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"])){return $GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
	$SMB=SAMBA_GetNetAdsInfos();
	return $SMB["LDAP server"];
	WLOG("[START]: BuildDefault_ldap_server did not find any LDAP server...");
}

function BuildDefault(){
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SERVER"])){
		WLOG("KerbAuthInfos -> Try to find LDAP_SERVER");
		$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]=BuildDefault_ldap_server();
		WLOG("KerbAuthInfos -> {$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]}");
	}
	
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_PORT"])){$GLOBALS["KerbAuthInfos"]["LDAP_PORT"]=389;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"])){
		$SMB=SAMBA_GetNetAdsInfos();
		$GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"]=$SMB["Bind Path"];
	}
	
	
}
function ConnectToPureLDAP(){
	$ldappassword=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/password"));
	$suffix=@trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
	$server=@file_get_contents("/etc/artica-postfix/ldap_settings/server");
	$port=@file_get_contents("/etc/artica-postfix/ldap_settings/port");
	$admin=@file_get_contents("/etc/artica-postfix/ldap_settings/admin");
	if(trim($server)==null){$server="127.0.0.1";}
	if(trim($port)==null){$port="389";}
	if(trim($ldappassword)==null){$ldappassword="secret";}
	if(trim($admin)==null){$admin="Manager";}
	$GLOBALS["SUFFIX"]=$suffix;
	
	$GLOBALS["CONNECTION"]=ldap_connect($server,$port);
	WLOG("[LDAP]: Connecting to LDAP server `$server:$port`");
	if(!$GLOBALS["CONNECTION"]){
		WLOG("[LDAP]: <strong style='color:red'>Fatal: ldap_connect()");
		@ldap_close();
		return false;
	}
	
	WLOG("[LDAP]: Connecting to LDAP server $server <span style='font-weight:bold;color:#00B218'>success</span> with suffix:&laquo;$suffix&raquo;");
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	
	
	
	
	$GLOBALS["BIND_LDAP"]=ldap_bind($GLOBALS["CONNECTION"], "cn=$admin,$suffix", $ldappassword);
	if(!$GLOBALS["BIND_LDAP"]){
	
		if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
		}
	
		switch (ldap_errno($GLOBALS["CONNECTION"])) {
			case 0x31:
				$error=$error . "<br>Bad username or password. Please try again.";
				break;
			case 0x32:
				$error=$error . "<br>Insufficient access rights.";
				break;
			case 81:
				$error=$error . "<br>Unable to connect to the LDAP server<br>
				$server<br>please,<br>verify if ldap daemon is running<br> or the ldap server address";
				break;
			case -1:
					
				break;
			default:
				$error=$error . "<br>Could not bind to the LDAP server." ."<br>". @ldap_err2str($GLOBALS["CONNECTION"]);
		}
		WLOG("[LDAP]: Connecting to LDAP server $server failed<br>$error");
		return false;
	}
	WLOG("[LDAP]: Binding to LDAP server $server <span style='font-weight:bold;color:#00B218'>success</span>.");
	return true;	
	
}



function ConnectToLDAP(){
	BuildDefault();
	$array=$GLOBALS["KerbAuthInfos"];
	if(!is_array($array)){
		WLOG("KerbAuthInfos not an array");
		return false;
	}	
	
	if(!isset($array["LDAP_SERVER"])){WLOG("LDAP_SERVER not set");return;}
	if(!isset($array["LDAP_SUFFIX"])){WLOG("LDAP_SUFFIX not set");return;}
	
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	$GLOBALS["CONNECTION"]=ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
	WLOG("[LDAP]: Connecting to LDAP server `{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}`");
	if(!$GLOBALS["CONNECTION"]){
		WLOG("[LDAP]: <strong style='color:red'>Fatal: ldap_connect({$array["LDAP_SERVER"]},{$array["LDAP_PORT"]} )");
		@ldap_close();
		return false;
	}	
	
	WLOG("[LDAP]: Connecting to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span> with suffix:&laquo;{$GLOBALS["SUFFIX"]}&raquo;");
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);	
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	
	
	
	//$GLOBALS["BIND"]=ldap_bind($GLOBALS["CONNECTION"], $array["LDAP_DN"], $array["LDAP_PASSWORD"]);
	$GLOBALS["BIND"]=@ldap_bind($GLOBALS["CONNECTION"], "{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}", $array["WINDOWS_SERVER_PASS"]);
	if(!$GLOBALS["BIND"]){
		
		if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
		}
		
		switch (ldap_errno($GLOBALS["CONNECTION"])) {
			case 0x31:
				$error=$error . "<br>Bad username or password. Please try again.";
				break;
			case 0x32:
				$error=$error . "<br>Insufficient access rights.";
				break;
			case 81:
				$error=$error . "<br>Unable to connect to the LDAP server<br>
				{$array["LDAP_SERVER"]}<br>please,<br>verify if ldap daemon is running<br> or the ldap server address";
				break;
			case -1:
					
				break;
			default:
				$error=$error . "<br>Could not bind to the LDAP server." ."<br>". @ldap_err2str($GLOBALS["CONNECTION"]);
		}
		WLOG("[LDAP]: Connecting to LDAP server {$array["LDAP_SERVER"]} failed<br>$error");
		return false;
	}
	WLOG("[LDAP]: Binding to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span>.");
	return true;
}


function CleanSessions(){
	if(!isset($GLOBALS["SESSIONS"])){return;}
	if(!is_array($GLOBALS["SESSIONS"])){return;}
	$cachesSessions=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
	if(isset($cachesSessions)){
		if(is_array($cachesSessions)){
			while (list ($md5, $array) = each ($cachesSessions)){$GLOBALS["SESSIONS"][$md5]=$array;}
		}
	}
	@file_put_contents("/etc/squid3/".basename(__FILE__).".cache", serialize($GLOBALS["SESSIONS"]));
}

function LOCATE_NET_BIN_PATH(){
	$net=internal_find_program("net");
	if(is_file($net)){return $net;}
	$net=internal_find_program("net.samba3");
	if(is_file($net)){return $net;}
}


function SAMBA_GetNetAdsInfos(){

	
	
	if(isset($GLOBALS["CACHE_NET"])){return $GLOBALS["CACHE_NET"];}
	
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
		if(count($array)>5){
			$GLOBALS["CACHE_NET"]=$array;
			return $array;
		}
	}	
	
	$net=LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	WLOG("$net ads info 2>&1 return ".count($array)." items");
	$GLOBALS["CACHE_NET"]=$array;
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	return $array;
}
function internal_find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}



function WLOG($text=null){
	$filename="/var/log/squid/external-acl-ldap.log";
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
   	if (is_file($filename)) { 
   		$size=@filesize($filename);
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink($filename);
   			$GLOBALS["F"] = @fopen($filename, 'a');
   		}
   	}
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["PID"]}]: $text $called\n";}
	@fwrite($GLOBALS["F"], "$date [{$GLOBALS["PID"]}]: $text $called\n");
}


function ufdbguard_checks($id){
	LoadSettings();
	if($GLOBALS["VERBOSE"]){echo "OPEN: /etc/squid3/ufdb.groups.$id.db\n";}
	$arrayGROUPS=unserialize(@file_get_contents("/etc/squid3/ufdb.groups.$id.db"));
	$FINAL=array();
	

	
	if(isset($arrayGROUPS["AD"])){
		while (list ($index, $DNenc) = each ($arrayGROUPS["AD"]) ){
			$DN=base64_decode($DNenc);
			$Hash=HashUsersFromGroupDN($DN);
			if(count($Hash)==0){WLOG("[QUERY]: ufdbguard_checks($id) $DN store no user...");continue;}
			while (list ($a, $b) = each ($Hash) ){if($GLOBALS["VERBOSE"]){echo "USER= $b\n";}$FINAL[]=$b;}
		}
		
		
	}
	
	if(isset($arrayGROUPS["LDAP"])){
		while (list ($index, $gpid) = each ($arrayGROUPS["LDAP"]) ){
			$Hash=HashUsersFromGPID($gpid);
			if(count($Hash)==0){WLOG("[QUERY]: ufdbguard_checks($id) GPID:$gpid store no user...");continue;}
			while (list ($a, $b) = each ($Hash) ){if($GLOBALS["VERBOSE"]){echo "USER= $b\n";}$FINAL[]=$b;}
		}
		
		
	}
	
	
	
	
	if(count($FINAL)==0){
		WLOG("[QUERY]: ufdbguard_checks($id) no user...");
		return;
	}
	echo @implode($FINAL, "\n")."\n";
	
	
}

function HashUsersFromGPID($gpid){
	$array=array();
	if(!is_numeric($gpid)){return array();}
	TestConnectToPureLDAP();
	$sr =@ldap_search($GLOBALS["CONNECTION"],$GLOBALS["SUFFIX"],"(&(gidnumber=$gpid)(objectclass=posixGroup))");
	if(!$sr){WLOG("[QUERY]:HashUsersFromGPID::$gpid Ressource false query (gidnumber=$gpid)");return array();}
	$entry_id =@ldap_first_entry($GLOBALS["CONNECTION"],$sr);
	if(!$entry_id){WLOG("[QUERY]:HashUsersFromGPID::$gpid entry_id false query (gidnumber=$gpid)");return array();}
	$attrs = @ldap_get_attributes($GLOBALS["CONNECTION"], $entry_id);
	if(!isset($attrs["memberUid"])){return array();}
	for($i=0;$i<$attrs["memberUid"]["count"];$i++){
			$array[$attrs["memberUid"][$i]]=$attrs["memberUid"][$i];
	}	
	return $array;
}


?>
