# CodeCase - Competitive Programming Hub

CodeCase is a comprehensive platform designed to help competitive programmers track their progress, manage resources, and stay updated with upcoming contests across multiple coding platforms.


## 🚀 Features

- **Contest Calendar**: Track upcoming contests from LeetCode, CodeChef, and Codeforces with IST timezone support
- **Problem Management**: Save, categorize, and filter coding problems by platform and difficulty
- **Notes System**: Create and organize programming notes, algorithms, and code snippets
- **User Statistics**: Track your problem-solving progress across multiple platforms
- **CP Helper**: Get AI-powered assistance for competitive programming concepts
- **Platform Integration**: Connect your LeetCode, CodeChef, and Codeforces accounts
- **Visualization**: View your progress with interactive charts and statistics

## 📋 Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- XAMPP/WAMP/LAMP stack
- Web browser with JavaScript enabled

## 🔧 Installation

1. Clone the repository:
   ```
   git clone https://github.com/OmikAcharya/codeKeep.git
   ```

2. Move the project to your web server directory (e.g., `htdocs` for XAMPP):
   ```
   mv codeKeep /path/to/xampp/htdocs/
   ```

3. Start your Apache and MySQL services through XAMPP/WAMP/LAMP control panel

4. Create a database named `codekeep` in your MySQL server

5. Import the database schema:
   - Navigate to `http://localhost/phpmyadmin`
   - Create a new database named `codekeep`
   - Run the `setup_db.php` script by visiting `http://localhost/codeKeep/setup_db.php` in your browser

6. Access the application:
   ```
   http://localhost/codeKeep/
   ```

## 🏗️ Project Structure

```
codeKeep/
├── api/                  # API endpoints for platform integrations
│   ├── functions.php     # Helper functions for API calls
│   ├── get_user_stats.php # User statistics API
│   └── ...
├── assets/               # Static assets (images, icons)
├── css/                  # CSS stylesheets
├── js/                   # JavaScript files
├── config.php            # Database configuration
├── dashboard.php         # Main dashboard
├── contests.php          # Contest tracking page
├── problems.php          # Problem management page
├── notes.php             # Notes management page
├── cp_helper.php         # CP Helper feature
├── index.php             # Landing page
├── login.php             # User login
├── signup.php            # User registration
└── setup_db.php          # Database setup script
```

## 🔄 API Integrations

CodeCase integrates with the following APIs:

- **Contest Information**:
  - `https://competeapi.vercel.app/contests/upcoming/` - For CodeChef and Codeforces contests
  - `https://competeapi.vercel.app/contests/leetcode/` - For LeetCode contests

- **User Statistics**:
  - LeetCode: `https://competeapi.vercel.app/user/leetcode/<username>/`
  - CodeChef: `https://competeapi.vercel.app/user/codechef/<username>/`
  - Codeforces: `https://competeapi.vercel.app/user/codeforces/<username>/`

## 📊 Database Schema

The application uses the following main tables:

- `users` - User account information
- `profile` - User profile data including platform usernames
- `problems` - Coding problems information
- `bookmarks` - User bookmarked problems
- `notes` - User notes and snippets
- `saved_contests` - User saved contests
- `solved_problems` - Problems solved by users
- `user_statistics` - Aggregated user statistics

## 🔐 Authentication

The application uses PHP session-based authentication with password hashing for security.

## 🎨 UI Features

- Responsive design for desktop and mobile devices
- Dark theme optimized for coding
- Interactive charts for visualizing progress
- Platform-specific styling for different coding platforms

## 🛠️ Development

To contribute to the project:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Contributors

- [Om Thanage](https://github.com/Om-Thanage)
- [Omik Acharya](https://github.com/OmikAcharya)
- [Omkar Dinde](https://github.com/omkardinde04)

