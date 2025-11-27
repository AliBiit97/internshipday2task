<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
   
    $contact_errors = [];
    $contact_success = '';
    
    if (empty($name)) $contact_errors[] = "Name is required";
    if (empty($email)) $contact_errors[] = "Email is required";
    if (empty($subject)) $contact_errors[] = "Subject is required";
    if (empty($message)) $contact_errors[] = "Message is required";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_errors[] = "Invalid email format";
    }
    
    if (empty($contact_errors)) {
        // Here you would typically:
        // 1. Save to database
        // 2. Send email
        // 3. etc.
        
        $contact_success = "Thank you for your message! We'll get back to you within 24 hours.";
        
        // Clear form
        $name = $email = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact & Support - Our Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            line-height: 1.6;
        }

        .support-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: white;
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.2em;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-card {
            grid-column: 1;
        }

        .faq-card {
            grid-column: 2;
        }

        .policies-card {
            grid-column: 1 / -1;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            font-size: 1.8em;
        }

        h3 {
            color: #667eea;
            margin: 20px 0 10px 0;
            font-size: 1.3em;
        }

        /* Contact Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        /* FAQ Styles */
        .faq-item {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .faq-question {
            background: #f8f9fa;
            padding: 15px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }

        .faq-question:hover {
            background: #e9ecef;
        }

        .faq-answer {
            padding: 15px;
            background: white;
            display: none;
        }

        .faq-answer.show {
            display: block;
        }

        .faq-toggle {
            font-size: 1.2em;
            transition: transform 0.3s;
        }

        .faq-toggle.rotated {
            transform: rotate(45deg);
        }

        /* Contact Methods */
        .contact-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .contact-method {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s;
        }

        .contact-method:hover {
            transform: translateY(-5px);
        }

        .contact-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .contact-method h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .contact-method p {
            color: #666;
        }

        .contact-link {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .contact-link:hover {
            text-decoration: underline;
        }

        /* Policy Sections */
        .policy-section {
            margin-bottom: 25px;
        }

        .policy-section:last-child {
            margin-bottom: 0;
        }

        .policy-section h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .policy-section p {
            color: #666;
            margin-bottom: 8px;
        }

        .policy-list {
            list-style: none;
            padding-left: 0;
        }

        .policy-list li {
            padding: 5px 0;
            color: #666;
            position: relative;
            padding-left: 20px;
        }

        .policy-list li:before {
            content: "•";
            color: #667eea;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Messages */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #dc3545;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-card,
            .faq-card {
                grid-column: 1;
            }
            
            .header h1 {
                font-size: 2.2em;
            }
        }
    </style>
</head>
<body>
    <div class="support-container">
        <div class="header">
            <h1>📞 Contact & Support</h1>
            <p>We're here to help you with any questions or concerns</p>
        </div>

        <div class="content-grid">
            <!-- Contact Form -->
            <div class="card contact-card">
                <h2>💬 Get In Touch</h2>
                
                <?php if (!empty($contact_errors)): ?>
                    <div class="error-message">
                        <?php foreach ($contact_errors as $error): ?>
                            <div>❌ <?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($contact_success)): ?>
                    <div class="success-message">
                        ✅ <?= $contact_success ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="Order Issue" <?= ($subject ?? '') === 'Order Issue' ? 'selected' : '' ?>>Order Issue</option>
                            <option value="Product Question" <?= ($subject ?? '') === 'Product Question' ? 'selected' : '' ?>>Product Question</option>
                            <option value="Shipping Inquiry" <?= ($subject ?? '') === 'Shipping Inquiry' ? 'selected' : '' ?>>Shipping Inquiry</option>
                            <option value="Return Request" <?= ($subject ?? '') === 'Return Request' ? 'selected' : '' ?>>Return Request</option>
                            <option value="Technical Support" <?= ($subject ?? '') === 'Technical Support' ? 'selected' : '' ?>>Technical Support</option>
                            <option value="Other" <?= ($subject ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Please describe your issue or question in detail..."><?= htmlspecialchars($message ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_contact" class="submit-btn">Send Message</button>
                </form>

                <div class="contact-methods">
                    <div class="contact-method">
                        <div class="contact-icon">📧</div>
                        <h4>Email Us</h4>
                        <p><a href="mailto:support@ecomstore.com" class="contact-link">support@ecomstore.com</a></p>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">📞</div>
                        <h4>Call Us</h4>
                        <p><a href="tel:+11234567890" class="contact-link">051-8374383</a></p>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">💬</div>
                        <h4>Live Chat</h4>
                        <p>Available 24/7</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card faq-card">
                <h2>❓ Frequently Asked Questions</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How long does shipping take?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Standard shipping takes 3-5 business days. Express shipping is available for 1-2 business days. International shipping may take 7-14 business days depending on the destination.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        What is your return policy?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        We offer a 30-day return policy for all items in original condition. Items must be unused with tags attached. Refunds will be processed within 5-7 business days after we receive the return.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Do you offer international shipping?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Yes! We ship to over 50 countries worldwide. Shipping costs and delivery times vary by location. You'll see the exact shipping cost at checkout.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How can I track my order?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Once your order ships, you'll receive a tracking number via email. You can also track your order by logging into your account and visiting the "Order History" section.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        What payment methods do you accept?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        We accept all major credit cards (Visa, MasterCard, American Express), PayPal, Apple Pay, and Google Pay. All payments are processed securely.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Can I modify or cancel my order?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Orders can be modified or cancelled within 1 hour of placement. After that, orders enter our processing system and cannot be changed. Please contact us immediately if you need to make changes.
                    </div>
                </div>
            </div>

            <!-- Policies Section -->
            <div class="card policies-card">
                <h2>📋 Store Policies</h2>
                
                <div class="policy-section">
                    <h4>🚚 Shipping Policy</h4>
                    <p>We strive to process and ship all orders within 24-48 hours of placement.</p>
                    <ul class="policy-list">
                        <li>Free standard shipping on orders over 5000</li>
                        <li>Express shipping available for 200</li>
                        <li>Same-day shipping for orders placed before 2 PM EST</li>
                        <li>Tracking information provided for all orders</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h4>🔄 Return & Exchange Policy</h4>
                    <p>Your satisfaction is our priority. We make returns easy and hassle-free.</p>
                    <ul class="policy-list">
                        <li>30-day return window from delivery date</li>
                        <li>Items must be in original condition with tags</li>
                        <li>Free return shipping for defective items</li>
                        <li>Exchanges available for different sizes/colors</li>
                        <li>Refunds processed within 5-7 business days</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h4>🔒 Privacy & Security</h4>
                    <p>We take your privacy and security seriously.</p>
                    <ul class="policy-list">
                        <li>SSL encrypted checkout process</li>
                        <li>We never store your payment information</li>
                        <li>Your data is never shared with third parties</li>
                        <li>Secure account protection</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h4>⏰ Customer Support Hours</h4>
                    <p>We're here to help you whenever you need us.</p>
                    <ul class="policy-list">
                        <li>Monday - Friday: 9:00 AM - 8:00 PM EST</li>
                        <li>Saturday: 10:00 AM - 6:00 PM EST</li>
                        <li>Sunday: 12:00 PM - 5:00 PM EST</li>
                        <li>24/7 email support with 24-hour response time</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
   
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const toggle = element.querySelector('.faq-toggle');
            
            answer.classList.toggle('show');
            toggle.classList.toggle('rotated');
        }

  
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('message');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>