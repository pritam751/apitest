<?php
namespace App\Helpers;

use Mail;

/**
 * Mail helper to send mail
 *
 * @author chetanaher
 */
class MailHelper {
   
    /**
     * Common mail functionality for mail using template 
     * 
     * @param string $viewToPass
     * @param array $dataForView
     * @param array $emails
     * @param string $subject
     * @return \Mail returns mail object 
     */
    public function sendMail($viewToPass, $dataForView, $emails, $subject)
    {
        Mail::send($viewToPass, $dataForView, function($message) use ($emails, $subject)
        {   
            $message->to($emails)->subject($subject);
        });
        
        return Mail::failures();
    }
}
