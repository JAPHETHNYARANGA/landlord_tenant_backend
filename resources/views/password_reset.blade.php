<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-image: linear-gradient(to right, #ED4690 0%, #5522CC 100%) !important;
            color: white;
            text-align: center;
            padding: 10px 0;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .content {
            padding: 20px;
        }
        .button {
            display: inline-block;
            background-image: linear-gradient(to right, #ED4690 0%, #5522CC 100%) !important;
            color: white !important;
            font-weight: bold;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Reset your LandLordTenant Password!</h2>
        </div>
        <div class="content">
            <p>Thank you for creating an account with LandLordTenant! We're excited to have you on board.</p>
            <p>To complete your password reset, please click the button below:</p>
            <a class="button" href="{{ url('password/reset', $token) }}">Reset Password</a>
            <p>If the button above doesn't work, you can copy and paste the URL below into your browser:</p>
            <p><code>{{ url('/password/reset', $token) }}</code></p>
            <p>If you did not create a request to reset your password, please ignore this email or contact our support team at <b>info@landtordtenant.com</b>.</p>
            <p>Thank you for choosing LandLordTenant!</p>
            <p>Best regards,</p>
            <p><b>LandLordTenant Team</b></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} LandLordTenant. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
