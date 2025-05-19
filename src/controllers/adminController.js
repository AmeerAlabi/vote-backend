const jwt = require('jsonwebtoken');
const Admin = require('../models/Admin');

// Admin signup
exports.signup = async (req, res) => {
  try {
    const { email, password } = req.body;
    
    // Validate input
    if (!email || !password) {
      return res.status(400).json({ message: 'Please provide email and password' });
    }
    
    // Check if admin already exists
    const existingAdmin = await Admin.findOne({ email });
    if (existingAdmin) {
      return res.status(400).json({ message: 'Admin already exists' });
    }
    
    // Validate admin email domain (optional)
    // const validDomains = ['school.edu', 'admin.edu'];
    // const emailDomain = email.split('@')[1];
    // if (!validDomains.includes(emailDomain)) {
    //   return res.status(400).json({ message: 'Invalid admin email domain' });
    // }
    
    // Create new admin
    const admin = new Admin({ email, password });
    await admin.save();
    
    res.status(201).json({ 
      message: 'Admin created successfully',
      userId: admin._id
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
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
    
    // Check password
    const isMatch = await admin.comparePassword(password);
    if (!isMatch) {
      return res.status(400).json({ message: 'Invalid credentials' });
    }
    
    // Create and send JWT token
    const payload = {
      admin: {
        id: admin._id
      }
    };
    
    jwt.sign(
      payload,
      process.env.JWT_SECRET,
      { expiresIn: '24h' },
      (err, token) => {
        if (err) throw err;
        res.json({ token });
      }
    );
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Server error' });
  }
};