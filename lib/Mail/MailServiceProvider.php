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
			$mailer->setContainer($app);
			if ($app->bound('log')) {
				$mailer->setLogger($app['log']);
			}
			if ($app->bound('queue')) {
				$mailer->setQueue($app['queue']);
			}
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
