require('dotenv').config();
const { Resend } = require('resend');

async function testAdminEmail() {
  console.log('Testing admin email verification...');
  
  // Log the environment variables being used
  console.log('Environment variables:', {
    RESEND_API_KEY: process.env.RESEND_API_KEY ? '***' : 'NOT SET',
    SENDER_EMAIL: process.env.SENDER_EMAIL || 'NOT SET'
  });

  try {
    const resend = new Resend(process.env.RESEND_API_KEY);
    
    console.log('Sending test verification email with config:', {
      from: process.env.SENDER_EMAIL,
      to: process.env.SENDER_EMAIL, // Send to yourself for testing
    });

    const data = await resend.emails.send({
      from: process.env.SENDER_EMAIL,
      to: process.env.SENDER_EMAIL,
      subject: 'Test Admin Verification Email',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center;">
          <h2 style="color: #333;">Email Verification Test</h2>
          <p style="font-size: 16px; color: #555;">
            Your test verification code is:
          </p>
          <h3 style="font-size: 24px; color: #007bff; background-color: #f5f5f5; padding: 10px; margin: 20px 0; text-align: center;">
            123456
          </h3>
          <p style="font-size: 14px; color: #777;">
            This is a test email for admin verification.
          </p>
        </div>
      `,
    });

    console.log('Test admin email sent successfully:', data);
  } catch (error) {
    console.error('Error sending test admin email:', error);
  }
}

testAdminEmail().catch(console.error);
