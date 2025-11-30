<?php
class Mailer {
    public static function sendOTP($email, $otp) {
        $subject = 'Your OTP for CCET Student Vault';
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 30px; }
                    .otp { font-size: 32px; font-weight: bold; color: #2563eb; text-align: center; letter-spacing: 5px; }
                    .footer { text-align: center; color: #64748b; font-size: 12px; padding: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>CCET Student Vault</h1>
                    </div>
                    <div class='content'>
                        <h2>Your One-Time Password</h2>
                        <p>Use this OTP to log in to CCET Student Vault admin panel:</p>
                        <p class='otp'>$otp</p>
                        <p><strong>Valid for " . OTP_EXPIRY_MINUTES . " minutes</strong></p>
                        <p>If you didn't request this OTP, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 CCET Student Vault. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";

        // For production, use PHPMailer or similar with SMTP
        // This is a simple implementation
        if (mail($email, $subject, $message, $headers)) {
            return true;
        }

        // Log to file for testing (when mail() is not configured)
        error_log("OTP for $email: $otp");
        return true;
    }
}
?>
