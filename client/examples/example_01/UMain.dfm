object frmMain: TfrmMain
  Left = 800
  Top = 405
  BorderStyle = bsDialog
  Caption = 'example Windeco Update'
  ClientHeight = 489
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
    Top = 150
    Width = 584
    Height = 28
    Progress = 0
  end
  object Label1: TLabel
    Left = 24
    Top = 118
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
    Left = 24
    Top = 224
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
  object Button3: TButton
    Left = 423
    Top = 52
    Width = 177
    Height = 46
    Action = actUploadVideo
    TabOrder = 5
  end
  object ActionList1: TActionList
    Left = 444
    Top = 123
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
    Left = 344
    Top = 120
  end
  object OpenDialog2: TOpenDialog
    Options = [ofHideReadOnly, ofFileMustExist, ofEnableSizing]
    Left = 344
    Top = 192
  end
end
