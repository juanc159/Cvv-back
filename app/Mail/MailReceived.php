<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public $view;

    public $subject;

    public $files;

    /**
     * Create a new message instance.
     */
    public function __construct($view, $subject, $data, $files)
    {
        $this->view = $view;
        $this->subject = $subject;
        $this->data = $data;
        $this->files = $files;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this
            ->view($this->view)
            ->subject($this->subject)
            ->with('data', $this->data);

        foreach ($this->files as $archivo) {
            $f = str_replace(request()->root(), '', $archivo);
            $message->attach(public_path($f));
        }

        return $message;
    }
}
