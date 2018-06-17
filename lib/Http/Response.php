<?php
namespace R\Lib\Http;
use Zend\Diactoros\Response as DiactorosResponse;

class Response extends DiactorosResponse
{
    public function getContentType()
    {
        return strtolower($this->getHeaderLine('Content-Type'));
    }

// -- キャッシュ設定

    public function withExpires($time)
    {
        return $this->withHeader('Expires', $this->getTimeFromValue($time));
    }
    public function withMaxAge($time)
    {
        return $this->withHeader('Max-age', $this->getTimeFromValue($time));
    }
    protected function getTimeFromValue($time)
    {
        $format = 'D, d M Y H:i:s \G\M\T';
        if (is_int($time)) {
            return gmdate($format, $time);
        } elseif (is_string($time)) {
            try {
                $time = new \DateTime($time);
            } catch (\Exception $exception) {
            }
        } elseif ($time instanceof \DateTime) {
            $time = clone $time;
            $time->setTimezone(new \DateTimeZone('UTC'));
            return $time->format($format);
        }
        return null;
    }

    public function __report()
    {
        $body = $this->getBody();
        if (preg_match('!/json!', $this->getContentType())) $body = json_decode($this->getBody(), true);
        return array(
            "content-type"=>$this->getContentType(),
            "status_code"=>$this->getStatusCode(),
            "headers"=>$this->getHeaders(),
            "body"=>$body,
        );
    }
}
