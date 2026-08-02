<?php

function applicantConfirmationEmail(
    string $name,
    string $refNumber
): string {

    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>

body{
font-family:Arial,sans-serif;
background:#f4f6f9;
margin:0;
padding:30px;
}

.wrapper{
max-width:650px;
margin:auto;
background:#ffffff;
border-radius:12px;
overflow:hidden;
box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.header{
background:#0d9e78;
padding:30px;
color:#fff;
text-align:center;
}

.content{
padding:35px;
line-height:1.7;
font-size:15px;
color:#333;
}

.ref{
background:#0b2a23;
color:#fff;
padding:18px;
font-size:22px;
text-align:center;
border-radius:8px;
margin:20px 0;
font-weight:bold;
}

.step{

padding:12px;
margin:12px 0;
background:#f8fafc;
border-left:5px solid #0d9e78;

}

.footer{
padding:20px;
text-align:center;
font-size:12px;
color:#666;
border-top:1px solid #eee;
}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>Application Received</h1>

</div>

<div class="content">

<p>Dear <strong>{$name}</strong>,</p>

<p>

Thank you for applying to become a
<strong>CIBIL Repair Partner.</strong>

</p>

<p>

Your application has been received successfully.

</p>

<div class="ref">

{$refNumber}

</div>

<h3>What Happens Next?</h3>

<div class="step">

✅ Application Review (1–2 Business Days)

</div>

<div class="step">

✅ Document Verification

</div>

<div class="step">

✅ Approval Email

</div>

<div class="step">

✅ Partner Dashboard Access

</div>

<p>

Our Partnership Team will contact you shortly.

</p>

<p>

If you have any questions simply reply to this email.

</p>

</div>

<div class="footer">

© {$year} CIBIL Repair

</div>

</div>

</body>

</html>
HTML;

}



function adminNotificationEmail(
    string $body
): string{

return $body;

}