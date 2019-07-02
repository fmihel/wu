{:
UWindecoUpdate.pas : Выгрузка обновлений на веб сервер
Данный модуль содержит класс управления выгрузкой обновления
на web сервер windeco
Класс изначально настроен на работу с текущей актуальной версией
сервера. Однако при смене хостинга или способов доступа в интернет возможно
потребует дополнительной корректировки параметров.

//-----------------------------------------------------------------------
Простейший пример использования

var
windeco:TWindecoUpdate;
begin

windeco:=TWindecoUpdate.Create();
windeco.ZipFileName := 'c:\path\file.zip';
windeco.Run();
windeco.Free();

end;
//-----------------------------------------------------------------------

Для управление процессом обновления на уровне пользователя, реализовано событие
TWindecoUpdateProgress
соотвествующий обработчик  для него
TWindecoUpdate.OnProgress:TWindecoUpdateProgress
//-----------------------------------------------------------------------
Пример:
my = class
procedure init();
procedure progress(Sender: TObject; Proc: string;
ProcIndex, ProcCount: Integer; Table: string; TableIndex, TableCount,
Row, RowCount: Integer; aResult: TWindecoUpdateResult;var aStop: Boolean);
end;

procedure my.init();
begin
windeco:=TWindecoUpdate.Create();
windeco.ZipFileName := 'c:\path\file.zip';
windeco.onProgress:=progress;
windeco.Run();
windeco.Free();
end;

procedure my.progress(Sender: TObject; Proc: string;
ProcIndex, ProcCount: Integer; Table: string; TableIndex, TableCount,
Row, RowCount: Integer; aResult: TWindecoUpdateResult;var aStop: Boolean);
begin
Label1.Caption:=Proc;
Gauge.MaxValue:=ProcCount;
Gauge.Progress:=ProcIndex;

if (Proc = 'Update') then
Label2.Caption:=Format('Table:%s %d/%d : %s',[Table,Row,RowCount,
TWindecoUpdateResultNotes[integer(aResult)]]);
end;
//-----------------------------------------------------------------------

Пример получения списка файлов на сервере
(файлы упорядочиваются от новейшего к старейшему, т.е. первым идет последний
закачанный файл)
var cFiles:TStringList;
windeco:TWindecoUpdate;
begin
cFiles:=TStringList.Create();

windeco:=TWindecoUpdate.Create();
windeco.LoadExistsFiles(cFiles);
windeco.Free();

for i:=0 to cFiles.Count-1 do begin
Memo1.Lines.Add(cFiles[i]);
end;

....
...
cFiles.Free();
end;
}
unit UWindecoUpdate;

interface

uses
   Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
   Dialogs, ComCtrls, StdCtrls,KAZip,IdFTPCommon, IdFTP,IdBaseComponent, IdComponent, IdTCPConnection,
   IdTCPClient, IdHTTP,  IdIOHandler, IdIOHandlerSocket, IdIOHandlerStack, IdSSL, IdSSLOpenSSL;

const

    TWindecoUpdateResultStr:array [0..16] of string =  ('wurOk','wurError','wurCreateTmpDir',
    'wurZipNotExists','wurPrepare','wurFtpUpload','wurFtpConnect','wurDeleteTmp','wurHttpError',
    'wurKeyEnable','wurErrorInScript','wurHttpParam','wurHttpFileNotExists','wurContinue',
    'wurTerminateByUser','wurEndWithAnyErrors','wurHttpBaseReg');

    TWindecoUpdateResultNotes:array [0..16] of string =(
    'Ok',
    'Общая ошибка, возникает при перехватывании try finaly',
    'Ошибка создания временной папки на локальном диске (см Prepare)',
    'Указанный zip файл не существует (см Prepare)',
    'Ошибка выполнения процедуры подготовки Prepare',
    'Ошибка загрузки файла по ftp протоколу (см Upload) (скорее всего разрыв соединения)',
    'Нет соединения по ftp (см Upload и параметры соединения, логин пароль и.тд) ',
    'Неполучилось удалить временную папку (см Clear)',
    'Общая ошибка работы с http (проверить запросы, скрипты и возможность работать по http)',
    'Нет ключа авторизации ( ключ лежит в consts.php, ключи должны совпадать)',
    'Скрипт выдал ошибку',
    'Недостаточно нужных параметров для работы',
    'Отсутствует файл на сервере',
    'Нужно продолжить обработку',
    'Остановлено пользователем',
    'Завершено с несколькими ошибками',
    'Ошибка при выполнении запроса к базе сервера');
type

    TWindecoUpdateResult = (wurOk,wurError,wurCreateTmpDir,
    wurZipNotExists,wurPrepare,wurFtpUpload,wurFtpConnect,wurDeleteTmp,wurHttpError,
    wurKeyEnable,wurErrorInScript,wurHttpParam,wurHttpFileNotExists,wurContinue,
    wurTerminateByUser,wurEndWithAnyErrors,wurHttpBaseReg);



    {:
    Мониторинг процесса обновления (см TWindecoUpdate.OnProgress)
    Proc - имя текущей выполняемой процедуры
    ProcIndex - номер текущей выполняемой процедуры
    ProcCount - всего выполняемых процедур

    Table  - (только для Proc = 'Update') Имя обрабатываемой таблицы
    TableIndex - (только для Proc = 'Update') номер обрабатываемой таблицы
    TableCount - (только для Proc = 'Update') всего таблиц
    Row - (только для Proc = 'Update') текущая обрабатываемая позиция таблицы
    RowCount - (только для Proc = 'Update')всего строк таблицы

    aResult - результат обработки
    aStop - если присвоить true то процесс обновления прервется
    }
    TWindecoUpdateProgress = procedure (Sender: TObject; Proc: string;
        ProcIndex, ProcCount: Integer; Table: string; TableIndex, TableCount,
        Row, RowCount: Integer; aResult: TWindecoUpdateResult; var aStop:
        Boolean) of object;
    {:
    Коллекция внутренних утилит для работы класса TWindecoUpdate
    }
    WINDECO_UTILS = class(TObject)
    private
        class procedure TAG_TO_LIST(cRes: string; cList: TStrings; aTag,
            aDelim: string); static;
    public
        class function COUNT(const aBdrFileName: string): Integer; static;
        class procedure DIR_LIST(aOutFileList: TStrings;aPath: string;
            aFilter:string); static;
        class function EXTRACT_NAME(aFileName: string): string; static;
        class function FAST(aStream: TStream; aLeft, aRight: string): string;
            static;
        class function FIND(aStream: TStream; aSearch: string): Integer; static;
        class procedure HTTP_ERRORS_TO(cRes: string; cList: TStrings); static;
        class function HTTP_GET(const aURL: string; var aResult: string):
            Boolean; static;
        class function HTTP_TO_WUR(aHttpResult: string): TWindecoUpdateResult;
            static;
        class procedure LIST(cRes:string;cList:TStrings); static;
        class function REMOVE_DIR(aDir: string): Boolean; static;
        class function TMP_FOLDER: string; static;
    end;

    TWindecoTable = class(TObject)
    private
        fCount: Integer;
    public
        property Count: Integer read fCount write fCount;
    end;

    TWindecoUpdate = class(TObject)
    private
        fAfterUpdateScript: string;
        fCountPacksAtTime: Integer;
        fCurrentRow: Integer;
        fCurrentTable: Integer;
        fErrors: TStringList;
        fFiles: TStringList;
        fFtpHost: string;
        fFtpPassword: string;
        fFtpUsername: string;
        fKey: string;
        fOnProgress: TWindecoUpdateProgress;
        fScriptEnd: string;
        fScriptList: string;
        fScriptUnpack: string;
        fScriptUpdate: string;
        fSetupFileName: string;
        fTables: TStringList;
        fTmpFolder: string;
        fUpdateResult: TWindecoUpdateResult;
        fZipFileName: string;
        function ftpCreate: TidFTP;
        procedure ftpDestroy(var aFTP: TidFTP);
        procedure progress(Proc: string; ProcIndex, ProcCount: Integer; Table:
            string; TableIndex, TableCount, Row, RowCount: Integer; aResult:
            TWindecoUpdateResult);
        function tabAdd(aTable: string): TWindecoTable;
        procedure tabClear;
        function tabItem(index: Integer): TWindecoTable;
    public
        constructor Create;
        destructor Destroy; override;
        function AfterUpdate: TWindecoUpdateResult;
        function Clear: TWindecoUpdateResult;
        function LoadExistsFiles(aFiles: TStrings): TWindecoUpdateResult;
        function LoadSetup: Boolean;
        {:
        Создание списка таблиц
        Получение кол-ва строк обновлений в каждой таблице
        }
        function Prepare: TWindecoUpdateResult;
        function Run: TWindecoUpdateResult;
        function SaveSetup: Boolean;
        function Unpack: TWindecoUpdateResult;
        function Update: TWindecoUpdateResult;
        function Upload: TWindecoUpdateResult;
        property AfterUpdateScript: string read fAfterUpdateScript write
            fAfterUpdateScript;
        {:
        Время на обработку пакетов, не должно превышать время работы скрипта,
        заложенное хостером
        }
        property CountPacksAtTime: Integer read fCountPacksAtTime write
            fCountPacksAtTime;
        property Errors: TStringList read fErrors write fErrors;
        property Files: TStringList read fFiles write fFiles;
        property FtpHost: string read fFtpHost write fFtpHost;
        property FtpPassword: string read fFtpPassword write fFtpPassword;
        property FtpUsername: string read fFtpUsername write fFtpUsername;
        property Key: string read fKey write fKey;
        property ScriptEnd: string read fScriptEnd write fScriptEnd;
        property ScriptList: string read fScriptList write fScriptList;
        property ScriptUnpack: string read fScriptUnpack write fScriptUnpack;
        property ScriptUpdate: string read fScriptUpdate write fScriptUpdate;
        property SetupFileName: string read fSetupFileName write fSetupFileName;
        property Tables: TStringList read fTables write fTables;
        property TmpFolder: string read fTmpFolder write fTmpFolder;
        property ZipFileName: string read fZipFileName write fZipFileName;
        property OnProgress: TWindecoUpdateProgress read fOnProgress write
            fOnProgress;
    end;

implementation


{
******************************** WINDECO_UTILS *********************************
}
class function WINDECO_UTILS.COUNT(const aBdrFileName: string): Integer;
var
    cBdr: TMemoryStream;
    cCount: string;
begin
    cBdr:=TMemoryStream.Create;

    try
    try
        cBdr.LoadFromFile(aBdrFileName);
        cCount:=WINDECO_UTILS.FAST(cBdr,'<COUNT>','</COUNT>');

        result:=strToInt(cCount);

    except
    on e:Exception do
    begin
        result:=-1;
    end;
    end;
    finally
        cBdr.Free;
    end;
end;

class procedure WINDECO_UTILS.DIR_LIST(aOutFileList: TStrings;aPath: string;
    aFilter:string);
var
    SR: TSearchRec;

    function _IsAttrDir(aAttr:integer):boolean;
    var
        cData:string;
    begin
            cData:=IntToHex(aAttr,8)[7];
            result:=not ((StrToInt(cData) and 1) = 0);
    end;

begin

    if FindFirst(aPath + '*.'+aFilter, faAnyFile, SR) = 0 then
    begin
      repeat
        if not _IsAttrDir(SR.Attr) then begin
            if (SR.Attr = $00000020) then
                 aOutFileList.Add(SR.Name)
        end;
      until FindNext(SR) <> 0;
      FindClose(SR);
    end;
end;

class function WINDECO_UTILS.EXTRACT_NAME(aFileName: string): string;
var
    cPos: Integer;
begin
    result := SysUtils.ExtractFileName(aFileName);
    cPos:=Pos('.',result);
    if (cPos>0) then
        result:=copy(result,1,cPos-1);


end;

class function WINDECO_UTILS.FAST(aStream: TStream; aLeft, aRight: string):
    string;
var
    posLeft, posRight: Integer;
    cLen: Integer;
    cRead: AnsiChar;
    i: Integer;
begin
    result:='';
    cLen:=length(aLeft);
    posLeft:=WINDECO_UTILS.FIND(aStream,aLeft);
    if posLeft<0 then exit;

    posRight:=WINDECO_UTILS.FIND(aStream,aRight);
    if posRight<0 then exit;

    aStream.Position:=posLeft+cLen;
    for i:=posLeft+cLen to posRight-1 do begin
        aStream.Read(cRead,length(cRead));
        result:=result+string(cRead);
    end;
end;

class function WINDECO_UTILS.FIND(aStream: TStream; aSearch: string): Integer;
var
    i, cLen: Integer;
    cCurrent: string;
    cRead: array [0..10] of AnsiChar;
begin
    result:=-1;

    cLen:=length(aSearch);

    i:=aStream.Position;
    while (i<aStream.size) do begin
        aStream.Read(cRead,cLen);
        cCurrent:=copy(string(cRead),1,cLen);

        if (cCurrent = aSearch) then begin
            result:=i;
            exit;
        end;
        inc(i);
        aStream.Position:=i;
    end;

end;

class procedure WINDECO_UTILS.HTTP_ERRORS_TO(cRes: string; cList: TStrings);
begin
    WINDECO_UTILS.TAG_TO_LIST(cRes,cList,'errors','<br>');
end;

class function WINDECO_UTILS.HTTP_GET(const aURL: string; var aResult: string):
    Boolean;
var
    http: TIdHTTP;
    ssl: TIdSSLIOHandlerSocketOpenSSL;
begin
    begin
      result:=false;
    end;

      http:=TIdHTTP.Create(nil);
      ssl:=TIdSSLIOHandlerSocketOpenSSL.Create(nil);
      http.IOHandler:=ssl;
      try
      try

          aResult:=http.Get(aUrl);
          result:=true;

      except on e:Exception do begin

      end;
      end;
      finally
          http.IOHandler := nil;
          ssl.Free();
          http.Free();
      end;
end;

class function WINDECO_UTILS.HTTP_TO_WUR(aHttpResult: string):
    TWindecoUpdateResult;
begin
    result:=wurHttpError;

    if Pos('<result>1</result>',aHttpResult)>0 then
        result:=wurOk;

    if Pos('<result>-1</result>',aHttpResult)>0 then
        result:=wurKeyEnable;

    if Pos('<result>0</result>',aHttpResult)>0 then
        result:=wurErrorInScript;

    if Pos('<result>2</result>',aHttpResult)>0 then
        result:=wurHttpParam;

    if Pos('<result>3</result>',aHttpResult)>0 then
        result:=wurHttpFileNotExists;

    if Pos('<result>4</result>',aHttpResult)>0 then
        result:=wurHttpBaseReg;
end;

class procedure WINDECO_UTILS.LIST(cRes:string;cList:TStrings);
begin
    cList.Clear();
    WINDECO_UTILS.TAG_TO_LIST(cRes,cList,'list',',');
end;

class function WINDECO_UTILS.REMOVE_DIR(aDir: string): Boolean;
var
    i: Integer;
    cSearchRec: TSearchRec;
    cFileName: string;

    function _IsAttrDir(aAttr:integer):boolean;
    var
        cData:string;
    begin
            cData:=IntToHex(aAttr,8)[7];
            result:=not ((StrToInt(cData) and 1) = 0);
    end;

begin

    if not DirectoryExists(aDir) then
    begin
        result:=true;
        exit;
    end;

    // Добавляем слэш в конце и задаем маску - "все файлы и   директории  "
    aDir := IncludeTrailingBackslash(aDir);
    i := FindFirst(aDir + '*', faAnyFile, cSearchRec);

    try
    try
        //TO DO
        while i = 0 do
        begin
            // Получаем полный путь к файлу или   директорию
            cFileName := aDir + cSearchRec.Name;
            // Если это   директория
            if (cSearchRec.Name <> '') and (cSearchRec.Name <> '.') and (cSearchRec.Name <> '..') then
            begin

                if _IsAttrDir(cSearchRec.Attr) then
                begin
                    // Рекурсивный вызов этой же функции с ключом удаления корня

                    FileSetAttr(cFileName, faArchive);

                    WINDECO_UTILS.REMOVE_DIR(cFileName);
                end
                else // Иначе удаляем файл
                begin
                    FileSetAttr(cFileName, faArchive);

                    SysUtils.DeleteFile(cFileName);
                end;
            end;
            // Берем следующий файл или   директорию
            i := FindNext(cSearchRec);
        end;//while

    except
    on e:Exception do
    begin
        {$ifdef _log_}ULog.Error('',e,ClassName,cFuncName);{$endif}
    end;
    end;
    finally
      SysUtils.FindClose(cSearchRec);
    end;
    result:=RemoveDir(aDir);
end;

class procedure WINDECO_UTILS.TAG_TO_LIST(cRes: string; cList: TStrings; aTag,
    aDelim: string);
var
    p: Integer;
begin
    p:=Pos('<'+aTag+'>',cRes);
    if (p<=0) then exit;

    cRes:=copy(cRes,p+length(aTag)+2,length(cRes));

    p:=Pos('</'+aTag+'>',cRes);
    cRes:=copy(cRes,1,p-1);

    p:=Pos(aDelim,cRes);
    while (p>0) do begin
        cList.Add(copy(cRes,1,p-1));
        cRes:=trim(copy(cRes,p+length(aDelim),length(cRes)));

        p:=Pos(aDelim,cRes);
    end;

    if (length(cRes)>0) then
        cList.Add(cRes);
end;

class function WINDECO_UTILS.TMP_FOLDER: string;
begin
    result:=SysUtils.ExtractFileDir(Application.ExeName)+'\_tmpzipw3_\';
end;

{
******************************** TWindecoUpdate ********************************
}
constructor TWindecoUpdate.Create;
begin
    inherited Create;
        fSetupFileName:=ExtractFilePath(Application.ExeName)+'/windeco_update.ini';

      LoadSetup();
      fCountPacksAtTime:=100;
      fTables:=TStringList.Create();
      fTmpFolder:=WINDECO_UTILS.TMP_FOLDER();
      //fFtpHost:='xxxx';
      //fFtpUsername:='xxxx';
      //fFtpPassword:='xxxx';
      //fKey :='xxxx';
      //fScriptUpdate :='https://windeco.su/admin/modules/update/remote/update.php';
      //fScriptUnpack :='https://windeco.su/admin/modules/update/remote/unpack.php';
      //fScriptEnd    :='https://windeco.su/admin/modules/update/remote/clear.php';
      //fScriptList   :='https://windeco.su/admin/modules/update/remote/list.php';
      //fAfterUpdateScript   :='https://windeco.su/admin/modules/update/remote/after_update.php';
      fFiles:=TStringList.Create();
      fErrors:=TStringList.Create();
end;

destructor TWindecoUpdate.Destroy;
begin
     SaveSetup();
     self.tabClear();
     self.fTables.Free();
     self.fFiles.Free();
     self.fErrors.Free();

    inherited Destroy;
end;

function TWindecoUpdate.AfterUpdate: TWindecoUpdateResult;
var
    count: Integer;
    i: Integer;
    cRes: TWindecoUpdateResult;
    cStr: string;
begin

    if (WINDECO_UTILS.HTTP_GET(self.AfterUpdateScript+'?key='+self.Key+'&count',cStr)) then
        count:=StrToInt(cStr)
    else
        count:=0;

    for i:=0 to count-1 do begin

        if (WINDECO_UTILS.HTTP_GET(self.AfterUpdateScript+'?key='+self.Key+'&step='+IntToStr(i),cStr)) then begin
            cRes:=WINDECO_UTILS.HTTP_TO_WUR(cStr);
            progress('AfterUpdate',5,6,'step',i,count,0,0,cRes);
        end;

    end;

    result:=wurOk;
end;

function TWindecoUpdate.Clear: TWindecoUpdateResult;
var
    cRes: string;
begin
    // удаление временной папки
    result:=wurOk;
    if SysUtils.DirectoryExists(self.TmpFolder) then begin
        if (not WINDECO_UTILS.REMOVE_DIR(self.TmpFolder)) then
            result:=wurDeleteTmp;
    end;

    // очистка на сервере
    if result=wurOk then begin
        result:=wurHttpError;
        if (WINDECO_UTILS.HTTP_GET(self.ScriptEnd+'?key='+self.Key,cRes)) then
            result:=WINDECO_UTILS.HTTP_TO_WUR(cRes);
    end;

    progress('Clear',6,6,'',0,0,0,0,result);
end;

function TWindecoUpdate.ftpCreate: TidFTP;
begin
    result:=TIdFTP.Create(nil);

    result.Host:=FtpHost;
    result.Password:=FtpPassword;
    result.Username:=FtpUsername;

    result.Passive:=true;
    result.TransferType:=ftBinary;
    result.UseMLIS:=false;
end;

procedure TWindecoUpdate.ftpDestroy(var aFTP: TidFTP);
begin
    if aFtp = nil then
        exit;

    if aFtp.Connected then
        aFtp.Disconnect();

    aFtp.Free();

    aFtp:=nil;
end;

function TWindecoUpdate.LoadExistsFiles(aFiles: TStrings): TWindecoUpdateResult;
var
    cRes: string;
begin
    result:=wurHttpError;
    aFiles.Clear();
    try
    try
        if (WINDECO_UTILS.HTTP_GET(self.ScriptList+'?key='+self.Key,cRes)) then
        begin
            if (Pos('<list>',cRes)>0) then begin
                WINDECO_UTILS.LIST(cRes,aFiles);
                result:=wurOk;
            end else begin
                result:=WINDECO_UTILS.HTTP_TO_WUR(cRes);
            end;
        end;


    except
    on e:Exception do
    begin

    end;
    end;
    finally

    end;
end;

function TWindecoUpdate.LoadSetup: Boolean;
var
    ini: TStringList;

    function getStr(aName:string;aDefault:string):string;
    begin
        if (ini<>nil) and (ini.IndexOfName(aName)>-1) then begin
            result:=ini.Values[aName];
        end else
            result:=aDefault;
    end;

begin
    result:=false;

    if (FileExists(SetupFileName)) then
        ini:=TStringList.Create()
    else
        ini:=nil;

    try
    try
        if (ini<>nil) then
            ini.LoadFromFile(SetupFileName);

        fCountPacksAtTime:=StrToInt(getStr('CountPacksAtTime','100'));

        fFtpHost:=getStr('FtpHost','');
        fFtpUsername:=getStr('FtpUserName','');
        fFtpPassword:=getStr('FtpPassword','');
        fKey :=getStr('Key','test');
        fScriptUpdate :=getStr('ScriptUpdate','https://windeco.su/admin/modules/update/remote/update.php');
        fScriptUnpack :=getStr('ScriptUnpack','https://windeco.su/admin/modules/update/remote/unpack.php');
        fScriptEnd    :=getStr('ScriptEnd','https://windeco.su/admin/modules/update/remote/clear.php');
        fScriptList   :=getStr('ScriptList','https://windeco.su/admin/modules/update/remote/list.php');
        fAfterUpdateScript   :=getStr('AfterUpdateScript','https://windeco.su/admin/modules/update/remote/after_update.php');

        result:=true;
    except
    on e:Exception do
    begin

    end;
    end;
    finally
        if (ini<>nil) then
            ini.Free();
    end;



end;

function TWindecoUpdate.Prepare: TWindecoUpdateResult;
var
    cZip: TKAZip;
    cFiles: TStringList;
    i, idx: Integer;
    item: TWindecoTable;
    cCount: Integer;
    deleteLinesIndex: Integer;
    cName: string;
begin
     fErrors.Clear();
     fCurrentTable  :=0;
     fCurrentRow    :=0;
     fUpdateResult   :=wurOk;

    {$region 'assign'}
    if (not SysUtils.FileExists(self.ZipFileName)) then begin
        result:=wurZipNotExists;
        exit;
    end;

    if not SysUtils.ForceDirectories(self.TmpFolder) then begin
        result:=wurCreateTmpDir;
        exit;
    end;
    {$endregion}

    cZip:=TKaZip.Create(nil);
    cFiles:=TStringList.Create();
    tabClear();

    try
    try

        cZip.Open(AnsiString(self.ZipFileName));
        cZip.OverwriteAction:=oaOverWrite;
        cZip.ExtractAll(AnsiString(self.TmpFolder));

        WINDECO_UTILS.DIR_LIST(cFiles,self.TmpFolder,'bdr');
        deleteLinesIndex:=-1;
        idx:=0;

        for i:=0 to cFiles.Count-1 do begin
            cCount := WINDECO_UTILS.COUNT(self.TmpFolder+cFiles.Strings[i]);
            if (cCount>0) then begin
                cName:=WINDECO_UTILS.EXTRACT_NAME(cFiles.Strings[i]);

                if cName = 'DELETED_LINES' then
                    deleteLinesIndex:=idx;

                item:=self.tabAdd(cName);
                item.Count := cCount;
                idx:=idx+1;
            end;
        end;
        // перемещаем таблицу DELETED_LINES на самый верх, для приоритета обработки
        if (deleteLinesIndex>0) then
            fTables.Move(deleteLinesIndex,0);

        result:=wurOk;
    except
    on e:Exception do
    begin
        result:=wurPrepare;
    end;
    end;
    finally
        cFiles.Free();
        cZip.Free();
    end;

    if result = wurOk then
        result:=LoadExistsFiles(fFiles);

    progress('Prepare',1,6,'',0,0,0,0,wurOk);
end;

procedure TWindecoUpdate.progress(Proc: string; ProcIndex, ProcCount: Integer;
    Table: string; TableIndex, TableCount, Row, RowCount: Integer; aResult:
    TWindecoUpdateResult);
var
    aStop: Boolean;
begin
    aStop:=false;
    if (System.Assigned(self.OnProgress)) then begin

        self.OnProgress(self,Proc,ProcIndex,ProcCount,Table,TableIndex,TableCount,Row,RowCount,aResult,aStop);

        if (aStop) then begin
            fUpdateResult:=wurTerminateByUser;
            fCurrentTable:=Tables.Count;
        end;

    end;
end;

function TWindecoUpdate.Run: TWindecoUpdateResult;
begin
    result:=wurOk;
    if (result = wurOk) then
        result:=Prepare();

    if (result = wurOk) then
        result:=Upload();

    if (result = wurOk) then
        result:=Unpack();

    if (result = wurOk) then
        while (Update() = wurContinue) do Application.ProcessMessages();

    if (result = wurOk) then
        result:=AfterUpdate();


    if ((result = wurOk)and(fUpdateResult=wurOk)) then
        result:=Clear()
    else begin
        Clear();
        if (fUpdateResult<>wurOk) then
            result:=fUpdateResult
    end;
end;

function TWindecoUpdate.SaveSetup: Boolean;
var
    ini: TStringList;
begin
    result:=false;
    ini:=TStringList.Create();
    try
    try

        ini.Values['CountPacksAtTime']  :=  IntToStr(fCountPacksAtTime);
        ini.Values['FtpHost']           :=  FtpHost;
        ini.Values['FtpUsername']       :=  FtpUserName;
        ini.Values['FtpPassword']       :=  FtpPassword;
        ini.Values['Key']               :=  fKey;
        ini.Values['ScriptUpdate']      :=  fScriptUpdate;
        ini.Values['ScriptUnpack']      :=  fScriptUnpack;
        ini.Values['ScriptEnd']         :=  fScriptEnd;
        ini.Values['ScriptList']        :=  fScriptList;
        ini.Values['AfterUpdateScript'] :=  fAfterUpdateScript;
        ini.SaveToFile(SetupFileName);
        result:=true;
    except
    on e:Exception do
    begin

    end;
    end;
    finally
        ini.Free();

    end;

end;
function TWindecoUpdate.tabAdd(aTable: string): TWindecoTable;
var
    item: TWindecoTable;
begin
    item:=TWindecoTable.Create();
    self.fTables.AddObject(aTable,item);
    result:=item;
end;

procedure TWindecoUpdate.tabClear;
var
    i: Integer;
    item: TWindecoTable;
begin
    for i:=0 to fTables.Count-1 do begin
        item:=tabItem(i);
        item.Free();
    end;
    fTables.Clear();
end;

function TWindecoUpdate.tabItem(index: Integer): TWindecoTable;
begin
    result:=TWindecoTable(Tables.Objects[index]);
end;

function TWindecoUpdate.Unpack: TWindecoUpdateResult;
var
    cRes: string;
begin
    result:=wurHttpError;
    if (WINDECO_UTILS.HTTP_GET(self.ScriptUnpack+'?key='+self.Key+'&file='+ExtractFileName(self.ZipFileName),cRes)) then
        result:=WINDECO_UTILS.HTTP_TO_WUR(cRes);

    progress('Unpack',3,6,'',0,0,0,0,result);
end;

function TWindecoUpdate.Update: TWindecoUpdateResult;
var
    tab: TWindecoTable;
    cPos: Integer;
    cCount: Integer;
    cStrRes: string;
    cRes: TWindecoUpdateResult;
    cUrl: string;
begin
    result:=wurContinue;

    if (fCurrentTable>=fTables.Count) then begin
        result:=wurOk;
        exit;
    end;

    tab:=tabItem(fCurrentTable);

    cPos:=fCurrentRow;
    cCount:=self.CountPacksAtTime;
    if (cPos+cCount>=tab.Count) then
        cCount:=tab.Count-cPos;

    cRes:=wurHttpError;
    cUrl:=self.ScriptUpdate+'?key='+self.Key+'&table='+self.Tables[fCurrentTable]+'&pos='+IntToStr(cPos)+'&delta='+intToStr(cCount);

    if (WINDECO_UTILS.HTTP_GET(cUrl,cStrRes)) then begin

        cRes:=WINDECO_UTILS.HTTP_TO_WUR(cStrRes);
        if (cRes<>wurOk) then begin
            fUpdateResult:=wurEndWithAnyErrors;
            //WINDECO_UTILS.HTTP_ERRORS_TO(cStrRes,fErrors);
        end;
    end;

    progress('Update',4,6,Tables[fCurrentTable],fCurrentTable,Tables.Count,cPos,tab.Count,cRes);

    fCurrentRow:=fCurrentRow+self.CountPacksAtTime;

    if (fCurrentRow>=tab.Count) then begin
        inc(fCurrentTable);
        fCurrentRow:=0;
    end;

    if (fCurrentTable>=fTables.Count) then begin
        result:=wurOk;
        exit;
    end;
end;

function TWindecoUpdate.Upload: TWindecoUpdateResult;
var
    ftp: TIdFTP;
begin
    result:=wurFtpUpload;

    ftp:=self.ftpCreate();
    {$ifdef _log_} SLog.Stack(ClassName,cFuncName);{$endif}
    try
    try
        ftp.Connect;

        if not ftp.Connected then begin
            result:=wurFtpConnect;
            raise Exception.Create('wurFtpConnect');
        end;

        ftp.Put(self.ZipFileName,SysUtils.ExtractFileName(self.ZipFileName));
        result:=wurOk;
    except
    on e:Exception do
    begin

    end;
    end;
    finally
        self.ftpDestroy(ftp);
    end;
    progress('Upload',2,6,'',0,0,0,0,result);
end;


end.
