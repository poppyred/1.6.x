<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SERV_NAME"]="ftp-proxy";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}

function start(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/ftpproxystart.pid";
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$sock=new sockets();
	$EnableFTPProxy=$sock->GET_INFO("EnableFTPProxy");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}
	

	
	if($EnableFTPProxy==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is disabled...\n";}
		stop();
		return;		
		
	}
		
	
	$daemonbin=$unix->find_program("ftp-proxy");
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	$pid=FTPD_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: writing init.d\n";}
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --ftp-proxy >/dev/null 2>&1");
	
	
	build();
	$cmdline="$daemonbin -d -f /etc/proxy-suite/ftp-proxy.conf";
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting $SERV_NAME\n";}
	shell_exec("$nohup $cmdline 2>&1 &");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=FTPD_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=FTPD_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME success\n";}
		
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	
}


function stop(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}

	$pid=FTPD_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME with a ttl of {$time}mn\n";}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME smoothly...\n";}
	$cmd="$kill $pid >/dev/null";
	shell_exec($cmd);

	$pid=FTPD_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	
	
	for($i=0;$i<10;$i++){
		$pid=FTPD_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	$pid=FTPD_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";}
}

function FTPD_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/ftp-proxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("ftp-proxy"));
}



function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
	
}



function build(){
	//http://numsys.eu/ftp-proxy.php
	$sock=new sockets();
	$FTPProxyPort=$sock->GET_INFO("FTPProxyPort");
	if(!is_numeric($FTPProxyPort)){$FTPProxyPort="2121";}
	$FTPProxyUseAuth=$sock->GET_INFO("FTPProxyUseAuth");
	
	$FTPMaxClients=$sock->GET_INFO("FTPProxyMaxClients");
	if(!is_numeric($FTPMaxClients)){$FTPMaxClients=64;}
	
	if(!is_numeric($FTPProxyUseAuth)){$FTPProxyUseAuth=0;}
	
	$FTPProxyTimeOuts=$sock->GET_INFO("FTPProxyTimeOuts");
	if(!is_numeric($FTPProxyTimeOuts)){$FTPProxyTimeOuts=900;}
	
	$FTPLogLevel=$sock->GET_INFO("FTPLogLevel");
	if($FTPLogLevel==null){$FTPLogLevel="INF";}
	
	$FTPProxyListen=$sock->GET_INFO("FTPProxyListen");
	if($FTPProxyListen==null){$FTPProxyListen="0.0.0.0";}
	
	$FTPProxyDestinationTransferMode=$sock->GET_INFO("FTPProxyDestinationTransferMode");
	if($FTPProxyDestinationTransferMode==null){$FTPProxyDestinationTransferMode="client";}	
	
	$f[]="[-Global-]";
	$f[]="#";
	$f[]="# The following entries select a port range for client DTP";
	$f[]="# ports in active mode, i.e. when the client sends a PORT";
	$f[]="# command. The default is port 20 as per RFC 959, if the";
	$f[]="# proxy is running as root (user ID 0) or a random  port.";
	$f[]="#";
	$f[]="# ActiveMinDataPort	40000";
	$f[]="# ActiveMaxDataPort	40999";
	$f[]="";
	$f[]="#";

	$f[]="";
	$f[]="#";
	$f[]="# The follwing setting allows you to configure a so called";
	$f[]="# transparent proxy for outgoing ftp. To get it working you";
	$f[]="# also have to redirect client requests on a gateway or";
	$f[]="# firewall host (i.e. via ipchains) to the ftp-proxy.";
	$f[]="# You can combine this with the AllowMagicUser flag.";
	$f[]="#";
	$f[]="# AllowTransProxy	no";
	$f[]="";
	$f[]="#";
	$f[]="# This message prevents any login if a file with the given";
	$f[]="# name exists. Instead the contents of the file will be sent";
	$f[]="# to the client and the connection closed. Lines are prefixed";
	$f[]="# with \"421-\". If no such file exists, the mechanism is not";
	$f[]="# triggered and DenyString (s.b.) is ignored altogether.";
	$f[]="#";
	$f[]="# DenyMessage		/etc/proxy-suite/ftp-deny.txt";
	$f[]="";
	$f[]="#";
	$f[]="# If a DenyMessage file exists, the deny mechanism will be";
	$f[]="# activated in any case. If a DenyString exists, it will be";
	$f[]="# sent (with escape sequences) as the last line (with a 421";
	$f[]="# reply code), else the standard message";
	$f[]="# \"Service not available\" will be displayed.";
	$f[]="#";
	$f[]="# DenyString		 Service out of order";
	$f[]="";
	$f[]="#";
	$f[]="# Where to redirect incoming FTP traffic. This destination";
	$f[]="# will be used if a client has not set its own target.";
	$f[]="# WARNING: ftp-proxy will refuse to run if this directive";
	$f[]="# is not set and transparent proxying not enables (see also";
	$f[]="# AllowTransProxy).";
	$f[]="#";
	$f[]="# DestinationAddress	server.domain.tld";
	$f[]="DestinationAddress	localhost";
	$f[]="";
	$f[]="#";
	$f[]="# (Local) port range for all connections to the server. The";
	$f[]="# default is to let the proxy select any ephemeral port.";
	$f[]="#";
	$f[]="# DestinationMinPort	42900";
	$f[]="# DestinationMaxPort	42999";
	$f[]="";
	$f[]="#";
	$f[]="# This is the port corresponding to DestinationAddress. It";
	$f[]="# defaults to 21, the standard FTP port.";
	$f[]="#";
	$f[]="# DestinationPort	21";
	$f[]="DestinationTransferMode	$FTPProxyDestinationTransferMode";
	$f[]="";
	$f[]="#";
	$f[]="# Defines the action that is taken when a data transfer command";
	$f[]="# is failed on the server side. If set to \"yes\", the client";
	$f[]="# socket will be reset after a command is failed and the transfer";
	$f[]="# mode reset to the default (active ftp).";
	$f[]="# This option is a workarround for Netscape (4.x) clients, that";
	$f[]="# sends a second data transfer command if the first is failed";
	$f[]="# while \"user click\" on a symbolic link pointing to a directory.";
	$f[]="#";
	$f[]="# FailResetsPasv	no";
	$f[]="";
	$f[]="#";

	$f[]="";
	$f[]="#";
	$f[]="# Limits the number of incoming client connections per minute";
	$f[]="# in daemon mode - it defaults to 40 connections per minute.";
	$f[]="#";
	$f[]="# ForkLimit		40";
	$f[]="";
	$f[]="#";
	$f[]="# If given, change GID to give up root privileges. In POSIX";
	$f[]="# environments this changes all group ID's.";
	$f[]="#";
	$f[]="Group			nogroup";
	$f[]="";
	$f[]="#";
	if($FTPProxyUseAuth==1){
		
		$LDAPAuthDN=$sock->GET_INFO("FTPLDAPAuthDN");
		
		$LDAPAuthPWAttr=$sock->GET_INFO("FTPLDAPAuthPWAttr");
		$LDAPBaseDN=$sock->GET_INFO("FTPLDAPBaseDN");
		$LDAPBindDN=$sock->GET_INFO("FTPLDAPBindDN");
		$LDAPServer=$sock->GET_INFO("FTPLDAPServer");
		if($LDAPAuthDN==null){$LDAPAuthDN="dc=domain,dc=tld";}
		if($LDAPAuthPWAttr==null){$LDAPAuthPWAttr="userPassword";}
		if($LDAPBaseDN==null){$LDAPBaseDN="dc=abc,dc=lab";}
		if($LDAPBindDN==null){$LDAPBindDN="%s@domain.tld";}
		if($LDAPServer==null){$LDAPServer="10.10.0.2";}


		

		
		$f[]="#";
		$f[]="UserAuthType		ldap";
		$f[]="# LDAPAuthDN		$LDAPAuthDN";
		$f[]="# LDAPAuthOKFlag	allowedService=FTPProxy";
		$f[]="# LDAPAuthPWAttr	$LDAPAuthPWAttr";
		$f[]="# LDAPAuthPWType		plain";
		$f[]="LDAPBaseDN		$LDAPBaseDN";
		$f[]="# LDAPBindDN		uid=%s,dc=domain,dc=tld";
		$f[]="LDAPBindDN		$LDAPBindDN";
		$f[]="# LDAPBindDN		uid=ftp-proxy,dc=domain,dc=tld";
		$f[]="# LDAPBindDN auto";
		$f[]="# LDAPBindPW		aPassword";
		$f[]="# LDAPIdentifier	LoginName";
		$f[]="# LDAPObjectClass	FTPProxyUser";
		$f[]="LDAPServer		$LDAPServer";



	}

	if($FTPUserAuthMagic==null){$FTPUserAuthMagic="@user";}
	if($FTPUseMagicChar==null){$FTPUseMagicChar="@";}
	$FTPAllowMagicUser=$sock->GET_INFO("FTPAllowMagicUser");
	if(!is_numeric($FTPAllowMagicUser)){$FTPAllowMagicUser=1;}	
	$FTPUserAuthMagic=$sock->GET_INFO("FTPUserAuthMagic");
	$FTPUseMagicChar=$sock->GET_INFO("FTPUseMagicChar");	
	
	$f[]="# ForceMagicUser	yes";
	if($FTPAllowMagicUser==1){
		$f[]="AllowMagicUser	yes";
	}else{
		$f[]="AllowMagicUser	no";
	}
	
	$f[]="UserAuthMagic		$FTPUserAuthMagic";
	$f[]="UseMagicChar		$FTPUseMagicChar";	
	$f[]="LogDestination	daemon";
	$f[]="LogLevel		$FTPLogLevel";
	$f[]="";
	$f[]="#";
	$f[]="# Maximum number of concurrent clients if running as daemon.";
	$f[]="#";
	$f[]="# MaxClients		64";
	$f[]="";
	$f[]="#";
	$f[]="# This message (or rather the contents of a file with this";
	$f[]="# name) will be issued when MaxClients is exceeded, each";
	$f[]="# line prefixed with \"421-\". If no such file exists, only";
	$f[]="# the MaxClientsString below will be displayed.";
	$f[]="#";
	$f[]="# MaxClientsMessage	/etc/proxy-suite/ftp-maxclients.txt";
	$f[]="";
	$f[]="#";
	$f[]="# This string (with a default of \"Service not available\" will";
	$f[]="# be displayed, if the configured maximum number of concurrent";
	$f[]="# clients has been reached. It is prefixed with '421 '.";
	$f[]="#";
	$f[]="# MaxClientsString	The server is full";
	$f[]="";
	$f[]="#";
	$f[]="# Defines the maximum number of bytes read from socket at once";
	$f[]="# while data transfers. Default is to read all data as reported";
	$f[]="# by the kernel.";
	$f[]="# It may be usefull to set a limit (i.e. to 8192), if your proxy";
	$f[]="# machine uses two interfaces of different speed, i.e. the clients";
	$f[]="# are accessing the proxy via a high-speed interface (i.e.";
	$f[]="# FastEthernet) and the proxy is accessing servers using a slower";
	$f[]="# one (i.e. modem, ISDN link) and your ftp-clients aborts the data";
	$f[]="# transfers because of a timeout.";
	$f[]="#";
	$f[]="# MaxRecvBufSize	0";
	$f[]="";
	$f[]="#";
	$f[]="# The following entries select a port range for client DTP";
	$f[]="# ports in passive mode, i.e. when the client sends a PASV.";
	$f[]="# If no port range is given, no bind is performed, in which";
	$f[]="# case the proxy lets the machine select an ephemeral port.";
	$f[]="#";
	$f[]="# PassiveMinDataPort	41000";
	$f[]="# PassiveMaxDataPort	41999";
	$f[]="PidFile		/var/run/ftp-proxy.pid";
	$f[]="Port $FTPProxyPort";
	$f[]="Listen		$FTPProxyListen";
	$f[]="";
	$f[]="#";
	$f[]="# The following flag specifies the action when a PORT command";
	$f[]="# is received while a PASV listening socket is outstanding.";
	$f[]="# The RFC is not really clear about the \"correct\" behaviour,";
	$f[]="# but since most existing implementations seem to reset the";
	$f[]="# listener, we do the same by default. Nevertheless they all";
	$f[]="# may be ... inaccurate.";
	$f[]="#";
	$f[]="# PortResetsPasv yes";
	$f[]="";
	$f[]="#";
	$f[]="# Shall we allow data connections only from the same host where";
	$f[]="# the control connection originated from? Default is yes. If";
	$f[]="# you say no here, the proxy is able to take part in so called";
	$f[]="# third party server to server transfers.";
	$f[]="#";
	$f[]="# SameAddress		yes";
	$f[]="ServerType		standalone";
	$f[]="";
	$f[]="#";
	$f[]="# Enable this flag if you want to use a random port in";
	$f[]="# the specified range with PassiveMinDataPort/MaxDataPort,";
	$f[]="# DestinationMinPort/MaxPort, ActiveMinPort/MaxDataPort";
	$f[]="# instead of incrementing the port number.";
	$f[]="#";
	$f[]="# SockBindRand		no";
	$f[]="";
	$f[]="#";
	$f[]="# Shall we use the TCP Wrapper Library when running as daemon?";
	$f[]="# \"on\", \"yes\", \"true\" or a non-zero number means yes, anything";
	$f[]="# else no. Default no. Only applicable when running as daemon.";
	$f[]="# Note that TCP Wrapper support must be compiled in for this to";
	$f[]="# work.";
	$f[]="#";
	$f[]="# TCPWrapper		yes";
	$f[]="";
	$f[]="#";
	$f[]="# Defines the name to use for TCPWrapper checks. Default is";
	$f[]="# to use the base name of the ftp-proxy binary (ftp-proxy).";
	$f[]="#";
	$f[]="# TCPWrapperName	ftp-proxy";
	$f[]="TimeOut		$FTPProxyTimeOuts";
	$f[]="";
	$f[]="#";
	$f[]="# If the proxy server needs to advertise itself (in outgoing";
	$f[]="# responses to the ftp-server, like answers to PASV commands)";
	$f[]="# with a different address than it actually has, the following";
	$f[]="# option can be used. Relevant e.g. when using a NAT device";
	$f[]="# in the path.";
	$f[]="#";
	$f[]="# TranslatedAddress	0.0.0.0";
	$f[]="";
	$f[]="#";
	$f[]="# If given, change UID to give up root privileges. In POSIX";
	$f[]="# environments this changes all user ID's.";
	$f[]="# If set, the proxy will use non-privileged ports (>1024) for";
	$f[]="# active mode ftp transfers - see also ActiveMin/MaxDataPort.";
	$f[]="#";
	$f[]="User			nobody";
	$f[]="# User			ftpproxy";
	$f[]="";


	$f[]="";
	$f[]="#";

	$f[]="";
	$f[]="#";
	$f[]="# Allows to define a regular expression rule for validation";
	$f[]="# of the user name. The default setting matches the usual";
	$f[]="# cases inclusive E-Mail adresses and \"domain/user\" names:";
	$f[]="#";
	$f[]="# UserNameRule		^[[:alnum:]]+([%20@/\._-][[:alnum:]]+)*\$";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# List of FTP commands that will be allowed from a client.";
	$f[]="# All commands not on this list will be rejected. If no list";
	$f[]="# exists, then all commands will be allowed.";
	$f[]="# Each command can be followed by an optional equals sign";
	$f[]="# and regular expression (POSIX 1003.2) to restrict legal";
	$f[]="# argument(s) syntax. In order to avoid confusing the";
	$f[]="# configuration reading functions, the expression is \"pre-";
	$f[]="# processed.\" This means that a sequence like \"%20\" will be";
	$f[]="# replaced by a space and \"%5c\" or \"%5C\" by a backslash";
	$f[]="# before being compiled. In fact, this looks a bit like the";
	$f[]="# HTML way of doing things. The percent sign itself is";
	$f[]="# represented by \"%25\" of course. The pattern is interpreted";
	$f[]="# as a POSIX 1003.2 RE (with REG_NEWLINE flag set), and is";
	$f[]="# case sensitive. In any case, this works only if compiled";
	$f[]="# with regular expression support compiled into the program.";
	$f[]="#";
	$f[]="# ValidCommands		ABOR, PASS, PASV, STOR, USER, MODE, QUIT, SYST";
	$f[]="";
	$f[]="#";
	$f[]="# This file will be presented to all clients immediately after";
	$f[]="# the connection has been established. Each line is prefixed";
	$f[]="# with \"220-\". The whole message is followed by a standard";
	$f[]="# \"220 <host> FTP server (<version>) ready\" or whatever has";
	$f[]="# been substituted with WelcomeString below. Escape sequences";
	$f[]="# (like %h for hostname; see ftp-proxy.conf(5)) are active.";
	$f[]="#";
	$f[]="# WelcomeMessage	/etc/proxy-suite/ftp-welcome.txt";
	$f[]="";
	$f[]="#";
	$f[]="# If we wanted to disguise as some known other FTP server we";
	$f[]="# could use the following option. It replaces the standard";
	$f[]="# \"<host> FTP server (<version>) ready\" in the initial 220";
	$f[]="# message. As with all Messages and Strings, various escape";
	$f[]="# sequences are available.";
	$f[]="#";
	$f[]="# WelcomeString		Welcome to %h";
	$f[]="";
	$f[]="";	
	@mkdir("/etc/proxy-suite",0755,true);
	@file_put_contents("/etc/proxy-suite/ftp-proxy.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building /etc/proxy-suite/ftp-proxy.conf done...\n";}
}


?>