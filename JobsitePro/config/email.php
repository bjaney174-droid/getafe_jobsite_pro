<?php
class EmailSender {
    private $from;
    private $site_name;

    public function __construct() {
        $this->from = getSetting('site_email', 'info@getafejobsite.com');
        $this->site_name = getSetting('site_name', 'Getafe Jobsite');
    }

    public function sendApplicationConfirmation($to, $user_name, $job_title, $company) {
        $subject = "Application Received - $job_title";
        
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; }
                    .email-header { background: #007bff; color: white; padding: 20px; text-align: center; }
                    .email-body { background: white; padding: 30px; }
                    .email-footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <h2>🏢 {$this->site_name}</h2>
                    </div>
                    <div class='email-body'>
                        <p>Hi $user_name,</p>
                        <p>Thank you for applying to the position of <strong>$job_title</strong> at <strong>$company</strong>.</p>
                        <p>We have received your application and will review it shortly. We'll contact you soon with updates.</p>
                        <p>Best regards,<br>The {$this->site_name} Team</p>
                    </div>
                    <div class='email-footer'>
                        <p>© 2026 {$this->site_name}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return $this->send($to, $subject, $message);
    }

    public function sendJobPostedNotification($to, $employer_name, $job_title) {
        $subject = "Your Job Posting is Live - $job_title";
        
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; }
                    .email-header { background: #28a745; color: white; padding: 20px; text-align: center; }
                    .email-body { background: white; padding: 30px; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <h2>✓ Job Posted Successfully</h2>
                    </div>
                    <div class='email-body'>
                        <p>Hi $employer_name,</p>
                        <p>Your job posting for <strong>$job_title</strong> is now live!</p>
                        <p>Candidates can now see and apply to your position.</p>
                        <p>Best regards,<br>The {$this->site_name} Team</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return $this->send($to, $subject, $message);
    }

    private function send($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->from . "\r\n";

        return mail($to, $subject, $message, $headers);
    }
}
?>