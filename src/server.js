require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const path = require('path');

const swaggerDocs = {
  "openapi": "3.0.0",
  "info": {
    "title": "School Voting System API",
    "version": "1.0.0",
    "description": "API documentation for the School Voting System"
  },
  "servers": [
    {
      "url": "http://localhost:5000",
      "description": "Local development server"
    },
    {
      "url": "https://vote-backend-be2f.onrender.com",
      "description": "Deployed server"
    }
  ],
  "components": {
    "schemas": {
      "Admin": {
        "type": "object",
        "properties": {
          "email": { "type": "string", "format": "email" },
          "password": { "type": "string", "format": "password" },
          "name": { "type": "string" }
        },
        "required": ["email", "password"]
      },
      "Election": {
        "type": "object",
        "properties": {
          "title": { "type": "string" },
          "description": { "type": "string" },
          "allowedDomains": {
            "type": "array",
            "items": { "type": "string" }
          },
          "active": { "type": "boolean" }
        },
        "required": ["title", "description", "allowedDomains"]
      },
      "Candidate": {
        "type": "object",
        "properties": {
          "name": { "type": "string" },
          "bio": { "type": "string" },
          "photoUrl": { "type": "string", "format": "url" }
        },
        "required": ["name", "bio"]
      },
      "Vote": {
        "type": "object",
        "properties": {
          "token": { "type": "string" },
          "candidateId": { "type": "string" }
        },
        "required": ["token", "candidateId"]
      }
    },
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "JWT"
      }
    }
  },
  "paths": {
    "/api/admin/signup": {
      "post": {
        "tags": ["Admin"],
        "summary": "Register a new admin",
        "description": "Create a new admin account with email and password",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["email", "password"],
                "properties": {
                  "email": { "type": "string", "format": "email" },
                  "password": { "type": "string", "format": "password" }
                }
              }
            }
          }
        },
        "responses": {
          "201": {
            "description": "Admin successfully registered",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "token": { "type": "string" },
                    "admin": {
                      "type": "object",
                      "properties": {
                        "id": { "type": "string" },
                        "email": { "type": "string" },
                        "name": { "type": "string" }
                      }
                    }
                  }
                }
              }
            }
          },
          "400": {
            "description": "Invalid input or email already exists",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "error": { "type": "string" }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/api/admin/verify-email": {
      "post": {
        "tags": ["Admin"],
        "summary": "Verify admin email with code",
        "description": "Verify admin email using the verification code sent via email",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["email", "code"],
                "properties": {
                  "email": { "type": "string", "format": "email", "description": "Email address of the admin" },
                  "code": { "type": "string", "description": "Verification code received in email" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Email verified successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "message": { "type": "string" } }
                }
              }
            }
          },
          "400": { "description": "Invalid or expired verification code" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/admin/forgot-password": {
      "post": {
        "tags": ["Admin"],
        "summary": "Request password reset",
        "description": "Send password reset email to admin",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["email"],
                "properties": { "email": { "type": "string", "format": "email" } }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Password reset email sent successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "message": { "type": "string" } }
                }
              }
            }
          },
          "400": { "description": "Invalid email" }
        }
      }
    },
    "/api/admin/reset-password": {
      "post": {
        "tags": ["Admin"],
        "summary": "Reset admin password",
        "description": "Reset admin password using the reset token",
        "parameters": [
          {
            "in": "query",
            "name": "token",
            "required": true,
            "schema": { "type": "string" }
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["password"],
                "properties": { "password": { "type": "string", "format": "password" } }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Password reset successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "message": { "type": "string" } }
                }
              }
            }
          },
          "400": { "description": "Invalid or expired token" }
        }
      }
    },
    "/api/admin/login": {
      "post": {
        "tags": ["Admin"],
        "summary": "Login as admin",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["email", "password"],
                "properties": {
                  "email": { "type": "string", "format": "email" },
                  "password": { "type": "string", "format": "password" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Login successful",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "token": { "type": "string" } }
                }
              }
            }
          },
          "401": { "description": "Invalid credentials" }
        }
      }
    },
    "/api/elections": {
      "post": {
        "tags": ["Elections"],
        "summary": "Create a new election",
        "description": "Create a new election with specified details",
        "security": [{ "bearerAuth": [] }],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["title", "description", "allowedDomains"],
                "properties": {
                  "title": { "type": "string", "description": "Title of the election" },
                  "description": { "type": "string", "description": "Detailed description of the election" },
                  "allowedDomains": {
                    "type": "array",
                    "items": { "type": "string" },
                    "description": "List of email domains allowed to vote"
                  }
                }
              }
            }
          }
        },
        "responses": {
          "201": {
            "description": "Election created successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string" },
                    "electionId": { "type": "string" },
                    "votingLink": { "type": "string" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - missing required fields" },
          "401": { "description": "Unauthorized - admin authentication required" },
          "500": { "description": "Server error" }
        }
      },
      "get": {
        "tags": ["Elections"],
        "summary": "Get all elections",
        "description": "Get all elections created by the authenticated admin",
        "security": [{ "bearerAuth": [] }],
        "responses": {
          "200": {
            "description": "List of elections",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "elections": {
                      "type": "array",
                      "items": {
                        "type": "object",
                        "properties": {
                          "id": { "type": "string" },
                          "title": { "type": "string" },
                          "description": { "type": "string" },
                          "createdAt": { "type": "string", "format": "date-time" },
                          "active": { "type": "boolean" }
                        }
                      }
                    }
                  }
                }
              }
            }
          },
          "401": { "description": "Unauthorized - admin authentication required" },
          "500": { "description": "Server error or token generation failure" }
        }
      }
    },
    "/api/elections/{electionId}": {
      "get": {
        "tags": ["Elections"],
        "summary": "Get election details",
        "description": "Get detailed information about a specific election including candidates",
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to fetch"
          }
        ],
        "responses": {
          "200": {
            "description": "Election details retrieved successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "election": {
                      "type": "object",
                      "properties": {
                        "id": { "type": "string" },
                        "title": { "type": "string" },
                        "description": { "type": "string" },
                        "allowedDomains": { "type": "array", "items": { "type": "string" } },
                        "createdAt": { "type": "string", "format": "date-time" },
                        "active": { "type": "boolean" }
                      }
                    },
                    "candidates": {
                      "type": "array",
                      "items": {
                        "type": "object",
                        "properties": {
                          "id": { "type": "string" },
                          "name": { "type": "string" },
                          "bio": { "type": "string" },
                          "photoUrl": { "type": "string" }
                        }
                      }
                    }
                  }
                }
              }
            }
          },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      },
      "delete": {
        "tags": ["Elections"],
        "summary": "Delete an election",
        "description": "Delete an election and all associated data (candidates, votes)",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to delete"
          }
        ],
        "responses": {
          "200": {
            "description": "Election deleted successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "message": { "type": "string" } }
                }
              }
            }
          },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to delete this election" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      },
      "patch": {
        "tags": ["Elections"],
        "summary": "Update election details",
        "description": "Update title, description, and other details of an election",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to update"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "title": { "type": "string", "description": "New title for the election" },
                  "description": { "type": "string", "description": "New description for the election" },
                  "allowedDomains": {
                    "type": "array",
                    "items": { "type": "string" },
                    "description": "Updated list of allowed email domains"
                  }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Election updated successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string" },
                    "election": {
                      "type": "object",
                      "properties": {
                        "id": { "type": "string" },
                        "title": { "type": "string" },
                        "description": { "type": "string" },
                        "active": { "type": "boolean" }
                      }
                    }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - invalid input" },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to update this election" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/elections/{electionId}/status": {
      "patch": {
        "tags": ["Elections"],
        "summary": "Update election status",
        "description": "Activate or deactivate an election",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to update status"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["active"],
                "properties": {
                  "active": { "type": "boolean", "description": "Set to true to activate, false to deactivate" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Election status updated successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string" },
                    "electionId": { "type": "string" },
                    "active": { "type": "boolean" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - active must be a boolean" },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to update this election" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/elections/{electionId}/candidates": {
      "post": {
        "tags": ["Elections"],
        "summary": "Add a candidate to an election",
        "description": "Add a new candidate with details to an existing election",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to add candidate to"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["name", "bio"],
                "properties": {
                  "name": { "type": "string", "description": "Name of the candidate" },
                  "bio": { "type": "string", "description": "Biography or description of the candidate" },
                  "photoUrl": { "type": "string", "description": "URL to candidate photo (optional)" }
                }
              }
            }
          }
        },
        "responses": {
          "201": {
            "description": "Candidate added successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string" },
                    "candidateId": { "type": "string" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - missing required fields" },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to modify this election" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      },
      "get": {
        "tags": ["Elections"],
        "summary": "Get all candidates for an election",
        "description": "Retrieve all candidates for a specific election",
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to get candidates for"
          }
        ],
        "responses": {
          "200": {
            "description": "Candidates retrieved successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "candidates": {
                      "type": "array",
                      "items": {
                        "type": "object",
                        "properties": {
                          "id": { "type": "string" },
                          "name": { "type": "string" },
                          "bio": { "type": "string" },
                          "photoUrl": { "type": "string" },
                          "createdAt": { "type": "string", "format": "date-time" }
                        }
                      }
                    }
                  }
                }
              }
            }
          },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/elections/{electionId}/candidates/{candidateId}": {
      "patch": {
        "tags": ["Elections"],
        "summary": "Update candidate details",
        "description": "Update name, bio, or photo of an existing candidate",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election"
          },
          {
            "in": "path",
            "name": "candidateId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the candidate to update"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "name": { "type": "string", "description": "Updated name of the candidate" },
                  "bio": { "type": "string", "description": "Updated biography or description" },
                  "photoUrl": { "type": "string", "description": "Updated URL to candidate photo" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Candidate updated successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string" },
                    "candidate": {
                      "type": "object",
                      "properties": {
                        "id": { "type": "string" },
                        "name": { "type": "string" },
                        "bio": { "type": "string" },
                        "photoUrl": { "type": "string" }
                      }
                    }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - invalid input" },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to modify this election" },
          "404": { "description": "Election or candidate not found" },
          "500": { "description": "Server error" }
        }
      },
      "delete": {
        "tags": ["Elections"],
        "summary": "Delete a candidate",
        "description": "Remove a candidate from an election",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election"
          },
          {
            "in": "path",
            "name": "candidateId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the candidate to delete"
          }
        ],
        "responses": {
          "200": {
            "description": "Candidate deleted successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": { "message": { "type": "string" } }
                }
              }
            }
          },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to modify this election" },
          "404": { "description": "Election or candidate not found" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/elections/{electionId}/results": {
      "get": {
        "tags": ["Elections"],
        "summary": "Get election results",
        "description": "Get vote counts and results for an election",
        "security": [{ "bearerAuth": [] }],
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election to get results for"
          }
        ],
        "responses": {
          "200": {
            "description": "Election results retrieved successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "electionId": { "type": "string" },
                    "title": { "type": "string" },
                    "totalVotes": { "type": "integer" },
                    "results": {
                      "type": "array",
                      "items": {
                        "type": "object",
                        "properties": {
                          "candidateId": { "type": "string" },
                          "name": { "type": "string" },
                          "voteCount": { "type": "integer" }
                        }
                      }
                    }
                  }
                }
              }
            }
          },
          "401": { "description": "Unauthorized - admin authentication required" },
          "403": { "description": "Forbidden - not authorized to view results of this election" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error" }
        }
      }
    },
    "/api/vote/{electionId}/verify": {
      "post": {
        "tags": ["Vote"],
        "summary": "Verify voter email",
        "description": "Send verification code to voter's email for a specific election",
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["email"],
                "properties": {
                  "email": { "type": "string", "format": "email", "description": "Voter's email address" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Verification code sent successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string", "example": "Verification code sent to your email" },
                    "sessionId": { "type": "string", "description": "Session ID for verification" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - Email domain not allowed or already voted" },
          "404": { "description": "Election not found" },
          "500": { "description": "Server error or failed to send email" }
        }
      }
    },
    "/api/vote/{electionId}/confirm": {
      "post": {
        "tags": ["Vote"],
        "summary": "Confirm voter verification code",
        "description": "Verify the code sent to voter's email and generate voting token",
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election"
          }
        ],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["sessionId", "code"],
                "properties": {
                  "sessionId": { "type": "string", "description": "Session ID received from verify endpoint" },
                  "code": { "type": "string", "description": "Verification code received in email" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Verification successful",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string", "example": "Verification successful" },
                    "token": { "type": "string", "description": "JWT token for voting" },
                    "voteToken": { "type": "string", "description": "JWT token for voting (legacy)" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - Invalid session or verification code" },
          "500": { "description": "Server error or token generation failure" }
        }
      }
    },
    "/api/vote/{electionId}": {
      "post": {
        "tags": ["Vote"],
        "summary": "Cast a vote",
        "description": "Cast a vote for a candidate in an election",
        "parameters": [
          {
            "in": "path",
            "name": "electionId",
            "required": true,
            "schema": { "type": "string" },
            "description": "ID of the election"
          }
        ],
        "security": [{ "bearerAuth": [] }],
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "required": ["candidateId"],
                "properties": {
                  "candidateId": { "type": "string", "description": "ID of the candidate to vote for" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Vote cast successfully",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": { "type": "string", "example": "Vote cast successfully" },
                    "voteId": { "type": "string", "description": "ID of the recorded vote" }
                  }
                }
              }
            }
          },
          "400": { "description": "Bad request - Already voted or election not active" },
          "401": { "description": "Unauthorized - Invalid or missing token" },
          "404": { "description": "Election or candidate not found" },
          "500": { "description": "Server error" }
        }
      }
    }
  },
  "tags": [
    { "name": "Admin", "description": "Endpoints for admin authentication and management" },
    { "name": "Elections", "description": "Endpoints for managing elections" },
    { "name": "Vote", "description": "Endpoints for voter verification and casting votes" }
  ],
  "components": {
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "JWT"
      }
    }
  }
};

module.exports = swaggerDocs;
// Load environment variables from .env file (optional, kept for PORT)

// Import routes
const adminRoutes = require('./routes/adminRoutes');
const electionRoutes = require('./routes/electionRoutes');
const voteRoutes = require('./routes/voteRoutes');

// Add these imports with your existing ones
const http = require('http');
const socketIo = require('socket.io');
const monitorRoutes = require('./routes/monitorRoutes');

// Replace your app creation with this:
const app = express();
const server = http.createServer(app);

// Add Socket.IO
const io = socketIo(server, {
  cors: {
    origin: [
      process.env.CLIENT_URL || "http://localhost:3000",
      "https://cast-vote.vercel.app", // Add your deployed frontend URL here
      "http://localhost:3000" // Keep localhost for development
    ],
    methods: ["GET", "POST"],
    credentials: true
  }
});

// Your existing middleware
app.use(cors());
app.use(express.json());

// Make io accessible to routes
app.set('io', io);

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

// Your existing routes
app.use('/api/admin', adminRoutes);
app.use('/api/elections', electionRoutes);
app.use('/api/vote', voteRoutes);

// Add the new monitoring route
app.use('/monitor', monitorRoutes);

// Socket.IO connection handling
io.on('connection', (socket) => {
  console.log('Client connected for real-time updates:', socket.id);

  socket.on('join-election', (electionId) => {
    socket.join(`election-${electionId}`);
    console.log(`Client ${socket.id} joined election room: election-${electionId}`);
  });

  socket.on('leave-election', (electionId) => {
    socket.leave(`election-${electionId}`);
    console.log(`Client ${socket.id} left election room: election-${electionId}`);
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

// Your existing MongoDB connection function stays the same
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
