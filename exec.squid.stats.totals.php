<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.squid.youtube.inc');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_DIE();

$GLOBALS["Q"]=new mysql_squid_builder();
if($argv[1]=="--interface"){donnees_interface();exit;}
if($argv[1]=="--repair"){TOTALS_REPAIR();exit;}


if($argv[1]=="--xtime"){start($argv[2]);exit;}

start();
function start($xtime=0){
	
	
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($xtime==0){
		if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$oldpid=@file_get_contents($pidfile);
		if(!$GLOBALS["FORCE"]){
			if($oldpid<100){$oldpid=null;}
			$unix=new unix();
			if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
			$timeexec=$unix->file_time_min($timefile);
			if($timeexec<720){return;}
			$mypid=getmypid();
			@file_put_contents($pidfile,$mypid);
		}	
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($xtime>0){
		$dateRequested=date("Y-m-d",$xtime);
		$dateRequested_sql=" WHERE zDate='$dateRequested'";
	}
	
	$sql="SELECT
	DATE_FORMAT(zDate,'%Y%m%d') as tprefix,DATE_FORMAT(zDate,'%Y-%m-%d') as CurDay,tablename FROM tables_day$dateRequested_sql  
	ORDER BY zDate DESC";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
	//bigint(100)
	if($q->FIELD_TYPE("tables_day", "totalBlocked","syslogs")=="bigint(100)"){
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size` `size` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalBlocked` `totalBlocked` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `requests` `requests` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `totalsize` `totalsize` BIGINT( 255 ) NOT NULL');
		$q->QUERY_SQL('ALTER TABLE `tables_day` CHANGE `size_cached` `size_cached` BIGINT( 255 ) NOT NULL');
	}
	
	if(!$q->FIELD_EXISTS("tables_day", "totalKeyWords")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `totalKeyWords` BIGINT( 255 ) NOT NULL NOT NULL,ADD INDEX ( `totalKeyWords`)");
	}
	if(!$q->FIELD_EXISTS("tables_day", "DangerousCatz")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `DangerousCatz` smallint( 1 ) NOT NULL NOT NULL,ADD INDEX ( `DangerousCatz`)");
	}

	
	if(!$q->ok){echo "$q->mysql_error.<hr>$sql</hr>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$tablename=$ligne["tablename"];
		$hourtable=$ligne["tprefix"]."_hour";
		if($ligne["tprefix"]==date("Ymd")){continue;}
		$KeyWordsTable="searchwordsD_".$ligne["tprefix"];
		$members_table="{$ligne["tprefix"]}_members";
		$youtube_table="youtubeday_{$ligne["tprefix"]}";
		$myXtime=strtotime($ligne["CurDay"]."00:00:00");
		
		if($q->TABLE_EXISTS($hourtable)){
			$sql="SELECT SUM(size) as tsize, SUM(hits) as thits FROM $hourtable";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$size=$ligne2["tsize"];
			$hits=$ligne2["thits"];
			$sizeL=FormatBytes($size/1024);
			if($GLOBALS["VERBOSE"]){echo "$tablename - $sizeL / $hits $hourtable [ $sql ]\n";}
			$sql="UPDATE tables_day SET totalsize='$size',requests='$hits' WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){
					echo $q->mysql_error."\n";
				}
			}
			
			$sql="SELECT COUNT(`sitename`) as tcount FROM $hourtable WHERE LENGTH(`category`)=0";
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$max=$ligne2["tcount"];
			$sql="UPDATE tables_day SET `not_categorized`=$max WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);	

			
			if(!$q->TABLE_EXISTS($members_table)){
				$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.stats.members.hours.php $tablename $members_table >/dev/null 2>&1 &";
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				shell_exec($cmd);
			}else{
				if($q->COUNT_ROWS($members_table)==0){
					$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.stats.members.hours.php $tablename $members_table >/dev/null 2>&1 &";
					if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
					shell_exec($cmd);
					
				}else{
					$MembersField=which_filter($members_table,true);
					if($GLOBALS["VERBOSE"]){echo "Table members Calculate Members by $MembersField\n";}
					if($MembersField<>null){
						$MembersCount=CalculateElements($members_table,$MembersField);
						$sql="UPDATE tables_day SET `MembersCount`=$MembersCount WHERE tablename='$tablename'";
						if($GLOBALS["VERBOSE"]){echo $sql."\n";}
						$q->QUERY_SQL($sql);
					}				
				}
			}

		}
		
		if($q->TABLE_EXISTS($youtube_table)){
			$sql="SELECT SUM( hits ) AS tcount FROM $youtube_table";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$YouTubeHits=$ligne2["tcount"];
			$sql="UPDATE tables_day SET `YouTubeHits`=$YouTubeHits WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
		}else{
			$cmdline=$unix->find_program("nohup")." ".$unix->LOCATE_PHP5_BIN()." /exec.squid.stats.youtube.days.php --xtime $myXtime >/dev/null 2>&1 &";
			shell_exec($cmdline);
		}		
		
		
		if($q->TABLE_EXISTS($KeyWordsTable)){
			$sql="SELECT SUM(tcount) as mysum from( SELECT COUNT(words) as tcount,words FROM `$KeyWordsTable` GROUP BY words) as t";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$CountOfWords=$ligne2["mysum"];
			$sql="UPDATE tables_day SET totalKeyWords='$CountOfWords' WHERE tablename='$tablename'";
			$q->QUERY_SQL($sql);
		}

	}
	
	$cmdline=$unix->find_program("nohup")." ".$unix->LOCATE_PHP5_BIN()." /exec.squid.stats.youtube.days.php --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &";
	shell_exec($cmdline);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.global.categories.php >/dev/null 2>&1 &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.stats.dangerous.php  >/dev/null 2>&1 &");
	
}
function CalculateElements($tablename,$groupby){
	$q=new mysql_squid_builder();
	$sql="SELECT $groupby FROM $tablename GROUP BY $groupby";
	$results=$q->QUERY_SQL($sql);
	return mysql_num_rows($results);

}
function which_filter($tablename,$return_fields=false){
	if($GLOBALS["VERBOSE"]){echo "$tablename -> $return_fields\n";}
	$ipfield="client";
	if(preg_match("#^UserSizeD_#", $tablename)){
		$ipfield="ipaddr";
	}

	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM `$tablename` GROUP BY uid HAVING LENGTH(uid)>0";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "COUNT = $count\n";}
	if($count>2){
		if($GLOBALS["VERBOSE"]){echo "Number of members uid key for $count items\n";}
		if($return_fields){return "uid";}
		return $count;
	}

	$sql="SELECT MAC FROM `$tablename` GROUP BY MAC HAVING LENGTH(MAC)>0";
	$results=$q->QUERY_SQL($sql);
	$count=mysql_num_rows($results);
	if($count>2){
		if($GLOBALS["VERBOSE"]){echo "Number of members MAC key for $count items\n";}
		if($return_fields){return "MAC";}
		return $count;}


		$sql="SELECT COUNT(client) as tcount FROM $tablename WHERE LENGTH(client)>0";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$count=mysql_num_rows($results);
		if($count>1){
			if($GLOBALS["VERBOSE"]){echo "Number of members client key for $count items\n";}
			if($return_fields){return "$ipfield";}
			return $count;}

			$sql="SELECT COUNT(hostname) as tcount FROM $tablename WHERE LENGTH(hostname)>0";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$count=mysql_num_rows($results);
			if($count>1){
				if($GLOBALS["VERBOSE"]){echo "Number of members hostname key for $count items\n";}
				if($return_fields){return "hostname";}
				return $count;}

}

function donnees_interface(){
	
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile=dirname(__FILE__)."/ressources/logs/web/SQUID_STATS_GLOBALS_VALUES";
	@mkdir(dirname($timefile),0755,true);
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<30){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT uid FROM members_uid GROUP BY uid";
	$results=$q->QUERY_SQL($sql);
	$CountDeMembers=mysql_num_rows($results);
	$ARRAY["CountDeMembers"]=$CountDeMembers;
	
	
	$sql="SELECT AVG(size) as avg FROM `cached_total` WHERE cached=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$ligne["avg"]=$ligne["avg"]/1024;
	$UNIT="KB";
	if($ligne["avg"]>1024){$ligne["avg"]=$ligne["avg"]/1024;$UNIT="MB";}
	$ligne["avg"]=round($ligne["avg"]);
	$ARRAY["AVG_CACHED"]=$ligne["avg"].$UNIT;	

	$current_month=date("Ym");
	$catFamMonth="{$current_month}_catfam";
	if($q->TABLE_EXISTS($catFamMonth)){
		$sql="SELECT COUNT(familysite) as tcount, catfam FROM `$catFamMonth` GROUP BY catfam";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ARRAY["CATFAM"][$ligne["catfam"]]=$ligne["tcount"];
		}
		
	}
	
	
	$ARRAY["DATABASE_INFOS"]=$q->DATABASE_INFOS();
	$ARRAY["TIME"]=time();
	@unlink($timefile);
	@file_put_contents($timefile, serialize($ARRAY));
	@chmod($timefile,0777);
	
}

function TOTALS_REPAIR(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "time: $timefile\n";}
	$oldpid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($oldpid<100){$oldpid=null;}
		$unix=new unix();
		if($unix->process_exists($oldpid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $oldpid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<1440){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT DATE_FORMAT(zDate,'%Y%m%d') as tprefix,totalsize,tablename FROM tables_day WHERE totalsize<100");

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$quota_day="quotaday_{$ligne["tprefix"]}";
		
		if($q->TABLE_EXISTS($quota_day)){
			$sql="SELECT SUM(size) as tsize FROM `$quota_day`";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$SumSize=$ligne2["tsize"];
			if($GLOBALS["VERBOSE"]){echo "{$ligne["tablename"]} = {$ligne["totalsize"]} $quota_day = $SumSize\n";}
			$q->QUERY_SQL("UPDATE tables_day SET `totalsize`='$SumSize' WHERE tablename='{$ligne["tablename"]}'");
			
		}
	}
	
}


//totalBlocked,MembersCount,requests,totalsize,not_categorized,YouTubeHits FROM tables_day