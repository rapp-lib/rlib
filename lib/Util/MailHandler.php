<?php
namespace R\Lib\Util;

/**
 * 送信メールの作成を行うクラス
 */
class MailHandler
{
    protected $mailer;
    protected $template_dir;
    private $vars = array();
    public function __construct ()
    {
        $this->mailer = new \PHPMailer;
        $this->mailer->CharSet = "UTF-8";
        $this->mailer->Encoding = "base64";
        $this->template_dir = constant("R_APP_ROOT_DIR")."/mail";
    }
    public function assign ($name, $value=null)
    {
        if (is_array($name)) foreach ($name as $k=>$v) $this->assign($k, $v);
        else \R\Lib\Util\Arr::array_add($this->vars, $name, $value);
    }
    public function load ($template_filename, $assign=array())
    {
        $template_file = $this->template_dir."/".$template_filename;
        $template_file = app()->i18n->getLocalizedFile($template_file);
        $this->assign($assign);
        ob_start();
        try {
            $mail = $this;
            include($template_file);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $this->mailer->Body .= ob_get_clean();
        return $this;
    }
    public function subject ($subject)
    {
        $this->mailer->Subject = $subject;
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
            report_info("Mail Sent",array(
                "mailer" => $this->mailer,
            ));
        } else {
            report_warning("Mail Send Failure : ".$this->mailer->ErrorInfo,array(
                "mailer" => $this->mailer,
            ));
        }
        return $result;
    }
}
