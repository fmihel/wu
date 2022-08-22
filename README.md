# FTP zip pack upload (Delphi) and update (PHP) tables
Пошаговая загрузка, распаковка и обновление таблиц проекта windeco

[1. Пример использования uploader](#example-use-in-pascal)<br/>
[2. Параметры setup.ini](#setup-ini)<br/>
[3. Включить/выключить тесты во время обновления](#run-orders-tests)<br/>
[4. Загрузка видео для личного кабинета](#upload-video)<br/>

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
|ScriptVideo|адрес скритпа обработчика для загружаемых video|https://windeco.su/remote_access_api/wu/server/video.php|

---
# Включить/выключить тесты во время обновления
#### run-orders-tests
Для ВКЛючения тестов,необходимо выполнить GET запрос вида:</br>
``` https://windeco.su/remote_access_api/wu/server/after_update.php?key=XXXX&runOrdersTests=1 ```
<br/>
Для ВЫКЛючения тестов,необходимо выполнить GET запрос вида:</br>
``` https://windeco.su/remote_access_api/wu/server/after_update.php?key=XXXX&runOrdersTests=0 ```
</br>
, где ``` key=XXX ``` - ключь авторизации, его необходимо получить у администратора! 

---
# Загрузка видео для личного кабинета
#### upload-video
---
### ```Внимание! Для имен файлов допустимы лишь буквы латинского алфавита, цифры и символ "_".```
---
### ```Внимание! Не забыть добавить параметр ScriptVideo в ini файл проекта``` см [Параметры setup.ini](#setup-ini)
---


```pascal
Uses UWindecoUpdate;

procedure upload_video(Sender: TObject);
var
  windeco:TWindecoUpdate;
  cFileName:string;
  ID_C_MEDIA_FILE:integer;
  cToPath:string;
  cRes:integer;
begin

    windeco:=TWindecoUpdate.Create();
    try
        { путь к отправляемому файлу на локальном диске }
        cFileName:='c:\video\video-xxx.mpg'; 

        { идентификатор в таблице C_MEDIA_FILE, если такой записи не существует, она будет создана }
        ID_C_MEDIA_FILE:=12345;              

        { папка, в котрой будет лежать отправляемый файл на сервере, }
        cToPath:='karniz';
        
        {отправка файла, результат: 
            0       - все нормально
            1,2,..  - ошибка
        }
        cRes:=windeco.UploadVideo(cFileName,ID_C_MEDIA_FILE,cToPath);

    finally
        windeco.Free();

    end;
end;
```






