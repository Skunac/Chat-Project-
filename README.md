# Real-Time Chat Application

A modern chat application built with Symfony 7 backend and Next.js frontend. This project leverages real-time communication via Mercure, JWT authentication, and a robust messaging system.

## Features

- **Real-time messaging** powered by Mercure Hub
- **User authentication** with JWT and Google OAuth
- **Group conversations** with role-based permissions
- **Direct messaging** between users
- **Conversation management** (create, join, leave)
- **RESTful API** using API Platform
- **Role-based access control** for conversations
- **Responsive design** for desktop and mobile

## Tech Stack

### Backend
- **PHP 8.2+** with Symfony 7.2
- **API Platform** for API development
- **Doctrine ORM** with PostgreSQL
- **JWT Authentication** (LexikJWTAuthenticationBundle)
- **Google OAuth** integration
- **Mercure Hub** for real-time updates
- **Redis** for caching and pub/sub

### Frontend
- **Next.js 15+** with React 19
- **TypeScript** for type safety
- **Tailwind CSS** for styling
- **React Query** for data fetching
- **SWR** for real-time data
- **Zod** for validation

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git
- Node.js 20+ (for frontend development)
- PHP 8.2+ (for backend development outside Docker)
- Composer (for backend development outside Docker)

### Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd Chat-Project-
   ```

2. Create a `.env.local` file in the `backend` directory:
   ```bash
   cp backend/.env backend/.env.local
   ```

3. Configure your `.env.local` file with your database credentials, JWT keys, Mercure settings, etc.

4. Generate JWT keys (inside the PHP container):
   ```bash
   docker-compose exec php bin/console lexik:jwt:generate-keypair
   ```

5. Start the Docker containers:
   ```bash
   docker-compose up -d
   ```

6. Install backend dependencies:
   ```bash
   docker-compose exec php composer install
   ```

7. Run migrations:
   ```bash
   docker-compose exec php bin/console doctrine:migrations:migrate
   ```

8. Install frontend dependencies and start development server:
   ```bash
   cd frontend
   yarn install
   yarn dev
   ```

9. Access the application:
    - Backend API: http://localhost:8000/api
    - API Documentation: http://localhost:8000/api/docs
    - Frontend: http://localhost:3030
    - Mercure Hub: http://localhost:9090
    - Mailpit (email testing): http://localhost:8025

## Architecture Overview

### Backend Structure

The backend follows a standard Symfony directory structure with API Platform integration:

- `config/`: Application configuration
- `src/`: Application source code
    - `Controller/`: API controllers
    - `Entity/`: Database entities
    - `Repository/`: Data access layer
    - `Service/`: Business logic
    - `Dto/`: Data transfer objects
    - `State/`: API Platform state processors

### Real-Time Communication

This application uses Mercure for real-time updates:

1. The backend publishes updates to the Mercure hub whenever a message is sent
2. Frontend clients subscribe to relevant Mercure topics
3. Updates are pushed to clients in real-time

### Authentication Flow

The application supports two authentication methods:

1. **JWT Authentication**:
    - User logs in with email/password
    - Server returns JWT token and refresh token
    - Client uses token for API requests
    - Refresh token is used to obtain new JWT when expired

2. **Google OAuth**:
    - User initiates Google login
    - Server handles OAuth callback
    - User account is created or linked
    - JWT token is issued

## API Documentation

The API documentation is available at http://localhost:8000/api/docs after starting the application. It provides a comprehensive list of all available endpoints, required parameters, and response formats.

Key API endpoints:

- `/api/auth/login`: User login
- `/api/auth/register`: User registration
- `/api/auth/refresh`: Refresh JWT token
- `/api/auth/google/connect`: Initiate Google OAuth

## Development Roadmap

Upcoming features and improvements:

- [ ] Read receipts and typing indicators
- [ ] User presence indicators (online/offline status)
- [ ] Message reactions and emoji support
- [ ] File attachments and media sharing
- [ ] Message editing and deletion
- [ ] @mentions and notifications
- [ ] Message search functionality
- [ ] Conversation archiving and pinning
- [ ] Dark/Light mode UI theme
- [ ] Voice and video call integration

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- [Symfony](https://symfony.com/)
- [API Platform](https://api-platform.com/)
- [Next.js](https://nextjs.org/)
- [Mercure](https://mercure.rocks/)
- [Docker](https://www.docker.com/)