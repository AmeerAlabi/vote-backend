<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
</head>
<body>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center;">
    <h2 style="color: #333;">{{ config('app.name') }}</h2>
    <p style="font-size: 16px; color: #555;">{{ $intro }}</p>
    <h3 style="font-size: 24px; color: #007bff; margin: 20px 0; letter-spacing: 4px;">{{ $code }}</h3>
    <p style="font-size: 14px; color: #777;">This code will expire in {{ $expiresIn }}.</p>
    <p style="font-size: 14px; color: #777;">If you did not request this code, please ignore this email.</p>
</div>
</body>
</html>
