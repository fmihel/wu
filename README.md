# FTP zip pack upload (Delphi) and update (PHP) tables
Пошаговая загрузка, распаковка и обновление таблиц проекта windeco

[1. Пример использования uploader](#example-use-in-pascal)<br/>
[2. Параметры setup.ini](#setup-ini)<br/>
[3. Включить/выключить тесты во время обновления](#run-orders-tests)<br/>

---
# Пример использования uploader
#### example-use-in-pascal 

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

---
# Параметры setup.ini
#### setup-init
Все неастройки windecoUpdate вынесены в файл `windeco_update.ini`. Доступны следующие параметры

|Параметр|Описание|Пример|
|----|----|----|
|ScriptUpdate|адрес скрипта пошагового обновления|http://windeco/wu/server/update.php|
|ScriptUnpack|адрес скрипта распаковки|http://windeco/wu/server/unpack.php|
|ScriptEnd|адрес скрипта очистки|http://windeco/wu/server/clear.php|
|ScriptList|адрес скритпа списка файлов|http://windeco/wu/server/list.php|
|AfterUpdateScript|адрес скрипта запускаемого в конце процесса|http://windeco/wu/server/after_update.php|
|CountPacksAtTime|Кол-строк обновляемых за раз|100|
|Key|ключ автризации|xxxxxx|
|FtpHost|ftp host|u4673.ftp.masterhost.ru|
|FtpUsername|ftp user name|xxxxxx|
|FtpPassword|ftp password|xxxxxx|

---
# Включить/выключить тесты во время обновления
#### run-orders-tests
Для ВКЛючения тестов,необходимо выполнить GET запрос вида:
``` https://windeco.su/remote_access_api/wu/server/after_update.php?key=XXXX&runOrdersTests=1 ```
<br/>
Для ВЫКЛючения тестов,необходимо выполнить GET запрос вида:
``` https://windeco.su/remote_access_api/wu/server/after_update.php?key=XXXX&runOrdersTests=0 ```
</br>
, где ``` key=XXX ``` - ключь авторизации, его необходимо получить у администратора! 

