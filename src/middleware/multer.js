const multer = require('multer');
const path = require('path');

// Configure memory storage for file uploads
const storage = multer.memoryStorage();

// File filter to allow only image files
const fileFilter = (req, file, cb) => {
  try {
    console.log('Processing file:', {
      originalname: file.originalname,
      mimetype: file.mimetype,
      size: file.size
    });

    // Check file extension
    const filetypes = /jpe?g|png/;
    const extname = filetypes.test(path.extname(file.originalname).toLowerCase());
    
    // Check MIME type
    const mimetype = filetypes.test(file.mimetype);
    
    if (extname && mimetype) {
      console.log('File accepted:', file.originalname);
      return cb(null, true);
    } else {
      console.error('Invalid file type:', {
        originalname: file.originalname,
        mimetype: file.mimetype,
        extname: path.extname(file.originalname).toLowerCase()
      });
      return cb(new Error('Only JPEG, JPG, and PNG images are allowed'), false);
    }
  } catch (error) {
    console.error('Error in file filter:', error);
    return cb(error, false);
  }
};

// Configure multer with error handling
const upload = multer({
  storage: storage,
  limits: { 
    fileSize: 5 * 1024 * 1024, // 5MB limit
    files: 1 // Limit to 1 file
  },
  fileFilter: fileFilter
});

// Error handling middleware for multer
const handleMulterError = (err, req, res, next) => {
  if (err instanceof multer.MulterError) {
    // A Multer error occurred when uploading
    console.error('Multer error:', err);
    return res.status(400).json({
      message: 'File upload error',
      error: err.message
    });
  } else if (err) {
    // An unknown error occurred
    console.error('File upload error:', err);
    return res.status(500).json({
      message: 'Error processing file upload',
      error: process.env.NODE_ENV === 'development' ? err.message : 'File upload failed'
    });
  }
  // If no error, proceed to the next middleware
  next();
};

module.exports = { upload, handleMulterError };