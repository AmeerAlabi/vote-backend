require('dotenv').config();
const { Resend } = require('resend');

async function testResendDirectly() {
  try {
    console.log('Testing Resend email service directly...');
    console.log('Environment variables:', {
      RESEND_API_KEY: process.env.RESEND_API_KEY ? '***' : 'NOT SET',
      SENDER_EMAIL: 'onboarding@resend.dev' || 'NOT SET'
    });

    const resend = new Resend(process.env.RESEND_API_KEY);
    
    // Log the complete request
    console.log('Sending test email with the following configuration:');
    console.log({
      from: process.env.SENDER_EMAIL,
      to: 'ameeralabi7@gmail.com', // The email you're trying to verify
      subject: 'Test Email from Resend API',
      html: '<h1>This is a test email</h1><p>If you receive this, the Resend API is working correctly.</p>'
    });

    // Send the test email
    const { data, error } = await resend.emails.send({
      from: process.env.SENDER_EMAIL,
      to: 'ameeralabi7@gmail.com', // Must match the email used to create the Resend API key
      subject: 'Test Email from Resend API',
      html: '<h1>This is a test email</h1><p>If you receive this, the Resend API is working correctly.</p>'
    });

    if (error) {
      console.error('Error response from Resend API:', error);
    } else {
      console.log('Email sent successfully! Response data:', data);
      console.log('Check your inbox (and spam folder) for the test email');
    }
  } catch (error) {
    console.error('Exception during email sending:', error);
  }
}

testResendDirectly().catch(console.error);
