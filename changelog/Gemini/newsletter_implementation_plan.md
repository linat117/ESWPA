# Newsletter Functionality: Analysis and Implementation Plan

## 1. Analysis

After a thorough review of the admin panel and the database, I've determined that there is currently no existing email newsletter functionality. However, the necessary components are in place to build this feature.

- **Content Creation:** The `admin` directory contains forms for adding events (`add_event.php`), news (`add_news.php`), and posts (`add_post.php`), which are the triggers for sending a newsletter.
- **Subscriber List:** The `registrations` table in the `ethiosdt_database` contains an `email` column, which can serve as the source for our newsletter subscriber list.

## 2. Implementation Plan

Here is a step-by-step guide to integrating the newsletter feature:

### Step 1: Enhance the User Interface

I will add a checkbox to each of the content creation forms (`add_event.php`, `add_news.php`, `add_post.php`).

**Example for `add_event.php`:**
```html
<div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="send_newsletter" name="send_newsletter" value="1">
    <label class="form-check-label" for="send_newsletter">Send as Newsletter</label>
</div>
```

### Step 2: Develop the Backend Logic

I will update the form handler files (e.g., `admin/include/send_event.php`) to include the newsletter logic.

**Example Logic for `send_event.php`:**
```php
if (isset($_POST['send_newsletter']) && $_POST['send_newsletter'] == '1') {
    // 1. Get the new event data
    $event_title = $_POST['event_header'];
    $event_description = $_POST['event_description'];
    // ... other event details

    // 2. Fetch subscriber emails
    $sql = "SELECT email FROM registrations WHERE email IS NOT NULL AND email != ''";
    $result = $conn->query($sql);
    $subscribers = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $subscribers[] = $row['email'];
        }
    }

    // 3. Create the email content
    $subject = "New Event: " . $event_title;
    $body = "<h1>" . $event_title . "</h1>";
    $body .= "<p>" . $event_description . "</p>";
    // ... format the rest of the email body

    // 4. Send the emails
    if (!empty($subscribers)) {
        // We will use a dedicated function for sending emails
        sendNewsletter($subject, $body, $subscribers);
    }
}
```

### Step 3: Implement a Reusable Email Function

To avoid code duplication, I will create a central function for sending emails. This function will leverage the **PHPMailer** library for reliability. We will need to install PHPMailer first.

**Installation (using Composer):**
```bash
composer require phpmailer/phpmailer
```

**Example `sendNewsletter` function:**
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/vendor/autoload.php';

function sendNewsletter($subject, $body, $recipients) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@example.com'; // Your SMTP username
        $mail->Password   = 'your_smtp_password'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Sender
        $mail->setFrom('from@example.com', 'Ethio Social Works');

        //Recipients
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient);
        }

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
```
This new function would be placed in a central include file, for example `admin/include/email_handler.php`.

This plan provides a clear path to implementing a robust and maintainable newsletter feature. 