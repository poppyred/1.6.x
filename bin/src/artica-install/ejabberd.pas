unit ejabberd;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem;



  type
  tejabberd=class


private
     ejabberdEnabled:integer;
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
      function INITD():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    function    PID_NUM():string;
    procedure   STOP();
    function    VERSION():string;
     function   BIN_PATH():string;
END;

implementation

constructor tejabberd.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       if not TryStrToInt(SYS.GET_INFO('ejabberdEnabled'),ejabberdEnabled) then ejabberdEnabled:=1;

       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tejabberd.free();
begin
    logs.Free;
end;
//##############################################################################
function tejabberd.PID_NUM():string;
begin
     result:=SYS.GET_PID_FROM_PATH('/var/run/ejabberd/ejabberd.pid');
end;
//##############################################################################
 function tejabberd.BIN_PATH():string;
begin
 if FileExists('/usr/lib/erlang/erts-5.8/bin/beam') then exit('/usr/lib/erlang/erts-5.8/bin/beam');
end;
//#############################################################################
 function tejabberd.INITD():string;
begin
 if FileExists('/etc/init.d/ejabberd') then exit('/etc/init.d/ejabberd');
end;
//#############################################################################
 function tejabberd.VERSION():string;
var
   RegExpr:TRegExpr;
   path:string;
   tmp:string;
begin

   result:=SYS.GET_CACHE_VERSION('APP_EJABBERD');
   if length(result)>0 then exit;

 path:=SYS.LOCATE_GENERIC_BIN('ejabberdctl');
 if length(path)=0 then exit;

   tmp:=logs.FILE_TEMP();

   fpsystem(path+' status >'+ tmp + ' 2>&1');
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='ejabberd\s+([0-9\.]+)\s+';
   if RegExpr.Exec(logs.ReadFromFile(tmp)) then begin
      result:=RegExpr.Match[1];
   end;

   RegExpr.Free;
   logs.DeleteFile(tmp);
   SYS.SET_CACHE_VERSION('APP_EJABBERD',result);
end;
//#############################################################################
procedure tejabberd.START();
var
   pid:string;
   parms:string;
   count:integer;
   path:string;
   init:string;
begin
   init:=INITD();
   path:=SYS.LOCATE_GENERIC_BIN('ejabberdctl');

  if not FileExists(path) then begin
     logs.Syslogs('Could not start ejabberd..Unable to stat ejabberdctl binary path..aborting');
     exit;
  end;

  if not FileExists(init) then begin
     logs.Syslogs('Could not start ejabberd..Unable to stat init.d script path..aborting');
     exit;
  end;

  pid:=PID_NUM();
  count:=0;
   if SYS.PROCESS_EXIST(pid) then begin
      if ejabberdEnabled=0 then begin
         logs.Syslogs('Shutdown ejabberd: this service is disabled in Artica...');
         STOP();
         exit;
      end;


      logs.DebugLogs('Starting......: ejabberd daemon is already running using PID ' + pid + '...');
      exit;
   end;
   logs.DebugLogs('Starting......: ejabberd daemon configuring....');
   fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.ejabberd.php --reconfigure');
   logs.DebugLogs('Starting......: ejabberd daemon launching daemon....');
   fpsystem(SYS.LOCATE_GENERIC_BIN('nohup')+' '+INITD+' start >/dev/null 2>&1 &');


  while not SYS.PROCESS_EXIST(PID_NUM()) do begin
        sleep(500);
        count:=count+1;
        logs.syslogs('Starting......: ejabberd daemon '+intToStr(count)+'/80');
        logs.DebugLogs('tejabberd.START(): wait sequence ' + intToStr(count));
        if count>80 then begin
            logs.syslogs('Starting......: ejabberd daemon timeout..');
            break;
        end;
  end;

  if not SYS.PROCESS_EXIST(PID_NUM()) then  begin
     logs.syslogs('Starting......: ejabberd daemon failed..');
  end;

  logs.DebugLogs('Starting......: ejabberd daemon success...');

end;

//#############################################################################
procedure tejabberd.STOP();
var
   pid,init,bin:string;
   count:integer;
begin
pid:=PID_NUM();
count:=0;
 init:=INITD();
 bin:=BIN_PATH();

 if not FileExists(init) then begin
     writeln('Stopping ejabberd............: Unable to stat init.d script path..aborting');
     exit;
  end;

if SYS.PROCESS_EXIST(pid) then begin
   writeln('Stopping ejabberd............: ' + pid + ' PID..');
    fpsystem(SYS.LOCATE_GENERIC_BIN('nohup')+' '+INITD+' stop >/dev/null 2>&1 &');
end;

  while SYS.PROCESS_EXIST(PID_NUM()) do begin
        sleep(300);
        count:=count+1;
        writeln('Stopping ejabberd............: '+IntToStr(count)+'/80');
        if count>80 then begin
            writeln('Stopping ejabberd............: timeout...');
            fpsystem('/bin/kill -9 ' + pid);
        end;
  end;
if FileExists(bin) then begin
pid:=SYS.AllPidsByPatternInPath(bin);
if length(pid)>0 then begin
   writeln('Stopping ejabberd............: '+ pid + '...');
   fpsystem('/bin/kill ' + pid);
end;
end;

writeln('Stopping ejabberd............: success...');
end;

//#########################################################################################


end.
