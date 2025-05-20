const jwt = require('jsonwebtoken');

/**
 * Extract token from request headers
 * @param {Object} req - Express request object
 * @returns {string|null} - The extracted token or null
 */
const extractToken = (req) => {
  // First try Authorization header (Bearer token)
  const authHeader = req.headers.authorization || req.headers.Authorization;
  if (authHeader && authHeader.startsWith('Bearer ')) {
    return authHeader.split(' ')[1];
  }
  
  // Then try x-auth-token header
  const xAuthToken = req.header('x-auth-token');
  if (xAuthToken) {
    return xAuthToken;
  }
  
  // Finally try x-vote-token header
  const xVoteToken = req.header('x-vote-token');
  if (xVoteToken) {
    return xVoteToken;
  }
  
  return null;
};

/**
 * Verify JWT token and handle errors consistently
 * @param {string} token - JWT token to verify
 * @param {string} secret - Secret key for verification
 * @returns {Object} - Result object with success flag and decoded payload or error
 */
const verifyToken = (token, secret) => {
  try {
    const decoded = jwt.verify(token, secret);
    return { success: true, decoded };
  } catch (error) {
    return { 
      success: false, 
      error: {
        name: error.name,
        message: error.message
      }
    };
  }
};

// Middleware to verify admin JWT token
exports.adminAuth = (req, res, next) => {
  // Check if JWT_SECRET is properly set
  if (!process.env.JWT_SECRET) {
    console.error('JWT_SECRET is not defined in environment variables');
    return res.status(500).json({ message: 'Server configuration error' });
  }
  
  // Extract token from request
  const token = extractToken(req);
  
  if (!token) {
    return res.status(401).json({ message: 'No token, authorization denied' });
  }
  
  // Verify token
  const verification = verifyToken(token, process.env.JWT_SECRET);
  
  if (!verification.success) {
    return res.status(401).json({ 
      message: 'Token is not valid', 
      error: verification.error.message 
    });
  }
  
  // Check token structure
  if (!verification.decoded.admin) {
    return res.status(401).json({ message: 'Invalid token structure' });
  }
  
  // Set admin in request and proceed with the expected structure
  // This maintains compatibility with existing code that expects req.admin.admin.id
  req.admin = {
    admin: verification.decoded.admin
  };
  next();
};

// Middleware to verify voter token
exports.voterAuth = (req, res, next) => {
  // Check if JWT_SECRET is properly set
  if (!process.env.JWT_SECRET) {
    console.error('JWT_SECRET is not defined in environment variables');
    return res.status(500).json({ message: 'Server configuration error' });
  }
  
  // Extract token from request
  const token = extractToken(req);
  
  if (!token) {
    return res.status(401).json({ message: 'No token, authorization denied' });
  }
  
  // Verify token
  const verification = verifyToken(token, process.env.JWT_SECRET);
  
  if (!verification.success) {
    return res.status(401).json({ 
      message: 'Token is not valid', 
      error: verification.error.message 
    });
  }
  
  // Check token structure
  if (!verification.decoded.voter) {
    return res.status(401).json({ message: 'Invalid token structure' });
  }
  
  // Set voter in request and proceed with the expected structure
  // This maintains compatibility with existing code that expects req.voter.voter
  req.voter = {
    voter: verification.decoded.voter
  };
  next();
};