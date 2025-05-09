unit UMain;

interface

uses
  Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
  Dialogs, ComCtrls, StdCtrls, UWindecoUpdate, ActnList, Gauges;

type
  TfrmMain = class(TForm)
    Edit2: TEdit;
    mmLog: TMemo;
    ActionList1: TActionList;
    Gauge1: TGauge;
    Label1: TLabel;
    actRun: TAction;
    Button7: TButton;
    Button1: TButton;
    actStop: TAction;
    Button2: TButton;
    OpenDialog1: TOpenDialog;
    actUploadVideo: TAction;
    OpenDialog2: TOpenDialog;
    GroupBox1: TGroupBox;
    Button3: TButton;
    Label2: TLabel;
    Edit1: TEdit;
    Label3: TLabel;
    Edit3: TEdit;
    btnTest: TButton;
    procedure actRunExecute(Sender: TObject);
    procedure actStopExecute(Sender: TObject);
    procedure actUploadVideoExecute(Sender: TObject);
    procedure Button2Click(Sender: TObject);
    procedure btnTestClick(Sender: TObject);
    procedure FormCreate(Sender: TObject);
  private
    { Private declarations }
  public
    { Public declarations }
    fStop:boolean;
    procedure log(aMsg:string);
    procedure progress(Sender: TObject; Proc: string;
        ProcIndex, ProcCount: Integer; Table: string; TableIndex, TableCount,
        Row, RowCount: Integer; aResult: TWindecoUpdateResult;var aStop: Boolean);
  end;

var
  frmMain: TfrmMain;

implementation

{$R *.dfm}


procedure TfrmMain.actRunExecute(Sender: TObject);
var
  windeco:TWindecoUpdate;
  res: TWindecoUpdateResult;
  cFiles:TstringList;
begin

    fStop:=false;
    // �������� ����������
    windeco:=TWindecoUpdate.Create();
    windeco.OnProgress:=Progress;

    cFiles:=TStringList.Create();

    try

        // �������,  � ����� ������ ��������
        windeco.ZipFileName:=Edit2.Text;

        // �������� ������ ������ �������
        if windeco.LoadExistsFiles(cFiles)=wurOk then begin
            log('����� �� �������� �� ����������');
            mmLog.Lines.AddStrings(cFiles);
         end;


        // ������ �������� ����������
        res:=windeco.Run();


        windeco.LoadExistsFiles(cFiles);

        if windeco.LoadExistsFiles(cFiles)=wurOk then begin
            log('����� �� �������� ����� ����������');
            mmLog.Lines.AddStrings(cFiles);
        end;

    finally
        cFiles.Free();
        windeco.Free();

    end;


    log(Format('��������� %s',[TWindecoUpdateResultStr[integer(res)]]));
    log(Format('����������� "%s"',[TWindecoUpdateResultNotes[integer(res)]]));

end;

procedure TfrmMain.actStopExecute(Sender: TObject);
begin
 fStop:=true;
end;

procedure TfrmMain.actUploadVideoExecute(Sender: TObject);
var
  windeco:TWindecoUpdate;
  cFileName:string;
  ID_C_MEDIA_FILE:integer;
  cToPath:string;
  cRes:integer;
begin

    windeco:=TWindecoUpdate.Create();
    try
        if OpenDialog2.Execute() then begin

            { ���� � ����� �� ��������� ����� }
            cFileName:=OpenDialog2.FileName;

            { ������������� � ������� C_MEDIA_FILE, ���� ����� ������ �� ����������, ��� ����� ������� }
            ID_C_MEDIA_FILE:=StrToInt(Edit3.Text);

            { �����, � ������ ����� ������ ������������ ���� �� �������, }
            cToPath:=Edit1.Text;

            {�������� �����, ���������:
                0       - ��� ���������
                1,2,..  - ������
            }
            cRes:=windeco.UploadVideo(cFileName,ID_C_MEDIA_FILE,cToPath);

            log('�������� ����� ���������, ���:'+IntToStr(cRes));
        end;

    finally
        windeco.Free();

    end;
end;

procedure TfrmMain.Button2Click(Sender: TObject);
begin
    if OpenDialog1.Execute then begin
        Edit2.Text:=OpenDialog1.FileName;
    end;
end;

procedure TfrmMain.btnTestClick(Sender: TObject);
var
  windeco:TWindecoUpdate;
  res: TWindecoUpdateResult;
  cFiles:TstringList;
begin

    windeco:=TWindecoUpdate.Create();
    cFiles:=TStringList.Create();

    try

//        // �������,  � ����� ������ ��������
//        windeco.ZipFileName:=Edit2.Text;

        // �������� ������ ������ �������
        if windeco.LoadExistsFiles(cFiles)=wurOk then begin
            log('����� �� �������� �� ����������');
            mmLog.Lines.AddStrings(cFiles);
         end;


        // ������ �������� ����������
//        res:=windeco.Run();


//        windeco.LoadExistsFiles(cFiles);
//
//        if windeco.LoadExistsFiles(cFiles)=wurOk then begin
//            log('����� �� �������� ����� ����������');
//            mmLog.Lines.AddStrings(cFiles);
//        end;

    finally
        cFiles.Free();
        windeco.Free();

    end;


    log(Format('��������� %s',[TWindecoUpdateResultStr[integer(res)]]));
    log(Format('����������� "%s"',[TWindecoUpdateResultNotes[integer(res)]]));

end;

procedure TfrmMain.FormCreate(Sender: TObject);
begin
    OpenDialog1.InitialDir:=ExtractFilePath(Application.ExeName);
end;

procedure TfrmMain.log(aMsg: string);
begin
    mmLog.Lines.Add('['+TimeToStr(Time())+'] '+aMsg);
end;

procedure TfrmMain.progress(Sender: TObject; Proc: string; ProcIndex,
  ProcCount: Integer; Table: string; TableIndex, TableCount, Row,
  RowCount: Integer; aResult: TWindecoUpdateResult;var  aStop: Boolean);
begin
    Label1.Caption:=Proc;
    Gauge1.MaxValue:=ProcCount;
    Gauge1.Progress:=ProcIndex;

    if (Proc = 'Update') then
        log(Format('Table:%s %d/%d : %s',[Table,Row,RowCount,TWindecoUpdateResultNotes[integer(aResult)]]));
    if (Proc = 'AfterUpdate') then
        log(Format('%s %d : %s',[Table,TableIndex,TWindecoUpdateResultNotes[integer(aResult)]]));

    aStop:=fStop;
    Application.ProcessMessages;
end;

end.
