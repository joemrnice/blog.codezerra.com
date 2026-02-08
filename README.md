# CodeZerra Blog

A modern, feature-rich tech blog platform built with PHP, MySQL, and TailwindCSS.

## Features

### 🎨 Frontend
- Beautiful, responsive design with TailwindCSS
- Homepage with featured posts
- Blog listing with pagination
- Single post pages with full SEO optimization
- Search functionality
- Category and tag filtering
- Mobile-friendly navigation
- Interactive chatbot widget

### 🔐 Admin Panel
- Secure authentication system with bcrypt
- Dashboard with analytics
- Full CRUD operations for posts
- Rich text editor (TinyMCE)
- Media library with drag-and-drop upload
- Category and tag management
- Site settings management
- User-friendly interface

### 🔒 Security
- CSRF protection on all forms
- XSS prevention with output escaping
- SQL injection prevention (prepared statements)
- Rate limiting
- Secure password hashing
- Session management
- Security headers (.htaccess)
- Input validation and sanitization

### 🚀 SEO Optimization
- Meta tags (title, description, keywords)
- Open Graph tags for social sharing
- Twitter Card tags
- Schema.org JSON-LD markup
- XML sitemap generation
- Robots.txt
- SEO-friendly URLs
- Canonical URLs

### 💬 Interactive Chatbot
- Floating chat widget
- Pattern-based responses
- Conversation history
- Real-time interaction
- Typing indicators
- Mobile-friendly

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite
- PHP extensions: PDO, PDO_MySQL, GD (for image handling)

## Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/joemrnice/blog.codezerra.com.git
cd blog.codezerra.com
```

### Step 2: Create Database

Create a new MySQL database:

```sql
CREATE DATABASE codezerra_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the schema:

```bash
mysql -u your_username -p codezerra_blog < schema.sql
```

### Step 3: Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and update with your settings:

```ini
DB_HOST=localhost
DB_NAME=codezerra_blog
DB_USER=your_database_user
DB_PASS=your_database_password

SESSION_SECRET=your_random_session_secret_here
CSRF_SECRET=your_random_csrf_secret_here

SITE_URL=https://blog.codezerra.com
ADMIN_EMAIL=admin@codezerra.com
```

### Step 4: Set File Permissions

```bash
chmod 755 .
chmod 644 .env
chmod 755 uploads/
chmod 755 logs/
mkdir -p logs
chmod 755 logs/
```

### Step 5: Create Admin User

Run the installation script:

```bash
php install.php
```

This will create an admin user with:
- Username: admin
- Password: (you'll be prompted to set this)

Or manually create an admin user:

```sql
-- Generate password hash (replace 'your_password' with actual password)
-- Use online bcrypt generator or PHP script

INSERT INTO users (username, email, password_hash, role) 
VALUES ('admin', 'admin@codezerra.com', '$2y$12$...your_bcrypt_hash...', 'admin');
```

### Step 6: Configure Apache

Add the following to your Apache virtual host or `.htaccess` is already included:

```apache
<VirtualHost *:80>
    ServerName blog.codezerra.com
    DocumentRoot /path/to/blog.codezerra.com
    
    <Directory /path/to/blog.codezerra.com>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/blog_error.log
    CustomLog ${APACHE_LOG_DIR}/blog_access.log combined
</VirtualHost>
```

Enable Apache modules:

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo systemctl restart apache2
```

### Step 7: Configure HTTPS (Production)

For production, use Let's Encrypt:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d blog.codezerra.com
```

Update `.htaccess` to force HTTPS (uncomment the lines).

## Usage

### Admin Panel

Access the admin panel at: `https://blog.codezerra.com/admin/`

Default credentials (if using install.php):
- Username: admin
- Password: (the one you set during installation)

### Creating Posts

1. Log in to admin panel
2. Click "Posts" → "New Post"
3. Fill in title, content, excerpt
4. Add featured image, categories, and tags
5. Configure SEO settings
6. Choose Draft or Published status
7. Click "Create Post"

### Managing Media

1. Go to "Media" in admin panel
2. Click "Upload" or drag-and-drop images
3. Add alt text for accessibility
4. Images are stored in `/uploads/` directory

### Site Settings

1. Go to "Settings" in admin panel
2. Update site title, description
3. Add social media links
4. Configure posts per page
5. Save changes

## File Structure

```
/
├── index.php                 # Homepage
├── blog.php                  # Blog listing
├── post.php                  # Single post
├── about.php                 # About page
├── contact.php               # Contact page
├── search.php                # Search results
├── sitemap.xml.php           # XML sitemap generator
├── robots.txt                # Robots.txt for SEO
├── .htaccess                 # Apache configuration
├── .env                      # Environment configuration
├── schema.sql                # Database schema
│
├── admin/                    # Admin panel
│   ├── index.php            # Dashboard
│   ├── login.php            # Admin login
│   ├── logout.php           # Logout handler
│   ├── includes/            # Admin partials (header, sidebar)
│   ├── posts/               # Post management
│   ├── media/               # Media library
│   ├── categories/          # Category management
│   ├── tags/                # Tag management
│   └── settings/            # Site settings
│
├── api/                      # API endpoints
│   └── chatbot.php          # Chatbot API
│
├── includes/                 # Core PHP files
│   ├── config.php           # Configuration & DB connection
│   ├── security.php         # Security functions
│   ├── db.php               # Database functions
│   └── functions.php        # Helper functions
│
├── assets/                   # Frontend assets
│   ├── css/                 # Custom CSS (optional)
│   ├── js/                  # JavaScript files
│   │   ├── main.js         # Main frontend JS
│   │   └── chatbot.js      # Chatbot widget
│   └── images/              # Site images
│
├── uploads/                  # User uploads
└── logs/                     # Application logs
```

## Security Best Practices

1. **Change default credentials** immediately after installation
2. **Use strong passwords** with special characters, numbers
3. **Keep .env file secure** - never commit to version control
4. **Enable HTTPS** in production
5. **Regular backups** of database and uploads
6. **Update PHP** and dependencies regularly
7. **Monitor logs** for suspicious activity
8. **Restrict file permissions** appropriately
9. **Use security headers** (already configured in .htaccess)
10. **Implement rate limiting** for login attempts

## Development

### Local Development Setup

1. Use XAMPP, WAMP, or Laravel Valet
2. Set `DISPLAY_ERRORS=true` in `.env`
3. Enable error logging
4. Use development database

### TailwindCSS

The project uses TailwindCSS via CDN for simplicity. For production:

1. Install Tailwind CLI:
```bash
npm install -D tailwindcss
npx tailwindcss init
```

2. Configure `tailwind.config.js`:
```javascript
module.exports = {
  content: ["./**/*.php"],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

3. Build CSS:
```bash
npx tailwindcss -o assets/css/styles.css --minify
```

### TinyMCE API Key

Replace the `no-api-key` in admin post editor with your free TinyMCE API key from:
https://www.tiny.cloud/

## Troubleshooting

### Database Connection Failed
- Check database credentials in `.env`
- Ensure MySQL service is running
- Verify database exists and user has permissions

### 500 Internal Server Error
- Check Apache error logs
- Verify file permissions (755 for directories, 644 for files)
- Ensure mod_rewrite is enabled

### Upload Failed
- Check uploads/ directory permissions (755)
- Verify MAX_UPLOAD_SIZE in `.env`
- Check PHP upload_max_filesize and post_max_size

### Session Issues
- Clear browser cookies
- Check session directory permissions
- Verify SESSION_SECRET in `.env`

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-source software.

## Support

For issues, questions, or feature requests:
- Open an issue on GitHub
- Email: admin@codezerra.com

## Credits

Built with:
- PHP
- MySQL
- TailwindCSS
- TinyMCE
- Heroicons

---

**CodeZerra Blog** - A modern tech blog platform
