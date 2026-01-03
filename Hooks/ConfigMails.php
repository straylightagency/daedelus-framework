<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Filters;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class ConfigMails extends Hook
{
	/**
	 * Disable the internal usage of wp_mail by using the short-circuit and send email using the Illuminate/Mail component instead;
	 * Mails are sent using the .env configuration
	 *
	 * @return void
	 */
    public function register():void
    {
        Filters::add('pre_wp_mail', function ($v, array $wp_mail_args) {
	        $to = $subject = $message = '';
	        $headers = $attachments = [];

			extract( $wp_mail_args );

			$headers = array_filter( Arr::wrap( $headers ) );
			$attachments = array_filter( Arr::wrap( $attachments ) );

			if ( !isset( $headers['Content-Type'] ) ) {
				$headers['Content-Type'] = 'text/html; charset=UTF-8';
			}

	        Mail::to( $to )->sendNow( new class($subject, $message, $headers, $attachments) extends Mailable {
		        use Queueable, SerializesModels;

		        /** @var string */
		        protected string $message;

		        /** @var array */
		        protected array $headers;

		        /**
		         * @param string $subject
		         * @param string $message
		         * @param array $headers
		         * @param array $attachments
		         */
		        public function __construct(string $subject, string $message, array $headers = [], array $attachments = [])
		        {
			        $this->subject = $subject;
			        $this->message = $message;
			        $this->headers = $headers;
			        $this->attachments = $attachments;
		        }

		        /**
		         * @return Envelope
		         */
		        public function envelope(): Envelope
		        {
			        return new Envelope(
				        subject: $this->subject,
			        );
		        }

		        /**
		         * @return Content
		         */
		        public function content(): Content
		        {
			        return new Content(
				        htmlString: $this->message,
			        );
		        }

		        /**
		         * @return Headers
		         */
		        public function headers(): Headers
		        {
			        return new Headers(
				        text: $this->headers,
			        );
		        }

		        /**
		         * Get the attachments for the message.
		         *
		         * @return array<int, Attachment>
		         */
		        public function attachments(): array
		        {
			        return $this->attachments;
		        }
	        } );

			return true;
        }, 10, 5 );
    }
}