require('dotenv').config();
const cloudinary = require('cloudinary').v2;

// Configure Cloudinary
cloudinary.config({
  cloud_name: process.env.CLOUDINARY_CLOUD_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET,
  secure: true,
  timeout: 30000
});

console.log('Testing Cloudinary connection...');
console.log('Cloud name:', process.env.CLOUDINARY_CLOUD_NAME);

// Test Cloudinary API
cloudinary.api.ping()
  .then(result => {
    console.log('Cloudinary connection successful!', result);
  })
  .catch(error => {
    console.error('Cloudinary connection failed:', {
      message: error.message,
      code: error.code,
      http_code: error.http_code,
      name: error.name,
      stack: error.stack
    });
  });
