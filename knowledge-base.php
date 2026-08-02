<?php
// knowledge-base.php - Simple FAQ/knowledge base
$faqs = [
    ['Q: How long does credit repair take?', 'A: Typically 30-45 days for simple cases.'],
    ['Q: What is a good CIBIL score?', 'A: 750+ is considered excellent.'],
    ['Q: Can I check my CIBIL score for free?', 'A: Yes, once a year from each bureau.'],
    ['Q: How to improve credit score fast?', 'A: Pay bills on time, reduce credit card usage.'],
];
?>
<!DOCTYPE html>
<html>
<head><title>Knowledge Base</title></head>
<body>
<h1>📚 Knowledge Base</h1>
<?php foreach ($faqs as $faq): ?>
<div style="margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 5px;">
    <strong><?= $faq[0] ?></strong>
    <p><?= $faq[1] ?></p>
</div>
<?php endforeach; ?>
</body>
</html>
