# School Voting App Backend

A secure backend system for managing school elections. Allows administrators to create elections and students to vote using their school email.

## Setup

1. Clone the repository
2. Install dependencies: `npm install`
3. Create a `.env` file with the following variables:
   ```
   PORT=5000
   MONGO_URI=your_mongodb_connection_string
   JWT_SECRET=your_jwt_secret_key
   EMAIL_SERVICE=gmail
   EMAIL_USER=your-email@gmail.com
   EMAIL_PASS=your-email-password
   ```
4. Start the server: `npm run dev`

## API Endpoints

### Admin Routes

- **POST /api/admin/signup**
  - Create a new admin account
  - Body: `{ "email": "admin@school.edu", "password": "password123" }`

- **POST /api/admin/login**
  - Log in as an admin
  - Body: `{ "email": "admin@school.edu", "password": "password123" }`
  - Returns: JWT token for authentication

### Election Routes

- **POST /api/elections** (requires admin token)
  - Create a new election
  - Body: `{ "title": "Student Council", "description": "Vote for president", "allowedDomains": ["@school.edu"] }`

- **POST /api/elections/:electionId/candidates** (requires admin token)
  - Add a candidate to an election
  - Body: `{ "name": "Jane Doe", "bio": "Passionate leader", "photoUrl": "https://example.com/photo.jpg" }`

- **GET /api/elections/:electionId**
  - Get election details and candidates
  - Public access (no token needed)

- **GET /api/elections/:electionId/results** (requires admin token)
  - Get election results
  - Returns vote counts for each candidate

- **GET /api/elections** (requires admin token)
  - Get all elections created by the admin

- **PATCH /api/elections/:electionId/status** (requires admin token)
  - Update election status (active/inactive)
  - Body: `{ "active": true }`

### Vote Routes

- **POST /api/vote/:electionId/verify**
  - Verify voter's email
  - Body: `{ "email": "student@school.edu" }`
  - Returns a session ID and sends verification code to email

- **POST /api/vote/:electionId/confirm**
  - Confirm verification code
  - Body: `{ "sessionId": "session_id_here", "code": "123456" }`
  - Returns a vote token

- **POST /api/vote/:electionId** (requires vote token)
  - Cast a vote
  - Body: `{ "candidateId": "candidate_id_here" }`
  - Requires x-vote-token header

## Authentication

- Admin routes require an `x-auth-token` header with the JWT token from login
- Voting requires an `x-vote-token` header with the token from code confirmation

## Data Models

- **Admin**: Stores admin accounts
- **Election**: Stores election details
- **Candidate**: Stores candidate information
- **Vote**: Records votes cast
- **VoterSession**: Manages email verification sessions
```

This completes the backend implementation for the School Voting App. The system includes:

1. Admin authentication (signup and login)
2. Election management (create, add candidates, view results)
3. Secure voting process with email verification
4. Protection against duplicate votes
5. Domain restriction for voters

To run the server:

```bash
npm run dev