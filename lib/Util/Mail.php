<?php
namespace R\Lib\Util;
/*
    util("Mail")
        ->factory()
        ->import("register-thanks.php", array("form"=>$this->forms["entry"]))
        ->send();
    // -- register-thanks.php
    $mail->import("include/common.php", $vars);
    $mail->from("user@example.com", "サービス管理者");
    $mail->smtp(array(
        "auth" => true,
        "secure" => "tls",
        "host" => "@example.com",
        "port" => 587,
        "username" => "user@example.com",
        "password" => "xxxxxxx",
    ));
 */

/**
 *
 */
class Mail
{
    protected $mailer;
    public static function factory ()
    {
        return new Mail;
    }
    public function __construct ()
    {
        $this->mailer = new \PHPMailer;
    }
    public function getTemplateDir ()
    {
        return app()->getAppRootDir()."/mail";
    }

// -- impoort制御とvars管理

    protected $vars = array();
    public function getAssigned ($name)
    {
        return $this->vars[$name];
    }
    public function assign ($name, $value=null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->assign($k,$v);
            }
        } else {
            $this->vars[$name] = $value;
        }
        return $this;
    }
    public function import ($template_filename, $assign=array())
    {
        if (isset($assign)) {
            $this->assign($assign);
        }
        report_buffer_start();
        $mail = $this;
        include($this->getTemplateDir()."/".$template_filename);
        $mail->endBody();
        report_buffer_end();
        return $this;
    }

// -- body設定

    protected $body_started = 0;
    public function startBody ()
    {
        $this->body_started++;
        ob_start();
        return $this;
    }
    public function endBody ()
    {
        if ($this->body_started) {
            $this->body_started--;
            $body = ob_get_clean();
            $lines = explode("\n", $body);
            if (preg_match('!^subject\s*:\s*(.+)$!',trim($lines[0]),$match)) {
                $this->mailer->Subject = $match[1];
                array_shift($lines);
                if (strlen(trim($lines[0]))==0) {
                    array_shift($lines);
                }
            }
            $this->mailer->Body .= implode("\n", $lines);
        }
        return $this;
    }
    public function from ($mail, $name=null)
    {
        $this->mailer->setFrom($mail,$name);
        return $this;
    }
    public function to ($mail, $name=null)
    {
        $this->mailer->addAddress($mail,$name);
        return $this;
    }
    public function cc ($mail, $name=null)
    {
        $this->mailer->addCC($mail,$name);
        return $this;
    }
    public function bcc ($mail, $name=null)
    {
        $this->mailer->addBCC($mail,$name);
        return $this;
    }
    public function attachment ($file, $filename=null)
    {
        $this->mailer->addAttachment($file,$filename);
        return $this;
    }
    public function replyTo ($mail, $name=null)
    {
        $this->mailer->addReplyTo($mail,$name);
        return $this;
    }
    public function isHTML ($is_html=true)
    {
        $this->mailer->isHTML($is_html);
        return $this;
    }
    public function smtp ($smtp_config)
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $smtp_config["host"];
        $this->mailer->Port = $smtp_config["port"];
        $this->mailer->SMTPSecure = $smtp_config["secure"];
        $this->mailer->SMTPAuth = $smtp_config["auth"];
        $this->mailer->Username = $smtp_config["username"];
        $this->mailer->Password = $smtp_config["password"];
        return $this;
    }
    public function send ()
    {
        report($this->mailer);
        return $this->mailer->send();
    }
}
