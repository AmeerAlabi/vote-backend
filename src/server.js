const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const dotenv = require('dotenv');
// Swagger documentation
const swaggerDocs = {
  openapi: '3.0.0',
  info: {
    title: 'School Voting System API',
    version: '1.0.0',
    description: 'API documentation for the School Voting System',
  },
  servers: [
    {
      url: 'http://localhost:5000',
      description: 'Development server',
    },
    {
      url: 'https://vote-backend-be2f.onrender.com',
      description: 'Deployed server',
    },
  ],
  paths: {
    '/api/admin/signup': {
      post: {
        tags: ['Admin'],
        summary: 'Register a new admin',
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['email', 'password'],
                properties: {
                  email: {
                    type: 'string',
                    format: 'email',
                  },
                  password: {
                    type: 'string',
                    format: 'password',
                  },
                },
              },
            },
          },
        },
        responses: {
          '201': {
            description: 'Admin successfully registered',
          },
          '400': {
            description: 'Invalid input or email already exists',
          },
        },
      },
    },
    '/api/admin/verify-email': {
      get: {
        tags: ['Admin'],
        summary: 'Verify admin email',
        description: 'Verify admin email using the verification token',
        parameters: [
          {
            in: 'query',
            name: 'token',
            required: true,
            schema: {
              type: 'string',
            },
          },
        ],
        responses: {
          '200': {
            description: 'Email verified successfully',
            content: {
              'application/json': {
                schema: {
                  type: 'object',
                  properties: {
                    message: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
          '400': {
            description: 'Invalid or expired token',
          },
        },
      },
    },
    '/api/admin/forgot-password': {
      post: {
        tags: ['Admin'],
        summary: 'Request password reset',
        description: 'Send password reset email to admin',
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['email'],
                properties: {
                  email: {
                    type: 'string',
                    format: 'email',
                  },
                },
              },
            },
          },
        },
        responses: {
          '200': {
            description: 'Password reset email sent successfully',
            content: {
              'application/json': {
                schema: {
                  type: 'object',
                  properties: {
                    message: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
          '400': {
            description: 'Invalid email',
          },
        },
      },
    },
    '/api/admin/reset-password': {
      post: {
        tags: ['Admin'],
        summary: 'Reset admin password',
        description: 'Reset admin password using the reset token',
        parameters: [
          {
            in: 'query',
            name: 'token',
            required: true,
            schema: {
              type: 'string',
            },
          },
        ],
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['password'],
                properties: {
                  password: {
                    type: 'string',
                    format: 'password',
                  },
                },
              },
            },
          },
        },
        responses: {
          '200': {
            description: 'Password reset successfully',
            content: {
              'application/json': {
                schema: {
                  type: 'object',
                  properties: {
                    message: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
          '400': {
            description: 'Invalid or expired token',
          },
        },
      },
    },
    '/api/admin/login': {
      post: {
        tags: ['Admin'],
        summary: 'Login as admin',
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['email', 'password'],
                properties: {
                  email: {
                    type: 'string',
                    format: 'email',
                  },
                  password: {
                    type: 'string',
                    format: 'password',
                  },
                },
              },
            },
          },
        },
        responses: {
          '200': {
            description: 'Login successful',
            content: {
              'application/json': {
                schema: {
                  type: 'object',
                  properties: {
                    token: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
          '401': {
            description: 'Invalid credentials',
          },
        },
      },
    },
    '/api/elections': {
      post: {
        tags: ['Elections'],
        summary: 'Create a new election',
        security: [{ bearerAuth: [] }],
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['title', 'description', 'allowedDomains'],
                properties: {
                  title: {
                    type: 'string',
                  },
                  description: {
                    type: 'string',
                  },
                  allowedDomains: {
                    type: 'array',
                    items: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
        },
        responses: {
          '201': {
            description: 'Election created successfully',
          },
          '401': {
            description: 'Unauthorized',
          },
        },
      },
      get: {
        tags: ['Elections'],
        summary: 'Get all elections',
        security: [{ bearerAuth: [] }],
        responses: {
          '200': {
            description: 'List of all elections',
            content: {
              'application/json': {
                schema: {
                  type: 'array',
                  items: {
                    type: 'object',
                    properties: {
                      _id: {
                        type: 'string',
                      },
                      title: {
                        type: 'string',
                      },
                      description: {
                        type: 'string',
                      },
                      active: {
                        type: 'boolean',
                      },
                    },
                  },
                },
              },
            },
          },
          '401': {
            description: 'Unauthorized',
          },
        },
      },
    },
    '/api/vote/{electionId}/verify': {
      post: {
        tags: ['Voting'],
        summary: 'Verify voter email',
        parameters: [
          {
            in: 'path',
            name: 'electionId',
            required: true,
            schema: {
              type: 'string',
            },
          },
        ],
        requestBody: {
          required: true,
          content: {
            'application/json': {
              schema: {
                type: 'object',
                required: ['email'],
                properties: {
                  email: {
                    type: 'string',
                    format: 'email',
                  },
                },
              },
            },
          },
        },
        responses: {
          '200': {
            description: 'Verification email sent',
            content: {
              'application/json': {
                schema: {
                  type: 'object',
                  properties: {
                    sessionId: {
                      type: 'string',
                    },
                  },
                },
              },
            },
          },
          '400': {
            description: 'Invalid email domain',
          },
        },
      },
    },
  },
  components: {
    securitySchemes: {
      bearerAuth: {
        type: 'http',
        scheme: 'bearer',
        bearerFormat: 'JWT',
      },
    },
  },
};


const path = require('path');

// Load environment variables from .env file (optional, kept for PORT)
dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Import routes
const adminRoutes = require('./routes/adminRoutes');
const electionRoutes = require('./routes/electionRoutes');
const voteRoutes = require('./routes/voteRoutes');

// Initialize express app
const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Hardcoded MongoDB connection URL
const MONGO_URI = 'mongodb+srv://ameeralabi7:wWPm6KXWigQ7ddPP@cluster0.vtdwxof.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0';

// MongoDB connection with retry logic and enhanced error handling
const connectToMongoDB = async (retries = 3, delay = 5000) => {
  console.log(process.env.MONGO_URI);
  
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      await mongoose.connect(process.env.MONGO_URI, {
        // useNewUrlParser: true,
        // useUnifiedTopology: t
        //rue,
        serverSelectionTimeoutMS: 10000,
        // family: 4
      });
      
      console.log('Successfully connected to MongoDB');
      return;
    } catch (err) {
      console.error(`MongoDB connection attempt ${attempt} failed:`, err.message);

      if (attempt < retries) {
        // clg: Log retry attempt
        console.log(`Retrying connection in ${delay / 1000} seconds...`);
        await new Promise(resolve => setTimeout(resolve, delay));
      } else {
        // clg: Log final failure
        console.error('All MongoDB connection attempts failed. Server will not start.');
        process.exit(1);
      }
    }
  }
};

// Connect to MongoDB before starting routes
connectToMongoDB().catch(err => {
  // clg: Log unhandled connection error
  console.error('Unexpected error during MongoDB connection:', err.message);
  process.exit(1);
});

// Serve Swagger documentation
app.get('/api-docs', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>School Voting System API Documentation</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.9.0/swagger-ui.css">
      </head>
      <body>
        <div id="swagger-ui"></div>
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script>
          window.onload = () => {
            window.ui = SwaggerUIBundle({
              spec: ${JSON.stringify(swaggerDocs)},
              dom_id: '#swagger-ui',
            });
          };
        </script>
      </body>
    </html>
  `);
});

// Routes
app.use('/api/admin', adminRoutes);
app.use('/api/elections', electionRoutes);
app.use('/api/vote', voteRoutes);

// Root route for testing
app.get('/', (req, res) => {
  // clg: Log root route access
  console.log('Root route accessed');
  res.send('School Voting App API is running');
});

// Error handling middleware
app.use((err, req, res, next) => {
  // clg: Log server errors
  console.error('Server error:', err.message);
  res.status(500).json({ error: 'Internal server error' });
});

// Start server
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  // clg: Log server start
  console.log(`Server running on port ${PORT}`);
});