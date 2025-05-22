const cloudinary = require('cloudinary').v2;

// Verify required environment variables
const requiredCredentials = ['CLOUDINARY_CLOUD_NAME', 'CLOUDINARY_API_KEY', 'CLOUDINARY_API_SECRET'];
const missingCredentials = requiredCredentials.filter(key => !process.env[key]);

if (missingCredentials.length > 0) {
  console.error('Missing required Cloudinary environment variables:', missingCredentials.join(', '));
  throw new Error('Missing Cloudinary configuration');
}

// Configure Cloudinary
cloudinary.config({
  cloud_name: process.env.CLOUDINARY_CLOUD_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET,
  secure: true, // Use HTTPS
  timeout: 30000, // 30 seconds timeout
  api_proxy: process.env.HTTP_PROXY || process.env.http_proxy // Support for proxy if needed
});

// Test Cloudinary connection
console.log('Cloudinary configured with cloud name:', process.env.CLOUDINARY_CLOUD_NAME);

module.exports = cloudinary;