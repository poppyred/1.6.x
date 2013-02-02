<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["central-infos"])){central_information();exit;}
	if(isset($_GET["panel"])){page();exit;}
	if(isset($_GET["graphique_heure"])){graphique_heure();exit;}
tabs();

function tabs(){
	$t=time();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$array["panel"]='{panel}';
	
	if(!$users->PROXYTINY_APPLIANCE){
		$array["status"]='{status}';
		//$array["panel-week"]='{this_week}';
		$array["days"]='{days}';
		$array["users"]='{members}';
		$array["not_categorized"]='{not_categorized}';
	}
	
while (list ($num, $ligne) = each ($array) ){
	
		if($num=="panel-week"){
			$html[]= "<li><a href=\"squid.traffic.panel.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}	
		
		if($num=="status"){
			$html[]= "<li><a href=\"squid.traffic.statistics.php?status=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="days"){
			$html[]= "<li><a href=\"squid.traffic.statistics.days.php?day-consumption=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
		if($num=="users"){
			$html[]= "<li><a href=\"squid.members.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="week-consumption"){
			$html[]= "<li><a href=\"squid.traffic.statistics.week.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="month-consumption"){
			$html[]= "<li><a href=\"squid.traffic.statistics.month.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="find"){
			$html[]= "<li><a href=\"squid.search.statistics.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="not_categorized"){
			$html[]= "<li><a href=\"squid.not-categorized.statistics.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="events"){
			$html[]= "<li><a href=\"squid.stats.events.php\"><span>$ligne</span></a></li>\n";
			continue;
		}		
	
	
		$html[]= "<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_stats_central style='width:910px;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_stats_central').tabs();
			
			
			});
		</script>");		
	
}


function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' width=240px><div id='info-gene-$t' style='width:240px' class=form></div></td>
		<td valign='top'><div id='info-central-$t'></div></td>
	</tr>
	</table>
	
	<script>
		LoadAjax('info-central-$t','$page?central-infos=yes&t=$t');
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function central_information(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	
	if(!$users->PROXYTINY_APPLIANCE){
	//$tr[]=Paragraphe32("old_statistics_interface", "old_statistics_interface_text", "SquidQuickLinksStatistics();", "status_statistics-22.png");
	$tr[]=Paragraphe32("new_statistics_interface", "new_statistics_interface_text",
			 "s_PopUpFull('http://proxy-appliance.org/index.php?cID=326',1024,768,'Statistics');",
	 "help-32.png");
	$tr[]=Paragraphe32("proxy_statistics_interface", "proxy_statistics_interface_text", 
		"document.location.href='logoff.php?goto=miniadm.logon.php';",
		 "link-32.png");
	
	if(!$users->CORP_LICENSE){
		$more_features="<div class=explain style='font-size:14px;'>{squid_stats_nolicence_explain}</div>";
	}
	
	
	}
		
	if($users->PROXYTINY_APPLIANCE){
		//
		$tr[]=Paragraphe32('APP_SARG','APP_SARG_TXT',"Loadjs('sarg.php')","sarg-logo-32.png");
		$tr[]=Paragraphe32('APP_SARG_HOWTO','APP_SARG_HOWTO_TXT',
		"s_PopUpFull('http://proxy-appliance.org/index.php?cID=203',1024,768,'Statistics');","help-32.png");
	}
	
	if($users->URLSNARF_INSTALLED){
		//$tr[]=Paragraphe32('APP_URLSNARF','APP_URLSNARF_TEXT',"Loadjs('urlsnarf.php')","website-32.png");
	}
	
	
	$table=CompileTr2($tr,true);	
	
	$html="
	<div style='font-size:18px'>{SQUID_STATS}</div>
	$more_features
	<div id='graph1-$t'></div>
	<center>
	<div style='margin-top:15px;width:80%'>$table</div>
	</center>
	<script>
		LoadAjax('info-gene-$t','squid.traffic.statistics.php?squid-status-stats=yes');
		LoadAjax('graph1-$t','$page?graphique_heure=yes');
	</script>
	";
	

	echo $tpl->_ENGINE_parse_body($html);
	
}



function graphique_heure(){
	$users=new usersMenus();
	if($users->PROXYTINY_APPLIANCE){return;}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$currenttime=date("YmdH");
	$table="squidhour_$currenttime";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table)){return null;}
	$sql="SELECT SUM(QuerySize) as tsize,DATE_FORMAT(zDate,'%i') as tdate FROM $table 
	group by tdate HAVING tsize>0
	ORDER BY tdate ";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$size=round(($ligne["tsize"]/1024));;
		if($GLOBALS["VERBOSE"]){echo "<strong>{$ligne["tdate"]} = $size</strong><br>\n";}
		$xdata[]=$ligne["tdate"];
		$ydata[]=$size;
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".$t.png";
	$gp=new artica_graphs();
	$gp->width=600;
	$gp->height=210;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title="KB";
	$gp->x_title=$tpl->_ENGINE_parse_body("{minutes}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(is_file($targetedfile)){
		
		$html="
		<div style='font-size:16px;margin-top:15px;margin-bottom:10px;font-weight:bold'>{downloaded_flow_this_hour}</div>
		<center><img src='$targetedfile'></center>
		";
		echo $tpl->_ENGINE_parse_body($html);
	}	
	
	
}
