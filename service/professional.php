<?php
include '../config/database.php';
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Save lead to database when form is submitted via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    // Verify CSRF token
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    $name         = trim(htmlspecialchars($data['name']  ?? '', ENT_QUOTES, 'UTF-8'));
    $email        = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $phone        = trim(preg_replace('/[^0-9]/', '', $data['phone'] ?? ''));
    $service_id   = (int)($data['service_id'] ?? 8);
    $service_name = 'Professional — Comprehensive Credit Repair';
    $amount       = 10999;
    $ip_address   = $_SERVER['REMOTE_ADDR'];

    if (empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']); exit;
    }
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid 10-digit mobile number']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address']); exit;
    }
    if (strlen($name) < 3 || strlen($name) > 100) {
        echo json_encode(['success' => false, 'error' => 'Name must be between 3 and 100 characters']); exit;
    }

    // Rate limiting
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count >= 3) {
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO leads (name, phone, email, service, service_name, amount, ip_address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    if ($stmt) {
        $stmt->bind_param("sssssds", $name, $phone, $email, $service_name, $service_name, $amount, $ip_address);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'lead_id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-CJK55QNK22"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-CJK55QNK22');
  </script>
  
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <meta name="theme-color" content="#0a1628">
  <meta name="description" content="Remove wrong entries, written-off accounts, and settled accounts from your CIBIL report. Includes forensic audit and dedicated case manager. 98% success rate.">
  <meta name="keywords" content="professional credit repair, wrong entry removal, written-off clearance, settled clearance, comprehensive credit repair, CIBIL repair">
  <meta name="author" content="CIBIL Repair">
  <title>Professional — Comprehensive Credit Repair | CIBIL Score Repair</title>
  <link rel="canonical" href="https://cibilrepair.in/service/professional">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

  <!-- ============================================================
     SCHEMA 1: WEBSITE (for site name in search results)
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "CIBIL Repair",
      "alternateName": ["CIBIL Repair India", "CIBIL"],
      "url": "https://cibilrepair.in/",
      "description": "India's most trusted credit repair consultancy. 5,000+ Indians have fixed their CIBIL scores legally with 98% success rate.",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://cibilrepair.in/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <!-- ============================================================
     SCHEMA 2: ORGANIZATION
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "CIBIL Repair",
      "alternateName": ["CIBIL Repair India", "CIBIL"],
      "url": "https://cibilrepair.in/",
      "logo": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
      "image": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
      "telephone": "+919905482503",
      "email": "contact@cibilrepair.in",
      "description": "India's most trusted credit repair consultancy since 2018. 5,000+ Indians have fixed their CIBIL scores legally with 98% success rate.",
      "foundingDate": "2018",
      "founder": {
        "@type": "Person",
        "name": "Vikram Malhotra"
      },
      "numberOfEmployees": {
        "@type": "QuantitativeValue",
        "value": "25"
      },
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Delhi NCR",
        "addressLocality": "Delhi",
        "addressRegion": "Delhi",
        "postalCode": "110070",
        "addressCountry": "IN"
      },
      "sameAs": [
        "https://www.facebook.com/cibilrepair",
        "https://www.instagram.com/cibilrepair1",
        "https://twitter.com/cibilrepair0",
        "https://www.linkedin.com/company/cibil-repair",
        "https://www.youtube.com/channel/UCG5yi-vJkUPb2OJESSKf8Kg"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+919905482503",
        "contactType": "customer service",
        "areaServed": "IN",
        "availableLanguage": ["English", "Hindi"]
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "5000",
        "bestRating": "5"
      }
    }
    </script>

    <!-- ============================================================
     SCHEMA 3: LOCAL BUSINESS
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "CIBIL Repair",
      "alternateName": ["CIBIL Repair India", "CIBIL"],
      "image": "https://cibilrepair.in/images/logo/ciibil-repair-logo.png",
      "url": "https://cibilrepair.in/",
      "telephone": "+919905482503",
      "email": "contact@cibilrepair.in",
      "description": "India's most trusted credit repair consultancy. 5,000+ Indians have fixed their CIBIL scores legally with 98% success rate.",
      "priceRange": "₹3,999 - ₹10,999",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Delhi NCR",
        "addressLocality": "Delhi",
        "addressRegion": "Delhi",
        "postalCode": "110070",
        "addressCountry": "IN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 28.6139,
        "longitude": 77.2090
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
          "opens": "09:00",
          "closes": "19:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Saturday",
          "opens": "10:00",
          "closes": "17:00"
        }
      ],
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "5000",
        "bestRating": "5"
      },
      "sameAs": [
        "https://www.facebook.com/cibilrepair",
        "https://www.instagram.com/cibilrepair1",
        "https://twitter.com/cibilrepair0",
        "https://www.linkedin.com/company/cibil-repair",
        "https://www.youtube.com/channel/UCG5yi-vJkUPb2OJESSKf8Kg"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+919905482503",
        "contactType": "customer service",
        "areaServed": "IN",
        "availableLanguage": ["English", "Hindi"]
      }
    }
    </script>

    <!-- ============================================================
     SCHEMA 4: SERVICE (PROFESSIONAL)
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "name": "Professional — Comprehensive Credit Repair",
      "image": "https://cibilrepair.in/images/services/professional.jpg",
      "description": "Remove wrong entries, written-off accounts, and settled accounts across all four credit bureaus. Includes forensic audit (₹5,999 value), dedicated case manager, and bank introduction post-fix.",
      "provider": {
        "@type": "Organization",
        "name": "CIBIL Repair",
        "alternateName": ["CIBIL Repair India", "CIBIL"],
        "url": "https://cibilrepair.in"
      },
      "brand": {
        "@type": "Brand",
        "name": "CIBIL Repair"
      },
      "url": "https://cibilrepair.in/service/professional",
      "areaServed": "IN",
      "serviceType": "Credit Repair",
      "offers": {
        "@type": "Offer",
        "priceCurrency": "INR",
        "price": "10999",
        "availability": "https://schema.org/InStock",
        "priceValidUntil": "2026-12-31"
      },
      "audience": {
        "@type": "Audience",
        "name": "Individuals with wrong entries, written-off, or settled accounts on their credit report"
      },
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "https://cibilrepair.in/service/professional"
      }
    }
    </script>

    <!-- ============================================================
     SCHEMA 5: WEB PAGE
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Professional — Comprehensive Credit Repair | CIBIL Repair",
      "description": "Remove wrong entries, written-off accounts, and settled accounts from your CIBIL report. Includes forensic audit and dedicated case manager. 98% success rate.",
      "url": "https://cibilrepair.in/service/professional",
      "mainEntity": {
        "@type": "Service",
        "name": "Professional — Comprehensive Credit Repair",
        "url": "https://cibilrepair.in/service/professional"
      }
    }
    </script>

    <!-- ============================================================
     SCHEMA 6: BREADCRUMB
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "name": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "https://cibilrepair.in/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Services",
          "item": "https://cibilrepair.in/services"
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": "Professional — Comprehensive Credit Repair",
          "item": "https://cibilrepair.in/service/professional"
        }
      ]
    }
    </script>
    
    <!-- ============================================================
     SCHEMA 7: PRODUCT/OFFER (PROFESSIONAL)
     ============================================================ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "Professional — Comprehensive Credit Repair",
      "description": "Remove wrong entries, written-off accounts, and settled accounts across all four credit bureaus. Includes forensic audit (₹5,999 value), dedicated case manager, and bank introduction post-fix.",
      "image": "https://cibilrepair.in/images/services/professional.jpg",
      "brand": {
        "@type": "Brand",
        "name": "CIBIL Repair"
      },
      "offers": {
        "@type": "Offer",
        "priceCurrency": "INR",
        "price": "10999",
        "availability": "https://schema.org/InStock",
        "url": "https://cibilrepair.in/service/professional",
        "priceValidUntil": "2026-12-31",
        "hasMerchantReturnPolicy": {
          "@type": "MerchantReturnPolicy",
          "applicableCountry": "IN",
          "returnPolicyCategory": "https://schema.org/MerchantReturnNotPermitted"
        },
        "shippingDetails": {
          "@type": "OfferShippingDetails",
          "shippingRate": {
            "@type": "MonetaryAmount",
            "value": "0",
            "currency": "INR"
          },
          "shippingDestination": {
            "@type": "DefinedRegion",
            "addressCountry": "IN"
          },
          "deliveryTime": {
            "@type": "ShippingDeliveryTime",
            "handlingTime": {
              "@type": "QuantitativeValue",
              "minValue": 0,
              "maxValue": 1,
              "unitCode": "DAY"
            },
            "transitTime": {
              "@type": "QuantitativeValue",
              "minValue": 0,
              "maxValue": 1,
              "unitCode": "DAY"
            }
          },
          "shippingLabel": "Free Digital Delivery",
          "shippingSettingsLink": "https://cibilrepair.in/terms"
        }
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "5000",
        "bestRating": "5"
      }
    }
    </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ============================================================
   ROOT TOKENS — exact match with existing pages
   ============================================================ */
:root{
  --navy:#071428;--navy2:#0a1e3c;--navy3:#0d2550;
  --blue:#1a4a9c;--blue2:#1e5ac0;--blueL:#4c8cff;
  --green:#22c55e;--green2:#16a34a;
  --gold:#f5c518;--gold2:#e8b000;
  --orange:#f97316;--red:#ef4444;
  --white:#ffffff;--light:#f0f4fa;
  --t1:#1a2340;--t2:#4a5a7a;--t3:#7a8aaa;
  --bd:#dde5f0;--bds:#edf2fb;
  --r:14px;--rs:8px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Open Sans',sans-serif;background:#fff;color:var(--t1);overflow-x:hidden}

/* PROGRESS BAR */
.prog{position:fixed;top:0;left:0;right:0;z-index:10000;height:3px;background:rgba(34,197,94,.15)}
.prog-f{width:0%;height:100%;background:linear-gradient(90deg,var(--green),var(--gold));transition:width .1s linear}

/* ============================================================
   NAV
   ============================================================ */
nav{position:fixed;top:0;left:0;right:0;z-index:9999;background:#fff;box-shadow:0 2px 20px rgba(0,0,0,.12);height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;transition:all .3s}
nav.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.18)}
.nav-logo{display:flex;align-items:center;gap:12px;text-decoration:none;flex-shrink:0}
.logo-text-wrap{display:flex;flex-direction:column}
.logo-name{font-family:'Montserrat',sans-serif;font-size:1.5rem;font-weight:900;line-height:1;color:var(--t1)}
.logo-name span{color:var(--green);font-style:italic}
.logo-tag{font-size:10px;color:var(--t2);letter-spacing:.5px;margin-top:2px;font-weight:500}
.nav-links{display:flex;align-items:center;gap:4px;list-style:none}
.nl-item{position:relative}
.nl-item>a,.nl-item>button{font-family:'Montserrat',sans-serif;font-size:13.5px;font-weight:600;color:var(--t1);padding:9px 14px;border-radius:var(--rs);text-decoration:none;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .2s;white-space:nowrap}
.nl-item>a:hover,.nl-item>button:hover{color:var(--blue2);background:#f0f5ff}
.nl-item>a.active{color:var(--blue2)}
.chev{font-size:9px;color:var(--t3);transition:transform .25s;display:inline-block}
.nl-item:hover .chev{transform:rotate(180deg);color:var(--green)}
.dd{position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%) translateY(8px);background:#fff;border:1px solid var(--bd);border-radius:var(--r);padding:8px;box-shadow:0 20px 60px rgba(0,0,0,.15);min-width:230px;opacity:0;visibility:hidden;pointer-events:none;transition:all .26s cubic-bezier(.4,0,.2,1);z-index:200}
.nl-item:hover .dd{opacity:1;visibility:visible;pointer-events:auto;transform:translateX(-50%) translateY(0)}
.dd::before{content:'';position:absolute;top:-6px;left:50%;transform:translateX(-50%);width:12px;height:6px;background:#fff;clip-path:polygon(50% 0%,0% 100%,100% 100%)}
.dd-head{font-size:10px;font-weight:700;color:var(--t3);letter-spacing:1.2px;text-transform:uppercase;padding:6px 12px 4px;border-bottom:1px solid var(--bd);margin-bottom:4px}
.dd a{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:var(--rs);text-decoration:none;color:var(--t1);font-size:13px;font-weight:500;transition:all .18s}
.dd a:hover{background:#f0f7ff;color:var(--blue2);padding-left:16px}
.dd a i{color:var(--blue2);font-size:13px;width:18px;text-align:center;margin-top:1px;flex-shrink:0}
.dd a strong{display:block;font-size:12.5px;font-weight:700;margin-bottom:2px}
.dd a span{font-size:10.5px;color:var(--t3);font-weight:400}
.dd-wide{min-width:440px;display:grid;grid-template-columns:1fr 1fr}
.dd-wide .dd-col{padding:4px}
.dd-div{height:1px;background:var(--bd);margin:4px 8px}
.nav-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.btn-login{font-family:'Montserrat',sans-serif;font-size:13px;font-weight:600;color:var(--t1);background:transparent;border:2px solid var(--bd);padding:8px 18px;border-radius:var(--rs);text-decoration:none;cursor:pointer;transition:all .2s}
.btn-login:hover{border-color:var(--blue2);color:var(--blue2)}
.btn-partner{font-family:'Montserrat',sans-serif;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--blue),var(--blue2));padding:9px 20px;border-radius:var(--rs);text-decoration:none;cursor:pointer;border:none;transition:all .2s;box-shadow:0 4px 14px rgba(26,74,156,.35)}
.btn-partner:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(26,74,156,.5)}
.btn-getstarted{font-family:'Montserrat',sans-serif;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--green),var(--green2));padding:10px 24px;border-radius:var(--rs);text-decoration:none;cursor:pointer;border:none;transition:all .2s;box-shadow:0 4px 14px rgba(34,197,94,.35)}
.btn-getstarted:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(34,197,94,.55)}
.ham{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:10px;z-index:1002}
.ham span{width:24px;height:2.5px;background:var(--t1);border-radius:3px;transition:all .3s;display:block}
.ham.active span:nth-child(1){transform:translateY(8px) rotate(45deg)}
.ham.active span:nth-child(2){opacity:0;transform:scaleX(0)}
.ham.active span:nth-child(3){transform:translateY(-8px) rotate(-45deg)}

/* MOBILE MENU */
.mob{position:fixed;top:0;right:-100%;width:320px;height:100vh;background:linear-gradient(180deg,#0a1a2e 0%,#0d2a3a 100%);padding:0;list-style:none;z-index:1001;transition:right .4s cubic-bezier(.4,0,.2,1);overflow-y:auto;overflow-x:hidden;box-shadow:-5px 0 30px rgba(0,0,0,.3)}
.mob.open{right:0}
.mob-header{padding:25px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:10px}
.mob-header .logo{font-family:'Montserrat',sans-serif;font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:5px}
.mob-header .logo span{color:var(--green)}
.mob-header .tagline{font-size:.7rem;color:rgba(255,255,255,.5);letter-spacing:1px}
.mob-user{padding:20px;background:rgba(255,255,255,.05);margin:10px 15px 20px;border-radius:16px;display:flex;align-items:center;gap:12px}
.mob-user-icon{width:48px;height:48px;background:linear-gradient(135deg,var(--green),var(--blue2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff}
.mob-user-info h4{color:#fff;font-size:.9rem;font-weight:600;margin-bottom:4px}
.mob-user-info p{color:rgba(255,255,255,.5);font-size:.7rem}
.mob-nav-item{margin:0 15px 5px;border-radius:12px;overflow:hidden}
.mob-nav-link{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;color:#fff;text-decoration:none;font-size:.95rem;font-weight:500;cursor:pointer;border-radius:12px;transition:all .3s}
.mob-nav-link:hover{background:rgba(255,255,255,.08)}
.mob-nav-link .link-left{display:flex;align-items:center;gap:14px}
.mob-nav-link .link-left i{width:24px;font-size:1.1rem;color:var(--green)}
.mob-nav-link .chevron{transition:transform .3s;font-size:.7rem;color:rgba(255,255,255,.5)}
.mob-dropdown.active>.mob-nav-link .chevron{transform:rotate(180deg)}
.mob-submenu{max-height:0;opacity:0;overflow:hidden;transition:all .4s cubic-bezier(.4,0,.2,1);background:rgba(0,0,0,.2);border-radius:0 0 12px 12px}
.mob-dropdown.active .mob-submenu{max-height:500px;opacity:1}
.mob-submenu a{display:flex;align-items:center;gap:12px;padding:12px 18px 12px 52px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;transition:all .3s;border-left:2px solid transparent}
.mob-submenu a:hover{background:rgba(34,197,94,.15);color:#fff;padding-left:58px;border-left-color:var(--green)}
.mob-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);margin:15px}
.mob-actions{padding:15px;margin-top:10px}
.mob-action-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px;border-radius:40px;text-decoration:none;font-weight:600;font-size:.85rem;transition:all .3s;margin-bottom:10px;border:none;cursor:pointer}
.mob-action-btn.partner{background:linear-gradient(135deg,var(--blue),var(--blue2));color:#fff}
.mob-action-btn.get-started{background:linear-gradient(135deg,var(--green),var(--green2));color:#fff}
.menu-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:1000}
.menu-overlay.active{display:block}

/* ============================================================
   PAGE HERO
   ============================================================ */
.page-hero{background:linear-gradient(135deg,#060e1e 0%,#091530 40%,#0d1f45 70%,#071020 100%);min-height:60vh;display:flex;align-items:center;padding:140px 0 80px;position:relative;overflow:hidden;margin-top:72px}
.page-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(76,140,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(76,140,255,.03) 1px,transparent 1px);background-size:45px 45px}
.page-hero::after{content:'';position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--green),var(--blueL),var(--gold))}
.hero-orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
.orb1{width:500px;height:500px;background:rgba(34,197,94,.07);top:-100px;right:-100px}
.orb2{width:400px;height:400px;background:rgba(26,74,156,.1);bottom:-80px;left:-60px}
.ph-inner{max-width:1280px;margin:0 auto;padding:0 40px;display:grid;grid-template-columns:1.1fr 1fr;gap:80px;align-items:center;position:relative;z-index:2}
.ph-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:var(--green);padding:7px 16px;border-radius:30px;font-family:'Montserrat',sans-serif;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:22px}
.ph-dot{width:7px;height:7px;background:var(--green);border-radius:50%;animation:glow 1.8s ease-in-out infinite}
@keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.6)}50%{box-shadow:0 0 0 7px rgba(34,197,94,0)}}
.ph-h1{font-family:'Bebas Neue',sans-serif;font-size:clamp(3rem,5.5vw,5rem);line-height:.95;letter-spacing:2px;margin-bottom:18px}
.ph-h1 .w{color:#fff;display:block}.ph-h1 .g{color:var(--green);display:block}.ph-h1 .y{color:var(--gold);display:block}
.ph-sub{font-size:1rem;color:rgba(255,255,255,.65);line-height:1.75;max-width:480px;margin-bottom:30px}
.ph-acts{display:flex;gap:12px;flex-wrap:wrap}
.btn-primary{display:inline-flex;align-items:center;gap:9px;font-family:'Montserrat',sans-serif;font-weight:800;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--green),var(--green2));padding:14px 30px;border-radius:50px;border:none;cursor:pointer;text-decoration:none;transition:all .3s;box-shadow:0 8px 24px rgba(34,197,94,.35)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 14px 36px rgba(34,197,94,.5)}
.btn-ghost{display:inline-flex;align-items:center;gap:9px;font-family:'Montserrat',sans-serif;font-weight:700;font-size:14px;color:#fff;background:transparent;border:2px solid rgba(255,255,255,.3);padding:12px 28px;border-radius:50px;text-decoration:none;transition:all .3s}
.btn-ghost:hover{border-color:rgba(255,255,255,.7);background:rgba(255,255,255,.08)}
.ph-trust{display:flex;gap:28px;margin-top:28px;padding-top:24px;border-top:1px solid rgba(255,255,255,.1);flex-wrap:wrap}
.pht{text-align:center}
.pht-n{font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--gold);line-height:1;display:block;letter-spacing:1px}
.pht-l{font-family:'Montserrat',sans-serif;font-size:10px;font-weight:600;color:rgba(255,255,255,.45);margin-top:3px;letter-spacing:.3px}

/* Hero right card */
.ph-card{background:rgba(255,255,255,.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.12);border-radius:24px;padding:28px;position:relative;overflow:hidden}
.ph-card::before{content:'';position:absolute;top:-1px;left:15%;right:15%;height:3px;background:linear-gradient(90deg,transparent,var(--green),var(--gold),transparent)}
.ph-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.ph-card-title{font-family:'Montserrat',sans-serif;font-size:11px;font-weight:700;color:rgba(255,255,255,.5);letter-spacing:1.5px;text-transform:uppercase}
.ph-live{display:flex;align-items:center;gap:6px;font-family:'Montserrat',sans-serif;font-size:11px;font-weight:700;color:var(--green)}
.ph-live::before{content:'';width:7px;height:7px;background:var(--green);border-radius:50%;display:block;animation:glow 1.8s infinite}
.milestones{display:flex;flex-direction:column;gap:12px}
.ms{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:14px;transition:all .3s}
.ms:hover{background:rgba(34,197,94,.06);border-color:rgba(34,197,94,.2)}
.ms-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.ms-icon.g{background:rgba(34,197,94,.2)}.ms-icon.b{background:rgba(76,140,255,.2)}.ms-icon.y{background:rgba(245,197,24,.15)}
.ms-text{flex:1}
.ms-title{font-family:'Montserrat',sans-serif;font-size:12.5px;font-weight:700;color:#fff;margin-bottom:3px}
.ms-sub{font-size:11px;color:rgba(255,255,255,.4)}
.ms-val{font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:1px;white-space:nowrap}
.ms-val.g{color:var(--green)}.ms-val.y{color:var(--gold)}.ms-val.b{color:#7ab4ff}

/* ============================================================
   BREADCRUMB
   ============================================================ */
.breadcrumb-bar{background:#f8faff;border-bottom:1px solid var(--bds);padding:12px 0}
.bc-inner{max-width:1280px;margin:0 auto;padding:0 40px;display:flex;align-items:center;gap:8px;font-family:'Montserrat',sans-serif;font-size:12px;font-weight:600}
.bc-inner a{color:var(--t3);text-decoration:none;transition:color .2s}
.bc-inner a:hover{color:var(--blue2)}
.bc-inner span{color:var(--t3)}
.bc-inner .current{color:var(--blue2)}

/* ============================================================
   STATS BAND
   ============================================================ */
.stats-band{background:linear-gradient(135deg,#061020,#0a1a38);padding:50px 0;border-top:3px solid var(--green);border-bottom:3px solid rgba(255,255,255,.05)}
.sb-grid{max-width:1280px;margin:0 auto;padding:0 40px;display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.sb-item{text-align:center;padding:16px;border-right:1px solid rgba(255,255,255,.08)}
.sb-item:last-child{border-right:none}
.sb-num{font-family:'Bebas Neue',sans-serif;font-size:2.8rem;display:block;line-height:1;letter-spacing:2px}
.sb-num.gold{color:var(--gold)}.sb-num.green{color:var(--green)}.sb-num.blue{color:#7ab4ff}
.sb-desc{font-family:'Montserrat',sans-serif;font-size:11.5px;font-weight:600;color:rgba(255,255,255,.45);margin-top:6px}

/* ============================================================
   SHARED LAYOUT
   ============================================================ */
.W{max-width:1280px;margin:0 auto;padding:0 40px}
.sec{padding:90px 0}
.sec-alt{background:var(--light)}
.sh{text-align:center;margin-bottom:60px}
.pill{display:inline-flex;align-items:center;gap:7px;background:#e8f4ff;border:1px solid #c0d8ff;color:var(--blue2);padding:5px 14px;border-radius:30px;font-family:'Montserrat',sans-serif;font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:16px}
.pill-green{background:#e8fdf2;border-color:#a7f3cf;color:var(--green2)}
.pill-gold{background:#fff8e0;border-color:#fde68a;color:#b45309}
.pill-dark{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.3);color:var(--green)}
.st{font-family:'Bebas Neue',sans-serif;font-size:clamp(2.2rem,4.5vw,3.4rem);line-height:1;margin-bottom:14px;color:var(--t1);letter-spacing:2px}
.st em{font-style:normal;color:var(--green)}
.ss{font-size:.96rem;color:var(--t2);max-width:560px;margin:0 auto;line-height:1.75}
.r{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
.r.on{opacity:1;transform:translateY(0)}

/* ============================================================
   WHAT WE FIX — 3-col cards
   ============================================================ */
.check-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.ck{background:#fff;border:2px solid var(--bds);border-radius:20px;padding:28px 24px;text-align:center;transition:all .3s;position:relative;overflow:hidden}
.ck::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--green),var(--blue2));opacity:0;transition:opacity .3s}
.ck:hover{border-color:var(--green);transform:translateY(-5px);box-shadow:0 16px 36px rgba(0,0,0,.08)}
.ck:hover::after{opacity:1}
.ck-icon{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 18px;background:linear-gradient(135deg,#d8fce8,#b8f0d0);transition:transform .3s}
.ck:hover .ck-icon{transform:scale(1.08)}
.ck-name{font-family:'Montserrat',sans-serif;font-size:1rem;font-weight:800;color:var(--t1);margin-bottom:9px}
.ck-desc{font-size:.845rem;color:var(--t2);line-height:1.72}

/* ============================================================
   PROCESS STEPS — dark
   ============================================================ */
.process-sec{background:linear-gradient(135deg,#061020,#0a1a38);padding:90px 0;position:relative;overflow:hidden}
.process-sec::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(76,140,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(76,140,255,.03) 1px,transparent 1px);background-size:45px 45px}
.steps-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:0;position:relative;z-index:1}
.steps-grid::before{content:'';position:absolute;top:40px;left:8%;right:8%;height:2px;background:linear-gradient(90deg,var(--green),var(--blue2),var(--gold));z-index:0}
.step-card{text-align:center;padding:0 12px;position:relative;z-index:1}
.step-num-wrap{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green2));border:4px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(34,197,94,.4)}
.step-num-wrap i{font-size:28px;color:#fff}
.step-title{font-family:'Montserrat',sans-serif;font-size:.82rem;font-weight:800;color:#fff;margin-bottom:8px}
.step-desc{font-size:.75rem;color:rgba(255,255,255,.5);line-height:1.65}

/* ============================================================
   SERVICE LAYOUT — 2-col: content + sticky payment card
   ============================================================ */
.service-layout{display:grid;grid-template-columns:1fr 420px;gap:50px;margin:0 0 60px}
.service-content h2{font-family:'Bebas Neue',sans-serif;font-size:1.9rem;letter-spacing:1.5px;color:var(--t1);margin:36px 0 14px;border-left:4px solid var(--green);padding-left:15px}
.service-content h2:first-child{margin-top:0}
.service-content p{line-height:1.75;color:var(--t2);margin-bottom:18px;font-size:.93rem}

/* inline stats */
.inline-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:24px 0}
.istat{background:linear-gradient(135deg,#061020,#0a1a38);padding:22px 18px;border-radius:16px;text-align:center;border:1px solid rgba(34,197,94,.18)}
.istat-n{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;color:var(--green);line-height:1;display:block;letter-spacing:1px}
.istat-l{font-family:'Montserrat',sans-serif;font-size:10.5px;font-weight:600;color:rgba(255,255,255,.5);margin-top:5px}

/* impact 2-col */
.impact-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin:20px 0 28px}
.impact-item{display:flex;align-items:center;gap:12px;background:#fff5f5;padding:13px 16px;border-radius:12px;border-left:3px solid var(--red);transition:.2s}
.impact-item:hover{background:#ffe8e8}
.impact-item i{color:var(--red);font-size:13px;flex-shrink:0;width:18px}
.impact-item span{font-size:.875rem;color:var(--t1);font-weight:600}

/* benefits 2-col */
.benefits-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin:20px 0}
.benefit-item{background:#f8faff;padding:16px;border-radius:14px;border-left:4px solid var(--green);transition:.2s}
.benefit-item:hover{background:#e8fdf2}
.benefit-item strong{font-family:'Montserrat',sans-serif;font-size:13px;font-weight:800;color:var(--t1);display:block;margin-bottom:4px}
.benefit-item span{font-size:.8rem;color:var(--t2)}

/* report highlights */
.report-list{list-style:none;margin:12px 0 24px}
.report-list li{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;font-size:.875rem;color:var(--t2);border:1px solid var(--bds);margin-bottom:8px;transition:.2s}
.report-list li:hover{background:#f0f7ff;border-color:#bdd4f0}
.report-list li span{font-size:1.1rem}

/* FAQ */
.faq-item{background:#fff;border-radius:14px;padding:18px 20px;margin-bottom:12px;border:1px solid var(--bds);cursor:pointer;transition:.3s}
.faq-item:hover{border-color:rgba(34,197,94,.3);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.faq-item.active{border-color:var(--green)}
.faq-question{font-weight:700;color:var(--t1);display:flex;justify-content:space-between;align-items:center;font-family:'Montserrat',sans-serif;font-size:.9rem;gap:12px}
.faq-question i{color:var(--t3);transition:transform .3s;flex-shrink:0}
.faq-item.active .faq-question i{transform:rotate(180deg);color:var(--green)}
.faq-answer{display:none;margin-top:12px;color:var(--t2);line-height:1.7;font-size:.855rem}
.faq-item.active .faq-answer{display:block}

/* ============================================================
   PAYMENT CARD — sticky right panel
   ============================================================ */
.payment-card{background:#fff;border-radius:24px;padding:32px;position:sticky;top:96px;box-shadow:0 20px 50px rgba(0,0,0,.1);border:2px solid rgba(34,197,94,.18)}
.payment-card::before{content:'';display:block;height:4px;background:linear-gradient(90deg,var(--green),var(--blue2),var(--gold));border-radius:4px;margin:-32px -32px 28px}
.price-badge{text-align:center;margin-bottom:22px}
.price{font-family:'Bebas Neue',sans-serif;font-size:3.8rem;color:var(--green);line-height:1;letter-spacing:2px}
.price small{font-size:1rem;font-weight:400;color:var(--t3)}
.gst-note{color:var(--t3);font-size:.78rem;margin-top:5px;font-family:'Montserrat',sans-serif}
.save-tag{display:inline-block;background:#e8fdf2;border:1px solid rgba(34,197,94,.3);color:var(--green2);font-family:'Montserrat',sans-serif;font-size:.7rem;font-weight:700;padding:2px 10px;border-radius:20px;margin-top:5px}
.features-list{list-style:none;margin:0 0 22px}
.features-list li{padding:9px 0;display:flex;align-items:center;gap:11px;border-bottom:1px solid #f0f4fa;font-size:.875rem;color:var(--t1)}
.features-list li:last-child{border:none}
.features-list li i{color:var(--green);font-size:14px;flex-shrink:0}
.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:7px;font-weight:700;color:var(--t1);font-family:'Montserrat',sans-serif;font-size:.78rem;letter-spacing:.3px}
.form-group input{width:100%;padding:13px 16px;border:1.5px solid var(--bd);border-radius:12px;font-size:.95rem;transition:.3s;font-family:'Open Sans',sans-serif;color:var(--t1);background:#fafcff}
.form-group input:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(34,197,94,.1);background:#fff}
.pay-btn{width:100%;padding:15px;background:linear-gradient(135deg,var(--green),var(--green2));color:#fff;border:none;border-radius:50px;font-size:.95rem;font-weight:800;cursor:pointer;transition:.3s;margin-top:6px;font-family:'Montserrat',sans-serif;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:9px}
.pay-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 24px rgba(34,197,94,.45)}
.pay-btn:disabled{opacity:.65;cursor:not-allowed}
.secure-badge{text-align:center;margin-top:16px;font-size:.72rem;color:var(--t3);font-family:'Montserrat',sans-serif;font-weight:600}
.secure-badge i{color:var(--green);margin-right:4px}

/* ============================================================
   TRUST DARK SECTION
   ============================================================ */
.trust-sec{background:linear-gradient(135deg,#061020,#0a1a38);padding:80px 0;position:relative;overflow:hidden}
.trust-sec::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(34,197,94,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(34,197,94,.02) 1px,transparent 1px);background-size:40px 40px}
.trust-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;position:relative;z-index:1}
.trust-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:28px 24px;text-align:center;transition:all .3s}
.trust-card:hover{background:rgba(255,255,255,.07);border-color:rgba(34,197,94,.3);transform:translateY(-4px)}
.tci{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px}
.tci.g{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.25)}
.tci.b{background:rgba(76,140,255,.15);border:1px solid rgba(76,140,255,.25)}
.tci.y{background:rgba(245,197,24,.12);border:1px solid rgba(245,197,24,.2)}
.trust-card h4{font-family:'Montserrat',sans-serif;font-size:.95rem;font-weight:800;color:#fff;margin-bottom:8px}
.trust-card p{font-size:.83rem;color:rgba(255,255,255,.5);line-height:1.65}

/* ============================================================
   CTA SECTION
   ============================================================ */
.cta-sec{background:linear-gradient(135deg,#060e1e,#0a1a38,#071428);padding:90px 0;position:relative;overflow:hidden}
.cta-sec::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(34,197,94,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(34,197,94,.025) 1px,transparent 1px);background-size:40px 40px}
.cta-inner{text-align:center;position:relative;z-index:2}
.cta-h{font-family:'Bebas Neue',sans-serif;font-size:clamp(2.5rem,5vw,4.5rem);color:#fff;margin-bottom:16px;letter-spacing:3px;line-height:1}
.cta-h em{font-style:normal;color:var(--gold)}
.cta-p{font-size:1rem;color:rgba(255,255,255,.6);max-width:520px;margin:0 auto 36px;line-height:1.75}
.cta-acts{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.cta-trust{display:flex;justify-content:center;gap:24px;margin-top:28px;flex-wrap:wrap}
.ct{display:flex;align-items:center;gap:6px;font-family:'Montserrat',sans-serif;font-size:12px;font-weight:600;color:rgba(255,255,255,.5)}
.ct i{color:var(--green);font-size:11px}

/* ============================================================
   FOOTER
   ============================================================ */
footer{background:#040c1a;padding:70px 0 28px}
.fg{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:48px}
.f-brand{display:flex;align-items:center;gap:12px;margin-bottom:20px;text-decoration:none}
.f-brand-text{display:flex;flex-direction:column}
.f-brand-name{font-family:'Montserrat',sans-serif;font-size:1.5rem;font-weight:900;line-height:1;color:#fff}
.f-brand-name b{color:var(--green);font-style:italic}
.f-brand-tag{font-size:10px;color:rgba(255,255,255,.45);letter-spacing:.5px;margin-top:4px;font-weight:500}
.f-desc{font-size:.855rem;color:rgba(255,255,255,.35);line-height:1.75;margin-bottom:22px}
.f-soc{display:flex;gap:8px;flex-wrap:wrap}
.fs{width:36px;height:36px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:9px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.4);text-decoration:none;font-size:13px;transition:all .2s}
.fs:hover{background:rgba(34,197,94,.2);border-color:rgba(34,197,94,.4);color:var(--green)}
.f-col h5{font-family:'Montserrat',sans-serif;font-size:11px;font-weight:800;color:rgba(255,255,255,.6);letter-spacing:1px;text-transform:uppercase;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.06)}
.fl-links{list-style:none}
.fl-links li{margin-bottom:9px}
.fl-links a{font-size:13px;color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s}
.fl-links a:hover{color:var(--green)}
.f-ci{display:flex;align-items:center;gap:9px;font-size:12.5px;color:rgba(255,255,255,.35);margin-bottom:9px}
.f-ci i{color:var(--green);font-size:11px;width:14px;flex-shrink:0}
.f-ci a{color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s}
.f-ci a:hover{color:var(--green)}
.f-badges{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;padding:22px 0;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);margin-bottom:22px}
.fbg{display:inline-flex;align-items:center;gap:6px;font-family:'Montserrat',sans-serif;font-size:11px;font-weight:600;color:rgba(255,255,255,.35);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);padding:5px 13px;border-radius:20px}
.fbg-link{display:inline-flex;align-items:center;gap:6px;font-family:'Montserrat',sans-serif;font-size:11px;font-weight:600;color:rgba(255,255,255,.35);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);padding:5px 13px;border-radius:20px;cursor:pointer;text-decoration:none;transition:all .3s}
.fbg-link:hover{background:rgba(34,197,94,.2);border-color:rgba(34,197,94,.4);color:var(--green)}
.f-bot{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.f-leg{display:flex;gap:20px;flex-wrap:wrap}
.f-leg a{font-family:'Montserrat',sans-serif;font-size:11.5px;color:rgba(255,255,255,.25);text-decoration:none;transition:color .2s}
.f-leg a:hover{color:var(--green)}
.f-copy{font-family:'Montserrat',sans-serif;font-size:11.5px;color:rgba(255,255,255,.25)}

.wa{position:fixed;bottom:28px;left:28px;width:58px;height:58px;background:#25D366;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;text-decoration:none;box-shadow:0 8px 24px rgba(37,211,102,.5);z-index:9999;transition:all .3s}
.wa:hover{transform:scale(1.1)}

/* ============================================================
   MODALS
   ============================================================ */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(4,12,26,.9);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal-box{background:#fff;border-top:4px solid var(--green);border-radius:20px;padding:48px 42px;width:90%;max-width:460px;position:relative;box-shadow:0 32px 80px rgba(0,0,0,.4)}
.mc{position:absolute;top:16px;right:16px;background:none;border:none;color:var(--t3);font-size:22px;cursor:pointer}
.mc:hover{color:var(--t1)}
.m-ico{font-size:2.6rem;margin-bottom:14px;display:block;text-align:center}
.m-h{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;text-align:center;margin-bottom:8px;color:var(--t1);letter-spacing:2px}
.m-s{font-size:.875rem;color:var(--t2);text-align:center;margin-bottom:26px;line-height:1.65}
.fi{width:100%;background:#f8faff;border:2px solid var(--bds);border-radius:10px;padding:12px 16px;color:var(--t1);font-size:14px;font-family:'Open Sans',sans-serif;outline:none;margin-bottom:12px;transition:border-color .2s}
.fi:focus{border-color:var(--green);background:#fff}
.btn-submit{width:100%;padding:15px;background:linear-gradient(135deg,var(--green),var(--green2));color:#fff;border:none;border-radius:50px;font-family:'Montserrat',sans-serif;font-size:15px;font-weight:800;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:9px}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(34,197,94,.4)}
.m-disc{font-size:11px;color:var(--t3);text-align:center;margin-top:14px;font-family:'Montserrat',sans-serif}

/* Payment result modal */
.result-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);justify-content:center;align-items:center;z-index:10000}
.result-modal.active{display:flex}
.result-modal-content{background:#fff;border-radius:24px;padding:44px 36px;max-width:400px;width:90%;text-align:center;animation:fadeUp .3s ease;border-top:4px solid var(--green)}
@keyframes fadeUp{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.loading{display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,.5);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;margin-right:7px}
@keyframes spin{to{transform:rotate(360deg)}}
.result-modal-content h3{font-family:'Montserrat',sans-serif;font-size:1.4rem;font-weight:800;margin:14px 0 8px}
.result-modal-content p{color:var(--t2);font-size:.875rem;line-height:1.6;margin-bottom:8px}
.result-modal-content .txn{font-size:.75rem;color:var(--t3);margin-top:6px}
.result-modal-content button{margin-top:20px;padding:11px 28px;background:var(--green);color:#fff;border:none;border-radius:50px;cursor:pointer;font-family:'Montserrat',sans-serif;font-weight:700;font-size:.9rem;transition:.2s}
.result-modal-content button:hover{background:var(--green2)}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media(max-width:1100px){
  .service-layout{grid-template-columns:1fr}
  .payment-card{position:static}
  .check-grid{grid-template-columns:repeat(2,1fr)}
  .steps-grid{grid-template-columns:repeat(3,1fr)}
  .steps-grid::before{display:none}
  .trust-grid{grid-template-columns:repeat(2,1fr)}
  .fg{grid-template-columns:1fr 1fr;gap:32px}
  .inline-stats{grid-template-columns:1fr 1fr}
}
@media(max-width:768px){
  nav{padding:0 20px}
  .ham{display:flex}.nav-links,.nav-right{display:none}
  .W,.bc-inner,.sb-grid{padding:0 20px}
  .ph-inner{grid-template-columns:1fr;gap:30px;padding:0 20px;text-align:center}
  .ph-sub{margin:0 auto 28px}
  .ph-acts{justify-content:center}
  .ph-trust{justify-content:center}
  .ph-card{display:none}
  .page-hero{padding-top:90px;padding-bottom:50px}
  .ph-h1{font-size:2.8rem}
  .sb-grid{grid-template-columns:repeat(2,1fr)}
  .sb-item:nth-child(2n){border-right:none}
  .check-grid,.steps-grid,.trust-grid{grid-template-columns:1fr}
  .inline-stats{grid-template-columns:1fr}
  .benefits-grid,.impact-grid{grid-template-columns:1fr}
  .fg{grid-template-columns:1fr}
  .f-bot{flex-direction:column;align-items:center;text-align:center}
  .f-leg{justify-content:center;gap:10px}
  .cta-acts{flex-direction:column;align-items:center}
  .modal-box{padding:32px 22px}
}
@media(min-width:769px){.ham{display:none}}
</style>
</head>
<body>

<div class="prog"><div class="prog-f" id="pf"></div></div>

<!-- ============================================================
     NAV
     ============================================================ -->
<nav id="nav">
  <a href="../index.html" class="nav-logo">
    <svg width="52" height="56" viewBox="0 0 52 56" fill="none" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="shG" x1="26" y1="2" x2="26" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#1a3a7c"/><stop offset="1" stop-color="#071428"/></linearGradient>
        <linearGradient id="shS" x1="4" y1="2" x2="48" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#4a8aff"/><stop offset="1" stop-color="#1a4a9c"/></linearGradient>
      </defs>
      <path d="M26 2 L48 12 L48 30 C48 43 38 52 26 54 C14 52 4 43 4 30 L4 12 Z" fill="url(#shG)" stroke="url(#shS)" stroke-width="1.5"/>
      <rect x="11" y="36" width="5" height="10" rx="1.5" fill="#ef4444"/>
      <rect x="18" y="30" width="5" height="16" rx="1.5" fill="#f97316"/>
      <rect x="25" y="24" width="5" height="22" rx="1.5" fill="#f5c518"/>
      <rect x="32" y="18" width="5" height="28" rx="1.5" fill="#22c55e"/>
      <path d="M38 18 L42 10 L46 18" stroke="#22c55e" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
      <line x1="42" y1="10" x2="42" y2="22" stroke="#22c55e" stroke-width="2" stroke-linecap="round"/>
      <path d="M26 4 L46 13 L46 30 C46 41.5 37.5 50.2 26 52" stroke="rgba(255,255,255,.18)" stroke-width="1" fill="none"/>
    </svg>
    <div class="logo-text-wrap">
      <div class="logo-name">CIBIL<span>Repair</span></div>
      <div class="logo-tag">Better Credit. Better Future.</div>
    </div>
  </a>

  <ul class="nav-links">
    <li class="nl-item"><a href="../index.html">Home</a></li>
    <li class="nl-item">
      <a href="../services.html">Services <span class="chev">▼</span></a>
      <div class="dd dd-wide">
        <div class="dd-head" style="grid-column:1/-1">What We Fix For You</div>
        <div class="dd-col">
          <a href="/service/written-off.php"><i class="fas fa-file-alt"></i><div><strong>Complete Removal</strong><span>Removal from Credit bureaus</span></div></a>
          <a href="/service/settled.php"><i class="fas fa-handshake"></i><div><strong>Settled → Closed</strong><span>Convert settled → closed</span></div></a>
          <a href="/service/suit-filled.php"><i class="fas fa-gavel"></i><div><strong>Legal Removal</strong><span>Legal entry removal</span></div></a>
        </div>
        <div class="dd-col">
          <a href="/service/analysis.php"><i class="fas fa-chart-bar"></i><div><strong>Forensic Audit</strong><span>Credit bureau forensic audit</span></div></a>
          <a href="/service/profile.php" style="color:var(--blue2);background:#f0f7ff"><i class="fas fa-user-edit"></i><div><strong>Quick Fix</strong><span>Fix name, PAN, address errors</span></div></a>
          <a href="/service/wrong-entry.php"><i class="fas fa-times-circle"></i><div><strong>Fraud Protection</strong><span>Fraud &amp; identity theft disputes</span></div></a>
        </div>
      </div>
    </li>
    <li class="nl-item">
      <button>Tools <span class="chev">▼</span></button>
      <div class="dd">
        <a href="../index.html#simulator"><i class="fas fa-sliders-h"></i><div><strong>Score Simulator</strong><span>See your potential improvement</span></div></a>
        <div class="dd-div"></div>
        <a href="../index.html#calculator"><i class="fas fa-university"></i><div><strong>Loan Eligibility</strong><span>See how much loan you qualify</span></div></a>
        <a href="../index.html#emi"><i class="fas fa-calculator"></i><div><strong>EMI Calculator</strong><span>Plan your monthly payments</span></div></a>
      </div>
    </li>
    <li class="nl-item">
      <button>Company <span class="chev">▼</span></button>
      <div class="dd">
        <a href="../about-us.html"><i class="fas fa-building"></i><div><strong>About Us</strong><span>Our story since 2018</span></div></a>
        <a href="../index.html#team"><i class="fas fa-users"></i><div><strong>Our Team</strong><span>Meet the experts</span></div></a>
        <div class="dd-div"></div>
        <a href="../success-stories.html"><i class="fas fa-star"></i><div><strong>Success Stories</strong><span>5,000+ transformed lives</span></div></a>
      </div>
    </li>
    <li class="nl-item">
      <button>Resources <span class="chev">▼</span></button>
      <div class="dd">
        <a href="../index.html#faq"><i class="fas fa-question-circle"></i><div><strong>FAQ</strong><span>Common credit repair questions</span></div></a>
        <a href="../index.html#pricing"><i class="fas fa-tag"></i><div><strong>Pricing</strong><span>Transparent plans</span></div></a>
        <div class="dd-div"></div>
        <a href="../blog.php"><i class="fas fa-book"></i><div><strong>Blog</strong><span>Credit tips &amp; guides</span></div></a>
      </div>
    </li>
    <li class="nl-item"><a href="../contact.html">Contact</a></li>
  </ul>

  <div class="nav-right">
    <a href="../login.html" class="btn-login">Login</a>
    <a href="../partners.html" class="btn-partner">🤝 Partner With Us</a>
    <button class="btn-getstarted" onclick="openModal()">Get Started</button>
  </div>
  <button class="ham" id="ham" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<!-- Mobile nav - Modern Right Slide Menu -->
<div class="mob" id="mob">
    <div class="mob-header">
        <div class="logo">CIBIL<span>Repair</span></div>
        <div class="tagline">Better Credit. Better Future.</div>
    </div>
    <div class="mob-user">
        <div class="mob-user-icon"><i class="fas fa-user-shield"></i></div>
        <div class="mob-user-info"><h4>Welcome Back!</h4><p>Let's fix your credit score</p></div>
    </div>
    <div class="mob-nav-item"><a href="../index.html" class="mob-nav-link" onclick="cm()"><div class="link-left"><i class="fas fa-home"></i><span>Home</span></div></a></div>
    
    <div class="mob-nav-item mob-dropdown" id="mobServices">
        <div class="mob-nav-link" onclick="toggleMobSubmenu(this)"><div class="link-left"><i class="fas fa-concierge-bell"></i><span>Services</span></div><i class="fas fa-chevron-right chevron"></i></div>
        <div class="mob-submenu">
            <a href="../service/written-off.php" onclick="cm()"><i class="fas fa-file-alt"></i> Complete Removal</a>
            <a href="../service/settled.php" onclick="cm()"><i class="fas fa-handshake"></i> Settled → Closed</a>
            <a href="../service/suit-filled.php" onclick="cm()"><i class="fas fa-gavel"></i> Legal Removal</a>
            <a href="../service/analysis.php" onclick="cm()"><i class="fas fa-chart-bar"></i> Forensic Audit</a>
            <a href="../service/profile.php" onclick="cm()"><i class="fas fa-user-edit"></i> Quick Fix</a>
            <a href="../service/wrong-entry.php" onclick="cm()"><i class="fas fa-times-circle"></i> Fraud Protection</a>
        </div>
    </div>
    
    <div class="mob-nav-item mob-dropdown" id="mobTools">
        <div class="mob-nav-link" onclick="toggleMobSubmenu(this)"><div class="link-left"><i class="fas fa-tools"></i><span>Tools</span></div><i class="fas fa-chevron-right chevron"></i></div>
        <div class="mob-submenu">
            <a href="../index.html#simulator" onclick="cm()"><i class="fas fa-sliders-h"></i> Score Simulator</a>
            <a href="../index.html#calculator" onclick="cm()"><i class="fas fa-university"></i> Loan Eligibility</a>
            <a href="../index.html#emi" onclick="cm()"><i class="fas fa-calculator"></i> EMI Calculator</a>
        </div>
    </div>
    
    <div class="mob-nav-item mob-dropdown" id="mobCompany">
        <div class="mob-nav-link" onclick="toggleMobSubmenu(this)"><div class="link-left"><i class="fas fa-building"></i><span>Company</span></div><i class="fas fa-chevron-right chevron"></i></div>
        <div class="mob-submenu">
            <a href="../about-us.html" onclick="cm()"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="../index.html#team" onclick="cm()"><i class="fas fa-users"></i> Our Team</a>
            <a href="../success-stories.html" onclick="cm()"><i class="fas fa-star"></i> Success Stories</a>
        </div>
    </div>
    
    <div class="mob-nav-item mob-dropdown" id="mobResources">
        <div class="mob-nav-link" onclick="toggleMobSubmenu(this)"><div class="link-left"><i class="fas fa-book"></i><span>Resources</span></div><i class="fas fa-chevron-right chevron"></i></div>
        <div class="mob-submenu">
            <a href="../index.html#faq" onclick="cm()"><i class="fas fa-question-circle"></i> FAQ</a>
            <a href="../index.html#pricing" onclick="cm()"><i class="fas fa-tag"></i> Pricing</a>
            <a href="../blog.php" onclick="cm()"><i class="fas fa-newspaper"></i> Blog</a>
        </div>
    </div>
    
    <!-- Contact - Direct Link -->
    <div class="mob-nav-item">
        <a href="../contact.html" class="mob-nav-link" onclick="cm()">
            <div class="link-left">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </div>
        </a>
    </div>
    
    <!-- Login Button -->
    <div class="mob-nav-item">
        <a href="../login.html" class="mob-nav-link" onclick="cm()">
            <div class="link-left">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </div>
        </a>
    </div>
    
    <div class="mob-divider"></div>
    
    <!-- Action Buttons -->
    <div class="mob-actions">
        <a href="../partners.html" class="mob-action-btn partner" onclick="cm()">
            <i class="fas fa-handshake"></i> Partner With Us
        </a>
        <a href="#"class="mob-action-btn get-started" onclick="openModal();cm()">
            <i class="fas fa-rocket"></i> Get Started Free
        </a>
    </div>
</div>
<div class="menu-overlay" id="menuOverlay"></div>

<!-- ============================================================
     PAGE HERO
     ============================================================ -->
<section class="page-hero">
  <div class="hero-orb orb1"></div>
  <div class="hero-orb orb2"></div>
  <div class="ph-inner">
    <div>
      <div class="ph-badge r"><div class="ph-dot"></div> Most Popular — All 4 Bureaus</div>
      <h1 class="ph-h1 r">
        <span class="w">COMPREHENSIVE</span>
        <span class="g">CREDIT</span>
        <span class="y">REPAIR</span>
      </h1>
      <p class="ph-sub r">Remove wrong entries, written-off accounts, and settled accounts across all 4 bureaus. Includes a full forensic audit (₹5,999 value), a dedicated case manager, and bank introduction post-fix. The most comprehensive credit repair plan in India.</p>
      <div class="ph-acts r">
        <button class="btn-primary" onclick="document.getElementById('fullName').focus();document.getElementById('fullName').scrollIntoView({behavior:'smooth',block:'center'})">
          <i class="fas fa-rocket"></i> Start Your Repair Now
        </button>
        <a href="#what-we-fix" class="btn-ghost"><i class="fas fa-arrow-down"></i> See What We Fix</a>
      </div>
      <div class="ph-trust r">
        <div class="pht"><span class="pht-n">98%</span><div class="pht-l">Success Rate</div></div>
        <div class="pht"><span class="pht-n">60–90</span><div class="pht-l">Day Resolution</div></div>
        <div class="pht"><span class="pht-n">+120 pts</span><div class="pht-l">Max Score Gain</div></div>
        <div class="pht"><span class="pht-n">4</span><div class="pht-l">Bureaus Updated</div></div>
      </div>
    </div>

    <!-- Right: what's included card -->
    <div class="ph-card r">
      <div class="ph-card-header">
        <span class="ph-card-title">What's Included</span>
        <div class="ph-live">⭐ Most Popular</div>
      </div>
      <div class="milestones">
        <div class="ms"><div class="ms-icon y">🔄</div><div class="ms-text"><div class="ms-title">Wrong Entry Removal</div><div class="ms-sub">Unauthorized loans &amp; duplicate accounts</div></div><div class="ms-val y">Removed</div></div>
        <div class="ms"><div class="ms-icon g">📊</div><div class="ms-text"><div class="ms-title">Forensic Audit Included</div><div class="ms-sub">₹5,999 value — full bureau analysis</div></div><div class="ms-val g">Included</div></div>
        <div class="ms"><div class="ms-icon b">💼</div><div class="ms-text"><div class="ms-title">Dedicated Case Manager</div><div class="ms-sub">Single point of contact throughout</div></div><div class="ms-val b">Assigned</div></div>
        <div class="ms"><div class="ms-icon g">🏦</div><div class="ms-text"><div class="ms-title">Bank Introduction</div><div class="ms-sub">Post-fix lender liaison support</div></div><div class="ms-val g">Covered</div></div>
      </div>
    </div>
  </div>
</section>

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
  <div class="bc-inner">
    <a href="../index.html"><i class="fas fa-home" style="font-size:11px"></i> Home</a>
    <span>›</span>
    <a href="../services.html">Services</a>
    <span>›</span>
    <span class="current">Professional — Comprehensive Credit Repair</span>
  </div>
</div>

<!-- STATS BAND -->
<div class="stats-band">
  <div class="sb-grid">
    <div class="sb-item r"><span class="sb-num gold" data-target="70" data-s="%">0</span><div class="sb-desc">Reports Have Wrong Entries</div></div>
    <div class="sb-item r"><span class="sb-num green" data-target="120" data-s=" pts">0</span><div class="sb-desc">Max Score Improvement</div></div>
    <div class="sb-item r"><span class="sb-num blue" data-target="45" data-s=" Days">0</span><div class="sb-desc">Fastest Resolution</div></div>
    <div class="sb-item r"><span class="sb-num gold" data-target="98" data-s="%">0</span><div class="sb-desc">Industry-Best Success Rate</div></div>
  </div>
</div>

<!-- ============================================================
     PROCESS STEPS — dark section
     ============================================================ -->
<section class="process-sec">
  <div class="W">
    <div class="sh r">
      <div class="pill pill-dark">🔧 How It Works</div>
      <h2 class="st" style="color:#fff">Our <em>Comprehensive Process</em></h2>
      <p class="ss" style="color:rgba(255,255,255,.5)">Six expert-led steps from full bureau audit to confirmed resolution with bank introduction support.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card r">
        <div class="step-num-wrap"><i class="fas fa-search"></i></div>
        <div class="step-title">Full Bureau Audit</div>
        <p class="step-desc">We pull your reports from all 4 bureaus and identify every wrong entry, written-off, and settled account.</p>
      </div>
      <div class="step-card r">
        <div class="step-num-wrap" style="background:linear-gradient(135deg,var(--blue),var(--blue2))"><i class="fas fa-folder-open"></i></div>
        <div class="step-title">Collect Evidence</div>
        <p class="step-desc">We help you gather proof — bank statements, settlement deeds, NOCs — establishing each entry is incorrect.</p>
      </div>
      <div class="step-card r">
        <div class="step-num-wrap" style="background:linear-gradient(135deg,#d97706,var(--gold2))"><i class="fas fa-pen-alt"></i></div>
        <div class="step-title">Draft Legal Disputes</div>
        <p class="step-desc">Bureau-specific legal notices drafted citing RBI guidelines and the Credit Information Companies Act 2005.</p>
      </div>
      <div class="step-card r">
        <div class="step-num-wrap"><i class="fas fa-paper-plane"></i></div>
        <div class="step-title">File with All 4 Bureaus</div>
        <p class="step-desc">Disputes filed simultaneously with CIBIL, Equifax, Experian &amp; CRIF and the reporting lenders, with daily follow-up.</p>
      </div>
      <div class="step-card r">
        <div class="step-num-wrap" style="background:linear-gradient(135deg,#dc2626,#b91c1c)"><i class="fas fa-balance-scale"></i></div>
        <div class="step-title">Escalate if Needed</div>
        <p class="step-desc">Banking Ombudsman and RBI Consumer Protection filing if bureau or lender refuses to correct the entries.</p>
      </div>
      <div class="step-card r">
        <div class="step-num-wrap" style="background:linear-gradient(135deg,#7c3aed,#9333ea)"><i class="fas fa-check-double"></i></div>
        <div class="step-title">Confirm &amp; Deliver</div>
        <p class="step-desc">Entries removed or corrected, updated reports confirmed across all bureaus, and bank introduction support provided.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     WHAT WE FIX — check grid
     ============================================================ -->
<section class="sec" id="what-we-fix">
  <div class="W">
    <div class="sh r">
      <div class="pill pill-green">✅ Service Scope</div>
      <h2 class="st">What We <em>Fix</em></h2>
      <p class="ss">Every type of negative entry handled with a tailored legal strategy — nothing is left unresolved.</p>
    </div>
    <div class="check-grid" style="margin-bottom:80px">
      <div class="ck r">
        <div class="ck-icon">🚫</div>
        <div class="ck-name">Wrong Entry Removal</div>
        <p class="ck-desc">Unauthorized loans, duplicate accounts, and incorrect late payment remarks — all removed across all 4 bureaus.</p>
      </div>
      <div class="ck r">
        <div class="ck-icon">📄</div>
        <div class="ck-name">Written-Off Clearance</div>
        <p class="ck-desc">Complete removal of written-off accounts from your credit report. We legally dispute and validate every entry.</p>
      </div>
      <div class="ck r">
        <div class="ck-icon">🤝</div>
        <div class="ck-name">Settled → Closed</div>
        <p class="ck-desc">Convert "Settled" status to "Closed" across all 4 bureaus using legal dispute filings and direct lender negotiation.</p>
      </div>
      <div class="ck r">
        <div class="ck-icon">📊</div>
        <div class="ck-name">Forensic Audit</div>
        <p class="ck-desc">50-point expert audit across all bureaus included — identifying hidden errors and fraud entries you may have missed.</p>
      </div>
      <div class="ck r">
        <div class="ck-icon">🛡️</div>
        <div class="ck-name">Identity Theft Protection</div>
        <p class="ck-desc">Police complaint support and fraud removal assistance for unauthorized loans opened in your name.</p>
      </div>
      <div class="ck r">
        <div class="ck-icon">🏦</div>
        <div class="ck-name">Bank Introduction</div>
        <p class="ck-desc">Post-resolution introduction to partner banks and NBFCs to help you secure loans after your report is cleaned.</p>
      </div>
    </div>

    <!-- ============================================================
         2-COLUMN: CONTENT + PAYMENT CARD
         ============================================================ -->
    <div class="service-layout">
      <div class="service-content">

        <h2>📖 What is Comprehensive Credit Repair?</h2>
        <p>The Professional Plan is our flagship service — a complete, end-to-end credit repair solution that removes wrong entries, written-off accounts, and settled accounts across all four credit bureaus. Unlike single-fix services, this plan tackles every type of negative entry simultaneously and includes a full forensic audit (₹5,999 value) to identify errors you didn't even know existed.</p>
        <p>With a dedicated case manager and bank introduction support, this is the most comprehensive credit repair plan available in India today.</p>

        <div class="inline-stats">
          <div class="istat r"><span class="istat-n">70%</span><div class="istat-l">Reports have wrong entries</div></div>
          <div class="istat r"><span class="istat-n">+120 pts</span><div class="istat-l">Max score gain after fix</div></div>
          <div class="istat r"><span class="istat-n">45 Days</span><div class="istat-l">Fastest full resolution</div></div>
        </div>

        <h2>🔍 What We Remove</h2>
        <div class="impact-grid">
          <div class="impact-item"><i class="fas fa-user-slash"></i><span>Wrong entries &amp; duplicate accounts</span></div>
          <div class="impact-item"><i class="fas fa-file-alt"></i><span>Written-off accounts</span></div>
          <div class="impact-item"><i class="fas fa-handshake"></i><span>Settled accounts → Closed</span></div>
          <div class="impact-item"><i class="fas fa-shield-alt"></i><span>Identity theft &amp; fraud entries</span></div>
          <div class="impact-item"><i class="fas fa-calendar-alt"></i><span>Incorrect late payment remarks</span></div>
          <div class="impact-item"><i class="fas fa-id-card"></i><span>Wrong personal information</span></div>
        </div>

        <h2>🎯 What You Receive</h2>
        <p>Our Professional Plan is a complete end-to-end credit repair process:</p>
        <ul class="report-list">
          <li><span>🔍</span> Full 4-bureau forensic audit (₹5,999 value)</li>
          <li><span>✅</span> Wrong entries, written-off &amp; settled accounts removed</li>
          <li><span>📝</span> Legal dispute notices filed on your behalf</li>
          <li><span>🛡️</span> Identity theft FIR support</li>
          <li><span>🏦</span> Bank negotiation &amp; direct escalation</li>
          <li><span>⚖️</span> Banking Ombudsman &amp; RBI filing</li>
          <li><span>📋</span> Updated credit reports confirming all corrections</li>
          <li><span>📞</span> Dedicated case manager for 90 days</li>
          <li><span>🏦</span> Bank introduction post-fix</li>
          <li><span>💰</span> 100% money-back guarantee</li>
        </ul>

        <h2>✅ Benefits of the Professional Plan</h2>
        <div class="benefits-grid">
          <div class="benefit-item"><strong>📈 Massive Score Boost</strong><span>Up to 120 points increase once all negative entries are removed</span></div>
          <div class="benefit-item"><strong>🏦 Unlock Loan Approvals</strong><span>Banks that previously rejected you will now consider your application</span></div>
          <div class="benefit-item"><strong>⚖️ Fully Legal</strong><span>Entire process under RBI guidelines &amp; CIC Act 2005 — zero risk</span></div>
          <div class="benefit-item"><strong>💰 No-Win-No-Fee</strong><span>Full refund if removal cannot be achieved</span></div>
          <div class="benefit-item"><strong>📉 Lower Interest Rates</strong><span>A clean report helps you qualify for more competitive rates</span></div>
          <div class="benefit-item"><strong>🏠 Home Loan Eligibility</strong><span>Major banks approve home loans only after negative entries are removed</span></div>
        </div>

        <h2>❓ Frequently Asked Questions</h2>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">What types of entries can you remove? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">Wrong entries (unauthorized loans, duplicates), written-off accounts, settled accounts, incorrect late payment remarks, identity theft entries, and wrong personal information — all across all 4 bureaus simultaneously.</div>
        </div>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">How long does the comprehensive process take? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">Most cases are resolved within 60–90 working days. This allows us to handle multiple entry types and all four bureaus simultaneously, with daily follow-up and escalations as needed.</div>
        </div>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">Do I get a dedicated case manager? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">Yes. Every Professional Plan client is assigned a dedicated case manager who serves as your single point of contact throughout the process. They handle all disputes, follow-ups, and escalations on your behalf.</div>
        </div>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">What is the forensic audit? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">A 50-point expert audit across all 4 bureaus, worth ₹5,999, included free with the Professional Plan. It identifies hidden errors, fraud entries, and duplicate accounts you may have missed.</div>
        </div>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">What is bank introduction post-fix? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">After your report is cleaned, we introduce you to partner banks and NBFCs to help you secure loans, credit cards, and other financial products with your improved credit profile.</div>
        </div>
        <div class="faq-item" onclick="this.classList.toggle('active')">
          <div class="faq-question">Is my data safe with CIBIL Repair? <i class="fas fa-chevron-down"></i></div>
          <div class="faq-answer">Yes. All data is encrypted with 256-bit SSL and we are fully compliant with the IT Act 2000 and DPDP Act 2023. Your documents are never shared with third parties.</div>
        </div>

      </div>

      <!-- PAYMENT CARD -->
      <div>
        <div class="payment-card r">
          <div class="price-badge">
            <div class="price">₹10,999 <small>+ GST</small></div>
            <div class="gst-note">18% GST applicable — total ₹12,979</div>
            <span class="save-tag">⚡ Includes Forensic Audit (₹5,999 Value)</span>
          </div>

          <ul class="features-list">
            <li><i class="fas fa-check-circle"></i> Full 4-bureau forensic audit</li>
            <li><i class="fas fa-check-circle"></i> Wrong entry removal</li>
            <li><i class="fas fa-check-circle"></i> Written-off clearance</li>
            <li><i class="fas fa-check-circle"></i> Settled → Closed conversion</li>
            <li><i class="fas fa-check-circle"></i> Identity theft protection</li>
            <li><i class="fas fa-check-circle"></i> Legal dispute filing</li>
            <li><i class="fas fa-check-circle"></i> Banking Ombudsman escalation</li>
            <li><i class="fas fa-check-circle"></i> Dedicated case manager</li>
            <li><i class="fas fa-check-circle"></i> Bank introduction post-fix</li>
            <li><i class="fas fa-check-circle"></i> Email &amp; WhatsApp support for 90 days</li>
            <li><i class="fas fa-check-circle"></i> 100% money-back guarantee*</li>
          </ul>

          <form id="paymentForm">
            <input type="hidden" id="csrf_token" value="">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" id="fullName" required placeholder="Enter your full name">
            </div>
            <div class="form-group">
              <label>Email Address *</label>
              <input type="email" id="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
              <label>Phone Number *</label>
              <input type="tel" id="phone" required placeholder="10-digit mobile number">
            </div>
            <button type="submit" class="pay-btn" id="payBtn">
              <i class="fas fa-lock"></i> Pay ₹10,999 + GST &amp; Start Repair
            </button>
          </form>

          <div class="secure-badge"><i class="fas fa-shield-alt"></i> Razorpay Secured · 256-bit SSL Encrypted</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     TRUST DARK SECTION
     ============================================================ -->
<section class="trust-sec">
  <div class="W">
    <div class="sh r">
      <div class="pill pill-dark">🔒 Why Trust Us</div>
      <h2 class="st" style="color:#fff">Built on <em>Proof</em>, Not Promises</h2>
      <p class="ss" style="color:rgba(255,255,255,.5)">Every commitment we make is backed by verified results, legal compliance, and real client outcomes.</p>
    </div>
    <div class="trust-grid">
      <div class="trust-card r"><div class="tci g">⚖️</div><h4>RBI &amp; CIC Act Compliant</h4><p>All dispute processes strictly follow RBI guidelines and the Credit Information Companies (Regulation) Act, 2005.</p></div>
      <div class="trust-card r"><div class="tci b">🔒</div><h4>256-bit SSL Encryption</h4><p>Bank-grade security at every step. Your documents and personal data are always encrypted and protected.</p></div>
      <div class="trust-card r"><div class="tci g">🏦</div><h4>All 4 Bureaus Updated</h4><p>We file disputes simultaneously with CIBIL, Equifax, Experian, and CRIF — no bureau is skipped.</p></div>
      <div class="trust-card r"><div class="tci y">💰</div><h4>Money-Back Guarantee</h4><p>If we cannot remove your negative entries, you receive a full refund — no questions asked.</p></div>
      <div class="trust-card r"><div class="tci y">⭐</div><h4>4.9★ Google Rating</h4><p>Verified reviews from 5,000+ real clients across India — the most trusted credit repair brand in the country.</p></div>
      <div class="trust-card r"><div class="tci b">📋</div><h4>IT Act &amp; DPDP Compliant</h4><p>Your data privacy is fully protected under the Information Technology Act 2000 and DPDP Act 2023.</p></div>
    </div>
  </div>
</section>

<!-- ============================================================
     CTA SECTION
     ============================================================ -->
<section class="cta-sec">
  <div class="W">
    <div class="cta-inner r">
      <h2 class="cta-h">Ready for Complete <em>Credit Repair?</em></h2>
      <p class="cta-p">Talk to our credit repair expert before you pay anything. Free guidance, no obligation — real answers about your specific case.</p>
      <div class="cta-acts">
        <button class="btn-primary" onclick="openModal()"><i class="fas fa-rocket"></i> Get Free Consultation</button>
        <a href="https://wa.me/919905482503?text=Hi%2C%20I%20need%20help%20with%20professional%20credit%20repair%20on%20my%20CIBIL%20report" class="btn-ghost" target="_blank"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
        <a href="tel:+919905482503" class="btn-ghost"><i class="fas fa-phone"></i> Call Now</a>
      </div>
      <div class="cta-trust">
        <div class="ct"><i class="fas fa-check-circle"></i> Free initial consultation</div>
        <div class="ct"><i class="fas fa-check-circle"></i> No hidden charges</div>
        <div class="ct"><i class="fas fa-check-circle"></i> Money-back guarantee</div>
        <div class="ct"><i class="fas fa-check-circle"></i> Dedicated case manager</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer>
  <div class="W">
    <div class="fg">
      <div>
        <a href="../index.html" class="f-brand">
          <svg width="42" height="46" viewBox="0 0 52 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="fshG" x1="26" y1="2" x2="26" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#1a3a7c"/><stop offset="1" stop-color="#071428"/></linearGradient>
              <linearGradient id="fshS" x1="4" y1="2" x2="48" y2="54" gradientUnits="userSpaceOnUse"><stop stop-color="#4a8aff"/><stop offset="1" stop-color="#1a4a9c"/></linearGradient>
            </defs>
            <path d="M26 2 L48 12 L48 30 C48 43 38 52 26 54 C14 52 4 43 4 30 L4 12 Z" fill="url(#fshG)" stroke="url(#fshS)" stroke-width="1.5"/>
            <rect x="11" y="36" width="5" height="10" rx="1.5" fill="#ef4444"/>
            <rect x="18" y="30" width="5" height="16" rx="1.5" fill="#f97316"/>
            <rect x="25" y="24" width="5" height="22" rx="1.5" fill="#f5c518"/>
            <rect x="32" y="18" width="5" height="28" rx="1.5" fill="#22c55e"/>
            <path d="M38 18 L42 10 L46 18" stroke="#22c55e" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="42" y1="10" x2="42" y2="22" stroke="#22c55e" stroke-width="2" stroke-linecap="round"/>
            <path d="M26 4 L46 13 L46 30 C46 41.5 37.5 50.2 26 52" stroke="rgba(255,255,255,.18)" stroke-width="1" fill="none"/>
          </svg>
          <div class="f-brand-text"><div class="f-brand-name">CIBIL<b>Repair</b></div><div class="f-brand-tag">Better Credit. Better Future.</div></div>
        </a>
        <p class="f-desc">India's most trusted credit repair consultancy. 5,000+ Indians have fixed their CIBIL scores legally since 2018. Better Credit. Better Future.</p>
        <div class="f-soc">
          <a href="https://www.facebook.com/cibilrepair/" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-facebook-f"></i></a>
          <a href="https://www.instagram.com/cibilrepair1" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-instagram"></i></a>
          <a href="https://twitter.com/cibilrepair0" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-twitter"></i></a>
          <a href="https://www.linkedin.com/company/cibil-repair" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-linkedin-in"></i></a>
          <a href="https://www.youtube.com/channel/UCG5yi-vJkUPb2OJESSKf8Kg" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-youtube"></i></a>
          <a href="https://wa.me/919905482503" target="_blank" rel="noopener noreferrer" class="fs"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
      <div class="f-col">
        <h5>Services</h5>
        <ul class="fl-links">
          <li><a href="/service/written-off.php">Complete Removal</a></li>
          <li><a href="/service/settled.php">Settled → Closed</a></li>
          <li><a href="/service/suit-filled.php">Legal Removal</a></li>
          <li><a href="/service/analysis.php">Forensic Audit</a></li>
          <li><a href="/service/profile.php">Quick Fix</a></li>
          <li><a href="/service/wrong-entry.php">Fraud Protection</a></li>
        </ul>
      </div>
      <div class="f-col">
        <h5>Company</h5>
        <ul class="fl-links">
          <li><a href="../about-us.html">About Us</a></li>
          <li><a href="../index.html#team">Our Team</a></li>
          <li><a href="../success-stories.html">Success Stories</a></li>
          <li><a href="../partners.html">Partner With Us</a></li>
          <li><a href="../blog.php">Blog</a></li>
          <li><a href="../careers.html">Careers</a></li>
        </ul>
      </div>
      <div class="f-col">
        <h5>Contact</h5>
        <div class="f-ci"><i class="fas fa-envelope"></i><a href="mailto:contact@cibilrepair.in">contact@cibilrepair.in</a></div>
        <div class="f-ci"><i class="fas fa-phone"></i><a href="tel:+919905482503">+91 99054 82503</a></div>
        <div class="f-ci"><i class="fas fa-map-marker-alt"></i>Delhi NCR, India</div>
        <div class="f-ci"><i class="fas fa-clock"></i>Mon–Fri: 9AM–7PM</div>
        <div class="f-ci"><i class="fas fa-clock"></i>Sat: 10AM–5PM</div>
      </div>
    </div>
    <div class="f-badges">
      <span class="fbg">🔒 256-bit SSL</span>
      <a href="/services" class="fbg-link">📈 CIBIL Score Repair</a>
      <a href="/rbi-compliance" class="fbg-link">⚖️ RBI Compliant</a>
      <a href="/refund-cancellation" class="fbg-link">💰 Money-Back Guarantee</a>
      <span class="fbg">🏆 98% Success Rate</span>
      <a href="#" class="fbg-link">⭐ 4.9 Google Rating</a>
      <span class="fbg">🏦 All 4 Bureaus</span>
    </div>
    <div class="f-bot">
      <div class="f-leg">
        <a href="../privacy-policy.html">Privacy Policy</a>
        <a href="../terms-conditions.html">Terms &amp; Conditions</a>
        <a href="../refund-cancellation.html">Refund Policy</a>
        <a href="../disclaimer.html">Disclaimer</a>
        <a href="../complaints.html">Grievance Redressal</a>
      </div>
      <div class="f-copy">© 2025 Corvanta Financial Services. CIBIL® is a trademark of TransUnion CIBIL.</div>
    </div>
  </div>
</footer>

<!-- WhatsApp FAB -->
<a href="https://wa.me/919905482503?text=Hi%2C%20I%20need%20help%20with%20professional%20credit%20repair%20on%20my%20CIBIL%20report" class="wa" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>

<!-- FREE CONSULTATION MODAL -->
<div class="modal-bg" id="modal">
  <div class="modal-box">
    <button class="mc" onclick="closeModal()">×</button>
    <span class="m-ico">🚀</span>
    <h3 class="m-h">Free Credit Repair Consultation</h3>
    <p class="m-s">Get expert advice on comprehensive credit repair. Our team contacts you within 2 hours.</p>
    <input type="text" class="fi" id="mN" placeholder="Full Name">
    <input type="tel" class="fi" id="mP" placeholder="Mobile Number">
    <input type="email" class="fi" id="mEm" placeholder="Email Address (optional)">
    <button class="btn-submit" onclick="submitLead()"><i class="fab fa-whatsapp"></i> Get Free Consultation</button>
    <div class="m-disc">No spam. 100% confidential. We respect your privacy.</div>
  </div>
</div>

<!-- Payment Result Modal -->
<div id="resultModal" class="result-modal">
  <div class="result-modal-content">
    <div id="modalIcon" style="font-size:3.5rem">✅</div>
    <h3 id="modalTitle">Payment Successful!</h3>
    <p id="modalMessage">Your payment has been completed successfully.</p>
    <p class="txn" id="modalTransactionId"></p>
    <button onclick="closeResultModal()">Continue</button>
  </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
// ─── CSRF token from PHP ─────────────────────────────────────────────────────
const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('csrf_token');
  if (el && CSRF_TOKEN) el.value = CSRF_TOKEN;
});

const RAZORPAY_KEY = 'rzp_test_YOUR_TEST_KEY_HERE';
const SERVICE = { id: 8, name: 'Professional — Comprehensive Credit Repair', amount: 10999, caseNo: 'PRF' + Date.now() };

// ─── NAV scroll ──────────────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
  const h = document.documentElement, pf = document.getElementById('pf');
  if (pf) pf.style.width = (h.scrollTop / (h.scrollHeight - h.clientHeight) * 100) + '%';
  document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 30);
});

// ─── Scroll reveal ───────────────────────────────────────────────────────────
const obs = new IntersectionObserver(entries => {
  entries.forEach((e, i) => {
    if (e.isIntersecting) { setTimeout(() => e.target.classList.add('on'), i * 55); obs.unobserve(e.target); }
  });
}, { threshold: .08, rootMargin: '0px 0px -30px 0px' });
document.querySelectorAll('.r').forEach(el => obs.observe(el));

// ─── Counter animation ───────────────────────────────────────────────────────
const cobs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (!e.isIntersecting) return;
    const el = e.target, tg = +el.dataset.target, sf = el.dataset.s || '';
    let cur = 0, step = tg / 70;
    const t = setInterval(() => {
      cur = Math.min(cur + step, tg);
      el.textContent = Math.floor(cur) + (Math.floor(cur) >= tg ? sf : '');
      if (cur >= tg) clearInterval(t);
    }, 18);
    cobs.unobserve(el);
  });
}, { threshold: .5 });
document.querySelectorAll('[data-target]').forEach(el => cobs.observe(el));

// ─── MOBILE MENU - FIXED ────────────────────────────────────────────────────
const ham = document.getElementById('ham');
const mob = document.getElementById('mob');
const overlay = document.getElementById('menuOverlay');

// Close menu function
function cm() {
    mob.classList.remove('open');
    overlay.classList.remove('active');
    ham.classList.remove('active');
    document.body.style.overflow = '';
    ham.setAttribute('aria-expanded', 'false');
}

// Toggle main menu
if (ham) {
    ham.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = mob.classList.toggle('open');
        ham.classList.toggle('active', isOpen);
        overlay.classList.toggle('active', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
        ham.setAttribute('aria-expanded', isOpen);
    });
}

// Close menu when clicking overlay
if (overlay) {
    overlay.addEventListener('click', cm);
}

// Close menu on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cm();
    }
});

// ─── MOBILE SUBMENU TOGGLE - FIXED ─────────────────────────────────────────
function toggleMobSubmenu(el) {
    const parent = el.closest('.mob-dropdown');
    if (!parent) return;
    const isActive = parent.classList.contains('active');
    document.querySelectorAll('.mob-dropdown.active').forEach(function(dropdown) {
        if (dropdown !== parent) {
            dropdown.classList.remove('active');
        }
    });
    parent.classList.toggle('active');
    const chevron = parent.querySelector('.chevron');
    if (chevron) {
        chevron.style.transform = parent.classList.contains('active') ? 'rotate(180deg)' : '';
    }
}

// Close mobile menu on any link click
document.querySelectorAll('#mob a, #mob .mob-action-btn').forEach(function(link) {
    link.addEventListener('click', function(e) {
        if (this.classList.contains('mob-nav-link') && this.querySelector('.chevron')) {
            return;
        }
        setTimeout(cm, 150);
    });
});

// ─── Free Consultation Modal ─────────────────────────────────────────────────
function openModal() { 
    const modal = document.getElementById('modal');
    if (modal) modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal');
    if (modal && e.target === modal) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeResultModal();
    }
});

function submitLead() {
    const n = document.getElementById('mN')?.value?.trim() || '';
    const p = document.getElementById('mP')?.value?.trim() || '';
    const em = document.getElementById('mEm')?.value?.trim() || '';
    if (!n || !p) { alert('Please enter your name and phone number.'); return; }
    const msg = encodeURIComponent('Hi! I want a free consultation for professional credit repair on my CIBIL report.\n\nName: ' + n + '\nPhone: ' + p + (em ? '\nEmail: ' + em : ''));
    window.open('https://wa.me/919905482503?text=' + msg, '_blank');
    closeModal();
}

// ─── Payment form ─────────────────────────────────────────────────────────────
const form = document.getElementById('paymentForm');
const payBtn = document.getElementById('payBtn');

function showLoading() { 
    if (payBtn) { payBtn.disabled = true; payBtn.innerHTML = '<span class="loading"></span> Processing...'; }
}

function hideLoading() { 
    if (payBtn) { payBtn.disabled = false; payBtn.innerHTML = '<i class="fas fa-lock"></i> Pay ₹10,999 + GST &amp; Start Repair'; }
}

function showResultModal(success, title, message, transactionId = '') {
    const modal = document.getElementById('resultModal');
    if (!modal) return;
    document.getElementById('modalIcon').innerHTML = success ? '✅' : '❌';
    const titleEl = document.getElementById('modalTitle');
    if (titleEl) {
        titleEl.style.color = success ? '#16a34a' : '#dc2626';
        titleEl.innerText = title;
    }
    document.getElementById('modalMessage').innerText = message;
    document.getElementById('modalTransactionId').innerHTML = transactionId ? 'Transaction ID: ' + transactionId : '';
    modal.classList.add('active');
}

function closeResultModal() {
    const modal = document.getElementById('resultModal');
    if (modal) modal.classList.remove('active');
    if (document.getElementById('modalTitle')?.innerText === 'Payment Successful!') {
        window.location.href = '../client-dashboard.html';
    }
}

function generateOrderId() { 
    return 'order_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9); 
}

async function saveLeadToDatabase(name, email, phone) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ 
                name, email, phone, 
                service_id: SERVICE.id, 
                service_name: SERVICE.name, 
                amount: SERVICE.amount, 
                csrf_token: document.getElementById('csrf_token')?.value || '' 
            })
        });
        return await response.json();
    } catch (error) { 
        console.error('Error saving lead:', error); 
        return { success: false }; 
    }
}

if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fullName = document.getElementById('fullName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        if (!fullName || !email || !phone) { alert('Please fill all fields'); return; }
        if (!/^\d{10}$/.test(phone)) { alert('Please enter a valid 10-digit phone number'); return; }
        if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email)) { alert('Please enter a valid email address'); return; }
        showLoading();
        const leadResult = await saveLeadToDatabase(fullName, email, phone);
        const orderId = generateOrderId();
        const options = {
            key: RAZORPAY_KEY,
            amount: SERVICE.amount * 100,
            currency: 'INR',
            name: 'CIBIL Repair',
            description: SERVICE.name,
            order_id: orderId,
            prefill: { name: fullName, email, contact: phone },
            notes: { service_id: SERVICE.id, service_name: SERVICE.name, lead_id: leadResult.lead_id || '' },
            theme: { color: '#22c55e' },
            handler: function(response) {
                hideLoading();
                showResultModal(true, 'Payment Successful!', `Thank you ${fullName}! Your payment has been completed. Our team will contact you within 24 hours to begin your comprehensive credit repair.`, response.razorpay_payment_id);
                form.reset();
            },
            modal: { 
                ondismiss: function() { 
                    hideLoading(); 
                    showResultModal(false, 'Payment Cancelled', 'You cancelled the payment. Please try again whenever you are ready.'); 
                } 
            }
        };
        if (typeof Razorpay === 'undefined') { 
            hideLoading(); 
            alert('Payment gateway is loading. Please refresh and try again.'); 
            return; 
        }
        const rzp = new Razorpay(options);
        rzp.open();
        rzp.on('payment.failed', function(response) { 
            hideLoading(); 
            showResultModal(false, 'Payment Failed', response.error.description || 'Something went wrong. Please try again.'); 
        });
    });
}
</script>
</body>
</html>