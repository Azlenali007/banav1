-- ApexSMM Panel - Essential Initial Seed Data
-- Only system roles, permissions, default payment method slots, theme defaults, and editable landing/static pages.
-- Zero fake users, fake orders, fake reviews, or fake payments.

INSERT INTO `admin_roles` (`id`, `name`, `slug`, `description`, `is_super_admin`) VALUES
(1, 'Super Admin', 'super_admin', 'Unrestricted access to all system modules, staff, and security settings.', 1),
(2, 'Support Staff', 'support_staff', 'Handles support tickets, orders, and manual payment reviews.', 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT IGNORE INTO `admin_permissions` (`role_id`, `permission_key`) VALUES
(2, 'dashboard.view'),
(2, 'users.view'),
(2, 'orders.view'),
(2, 'orders.manage'),
(2, 'payments.view'),
(2, 'payments.manage'),
(2, 'tickets.view'),
(2, 'tickets.manage');

INSERT INTO `payment_methods` (`code`, `name`, `description`, `is_enabled`, `min_amount`, `max_amount`, `instructions`, `sort_order`) VALUES
('razorpay', 'Razorpay (UPI / Cards / NetBanking)', 'Instant automated wallet funding via Razorpay checkout.', 0, 10.00, 100000.00, 'Pay securely with UPI, Debit/Credit Card, or NetBanking via Razorpay.', 1),
('manual', 'Manual Bank / UPI Transfer', 'Submit manual payment details for admin verification and wallet credit.', 1, 50.00, 100000.00, 'Configure your UPI ID or Bank Account details in Admin > Payments before accepting live manual deposits.', 2)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`) VALUES
('general', 'site_name', 'ApexSMM'),
('general', 'site_tagline', 'Automated Social Media Marketing Panel'),
('general', 'currency_code', 'INR'),
('general', 'currency_symbol', '₹'),
('general', 'timezone', 'Asia/Kolkata'),
('general', 'registration_enabled', '1'),
('general', 'maintenance_mode', '0'),
('general', 'active_theme', 'classic'),
('general', 'logo_url', ''),
('general', 'favicon_url', ''),
('seo', 'meta_title', 'ApexSMM — Professional Social Media Marketing Panel'),
('seo', 'meta_description', 'Reliable SMM panel with automated order processing, real-time tracking, multi-provider routing, and instant wallet funding.'),
('seo', 'meta_keywords', 'smm panel, social media marketing, automated smm services, reseller smm api'),
('landing', 'hero_badge', 'Automated Order Infrastructure'),
('landing', 'hero_title', 'Scale Social Campaigns With Precision & Speed'),
('landing', 'hero_subtitle', 'A transparent, API-driven social media marketing panel built for agencies, creators, and resellers with real-time order tracking and atomic wallet accounting.'),
('landing', 'why_choose_title', 'Engineered for Reliability'),
('landing', 'why_choose_subtitle', 'Every order, wallet ledger entry, and provider sync is tracked with database-level integrity.'),
('landing', 'features_json', '[{"title":"Real-Time Order Engine","desc":"Instant validation, atomic wallet deduction, and automated provider routing with full status logs."},{"title":"Reseller REST API","desc":"Standard JSON API endpoints for balance, services, order placement, status, and refills."},{"title":"Verified Payment Gateways","desc":"Server-verified Razorpay checkout and audited manual deposit workflows with immutable ledger records."},{"title":"24/7 Ticket Support","desc":"Direct communication channel with staff admins linked to your orders and transactions."}]'),
('landing', 'faq_json', '[{"q":"How do I place my first order?","a":"Create an account, add funds to your wallet via Razorpay or manual transfer, choose a category and service, enter your target link and quantity, and submit."},{"q":"How does wallet accounting work?","a":"Every deposit, order charge, and automatic refund is recorded in an immutable transaction ledger tied to your account."},{"q":"What happens if a provider fails to accept an order?","a":"If an upstream provider rejects or fails an order, the system automatically logs the failure and refunds the full order amount back to your wallet."},{"q":"Do you provide an API for resellers?","a":"Yes. Once signed in, visit the API Access page to generate your personal API key and view endpoint documentation."}]'),
('contact', 'contact_email', 'support@example.com'),
('contact', 'contact_phone', ''),
('contact', 'contact_address', ''),
('contact', 'support_hours', 'Mon–Sat, 9:00 AM – 8:00 PM IST'),
('footer', 'footer_about', 'ApexSMM provides structured social media marketing fulfillment tools with transparent pricing and verifiable order tracking.'),
('footer', 'footer_copyright', 'All rights reserved.'),
('mail', 'mail_driver', 'php_mail'),
('mail', 'mail_from_email', 'noreply@example.com'),
('mail', 'mail_from_name', 'ApexSMM'),
('mail', 'smtp_host', ''),
('mail', 'smtp_port', '587'),
('mail', 'smtp_username', ''),
('mail', 'smtp_password_encrypted', ''),
('mail', 'smtp_encryption', 'tls'),
('backup', 'backup_retention_days', '14')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);

INSERT INTO `theme_settings` (`theme_slug`, `setting_key`, `setting_value`) VALUES
('classic', 'primary_color', '#7C3AED'),
('classic', 'primary_dark', '#5B21B6'),
('classic', 'accent_color', '#A855F7'),
('classic', 'surface_bg', '#F8FAFC'),
('classic', 'card_radius', '14px'),
('classic', 'show_announcements', '1'),
('classic', 'sidebar_style', 'light')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);

INSERT INTO `pages` (`slug`, `title`, `content`, `meta_title`, `meta_description`, `is_published`) VALUES
('terms', 'Terms of Service', '<h2>1. Acceptance of Terms</h2><p>By registering and placing orders on this panel, you agree to comply with all service guidelines and platform policies.</p><h2>2. Wallet &amp; Refunds</h2><p>Wallet deposits are credited upon verified payment confirmation. Cancelled or failed orders are refunded automatically to your panel wallet balance.</p><h2>3. Service Delivery</h2><p>Delivery speeds depend on upstream service capacity and target account availability (accounts must remain public during delivery).</p>', 'Terms of Service', 'Terms of Service for using the SMM panel.', 1),
('privacy', 'Privacy Policy', '<h2>1. Data Collection</h2><p>We collect only the account details required to authenticate your session, process orders, and maintain financial transaction records.</p><h2>2. Security</h2><p>Passwords are stored using cryptographic hashing, and sensitive gateway secrets are encrypted at rest.</p>', 'Privacy Policy', 'Privacy Policy explaining how user data is protected.', 1),
('about', 'About Our Panel', '<h2>Automated SMM Fulfillment Infrastructure</h2><p>We provide structured social media marketing services built on atomic wallet accounting, transparent pricing, and direct API provider routing.</p><h2>Why Agencies Choose Us</h2><p>Every order is tracked from submission to completion with verifiable start counts, remains counters, and automatic refunds if an upstream provider rejects a task.</p>', 'About Us', 'Learn about our automated SMM panel architecture and service reliability.', 1),
('contact', 'Contact & Support', '<h2>Customer Support &amp; Inquiries</h2><p>Need assistance with an order, wallet deposit, or reseller API key? Registered clients can open a direct support ticket inside the client dashboard for tracked staff responses.</p>', 'Contact Support', 'Get in touch with our support team for order or billing inquiries.', 1),
('faq', 'Frequently Asked Questions', '<p>Browse common answers regarding wallet funding, order processing, automated refunds, and reseller API integration below.</p>', 'Frequently Asked Questions', 'Answers to common questions about ordering, billing, and API usage.', 1)
ON DUPLICATE KEY UPDATE `slug` = VALUES(`slug`);
