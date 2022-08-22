object frmMain: TfrmMain
  Left = 800
  Top = 405
  BorderStyle = bsDialog
  Caption = 'example Windeco Update'
  ClientHeight = 554
  ClientWidth = 630
  Color = cl3DLight
  Ctl3D = False
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -16
  Font.Name = 'Arial'
  Font.Style = []
  OldCreateOrder = False
  Position = poScreenCenter
  Scaled = False
  Visible = True
  OnCreate = FormCreate
  PixelsPerInch = 96
  TextHeight = 18
  object Gauge1: TGauge
    Left = 24
    Top = 278
    Width = 584
    Height = 28
    Progress = 0
  end
  object Label1: TLabel
    Left = 24
    Top = 246
    Width = 70
    Height = 18
    Caption = 'Process...'
  end
  object Edit2: TEdit
    Left = 24
    Top = 21
    Width = 505
    Height = 24
    ReadOnly = True
    TabOrder = 0
    Text = 'E:\work\windeco\wu\client\release\DecoR_04062019_05062019_K.zip'
  end
  object mmLog: TMemo
    Left = 25
    Top = 320
    Width = 585
    Height = 226
    Font.Charset = DEFAULT_CHARSET
    Font.Color = clGray
    Font.Height = -16
    Font.Name = 'Courier New'
    Font.Style = []
    Lines.Strings = (
      'log..')
    ParentFont = False
    ScrollBars = ssBoth
    TabOrder = 1
    WordWrap = False
  end
  object Button7: TButton
    Left = 24
    Top = 51
    Width = 177
    Height = 46
    Action = actRun
    TabOrder = 2
  end
  object Button1: TButton
    Left = 224
    Top = 51
    Width = 177
    Height = 46
    Action = actStop
    TabOrder = 3
  end
  object Button2: TButton
    Left = 535
    Top = 21
    Width = 75
    Height = 25
    Caption = '...'
    TabOrder = 4
    OnClick = Button2Click
  end
  object GroupBox1: TGroupBox
    Left = 25
    Top = 120
    Width = 584
    Height = 105
    Caption = #1042#1080#1076#1077#1086
    TabOrder = 5
    object Label2: TLabel
      Left = 22
      Top = 26
      Width = 30
      Height = 21
      Caption = 'path'
    end
    object Label3: TLabel
      Left = 22
      Top = 66
      Width = 137
      Height = 18
      Caption = 'ID_C_MEDIA_FILE'
    end
    object Button3: TButton
      Left = 416
      Top = 40
      Width = 153
      Height = 41
      Action = actUploadVideo
      TabOrder = 0
    end
    object Edit1: TEdit
      Left = 176
      Top = 24
      Width = 201
      Height = 24
      TabOrder = 1
    end
    object Edit3: TEdit
      Left = 176
      Top = 64
      Width = 200
      Height = 24
      NumbersOnly = True
      TabOrder = 2
      Text = '1'
    end
  end
  object ActionList1: TActionList
    Left = 364
    Top = 379
    object actRun: TAction
      Caption = #1047#1072#1087#1091#1089#1090#1080#1090#1100
      OnExecute = actRunExecute
    end
    object actStop: TAction
      Caption = #1055#1088#1077#1088#1074#1072#1090#1100
      OnExecute = actStopExecute
    end
    object actUploadVideo: TAction
      Caption = #1047#1072#1075#1088#1091#1079#1082#1072' '#1074#1080#1076#1077#1086
      OnExecute = actUploadVideoExecute
    end
  end
  object OpenDialog1: TOpenDialog
    Filter = 'zip|*.zip'
    Left = 264
    Top = 376
  end
  object OpenDialog2: TOpenDialog
    Options = [ofHideReadOnly, ofFileMustExist, ofEnableSizing]
    Left = 264
    Top = 448
  end
end
