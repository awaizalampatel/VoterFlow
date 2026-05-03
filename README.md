# VoterFlow 🗳️

**VoterFlow** is a comprehensive, AI-powered platform designed to empower citizens by guiding them through the entire voting process. Created specifically as a hackathon project, VoterFlow combines modern web design, artificial intelligence, and personalized data tracking to make civic engagement easier, more accessible, and interactive.

## ✨ Key Features

- **🤖 AI Voting Assistant:** An integrated chat interface powered by OpenAI. Users can ask questions about voting procedures, candidate information, and election laws. Features a modern, typewriter-style response animation and reaction system.
- **🔐 Secure Authentication:** Supports traditional Email/Password login as well as "Continue with Google" (OAuth 2.0) for a seamless onboarding experience.
- **📍 Personalized Milestones:** Tracks the user's voting journey (Registration Status, Ballot Requested, Voted) and provides region-specific election dates and deadlines.
- **📰 Live Election News:** Pulls real-time election and political news using the NewsAPI to keep voters informed on current events.
- **🏆 Gamification & Badges:** Encourages civic participation by awarding badges and tracking quiz results based on the user's political knowledge and app engagement.
- **🔔 Real-time Notifications & Broadcasts:** Keeps users updated on upcoming deadlines and important platform announcements.
- **📱 Responsive & Premium UI:** Built with a stunning, glassmorphism-inspired "dark mode" aesthetic that looks great on both desktop and mobile devices.

## 🛠️ Technology Stack

- **Frontend:** HTML5, Vanilla JavaScript, and Custom CSS (Modern UI/UX with smooth animations and responsive layouts).
- **Backend:** PHP (Handles API endpoints, Authentication, and Database interactions).
- **Database:** MySQL (Stores user data, chat history, preferences, and election events).
- **External APIs:** 
  - OpenAI API (for the conversational AI assistant).
  - Google OAuth 2.0 (for social authentication).
  - NewsAPI (for fetching live news).

## 🚀 Getting Started (Local Development)

To run this project locally on your machine, follow these steps:

### Prerequisites
- A local web server stack like **XAMPP**, **WAMP**, or **MAMP** (must support PHP and MySQL).
- API Keys for OpenAI, Google OAuth, and NewsAPI.

### Installation Steps

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/awaizalampatel/VoterFlow.git
   cd VoterFlow
   ```

2. **Set Up the Database:**
   - Open your local phpMyAdmin (usually `http://localhost/phpmyadmin`).
   - Create a database named `voterflow`.
   - Import the `db_setup.sql` file provided in the repository to create all necessary tables and insert sample election events.

3. **Configure API Keys:**
   - Rename `config.sample.php` to `config.php`.
   - Open `config.php` and replace the placeholder strings with your actual API credentials:
     ```php
     define('OPENAI_API_KEY', 'your_openai_api_key');
     define('GOOGLE_CLIENT_ID', 'your_google_client_id');
     define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');
     define('NEWSAPI_KEY', 'your_newsapi_key');
     ```
   - *Note: `config.php` is ignored by Git to keep your credentials secure.*

4. **Launch the Application:**
   - Move the `VoterFlow` folder into your web server's root directory (e.g., `C:\xampp\htdocs\VoterFlow`).
   - Open your browser and navigate to `http://localhost/VoterFlow`.

## 📁 Project Structure

- `/api/` - Contains all PHP backend logic (Auth, Chat, News, Milestones, Notifications).
- `/assets/` - Contains JavaScript (`app.js`) and CSS (`style.css`).
- `/images/` - Branding assets.
- `index.php` - The landing and authentication page.
- `dashboard.php` - The main application interface for logged-in users.
- `admin.php` - Admin panel for broadcasting messages.
- `db_setup.sql` - Database schema and mock data.
- `config.sample.php` - Template for environment variables and database connections.

## 🤝 Contributing
Since this is a hackathon project, the current focus is on core functionality. Feel free to fork the repository, submit pull requests, or open issues if you'd like to improve the codebase!

## 📜 License
This project is open-source and available for educational and hackathon purposes.
