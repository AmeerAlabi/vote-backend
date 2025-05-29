require('dotenv').config();
const nodemailer = require('nodemailer');

async function testEmail() {
  console.log('Testing email configuration...');
  
  // Log the environment variables being used
  console.log('Environment variables:', {
    EMAIL_SERVICE: process.env.EMAIL_SERVICE,
    EMAIL_USER: process.env.EMAIL_USER,
    EMAIL_PASS: process.env.EMAIL_PASS ? '***' : 'NOT SET',
    NODE_ENV: process.env.NODE_ENV || 'development'
  });

  // Create a test account (not needed with real credentials, but helpful for debugging)
  // const testAccount = await nodemailer.createTestAccount();
  // console.log('Test account created:', testAccount.user);

  // Create reusable transporter object using the Gmail SMTP transport
  const transporter = nodemailer.createTransport({
    host: 'smtp.gmail.com',
    port: 587,
    secure: false, // true for 465, false for other ports
    auth: {
      user: process.env.EMAIL_USER,
      pass: process.env.EMAIL_PASS
    },
    tls: {
      rejectUnauthorized: false // Only for testing, remove in production
    }
  });

  try {
    // Verify connection configuration
    console.log('Verifying SMTP connection...');
    await transporter.verify();
    console.log('Server is ready to take our messages');

    // Send test email
    console.log('Sending test email...');
    const info = await transporter.sendMail({
      from: `"Test Sender" <${process.env.EMAIL_USER}>`,
      to: process.env.EMAIL_USER, // Send to yourself
      subject: 'Test Email from Vote App',
      text: 'This is a test email from the Vote App',
      html: '<b>This is a test email from the Vote App</b>'
    });

    console.log('Test email sent successfully!');
    console.log('Message ID:', info.messageId);
    // Preview only available when sending through an Ethereal account
    // console.log('Preview URL: %s', nodemailer.getTestMessageUrl(info));
  } catch (error) {
    console.error('Error sending test email:', {
      message: error.message,
      code: error.code,
      response: error.response,
      stack: error.stack
    });
  }
}

testEmail().catch(console.error);
