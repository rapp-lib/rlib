<?php
namespace R\Lib\Mail;

use \Illuminate\Mail\MailServiceProvider as IlluminateMailServiceProvider;

class MailServiceProvider extends IlluminateMailServiceProvider
{
	public function register()
	{
		$me = $this;
		$this->app->bindShared('mailer', function($app) use ($me)
		{
			$me->registerSwiftMailer();
			$mailer = new Mailer(
				$app['view'], $app['swift.mailer'], $app['events']
			);
			$this->setMailerDependencies($mailer, $app);
			$from = $app['config']['mail.from'];
			if (is_array($from) && isset($from['address'])){
				$mailer->alwaysFrom($from['address'], $from['name']);
			}
			$pretend = $app['config']->get('mail.pretend', false);
			$mailer->pretend($pretend);
			return $mailer;
		});
	}
}
