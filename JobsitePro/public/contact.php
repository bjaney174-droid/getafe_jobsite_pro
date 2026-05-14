<?php 
require_once '../config/config.php'; 
$message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { 
        $message = '<div class="alert alert-danger">Security error</div>'; 
    } else { 
        $name = sanitize($_POST['name'] ?? ''); 
        $email = sanitize($_POST['email'] ?? ''); 
        $subject = sanitize($_POST['subject'] ?? ''); 
        $message_text = sanitize($_POST['message'] ?? ''); 

        if (empty($name) || empty($email) || empty($subject) || empty($message_text)) { 
            $message = '<div class="alert alert-danger">All fields are required</div>'; 
        } else { 
            // This saves the message to your database so you can read it in the Admin Dashboard
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $subject, $message_text);
            
            if ($stmt->execute()) { 
                $message = '<div class="alert alert-success">✓ Message sent successfully! We will get back to you soon.</div>'; 
            } else { 
                $message = '<div class="alert alert-danger">Error sending message. Please try again.</div>'; 
            }
            $stmt->close();
        } 
    } 
} 

$page_title = 'Contact Us - ' . getSetting('site_name', 'Getafe Jobsite'); 
require_once '../includes/header.php'; 
?> 

<div class="container"> 
    <?php require_once '../includes/navbar.php'; ?> 
    
    <div class="contact-page"> 
        <h1>Contact Us</h1> 
        <p class="subtitle">Have a question? We'd love to hear from you.</p> 
        
        <div class="contact-container"> 
            <div class="contact-form-section"> 
                <?php echo $message; ?> 
                <form method="POST" class="contact-form"> 
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>"> 
                    
                    <div class="form-group"> 
                        <label>Name *</label> 
                        <input type="text" name="name" required placeholder="Your name"> 
                    </div> 
                    
                    <div class="form-group"> 
                        <label>Email *</label> 
                        <input type="email" name="email" required placeholder="your@email.com"> 
                    </div> 
                    
                    <div class="form-group"> 
                        <label>Subject *</label> 
                        <input type="text" name="subject" required placeholder="What is this about?"> 
                    </div> 
                    
                    <div class="form-group"> 
                        <label>Message *</label> 
                        <textarea name="message" required rows="8" placeholder="Your message here..."></textarea> 
                    </div> 
                    
                    <button type="submit" class="btn btn-primary btn-large">Send Message</button> 
                </form> 
            </div> 
            
            <div class="contact-info-section"> 
                <h3>Get In Touch</h3> 
                <div class="contact-info-box"> 
                    <h4>📧 Email</h4> 
                    <p><?php echo getSetting('site_email', 'info@getafejobsite.com'); ?></p> 
                </div> 
                
                <div class="contact-info-box"> 
                    <h4>📱 Phone</h4> 
                    <p><?php echo getSetting('contact_phone', '09701918626'); ?></p> 
                </div> 
                
                <div class="contact-info-box"> 
                    <h4>📍 Address</h4> 
                    <p><?php echo getSetting('address', 'Getafe, Bohol, Philippines'); ?></p> 
                </div> 
                
                <div class="contact-info-box"> 
                    <h4>🌐 Hours</h4> 
                    <p>Monday - Friday: 9:00 AM - 5:00 PM</p> 
                    <p>Saturday - Sunday: Closed</p> 
                </div> 
            </div> 
        </div> 
    </div> 
</div> 

<?php require_once '../includes/footer.php'; ?>
</body> 
</html>
