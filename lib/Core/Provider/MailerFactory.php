<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;
/*
    app()->mailer
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
class MailerFactory implements InvokableProvider
{
    public function invoke ($template_filename, $assign=array())
    {
        return $this->factory()->import($template_filename, $assign);
    }
    public function factory ()
    {
        return new Mail;
    }
}
/**
 *
 */
class Mail
{
    protected $mailer;
    public function __construct ()
    {
        $this->mailer = new \PHPMailer;
        $this->mailer->CharSet = "UTF-8";
        $this->mailer->Encoding = "base64";
    }
    public function getTemplateDir ()
    {
        return constant("R_APP_ROOT_DIR")."/mail";
    }

// -- impoort制御とvars管理

    public $vars = array();
    public function assign ($name, $value=null)
    {
        array_add($this->vars, $name, $value);
    }
    public function import ($template_filename, $assign=array())
    {
        $this->assign($assign);
        $mail = $this;
        include($this->getTemplateDir()."/".$template_filename);
        if ($this->body_started) {
            $mail->endBody();
        }
        return $this;
    }

// -- body設定

    protected $body_started = 0;
    public function startBody ()
    {
        if ($this->body_started) {
            report_error("既にBody設定が開始されています",array(
                "mailer" => $this,
            ));
        }
        $this->body_started = true;
        ob_start();
        return $this;
    }
    public function endBody ()
    {
        if ( ! $this->body_started) {
            report_error("Body設定が開始されていません",array(
                "mailer" => $this,
            ));
        }
        $body = ob_get_clean();
        $this->body_started = false;
        $lines = explode("\n", $body);
        if (preg_match('!^subject\s*:\s*(.+)$!',trim($lines[0]),$match)) {
            $this->mailer->Subject = $match[1];
            array_shift($lines);
            if (strlen(trim($lines[0]))==0) {
                array_shift($lines);
            }
        }
        $this->mailer->Body .= implode("\n", $lines);
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
        $result = $this->mailer->send();
        if ($result) {
            report("メール送信完了",array(
                "mailer" => $this->mailer,
            ));
        } else {
            report_warning("メール送信失敗",array(
                "mailer" => $this->mailer,
                "error" => $this->mailer->ErrorInfo,
            ));
        }
        return $result;
    }
}
