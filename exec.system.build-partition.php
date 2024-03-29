<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}


if($argv[1]=="--unlink"){disk_unlink($argv[2]);die();}
if($argv[1]=="--full"){disk_build_unique_partition($argv[2],$argv[3],$argv[4]);die();}
if($argv[1]=="--1part"){FindFirstPartition($argv[2]);die();}



function disk_unlink($dev){
	$unix=new unix();
	$fdisk=$unix->find_program("fdisk");
	$umount=$unix->find_program("umount");
	exec("$fdisk -l $dev 2>&1",$results);
	$parts=array();
	while (list ($num, $val) = each ($results) ){
		if(preg_match("#^(.+?)\s+.*?Linux#", $val,$re)){
			$parts[$re[1]]=true;
		}
	
	}	
while (list ($dev, $val) = each ($parts) ){
		echo "Umount $dev\n";
		shell_exec("$umount -l $dev");
		echo "Remove $dev from fstab\n";
		disk_remove_fstab($dev);
	}
	
	
	
}

function disk_remove_fstab($dev=null){
	$unix=new unix();
	if($dev==null){
		events("disk_remove_fstab():: No target specified...");
		return;
	}
	$UUID_TABLE=array();
	$uuidregex=null;
	$array=$unix->BLKID_ARRAY();
	
	while (list ($dev, $subarray) = each ($array) ){
		if($subarray["UUID"]<>null){
			$UUID_TABLE[$subarray["UUID"]]=$dev;
		}
	}
	
	reset($array);
	$UUID=$array[$dev]["UUID"];
	

	
	
	
	if($UUID<>null){
		$uuidregex="UUID=$UUID";
	}
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	$t=array();
	$devRegex=str_replace("/", "\/", $dev);
	$devRegex=str_replace(".", "\.", $dev);
	
	
	
	$found=false;
	while (list ($num, $val) = each ($f) ){
		$val=trim($val);
		if($val==null){continue;}
		if(count($UUID_TABLE)>0){
			if(preg_match("#UUID=(.+?)\s+#", $val,$re)){
				if(!isset($UUID_TABLE[$re[1]])){
					if($GLOBALS["VERBOSE"]){echo "**** REMOVE {$re[1]} ****\n";}
					continue;
				}
			}
		}
		
		if(preg_match("#^$devRegex\s+#", $val)){
			if($GLOBALS["VERBOSE"]){echo "**** REMOVE $val ****\n";}
			continue;}
		if($uuidregex<>null){
			if(preg_match("#^$uuidregex\s+#", $val)){
				if($GLOBALS["VERBOSE"]){echo "**** REMOVE $val ****\n";}
				continue;}
			
		}
		if($GLOBALS["VERBOSE"]){echo "NO MATCH #^$uuidregex\s+# or #^$devRegex\s+# $val \n";}
		$t[]=$val;
	}
	
	@file_put_contents("/etc/fstab", @implode("\n", $t)."\n");	
	
	
}




function build_progress($text,$pourc){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.partition.progress";
	
	
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function disk_build_unique_partition($dev,$label,$fs_type=null){
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/system.partition.txt";
	$GLOBALS["FILELOG"]=$filelogs;

	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".md5($dev.$label);
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Already PID $pid exists, aborting...");
		build_progress("Already PID $pid exists, aborting",110);
		return;
	}
	
	build_progress("{checking}",5);
	events("***********************************");
	events("Dev.........: $dev");
	events("Label.......: $label");
	events("FileSystem..: $fs_type");
	
	
	$mount=$unix->find_program("mount");
	
	$disk_label=str_replace(" ", "_", $label);
	$targetMountPoint=$unix->isDirInFsTab("/media/$disk_label");
	events("Target Mount.: $targetMountPoint");
	build_progress("Target Mount point = $targetMountPoint",10);
	
	events("***********************************");
	
	if($targetMountPoint<>null){
		events("/media/$disk_label already set in fstab!! remove entry in fstab first...");
		events("Mounting the new media");
		build_progress("Mounting the new media = /media/$disk_label",15);
		$cmd="$mount /media/$disk_label 2>&1";	
		$results=array();
		exec($cmd,$results);
		while (list ($num, $val) = each ($results) ){events($val);}		
		build_progress("{success}",100);
		return;
	}
	$tmpfile=$unix->FILE_TEMP();
	build_progress("Creating disk configuration",20);
	events("Writing to $tmpfile");
	@file_put_contents($tmpfile, ",,L\n");
	if(!is_file($tmpfile)){
		build_progress("Creating disk configuration $tmpfile {failed}",110);
		return;
	}
	
	events("Cleaning $dev..., please wait...");
	$dd=$unix->find_program("dd");
	$sfdisk=$unix->find_program("sfdisk");
	$mkfs=$unix->find_program("mkfs.ext4");
	$btrfs=$unix->find_program("mkfs.btrfs");
	$xfs=$unix->find_program("mkfs.xfs");
	$mount=$unix->find_program("mount");

	events("$dev filesystem $fs_type");
	$extV=$fs_type;
	$e2label=$unix->find_program("e2label");
	$e2label_EX=true;
	
	$MKFS["ext3"]="-b 4096 -L \"$disk_label\"";
	$MKFS["ext4"]="-L \"$disk_label\" -i 8096 -I 256 -Tlargefile4";
	$MKFS["btrfs"]="--label \"$disk_label\"";
	$MKFS["xfs"]="-f -L \"$disk_label\"";
	$MKFS["reiserfs"]="-q --label \"$disk_label\"";
	
	
	if($fs_type==null){$fs_type="ext4";}
	$pgr=$unix->find_program("mkfs.$fs_type");
	events("mkfs.$fs_type = $pgr");
	
	if(is_file($pgr)){
		$mkfs="$pgr {$MKFS[$fs_type]} ";
		$extV="$fs_type";
		$e2label_EX=false;
	}
		

	build_progress("Cleaning $dev..., {please_wait}",30);
	events("Cleaning $dev..., please wait...");
	$cmd="$sfdisk -f $dev <$tmpfile 2>&1";
	
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){
		events($val);
	}

	
	$FindFirstPartition=FindFirstPartition($dev);
	events("First partition = `$FindFirstPartition`");
	build_progress("First partition $FindFirstPartition",50);
	
	if($FindFirstPartition==null){
		build_progress("Find first partition failed",110);
		events("First partition = FAILED");
		return;
	}	
	
	build_progress("Building $FindFirstPartition..., {please_wait}",40);
	$cmd="$dd if=/dev/zero of=$FindFirstPartition bs=512 count=1 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}	


	
	build_progress("Formating $FindFirstPartition",60);
	$cmd="$mkfs $FindFirstPartition 2>&1";
	events("Formatting  $FindFirstPartition, please wait....");
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}

	if($e2label_EX){
		build_progress("Set label to $disk_label",70);
		events("Set label to $disk_label");
		$cmd="$e2label $FindFirstPartition $disk_label 2>&1";
		events($cmd);
		$results=array();
		exec($cmd,$results);
		while (list ($num, $val) = each ($results) ){events($val);}
	}

	build_progress("Change fstab $FindFirstPartition to /media/$disk_label",80);
	events("Change fstab to include new media $FindFirstPartition to /media/$disk_label");
	disk_change_fstab($FindFirstPartition,$extV,"/media/$disk_label");
	
	build_progress("Mounting the new media",90);
	events("Mounting the new media");
	$cmd="$mount $FindFirstPartition 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("Rebuild caches",95);
	shell_exec("$php /usr/share/artica-postfix/exec.usb.scan.write.php");
	
	build_progress("{success}",100);
	events("done...");	
	
}

function FindFirstPartition($dev){
	$unix=new unix();
	$fdisk=$unix->find_program("fdisk");
	$cmd="$fdisk -l $dev 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){
		if(!preg_match("#^(.+?)\s+1.*?Linux#", $val,$re)){
			if($GLOBALS["VERBOSE"]){echo "'$val' NO MATCH #^(.+?)\s+1.*?Linux#\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "'$val' MATCH #^(.+?)\s+1.*?Linux# -> {$re[1]}\n";}
		return $re[1];
		
		
	}
	
}

function disk_change_fstab($dev,$ext,$target){
	$unix=new unix();
	if($target==null){
		events("disk_change_fstab():: No target specified...");
		return;
	}
	$uuidregex=null;
	$array=$unix->BLKID_ARRAY();
	$UUID=$array[$dev]["UUID"];
	
	$optionsZ["ext3"]="defaults,relatime,errors=remount-ro";	
	$optionsZ["ext4"]="defaults,rw,noatime,async,data=writeback,barrier=0,commit=100,nobh,errors=remount-ro";	
	$optionsZ["reiserfs"]="defaults,notail,noatime,user_xattr,acl,barrier=none";
	$optionsZ["btrfs"]="defaults,noatime";
	$optionsZ["xfs"]="defaults,noatime,nodiratime,nosuid,nodev,allocsize=64m,quota";
	
	$options=$optionsZ[$ext];
	$tune2fs=$unix->find_program("tune2fs");
	if($ext=="ext4"){shell_exec("$tune2fs -o journal_data_writeback $dev");}
	
	$line="$dev\t$target\t$ext\t$options  0    1";
	if($UUID<>null){
		$line="UUID=$UUID\t$target\t$ext\t$options  0    1";
		$uuidregex="UUID=$UUID";
	}
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	
	$devRegex=str_replace("/", "\/", $dev);
	$devRegex=str_replace(".", "\.", $dev);
	
	
	@mkdir($target,0755,true);
	$found=false;
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#^$devRegex\s+#", $val)){
			$f[$num]=$line;
			$found=true;
			continue;
		}
		if($uuidregex<>null){
			if(preg_match("#^$uuidregex\s+#", $val)){
				$f[$num]=$line;
				$found=true;
				continue;
			}
		}
		
	}
	if(!$found){$f[]=$line."\n";}
	@file_put_contents("/etc/fstab", @implode("\n", $f));
}
//##############################################################################
function events($text){
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile=$GLOBALS["FILELOG"];

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [$pid]: $text\n";}
	@fwrite($f, "$date [$pid]: $text\n");
	@fclose($f);
	@chmod($logFile, 0777);
}