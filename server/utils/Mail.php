<?php
namespace wu\utils;

use fmihel\console;

class Mail
{

    public static function send($ToMail, $FromMail, $Theme, $Message, $coding = 'UTF-8' /*windows-1251*/)
    {
        $headers = 'From: ' . $FromMail . "\r\n" . 'Reply-To: ' . $FromMail . "\r\n";
        //$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'Content-type: text/html; charset=' . $coding . "\r\n";
        return @mail($ToMail, $Theme, $Message, $headers);
    }

    public static function sendToAdmin($param = [])
    {
        try {
            $br = "<br>\n";

            $p = array_merge([
                'emails' => ['fmihel76@gmail.com'],
                'from' => 'info@windeco.su',
                'header' => 'системная ошибка (только для администраторов)',
                'msg' => '',
                'footer' => '',
                'coding' => 'UTF-8',
            ], $param);

            //---------------------------------------------------------------
            $emails = $p['emails'];
            if (!is_array($emails)) {
                throw new \Exception('list of emails must be array');
            }

            if (count($emails) === 0) {
                throw new \Exception('list of emails is empty');
            }
            //---------------------------------------------------------------
            $msg = mb_convert_encoding($p['msg'], 'UTF-8'); // 'windows-1251','utf-8');
            $msg .= $br . $p['footer'];
            //---------------------------------------------------------------

            foreach ($emails as $email) {
                try {
                    self::send($email, $p['from'], $p['header'], $msg, $p['coding']);
                } catch (\Exception $e) {
                    console::error($e);
                };
            }

            //---------------------------------------------------------------

        } catch (\Exception $e) {
            console::error($e);
        };

    }
}
