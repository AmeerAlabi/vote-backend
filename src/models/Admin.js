const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');

const adminSchema = new mongoose.Schema({
  email: {
    type: String,
    required: true,
    unique: true
  },
  password: {
    type: String,
    required: true
  },
  isVerified: {
    type: Boolean,
    default: false
  },
  verificationCode: String,
  verificationCodeExpires: Date,
  passwordResetToken: String,
  passwordResetTokenExpires: Date
});

// Hash password before saving
adminSchema.pre('save', async function(next) {
  if (this.isModified('password')) {
    this.password = await bcrypt.hash(this.password, 10);
  }
  next();
});

// Generate verification code
adminSchema.methods.generateVerificationCode = function() {
  // Generate a 6-digit code
  const code = Math.floor(100000 + Math.random() * 900000).toString();
  this.verificationCode = code;
  this.verificationCodeExpires = Date.now() + 24 * 60 * 60 * 1000; // 24 hours
  return code;
};

// Generate password reset code
adminSchema.methods.generatePasswordResetCode = function() {
  // Generate a 6-digit code
  const code = Math.floor(100000 + Math.random() * 900000).toString();
  this.passwordResetToken = code;
  this.passwordResetTokenExpires = Date.now() + 60 * 60 * 1000; // 1 hour
  return code;
};

// Method to compare passwords
adminSchema.methods.comparePassword = async function(candidatePassword) {
  return await bcrypt.compare(candidatePassword, this.password);
};

const Admin = mongoose.model('Admin', adminSchema);

module.exports = Admin;