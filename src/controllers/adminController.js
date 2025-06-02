const jwt = require('jsonwebtoken');
const Admin = require('../models/Admin');
const crypto = require('crypto');
const { sendVerificationEmail } = require('../utils/emailHelper');
require('dotenv').config();

// Admin signup
exports.signup = async (req, res) => {
  try {
    const { email, password } = req.body;

    // Validate input
    if (!email || !password) {
      return res.status(400).json({ message: 'Please provide email and password' });
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({ message: 'Please provide a valid email address' });
    }

    // Validate password strength
    if (password.length < 8) {
      return res.status(400).json({ message: 'Password must be at least 8 characters long' });
    }

    // Check if admin already exists
    const existingAdmin = await Admin.findOne({ email });
    if (existingAdmin) {
      return res.status(400).json({ message: 'Admin already exists' });
    }

    // Create new admin with verification code
    const admin = new Admin({ email, password });
    const verificationCode = admin.generateVerificationCode();
    await admin.save();

    // Send verification email with code
    const emailSent = await sendVerificationEmail(email, verificationCode);
    if (!emailSent) {
      throw new Error('Failed to send verification email');
    }

    res.status(201).json({
      message: 'Admin created successfully. Please check your email for the verification code.',
      email: email,
      requiresVerification: true,
      userId: admin._id,
    });
  } catch (error) {
    console.error('Signup error:', error.message);
    res.status(500).json({ message: `Server error during signup: ${error.message}` });
  }
};

// Verify email
exports.verifyEmail = async (req, res) => {
  try {
    const { token } = req.query;

    if (!token) {
      return res.status(400).json({ message: 'Verification token is required' });
    }

    const admin = await Admin.findOne({
      verificationToken: token,
      verificationTokenExpires: { $gt: Date.now() },
    });

    if (!admin) {
      return res.status(400).json({ message: 'Invalid or expired verification token' });
    }

    admin.isVerified = true;
    admin.verificationToken = undefined;
    admin.verificationTokenExpires = undefined;
    await admin.save();

    res.json({ message: 'Email verified successfully' });
  } catch (error) {
    console.error('Verify email error:', error.message);
    res.status(500).json({ message: 'Server error during email verification' });
  }
};

// Verify email with code
exports.verifyEmailWithCode = async (req, res) => {
  try {
    const { email, code } = req.body;

    if (!email || !code) {
      return res.status(400).json({ message: 'Email and verification code are required' });
    }

    // Find admin by email
    const admin = await Admin.findOne({ email });

    if (!admin) {
      return res.status(404).json({ message: 'Admin not found with this email' });
    }

    // Check if already verified
    if (admin.isVerified) {
      return res.status(200).json({
        message: 'Email already verified',
        alreadyVerified: true,
      });
    }

    // Check verification code
    if (!admin.verificationCode || admin.verificationCode !== code) {
      return res.status(400).json({ message: 'Invalid verification code' });
    }

    // Check if code is expired
    if (!admin.verificationCodeExpires || admin.verificationCodeExpires < Date.now()) {
      // Generate a new verification code and send it
      const newVerificationCode = admin.generateVerificationCode();
      await admin.save();

      const emailSent = await sendVerificationEmail(email, newVerificationCode);
      if (!emailSent) {
        console.error('Error sending new verification email');
        return res.status(500).json({ message: 'Error sending new verification email' });
      }

      return res.status(400).json({
        message: 'Verification code expired. A new code has been sent to your email.',
        codeExpired: true,
      });
    }

    // Mark as verified and clear verification data
    admin.isVerified = true;
    admin.verificationCode = undefined;
    admin.verificationCodeExpires = undefined;
    await admin.save();

    // Generate login token
    const payload = {
      admin: {
        id: admin._id,
        email: admin.email,
      },
    };

    try {
      const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '24h' });
      res.json({
        message: 'Email verified successfully',
        token,
        admin: {
          id: admin._id,
          email: admin.email,
        },
      });
    } catch (tokenError) {
      console.error('Error generating token:', tokenError.message);
      res.json({ message: 'Email verified successfully. Please login to continue.' });
    }
  } catch (error) {
    console.error('Verification error:', error.message);
    res.status(500).json({ message: 'Server error during verification' });
  }
};

// Forgot password
exports.forgotPassword = async (req, res) => {
  try {
    const { email } = req.body;

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({ message: 'Please provide a valid email address' });
    }

    const admin = await Admin.findOne({ email });
    if (!admin) {
      return res.json({ message: 'If your email is registered, you will receive a password reset code' });
    }

    // Generate a password reset code
    const resetCode = admin.generatePasswordResetCode();
    await admin.save();

    // Send the reset code via email
    const emailSent = await sendVerificationEmail(email, resetCode);
    if (!emailSent) {
      throw new Error('Failed to send password reset email');
    }

    res.json({
      message: 'Password reset code sent successfully',
      email: email,
    });
  } catch (error) {
    console.error('Forgot password error:', error.message);
    res.status(500).json({ message: 'Server error during password reset request' });
  }
};

// Reset password
exports.resetPassword = async (req, res) => {
  try {
    const { email, code, password } = req.body;

    // Validate inputs
    if (!email || !code || !password) {
      return res.status(400).json({ message: 'Email, reset code, and new password are required' });
    }

    // Validate password strength
    if (password.length < 8) {
      return res.status(400).json({ message: 'Password must be at least 8 characters long' });
    }

    // Find admin by email
    const admin = await Admin.findOne({ email });

    if (!admin) {
      return res.status(404).json({ message: 'Admin not found with this email' });
    }

    // Verify reset code
    if (!admin.passwordResetToken || admin.passwordResetToken !== code) {
      return res.status(400).json({ message: 'Invalid reset code' });
    }

    // Check if code is expired
    if (!admin.passwordResetTokenExpires || admin.passwordResetTokenExpires < Date.now()) {
      return res.status(400).json({
        message: 'Reset code has expired. Please request a new code.',
        codeExpired: true,
      });
    }

    // Update password and clear reset data
    admin.password = password;
    admin.passwordResetToken = undefined;
    admin.passwordResetTokenExpires = undefined;
    await admin.save();

    // Generate login token
    const payload = {
      admin: {
        id: admin._id,
        email: admin.email,
      },
    };

    try {
      const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '24h' });
      res.json({
        message: 'Password reset successfully',
        token,
        admin: {
          id: admin._id,
          email: admin.email,
        },
      });
    } catch (tokenError) {
      console.error('Error generating token:', tokenError.message);
      res.json({ message: 'Password reset successfully. Please login with your new password.' });
    }
  } catch (error) {
    console.error('Password reset error:', error.message);
    res.status(500).json({ message: 'Server error during password reset' });
  }
};

// Admin login
exports.login = async (req, res) => {
  try {
    const { email, password } = req.body;

    // Validate input
    if (!email || !password) {
      return res.status(400).json({ message: 'Please provide email and password' });
    }

    // Find admin
    const admin = await Admin.findOne({ email });
    if (!admin) {
      return res.status(400).json({ message: 'Invalid credentials' });
    }

    // Check if admin is verified
    if (!admin.isVerified) {
      // Generate a new verification code and send it
      const verificationCode = admin.generateVerificationCode();
      await admin.save();

      const emailSent = await sendVerificationEmail(email, verificationCode);
      if (!emailSent) {
        console.error('Error sending verification email');
        return res.status(500).json({ message: 'Error sending verification email' });
      }

      return res.status(403).json({
        message: 'Email not verified. A new verification code has been sent to your email.',
        requiresVerification: true,
      });
    }

    // Check password
    const isMatch = await admin.comparePassword(password);
    if (!isMatch) {
      return res.status(400).json({ message: 'Invalid credentials' });
    }

    // Check if JWT_SECRET is set
    if (!process.env.JWT_SECRET) {
      console.error('JWT_SECRET is not defined in environment variables');
      return res.status(500).json({ message: 'Server configuration error' });
    }

    // Create and send JWT token
    const payload = {
      admin: {
        id: admin._id,
        email: admin.email,
      },
    };

    try {
      const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '24h' });
      return res.json({
        token,
        admin: {
          id: admin._id,
          email: admin.email,
        },
      });
    } catch (tokenError) {
      console.error('Error generating token:', tokenError.message);
      return res.status(500).json({ message: 'Error generating authentication token' });
    }
  } catch (error) {
    console.error('Login error:', error.message);
    res.status(500).json({ message: 'Server error during login' });
  }
};