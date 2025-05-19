const mongoose = require('mongoose');
require('dotenv').config();

// Use environment variable for connection string
const MONGODB_URI = process.env.MONGODB_URI;

const connectDB = async () => {
  let attempts = 0;
  const maxAttempts = 3;
  
  while (attempts < maxAttempts) {
    attempts++;
    try {
      await mongoose.connect(MONGODB_URI);
      console.log('MongoDB connected successfully');
      return true;
    } catch (error) {
      console.error(`MongoDB connection attempt ${attempts} failed: ${error.message}`);
      if (attempts < maxAttempts) {
        console.log('Retrying connection in 5 seconds...');
        await new Promise(resolve => setTimeout(resolve, 5000));
      } else {
        console.error('All MongoDB connection attempts failed. Server will not start.');
        return false;
      }
    }
  }
};

module.exports = connectDB;