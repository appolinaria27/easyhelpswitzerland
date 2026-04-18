<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Page not found — Easy Help Switzerland</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    :root{--blue:#4693e8}
    body{
      font-family:"Manrope",system-ui,sans-serif;
      background:#0a0e14;
      color:#fff;
      min-height:100vh;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      padding:40px 24px;
    }
    .code{
      font-family:"Cormorant Garamond",serif;
      font-size:clamp(100px,20vw,180px);
      font-weight:500;
      line-height:1;
      color:var(--blue);
      opacity:.18;
      user-select:none;
    }
    h1{
      font-family:"Cormorant Garamond",serif;
      font-size:clamp(28px,6vw,48px);
      font-weight:500;
      margin-top:16px;
    }
    p{
      color:rgba(255,255,255,.6);
      font-size:16px;
      margin-top:12px;
      max-width:360px;
      line-height:1.6;
    }
    .actions{
      margin-top:36px;
      display:flex;
      gap:14px;
      flex-wrap:wrap;
      justify-content:center;
    }
    a.btn{
      display:inline-block;
      text-decoration:none;
      border-radius:999px;
      font-size:15px;
      font-weight:500;
      padding:13px 28px;
      transition:.2s;
    }
    a.btn-blue{background:var(--blue);color:#fff}
    a.btn-blue:hover{background:#317bcd}
    a.btn-outline{border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.8)}
    a.btn-outline:hover{border-color:#fff;color:#fff}
  </style>
</head>
<body>
  <div class="code">404</div>
  <h1>Page not found</h1>
  <p>The page you were looking for doesn't exist or has been moved.</p>
  <div class="actions">
    <a href="/" class="btn btn-blue">Go to home page</a>
    <a href="/free-consultation.php" class="btn btn-outline">Free consultation</a>
  </div>
</body>
</html>
