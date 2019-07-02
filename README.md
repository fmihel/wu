# FTP zip pack upload (Delphi) and update (PHP) tables
Пошаговая загрузка, распаковка и обновление таблиц проекта windeco

## Пример использования uploader

```pascal
Uses UWindecoUpdate;

procedure upload(Sender: TObject);
var
  windeco:TWindecoUpdate;
  res: TWindecoUpdateResult;
begin
    windeco:=TWindecoUpdate.Create();
    try
        // указать,  с каким файлом работаем
        windeco.ZipFileName:='decoR_019890.zip';
        // запуск процесса обновления
        res:=windeco.Run();
    finally
        windeco.Free();

    end;

end;
```

## Параметры setup.ini
Все неастройки windecoUpdate вынесены в файл `windeco_update.ini`. Доступны следующие параметры

|Параметр|Описание|Пример|
|----|----|----|
|ScriptUpdate|адрес скрипта пошагового обновления|http://windeco/wu/server/update.php|
|ScriptUnpack|адрес скрипта распаковки|http://windeco/wu/server/unpack.php|
|ScriptEnd|адрес скрипта очистки|http://windeco/wu/server/clear.php|
|ScriptList|адрес скритпа списка файлов|http://windeco/wu/server/list.php|
|AfterUpdateScript|адрес скрипта запускаемого в конце процесса|http://windeco/wu/server/after_update.php|
|CountPacksAtTime|Кол-строк обновляемых за раз|100|
|Key|ключ автризации|test|
|FtpHost|ftp host|u4673.ftp.masterhost.ru|
|FtpUsername|ftp user name|userName|
|FtpPassword|ftp password|wjdkjw|

